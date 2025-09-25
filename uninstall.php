<?php
// Uninstall routine for Adiscon Outdated Content.

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options (single and multisite network-wide where applicable).
// Current option key and the legacy key from previous versions.
$option_names = array(
    'adiscon_outdated_content',
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
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall script needs to remove all data
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", $meta_key ) );
}

