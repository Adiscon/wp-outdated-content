<?php
// Uninstall routine for WP Outdated Content.

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options (single and multisite network-wide where applicable).
$option_names = array(
    'wp_outdated_content_options',
    'wp_outdated_content_version',
);

foreach ( $option_names as $option_name ) {
    delete_option( $option_name );
    delete_site_option( $option_name );
}

// Remove per-post override meta used by the plugin.
$meta_keys = array(
    'ocb_state',
    'ocb_threshold_months',
    'ocb_label_custom',
);

global $wpdb;
foreach ( $meta_keys as $meta_key ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", $meta_key ) );
}

