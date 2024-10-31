<?php

// If uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

require_once(plugin_dir_path( __FILE__ ) . 'nordot-common.php');

delete_option('nordot-curator-media-unit');
delete_option('nordot-curator-unit-api-key');
delete_option('nordot-curator-language');

delete_option('nordot-content-owner-media-unit' );
delete_option('nordot-content-owner-unit-api-key' );
delete_option('nordot-content-owner-auto-upload' );

delete_option('nordot-curator-read-more');
delete_option('welcome_email_sent');
delete_option('nordot_ads_txt_init');

delete_option('nordot_db_version');

global $wpdb;

$saved_searches = get_nordot_save_search_table();
$wpdb->query($wpdb->prepare( "DROP TABLE IF EXISTS `{$wpdb->prefix}nordot_save_searches`"));

$read_laters = get_nordot_read_later_table();
$wpdb->query($wpdb->prepare( "DROP TABLE IF EXISTS `{$wpdb->prefix}nordot_read_later`"));
