<?php
/**
 * Plugin Name:       WP Outdated Content
 * Description:       Adds an accessible, configurable notice to outdated posts/pages with thresholds, labels, and colors.
 * Version:           1.0.1
 * Author:            Adiscon GmbH
 * Text Domain:       wp-outdated-content
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 *  Coding:            Andre Lorbach (See alorbach github profile)
 * (c) 2025 Adiscon GmbH
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Adiscon_Outdated_Content' ) ) {
    final class Adiscon_Outdated_Content {
        private static $instance = null;

        const VERSION    = '1.0.1';
        const OPTION_KEY = 'wp_outdated_content';
        const OPTION_KEY_OLD = 'adiscon_outdated_content';

        private function __construct() {
            add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
            add_action( 'admin_init', [ $this, 'register_settings' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue' ] );
            add_action( 'admin_menu', [ $this, 'register_menu' ] );
            add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );
            add_action( 'save_post', [ $this, 'save_post_meta' ], 10, 2 );

            add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
            add_filter( 'the_content', [ $this, 'maybe_prepend_notice' ], 1 );

            add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'plugin_action_links' ] );
            add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );

            register_activation_hook( __FILE__, [ __CLASS__, 'on_activate' ] );
        }

        public static function instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function load_textdomain() {
            // WordPress automatically loads plugin translations since 4.6
            // No manual load_plugin_textdomain() needed for WordPress.org hosted plugins
        }

        public static function defaults() {
			$age_basis_default = 'modified';
			$label_warn_default = $age_basis_default === 'modified'
				? __( 'Outdated content notice: This article last update was {age_months} month(s) ago and may be outdated.', 'wp-outdated-content' )
				: __( 'Outdated content notice: This article is {age_months} months old and may be outdated.', 'wp-outdated-content' );
			$label_danger_default = $age_basis_default === 'modified'
				? __( 'Outdated content warning: This article last update was {age_years} year(s) ago and likely outdated.', 'wp-outdated-content' )
				: __( 'Outdated content warning: This article is {age_years} years old and likely outdated.', 'wp-outdated-content' );

			return [
				'enable'              => 1,
				'css_enable'          => 1,
				'theme_styling'       => 1,
				'jsonld_enable'       => 1,
				'jsonld_type'         => 'Article',
				'post_types'          => 'post,page',
				'age_basis'           => $age_basis_default,
				'warn_months'         => 12,
				'danger_months'       => 36,
				'label_warn'          => $label_warn_default,
				'label_danger'        => $label_danger_default,
				'warn_bg'             => '#fff8e1',
				'warn_border'         => '#ffcc80',
				'danger_bg'           => '#ffebee',
				'danger_border'       => '#ef9a9a',
				'warn_text'           => '#3b2f00',
				'danger_text'         => '#7a1f24',
				'warn_bg_dark'        => '#3b2f00',
				'warn_border_dark'    => '#855f1a',
				'danger_bg_dark'      => '#3a0c0f',
				'danger_border_dark'  => '#7a1f24',
				'warn_text_dark'      => '#ffe8b3',
				'danger_text_dark'    => '#ffffff',
			];
        }

        public static function on_activate() {
            $defaults = self::defaults();
            $new = get_option( self::OPTION_KEY );
            $old = get_option( self::OPTION_KEY_OLD );
            if ( is_array( $new ) ) {
                update_option( self::OPTION_KEY, array_merge( $defaults, $new ) );
            } elseif ( is_array( $old ) ) {
                update_option( self::OPTION_KEY, array_merge( $defaults, $old ) );
            } else {
                add_option( self::OPTION_KEY, $defaults );
            }
        }

        public function get_options() {
            $opts = get_option( self::OPTION_KEY, [] );
            if ( ! is_array( $opts ) || empty( $opts ) ) {
                $old = get_option( self::OPTION_KEY_OLD, [] );
                if ( is_array( $old ) && ! empty( $old ) ) {
                    $opts = $old;
                    update_option( self::OPTION_KEY, $opts );
                }
            }
            if ( ! is_array( $opts ) ) {
                $opts = [];
            }
            $opts = array_merge( self::defaults(), $opts );
            return $this->sanitize_options( $opts );
        }

        public function register_settings() {
            register_setting( 'wp_outdated_content', self::OPTION_KEY, [ $this, 'sanitize_options' ] );

            add_settings_section( 'aoc_docs', __( 'Documentation', 'wp-outdated-content' ), [ $this, 'render_docs_section' ], 'wp_outdated_content' );
            add_settings_section( 'aoc_general', __( 'General', 'wp-outdated-content' ), '__return_false', 'wp_outdated_content' );
            add_settings_section( 'aoc_labels', __( 'Labels', 'wp-outdated-content' ), '__return_false', 'wp_outdated_content' );
            add_settings_section( 'aoc_colors', __( 'Colors', 'wp-outdated-content' ), '__return_false', 'wp_outdated_content' );

            $this->add_field( 'enable', 'aoc_general', __( 'Enable', 'wp-outdated-content' ), 'checkbox' );
            $this->add_field( 'css_enable', 'aoc_general', __( 'Load built-in CSS', 'wp-outdated-content' ), 'checkbox' );
            $this->add_field( 'theme_styling', 'aoc_general', __( 'Use theme styling (colors and label style)', 'wp-outdated-content' ), 'checkbox' );
            $this->add_field( 'jsonld_enable', 'aoc_general', __( 'Output JSON-LD', 'wp-outdated-content' ), 'checkbox' );
            $this->add_field( 'jsonld_type', 'aoc_general', __( 'JSON-LD type(s)', 'wp-outdated-content' ), 'jsonld_types' );
            $this->add_field( 'post_types', 'aoc_general', __( 'Post types', 'wp-outdated-content' ), 'post_types' );
            $this->add_field( 'age_basis', 'aoc_general', __( 'Age basis', 'wp-outdated-content' ), 'age_basis' );
            $this->add_field( 'warn_months', 'aoc_general', __( 'Warn threshold (months)', 'wp-outdated-content' ), 'number' );
            $this->add_field( 'danger_months', 'aoc_general', __( 'Danger threshold (months)', 'wp-outdated-content' ), 'number' );

            $this->add_field( 'label_warn', 'aoc_labels', __( 'Warn label', 'wp-outdated-content' ), 'textarea' );
            $this->add_field( 'label_danger', 'aoc_labels', __( 'Danger label', 'wp-outdated-content' ), 'textarea' );

            $this->add_field( 'warn_bg', 'aoc_colors', __( 'Warn background', 'wp-outdated-content' ), 'color' );
            $this->add_field( 'warn_border', 'aoc_colors', __( 'Warn border', 'wp-outdated-content' ), 'color' );
            $this->add_field( 'danger_bg', 'aoc_colors', __( 'Danger background', 'wp-outdated-content' ), 'color' );
            $this->add_field( 'danger_border', 'aoc_colors', __( 'Danger border', 'wp-outdated-content' ), 'color' );
            $this->add_field( 'warn_text', 'aoc_colors', __( 'Warn text', 'wp-outdated-content' ), 'color' );
            $this->add_field( 'danger_text', 'aoc_colors', __( 'Danger text', 'wp-outdated-content' ), 'color' );
            $this->add_field( 'warn_bg_dark', 'aoc_colors', __( 'Warn background (dark)', 'wp-outdated-content' ), 'color' );
            $this->add_field( 'warn_border_dark', 'aoc_colors', __( 'Warn border (dark)', 'wp-outdated-content' ), 'color' );
            $this->add_field( 'danger_bg_dark', 'aoc_colors', __( 'Danger background (dark)', 'wp-outdated-content' ), 'color' );
            $this->add_field( 'danger_border_dark', 'aoc_colors', __( 'Danger border (dark)', 'wp-outdated-content' ), 'color' );
            $this->add_field( 'warn_text_dark', 'aoc_colors', __( 'Warn text (dark)', 'wp-outdated-content' ), 'color' );
            $this->add_field( 'danger_text_dark', 'aoc_colors', __( 'Danger text (dark)', 'wp-outdated-content' ), 'color' );
        }

        private function add_field( $key, $section, $label, $type ) {
            add_settings_field(
                $key,
                esc_html( $label ),
                function() use ( $key, $type ) {
                    $opts = $this->get_options();
                    $val  = isset( $opts[ $key ] ) ? $opts[ $key ] : '';
                    $name = self::OPTION_KEY . '[' . esc_attr( $key ) . ']';
                    if ( $type === 'checkbox' ) {
                        echo '<label><input type="checkbox" name="' . esc_attr( $name ) . '" value="1" ' . checked( ! empty( $val ), true, false ) . '> ' . esc_html__( 'Enabled', 'wp-outdated-content' ) . '</label>';
                    } elseif ( $type === 'number' ) {
                        echo '<input type="number" min="1" step="1" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $val ) . '" class="small-text">';
                    } elseif ( $type === 'textarea' ) {
                        echo '<textarea name="' . esc_attr( $name ) . '" rows="3" class="large-text">' . esc_textarea( (string) $val ) . '</textarea>';
                        if ( in_array( $key, [ 'label_warn', 'label_danger' ], true ) ) {
                            echo '<p class="description">' . esc_html__( 'Tokens: {age_days}, {age_months}, {age_years}, {published_date}, {company}', 'wp-outdated-content' ) . '</p>';
                        }
                    } elseif ( $type === 'post_types' ) {
                        $selected = [];
                        if ( is_array( $val ) ) {
                            $selected = array_map( 'strval', $val );
                        } else {
                            $selected = array_filter( array_map( 'trim', explode( ',', (string) $val ) ) );
                        }
                        $public_types = get_post_types( [ 'public' => true ], 'objects' );
                        $name_many = self::OPTION_KEY . '[' . esc_attr( $key ) . '][]';
                        foreach ( $public_types as $slug => $obj ) {
                            $label_pt = isset( $obj->labels->singular_name ) ? $obj->labels->singular_name : $slug;
                            echo '<label style="display:inline-block;margin:4px 12px 4px 0"><input type="checkbox" name="' . esc_attr( $name_many ) . '" value="' . esc_attr( $slug ) . '" ' . checked( in_array( $slug, $selected, true ), true, false ) . '> ' . esc_html( $label_pt ) . '</label>';
                        }
                        echo '<p class="description">' . esc_html__( 'Select post types that should display the outdated notice.', 'wp-outdated-content' ) . '</p>';
                    } elseif ( $type === 'jsonld_types' ) {
                        $allowed = [ 'Article', 'BlogPosting', 'NewsArticle', 'WebPage' ];
                        $selected = [];
                        if ( is_array( $val ) ) {
                            $selected = array_map( 'strval', $val );
                        } else {
                            $selected = array_filter( array_map( 'trim', explode( ',', (string) $val ) ) );
                        }
                        $name_many = self::OPTION_KEY . '[' . esc_attr( $key ) . '][]';
                        foreach ( $allowed as $opt ) {
                            echo '<label style="display:inline-block;margin:4px 12px 4px 0"><input type="checkbox" name="' . esc_attr( $name_many ) . '" value="' . esc_attr( $opt ) . '" ' . checked( in_array( $opt, $selected, true ), true, false ) . '> ' . esc_html( $opt ) . '</label>';
                        }
                        echo '<p class="description">' . esc_html__( 'Select one or more schema.org types. First selected will be used as @type; the rest will appear in additionalType.', 'wp-outdated-content' ) . '</p>';
                    } elseif ( $type === 'age_basis' ) {
                        $current = in_array( (string) $val, [ 'modified', 'published' ], true ) ? (string) $val : 'modified';
                        echo '<select name="' . esc_attr( $name ) . '">';
                        echo '<option value="modified" ' . selected( $current, 'modified', false ) . '>' . esc_html__( 'Last modified date', 'wp-outdated-content' ) . '</option>';
                        echo '<option value="published" ' . selected( $current, 'published', false ) . '>' . esc_html__( 'Publish date', 'wp-outdated-content' ) . '</option>';
                        echo '</select>';
                        echo '<p class="description">' . esc_html__( 'Used for age calculation and the displayed date.', 'wp-outdated-content' ) . '</p>';
                    } elseif ( $type === 'color' ) {
						echo '<input type="text" class="regular-text wpoc-color" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $val ) . '" placeholder="#rrggbb">';
                    } else {
                        echo '<input type="text" class="regular-text" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $val ) . '">';
                    }
                },
                'wp_outdated_content',
                $section
            );
        }

        public function register_menu() {
            add_options_page(
                __( 'WP Outdated Content', 'wp-outdated-content' ),
                __( 'WP Outdated Content', 'wp-outdated-content' ),
                'manage_options',
                'wp_outdated_content',
                [ $this, 'render_settings_page' ]
            );
        }

        public function render_settings_page() {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__( 'WP Outdated Content', 'wp-outdated-content' ) . '</h1>';
            echo '<form method="post" action="options.php">';
            settings_fields( 'wp_outdated_content' );
            do_settings_sections( 'wp_outdated_content' );
            submit_button();
            echo '</form>';
            echo '</div>';
        }

		public function admin_enqueue( $hook_suffix ) {
			if ( $hook_suffix !== 'settings_page_wp_outdated_content' ) {
				return;
			}
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
			$init = 'document.addEventListener("DOMContentLoaded",function(){if (window.jQuery){jQuery(".wpoc-color").wpColorPicker && jQuery(".wpoc-color").wpColorPicker();}});';
			wp_add_inline_script( 'wp-color-picker', $init );
		}

        public function sanitize_options( $input ) {
            $defaults = self::defaults();
            $out = is_array( $input ) ? $input : [];

            $out['enable'] = empty( $out['enable'] ) ? 0 : 1;
            $out['css_enable'] = empty( $out['css_enable'] ) ? 0 : 1;
            $out['theme_styling'] = empty( $out['theme_styling'] ) ? 0 : 1;
            $out['jsonld_enable'] = empty( $out['jsonld_enable'] ) ? 0 : 1;

            // Age basis sanitation
            $allowed_basis = [ 'modified', 'published' ];
            $age_basis_in = isset( $out['age_basis'] ) ? (string) $out['age_basis'] : ( isset( $defaults['age_basis'] ) ? (string) $defaults['age_basis'] : 'modified' );
            $out['age_basis'] = in_array( $age_basis_in, $allowed_basis, true ) ? $age_basis_in : 'modified';

            $available_types = get_post_types( [ 'public' => true ], 'names' );
            $parts = [];
            if ( isset( $out['post_types'] ) ) {
                if ( is_array( $out['post_types'] ) ) {
                    foreach ( $out['post_types'] as $t ) {
                        $parts[] = sanitize_key( (string) $t );
                    }
                } else {
                    $post_types_str = strtolower( (string) $out['post_types'] );
                    $post_types_str = preg_replace( '/[^a-z0-9_,\-]/', '', $post_types_str );
                    $parts = array_filter( array_map( 'trim', explode( ',', $post_types_str ) ) );
                }
            } else {
                $parts = array_filter( array_map( 'trim', explode( ',', (string) $defaults['post_types'] ) ) );
            }
            $parts = array_values( array_unique( $parts ) );
            $parts = array_values( array_intersect( $parts, $available_types ) );
            $out['post_types'] = implode( ',', $parts );

            $warn = isset( $out['warn_months'] ) ? intval( $out['warn_months'] ) : $defaults['warn_months'];
            $danger = isset( $out['danger_months'] ) ? intval( $out['danger_months'] ) : $defaults['danger_months'];
            $warn = max( 1, $warn );
            $danger = max( $warn + 1, $danger );
            $out['warn_months'] = $warn;
            $out['danger_months'] = $danger;

            $out['label_warn'] = $this->sanitize_label( isset( $out['label_warn'] ) ? $out['label_warn'] : $defaults['label_warn'] );
            $out['label_danger'] = $this->sanitize_label( isset( $out['label_danger'] ) ? $out['label_danger'] : $defaults['label_danger'] );

            $allowed_jsonld_types = [ 'Article', 'BlogPosting', 'NewsArticle', 'WebPage' ];
            $jsonld_types = [];
            if ( isset( $out['jsonld_type'] ) ) {
                if ( is_array( $out['jsonld_type'] ) ) {
                    foreach ( $out['jsonld_type'] as $t ) {
                        $t = (string) $t;
                        if ( in_array( $t, $allowed_jsonld_types, true ) ) {
                            $jsonld_types[] = $t;
                        }
                    }
                } else {
                    $t = (string) $out['jsonld_type'];
                    if ( in_array( $t, $allowed_jsonld_types, true ) ) {
                        $jsonld_types[] = $t;
                    }
                }
            }
            if ( empty( $jsonld_types ) ) {
                $jsonld_types[] = $defaults['jsonld_type'];
            }
            $out['jsonld_type'] = implode( ',', array_values( array_unique( $jsonld_types ) ) );

            foreach ( [ 'warn_bg', 'warn_border', 'danger_bg', 'danger_border', 'warn_text', 'danger_text', 'warn_bg_dark', 'warn_border_dark', 'danger_bg_dark', 'danger_border_dark', 'warn_text_dark', 'danger_text_dark' ] as $color_key ) {
                $out[ $color_key ] = $this->sanitize_color( isset( $out[ $color_key ] ) ? $out[ $color_key ] : $defaults[ $color_key ] );
            }

            return array_merge( $defaults, $out );
        }

        private function sanitize_label( $text ) {
            $text = (string) $text;
            $text = wp_kses( $text, [
                'a'      => [ 'href' => [], 'title' => [], 'rel' => [], 'target' => [] ],
                'strong' => [],
                'em'     => [],
                'span'   => [ 'class' => [] ],
                'br'     => [],
            ] );
            return $text;
        }

        private function sanitize_color( $color ) {
            $color = trim( (string) $color );
            if ( preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color ) ) {
                return $color;
            }
            return '#cccccc';
        }

        public function enqueue_frontend_assets() {
            $opts = $this->get_options();
            $css_enabled = apply_filters( 'wp_outdated_css_enabled', ! empty( $opts['css_enable'] ) );
            if ( function_exists( 'apply_filters_deprecated' ) ) {
                $css_enabled = apply_filters_deprecated( 'adiscon_outdated_css_enabled', [ $css_enabled ], '1.0.1', 'wp_outdated_css_enabled' );
            }
            $use_theme_styling = apply_filters( 'wp_outdated_use_theme_styling', ! empty( $opts['theme_styling'] ) );
            if ( function_exists( 'apply_filters_deprecated' ) ) {
                $use_theme_styling = apply_filters_deprecated( 'adiscon_outdated_use_theme_styling', [ $use_theme_styling ], '1.0.1', 'wp_outdated_use_theme_styling' );
            }
            if ( is_admin() || empty( $opts['enable'] ) || ! $css_enabled ) {
                return;
            }
            $handle = 'wp-outdated-content';
            $src    = plugins_url( 'assets/frontend.css', __FILE__ );
            wp_enqueue_style( $handle, $src, [], self::VERSION );
            if ( ! $use_theme_styling ) {
                $light_vars = sprintf(
                    '--ocb-warn-bg:%1$s;--ocb-warn-border:%2$s;--ocb-danger-bg:%3$s;--ocb-danger-border:%4$s;--ocb-warn-text:%5$s;--ocb-danger-text:%6$s;',
                    esc_attr( $opts['warn_bg'] ),
                    esc_attr( $opts['warn_border'] ),
                    esc_attr( $opts['danger_bg'] ),
                    esc_attr( $opts['danger_border'] ),
                    esc_attr( $opts['warn_text'] ),
                    esc_attr( $opts['danger_text'] )
                );
                $dark_vars = sprintf(
                    '--ocb-warn-bg:%1$s;--ocb-warn-border:%2$s;--ocb-danger-bg:%3$s;--ocb-danger-border:%4$s;--ocb-warn-text:%5$s;--ocb-danger-text:%6$s;',
                    esc_attr( $opts['warn_bg_dark'] ),
                    esc_attr( $opts['warn_border_dark'] ),
                    esc_attr( $opts['danger_bg_dark'] ),
                    esc_attr( $opts['danger_border_dark'] ),
                    esc_attr( $opts['warn_text_dark'] ),
                    esc_attr( $opts['danger_text_dark'] )
                );
                $inline_css = ':root{' . $light_vars . '}' . '@media (prefers-color-scheme: dark){:root{' . $dark_vars . '}}';
                wp_add_inline_style( $handle, $inline_css );
            }
        }

        public function render_docs_section() {
            echo '<div id="wp-outdated-docs">';
            echo '<h2 id="docs">' . esc_html__( 'How it works', 'wp-outdated-content' ) . '</h2>';
            echo '<p>' . esc_html__( 'This plugin prepends an accessible notice to selected post types if the content age exceeds your thresholds.', 'wp-outdated-content' ) . '</p>';
            echo '<h3>' . esc_html__( 'JSON-LD Output', 'wp-outdated-content' ) . '</h3>';
            echo '<p>' . esc_html__( 'When enabled, a JSON-LD block is emitted near the notice to help crawlers and AI systems detect outdated content. It includes the schema.org type, publish/modified dates, an explicit outdated state, and the content age in days/months/years.', 'wp-outdated-content' ) . '</p>';
            echo '<p><strong>' . esc_html__( 'Type', 'wp-outdated-content' ) . ':</strong> ' . esc_html__( 'Article (default). You can set BlogPosting, NewsArticle, or WebPage.', 'wp-outdated-content' ) . '</p>';
            echo '<h3>' . esc_html__( 'Tokens for labels', 'wp-outdated-content' ) . '</h3>';
            echo '<ul style="list-style:disc;margin-left:20px">';
            echo '<li><code>{age_days}</code> — ' . esc_html__( 'Age in days', 'wp-outdated-content' ) . '</li>';
            echo '<li><code>{age_months}</code> — ' . esc_html__( 'Age in months (approx.)', 'wp-outdated-content' ) . '</li>';
            echo '<li><code>{age_years}</code> — ' . esc_html__( 'Age in years (approx.)', 'wp-outdated-content' ) . '</li>';
            echo '<li><code>{published_date}</code> — ' . esc_html__( 'Localized date (publish or modified, based on setting)', 'wp-outdated-content' ) . '</li>';
            echo '<li><code>{company}</code> — ' . esc_html__( 'Company name', 'wp-outdated-content' ) . '</li>';
            echo '</ul>';
            echo '<p>' . esc_html__( 'Tip: You can override the state, threshold, or label per post via the sidebar meta box.', 'wp-outdated-content' ) . '</p>';
            echo '</div>';
        }

        public function plugin_action_links( $links ) {
            $settings_url = admin_url( 'options-general.php?page=wp_outdated_content' );
            $docs_url = $settings_url . '#docs';
            $settings_link = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'wp-outdated-content' ) . '</a>';
            $docs_link = '<a href="' . esc_url( $docs_url ) . '">' . esc_html__( 'Documentation', 'wp-outdated-content' ) . '</a>';
            array_unshift( $links, $settings_link );
            $links[] = $docs_link;
            return $links;
        }

        public function plugin_row_meta( $links, $file ) {
            if ( $file === plugin_basename( __FILE__ ) ) {
                $settings_url = admin_url( 'options-general.php?page=wp_outdated_content' );
                $docs_url = $settings_url . '#docs';
                $links[] = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'wp-outdated-content' ) . '</a>';
                $links[] = '<a href="' . esc_url( $docs_url ) . '">' . esc_html__( 'Documentation', 'wp-outdated-content' ) . '</a>';
            }
            return $links;
        }

        public function maybe_prepend_notice( $content ) {
            static $prepended_for = [];
            if ( ! is_main_query() || ! in_the_loop() ) {
                return $content;
            }

            $post = get_post();
            if ( ! $post instanceof WP_Post ) {
                return $content;
            }

            if ( isset( $prepended_for[ $post->ID ] ) ) {
                return $content;
            }

            if ( $post->post_status !== 'publish' || ! is_singular() ) {
                return $content;
            }

            $opts = $this->get_options();
            if ( empty( $opts['enable'] ) ) {
                return $content;
            }

            $allowed_types = array_filter( array_map( 'trim', explode( ',', $opts['post_types'] ) ) );
            if ( ! in_array( $post->post_type, $allowed_types, true ) ) {
                return $content;
            }

            $applicable = apply_filters( 'adiscon_outdated_is_applicable', true, $post );
            $applicable = apply_filters( 'wp_outdated_is_applicable', $applicable, $post );
            if ( ! $applicable ) {
                return $content;
            }

            $age_basis = isset( $opts['age_basis'] ) && in_array( (string) $opts['age_basis'], [ 'modified', 'published' ], true ) ? (string) $opts['age_basis'] : 'modified';
            $timestamp_gmt = null;
            if ( $age_basis === 'modified' ) {
                $timestamp_gmt = get_post_modified_time( 'U', true, $post );
            } else {
                $timestamp_gmt = get_post_time( 'U', true, $post );
            }
            if ( empty( $timestamp_gmt ) ) {
                return $content;
            }

            $now_gmt = time();
            $age_days = max( 0, (int) floor( ( $now_gmt - (int) $timestamp_gmt ) / DAY_IN_SECONDS ) );
            $age_months = (int) floor( $age_days / 30 );
            $age_years = (int) floor( $age_days / 365 );

            $warn_months = isset( $opts['warn_months'] ) ? (int) $opts['warn_months'] : 12;
            $danger_months = isset( $opts['danger_months'] ) ? (int) $opts['danger_months'] : 36;

            $override_state = get_post_meta( $post->ID, 'ocb_state', true );
            $override_threshold = (int) get_post_meta( $post->ID, 'ocb_threshold_months', true );
            $custom_label = (string) get_post_meta( $post->ID, 'ocb_label_custom', true );

            if ( $override_threshold > 0 ) {
                $warn_months = max( 1, $override_threshold );
            }

            $state = 'none';
            if ( $age_months >= $danger_months ) {
                $state = 'danger';
            } elseif ( $age_months >= $warn_months ) {
                $state = 'warn';
            }

            if ( $override_state === 'hide' ) {
                $state = 'none';
            } elseif ( in_array( $override_state, [ 'warn', 'danger' ], true ) ) {
                $state = $override_state;
            }

            $state = apply_filters( 'adiscon_outdated_state', $state, $post, $age_months, $warn_months, $danger_months );
            $state = apply_filters( 'wp_outdated_state', $state, $post, $age_months, $warn_months, $danger_months );
            if ( $state === 'none' ) {
                return $content;
            }

            $published_date = $age_basis === 'modified' ? get_the_modified_date( '', $post ) : get_the_date( '', $post );
            $tokens = [
                '{age_days}'      => (string) $age_days,
                '{age_months}'    => (string) $age_months,
                '{age_years}'     => (string) $age_years,
                '{published_date}' => (string) $published_date,
                '{company}'        => 'Adiscon',
            ];
            $tokens = apply_filters( 'adiscon_outdated_tokens', $tokens, $post );
            $tokens = apply_filters( 'wp_outdated_tokens', $tokens, $post );

            $label_template = '';
            if ( $custom_label !== '' ) {
                $label_template = $custom_label;
            } else {
                $label_template = $state === 'danger' ? $opts['label_danger'] : $opts['label_warn'];
            }
            $label = strtr( $label_template, $tokens );
            $label = apply_filters( 'adiscon_outdated_notice_text', $label, $state, $post, $age_months, $published_date );
            $label = apply_filters( 'wp_outdated_notice_text', $label, $state, $post, $age_months, $published_date );

            $use_theme_styling = apply_filters( 'wp_outdated_use_theme_styling', ! empty( $opts['theme_styling'] ) );
            if ( function_exists( 'apply_filters_deprecated' ) ) {
                $use_theme_styling = apply_filters_deprecated( 'adiscon_outdated_use_theme_styling', [ $use_theme_styling ], '1.0.1', 'wp_outdated_use_theme_styling' );
            }
            $theme_class = $use_theme_styling ? ' ocb--theme' : '';
            $html  = '<aside role="note" class="ocb ocb--' . esc_attr( $state ) . $theme_class . '">';
            $html .= '<span class="ocb-label">' . wp_kses_post( $label ) . '</span>';
            /* translators: %s: The date when the content was last updated */
            $date_label = $age_basis === 'modified' ? _x( 'Last updated: %s', 'modified date', 'wp-outdated-content' ) : _x( 'Published: %s', 'published date', 'wp-outdated-content' );
            $html .= ' <span class="ocb-meta">' . esc_html( sprintf( $date_label, $published_date ) ) . '</span>';
            $html .= '</aside>';

            if ( ! empty( $opts['jsonld_enable'] ) ) {
                $published_iso = get_the_date( 'c', $post );
                $modified_iso = get_the_modified_date( 'c', $post );
                $jsonld_types = array_filter( array_map( 'trim', explode( ',', (string) $opts['jsonld_type'] ) ) );
                $jsonld_primary = reset( $jsonld_types ) ?: 'Article';
                $jsonld_additional = array_values( array_slice( $jsonld_types, 1 ) );
                $jsonld = [
                    '@context' => 'https://schema.org',
                    '@type' => $jsonld_primary,
                    'headline' => get_the_title( $post ),
                    'inLanguage' => get_bloginfo( 'language' ),
                    'mainEntityOfPage' => get_permalink( $post ),
                    'datePublished' => $published_iso,
                    'dateModified' => $modified_iso,
                    'creativeWorkStatus' => 'Outdated',
                    'additionalType' => $jsonld_additional,
                    'additionalProperty' => [
                        [ '@type' => 'PropertyValue', 'name' => 'outdatedState', 'value' => $state ],
                        [ '@type' => 'PropertyValue', 'name' => 'contentAgeDays', 'value' => (string) $age_days ],
                        [ '@type' => 'PropertyValue', 'name' => 'contentAgeMonths', 'value' => (string) $age_months ],
                        [ '@type' => 'PropertyValue', 'name' => 'contentAgeYears', 'value' => (string) $age_years ]
                    ],
                ];
                $html .= '<script type="application/ld+json">' . wp_json_encode( $jsonld ) . '</script>';
            }

            $prepended_for[ $post->ID ] = true;
            return $html . $content;
        }

        public function register_meta_box() {
            foreach ( get_post_types( [ 'public' => true ], 'names' ) as $post_type ) {
                add_meta_box(
                    'aoc_meta',
                    __( 'Outdated Content Notice', 'wp-outdated-content' ),
                    [ $this, 'render_meta_box' ],
                    $post_type,
                    'side',
                    'default'
                );
            }
        }

        public function render_meta_box( $post ) {
            if ( ! current_user_can( 'edit_post', $post->ID ) ) {
                return;
            }
            wp_nonce_field( 'aoc_meta_box', 'aoc_meta_nonce' );
            $state = get_post_meta( $post->ID, 'ocb_state', true );
            $threshold = (int) get_post_meta( $post->ID, 'ocb_threshold_months', true );
            $label = (string) get_post_meta( $post->ID, 'ocb_label_custom', true );
            echo '<p><label for="aoc_state"><strong>' . esc_html__( 'State override', 'wp-outdated-content' ) . '</strong></label><br/>';
            echo '<select id="aoc_state" name="aoc[state]">';
            $options = [ '' => __( 'Default', 'wp-outdated-content' ), 'hide' => __( 'Hide', 'wp-outdated-content' ), 'warn' => __( 'Warn', 'wp-outdated-content' ), 'danger' => __( 'Danger', 'wp-outdated-content' ) ];
            foreach ( $options as $val => $label_opt ) {
                echo '<option value="' . esc_attr( $val ) . '" ' . selected( $state, $val, false ) . '>' . esc_html( $label_opt ) . '</option>';
            }
            echo '</select></p>';
            echo '<p><label for="aoc_threshold"><strong>' . esc_html__( 'Warn threshold override (months)', 'wp-outdated-content' ) . '</strong></label><br/>';
            echo '<input type="number" id="aoc_threshold" name="aoc[threshold]" min="0" step="1" value="' . esc_attr( (string) $threshold ) . '" class="small-text"></p>';
            echo '<p><label for="aoc_label"><strong>' . esc_html__( 'Custom label template', 'wp-outdated-content' ) . '</strong></label><br/>';
            echo '<textarea id="aoc_label" name="aoc[label]" rows="3" class="widefat">' . esc_textarea( $label ) . '</textarea>';
            echo '<span class="description">' . esc_html__( 'Tokens: {age_days}, {age_months}, {age_years}, {published_date}, {company}', 'wp-outdated-content' ) . '</span></p>';
        }

        public function save_post_meta( $post_id, $post ) {
            if ( ! isset( $_POST['aoc_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aoc_meta_nonce'] ) ), 'aoc_meta_box' ) ) {
                return;
            }
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
            }
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }
            if ( $post->post_type === 'revision' ) {
                return;
            }
            $data = isset( $_POST['aoc'] ) && is_array( $_POST['aoc'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['aoc'] ) ) : [];
            $state = isset( $data['state'] ) ? sanitize_text_field( (string) $data['state'] ) : '';
            if ( ! in_array( $state, [ '', 'hide', 'warn', 'danger' ], true ) ) {
                $state = '';
            }
            $threshold = isset( $data['threshold'] ) ? intval( $data['threshold'] ) : 0;
            $threshold = max( 0, $threshold );
            $label = isset( $data['label'] ) ? $this->sanitize_label( $data['label'] ) : '';

            if ( $state === '' ) {
                delete_post_meta( $post_id, 'ocb_state' );
            } else {
                update_post_meta( $post_id, 'ocb_state', $state );
            }
            if ( $threshold === 0 ) {
                delete_post_meta( $post_id, 'ocb_threshold_months' );
            } else {
                update_post_meta( $post_id, 'ocb_threshold_months', $threshold );
            }
            if ( $label === '' ) {
                delete_post_meta( $post_id, 'ocb_label_custom' );
            } else {
                update_post_meta( $post_id, 'ocb_label_custom', $label );
            }
        }
    }

    Adiscon_Outdated_Content::instance();
}


