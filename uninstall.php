<?php
/**
 * Uninstall Timeline Calendar Plugin
 * 
 * This file is executed when the plugin is deleted from WordPress.
 * It cleans up all plugin data including custom post types and meta data.
 *
 * @package TimelineCalendar
 * @since 1.0.0
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete all timeline articles
$timeline_articles = get_posts( array(
	'post_type' => 'timeline_article',
	'numberposts' => -1,
	'post_status' => 'any'
) );

foreach ( $timeline_articles as $article ) {
	wp_delete_post( $article->ID, true );
}

// Delete timeline meta data from all posts
global $wpdb;
$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => 'timeline_year' ) );
$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => 'timeline_month' ) );
$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => 'timeline_day' ) );
$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => 'timeline_time_of_day' ) );
$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => 'timeline_reference' ) );

// Delete plugin options
delete_option( 'timeline_calendar_options' );

// Flush rewrite rules
flush_rewrite_rules(); 