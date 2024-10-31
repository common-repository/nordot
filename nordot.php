<?php
/**
 * Plugin Name: Nordot: Content Curation & Syndication
 * Description: Curate and repost fresh news content from world-class sources. Syndicate your own original articles. 
 * Version: 1.8.9
 * Author: Kevin Webb
 * Text Domain: nordot-text-domain
 * Domain Path: /languages
 */

require_once(plugin_dir_path( __FILE__ ) . 'nordot-common.php');
include plugin_dir_path( __FILE__ ) .'nordot-search-page.php';
include plugin_dir_path( __FILE__ ) .'nordot-content-owner.php';

// Nordot plugin version and DB version
if(!defined("NORDOT_PLUGIN_VERSION")) define("NORDOT_PLUGIN_VERSION", "1.8.9");
if(!defined("NORDOT_DB_VERSION")) define("NORDOT_DB_VERSION", "1.2");

register_activation_hook(__FILE__, 'nordot_db_install');
register_activation_hook(__FILE__, 'nordot_welcome_message');

/**
 * Setup Nordot tables for Saved Searches and Read Later articles
 */
function nordot_db_install() {
	global $wpdb;
	
	$table_name_searches = $wpdb->prefix . "nordot_save_searches";
	$table_name_readlater = $wpdb->prefix . "nordot_read_later";
	
	$charset_collate = $wpdb->get_charset_collate();

	$searchesSql = "CREATE TABLE $table_name_searches (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  query varchar(255) DEFAULT '' NOT NULL,
	  autoPublish boolean DEFAULT 0 NOT NULL,
	  autoPublishAmount tinyint DEFAULT 1 NOT NULL,
	  autoPublishFrequency tinyint DEFAULT 12 NOT NULL,
	  autoPublishSetModalWindow boolean DEFAULT 1 NOT NULL,
	  autoPublishSetFeaturedImage boolean DEFAULT 1 NOT NULL,
	  autoPublishSetBodyImage boolean DEFAULT 0 NOT NULL,
	  autoPublishSetBodyImageStyle varchar(24) DEFAULT 'alignleft' NOT NULL,
	  autoPublishCategories tinytext NOT NULL,
	  name varchar(255) DEFAULT '' NOT NULL,
	  PRIMARY KEY  (id)
	) $charset_collate;";
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	
	dbDelta( $searchesSql );
	
	$readLaterSql = "CREATE TABLE $table_name_readlater (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  postid varchar(64) NOT NULL,
	  url varchar(255) NOT NULL,
	  img_url varchar(255) NOT NULL,
	  title tinytext NOT NULL,
	  description mediumtext NOT NULL,
	  publisher tinytext NOT NULL,
	  date_published tinytext NOT NULL,
	  PRIMARY KEY  (id)
	) $charset_collate;";
	
	dbDelta( $readLaterSql );
	
	update_option( 'nordot_db_version', NORDOT_DB_VERSION );
}


function nordot_welcome_message() {
	global $current_user;
	if (!get_option('welcome_email_sent')) {
		get_currentuserinfo();
		$email = $current_user->user_email;
		
		if ($email !== null && strlen($email) > 0) {
			// Send tracking email
			wp_mail('wordpresskevinwebb@gmail.com', "New WordPress Sign up: " . $email, $email . ' ' . get_bloginfo('name') . ' ' . get_bloginfo('url') . ' plugin version: ' . NORDOT_PLUGIN_VERSION);			
		}
		
		add_option('welcome_email_sent', 'true');
	}
}

add_action( 'plugins_loaded', 'nordot_update_db_check' );

/**
 * Re-run Database install/update if version does not match
 */
function nordot_update_db_check() {
	if ( get_option( 'nordot_db_version' ) !== NORDOT_DB_VERSION) {
		nordot_db_install();
	}
}

add_filter( 'cron_schedules', 'nordot_add_cron_interval' );
function nordot_add_cron_interval( $schedules ) {
	$schedules['ten_minutes'] = array(
			'interval' => 600,
			'display'  => esc_html__( 'Every Ten Minutes' ), );
	
	$schedules['thirty_minutes'] = array(
			'interval' => 1800,
			'display'  => esc_html__( 'Every Thirty Minutes' ), );
	
	$schedules['sixty_mintues'] = array(
			'interval' => 3600,
			'display'  => esc_html__( 'Every Sixty Minutes' ), );
	
	$schedules['three_hundred_sixty_minutes'] = array(
			'interval' => 21600,
			'display'  => esc_html__( 'Every Six Hours' ), );
	
	return $schedules;
}

add_action('plugins_loaded', 'nordot_plugin_init');
/**
 * Load text localization files
 */
function nordot_plugin_init() {
	load_plugin_textdomain( 'nordot-text-domain', false, dirname(plugin_basename(__FILE__)).'/languages/' );
	
	// Install ads.txt with Nordot info
	/*if (!get_option('nordot_ads_txt_init')) {
		add_option('nordot_ads_txt_init', 'true');
		file_put_contents(ABSPATH . 'ads.txt', "\r\n#Nordot plugin ads\r\ngoogle.com, pub-4307535858110282, DIRECT, f08c47fec0942fa0\r\ngoogle.com, pub-5897179876377792, RESELLER, f08c47fec0942fa0", FILE_APPEND);
	}*/
	
	add_action( 'nordot_tenM_cron_hook', 'nordot_cron_exec_tenM' );
	add_action( 'nordot_thirtyM_cron_hook', 'nordot_cron_exec_thirtyM' );
	add_action( 'nordot_one_cron_hook', 'nordot_cron_exec_one' );
	add_action( 'nordot_six_cron_hook', 'nordot_cron_exec_six' );
	add_action( 'nordot_twelve_cron_hook', 'nordot_cron_exec_twelve' );
	add_action( 'nordot_twenty_four_cron_hook', 'nordot_cron_exec_twenty_four' );

	
	if (!wp_next_scheduled('nordot_tenM_cron_hook')) {
		wp_schedule_event( time() + 10, 'ten_minutes', 'nordot_tenM_cron_hook' );
	}
	
	if (!wp_next_scheduled('nordot_thirtyM_cron_hook')) {
		wp_schedule_event( time() + 30, 'thirty_minutes', 'nordot_thirtyM_cron_hook' );
	}
	
	if (!wp_next_scheduled('nordot_one_cron_hook')) {
		wp_schedule_event( time() + 60, 'sixty_mintues', 'nordot_one_cron_hook' );
	}
	
	if (!wp_next_scheduled('nordot_six_cron_hook')) {
		wp_schedule_event( time() + 300, 'three_hundred_sixty_minutes', 'nordot_six_cron_hook' );
	}
	
	if (!wp_next_scheduled('nordot_twelve_cron_hook')) {
		wp_schedule_event( time() + 300, 'twicedaily', 'nordot_twelve_cron_hook' );
	}
	
	if (!wp_next_scheduled('nordot_twenty_four_cron_hook')) {
		wp_schedule_event( time() + 300, 'daily', 'nordot_twenty_four_cron_hook' );
	}
}

function nordot_cron_exec_tenM() {
	nordot_do_auto_publish(0);

	if (!wp_next_scheduled('nordot_tenM_cron_hook')) {
		wp_schedule_event( time() + 10, 'ten_minutes', 'nordot_tenM_cron_hook' );
	}
}

function nordot_cron_exec_thirtyM() {
	nordot_do_auto_publish(1);
	
	if (!wp_next_scheduled('nordot_thirtyM_cron_hook')) {
		wp_schedule_event( time() + 30, 'thirty_minutes', 'nordot_thirtyM_cron_hook' );
	}
}

function nordot_cron_exec_one() {
	nordot_do_auto_publish(2);
	
	if (!wp_next_scheduled('nordot_one_cron_hook')) {
		wp_schedule_event( time() + 60, 'sixty_mintues', 'nordot_one_cron_hook' );
	}
}

function nordot_cron_exec_six() {
	nordot_do_auto_publish(6);
	
	if (!wp_next_scheduled('nordot_six_cron_hook')) {
		wp_schedule_event( time() + 300, 'three_hundred_sixty_minutes', 'nordot_six_cron_hook' );
	}
}

function nordot_cron_exec_twelve() {
	nordot_do_auto_publish(12);
	nordot_do_expire_content();

	if (!wp_next_scheduled('nordot_twelve_cron_hook')) {
		wp_schedule_event( time() + 300, 'twicedaily', 'nordot_twelve_cron_hook' );
	}
}

function nordot_cron_exec_twenty_four() {
	nordot_do_auto_publish(24);
	
	if (!wp_next_scheduled('nordot_twenty_four_cron_hook')) {
		wp_schedule_event( time() + 300, 'daily', 'nordot_twenty_four_cron_hook' );
	}
}

register_deactivation_hook( __FILE__, 'nordot_deactivate' );
function nordot_deactivate() {
	$timestamp = wp_next_scheduled( 'nordot_tenM_cron_hook' );
	wp_unschedule_event( $timestamp, 'nordot_tenM_cron_hook' );
	
	$timestamp = wp_next_scheduled( 'nordot_thirtyM_cron_hook' );
	wp_unschedule_event( $timestamp, 'nordot_thirtyM_cron_hook' );
	
	$timestamp = wp_next_scheduled( 'nordot_one_cron_hook' );
	wp_unschedule_event( $timestamp, 'nordot_one_cron_hook' );
	
	$timestamp = wp_next_scheduled( 'nordot_six_cron_hook' );
	wp_unschedule_event( $timestamp, 'nordot_six_cron_hook' );
	
	$timestamp = wp_next_scheduled( 'nordot_twelve_cron_hook' );
	wp_unschedule_event( $timestamp, 'nordot_twelve_cron_hook' );
	
	$timestamp = wp_next_scheduled( 'nordot_twenty_four_cron_hook' );
	wp_unschedule_event( $timestamp, 'nordot_twenty_four_cron_hook' );
}



add_filter('plugin_row_meta', 'nordot_settings_link', 10, 2);
/**
 * This puts a Settings link on the All Plugins page
 */
function nordot_settings_link($links, $file) {
	if ($file === plugin_basename(__FILE__)) {
		$settings_link = '<a href="admin.php?page=nordot-menu-settings">' . __("Settings",'nordot-text-domain') . '</a>';
		$links[] = $settings_link;
	}
	return $links;
}

// create custom plugin settings menu
add_action('admin_menu', 'nordot_plugin_create_menu');

/**
 * Create Nordot menu on left nav
 */
function nordot_plugin_create_menu() {
	
	//create new top-level menu
	add_menu_page(__('Nordot','nordot-text-domain'), __('Nordot','nordot-text-domain'), 'manage_options', 'nordot-menu-settings', 'nordot_plugin_settings_page', 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" version="1.0" viewBox="0 0 279.000000 279.000000"><g xmlns="http://www.w3.org/2000/svg" transform="translate(0.000000,279.000000) scale(0.100000,-0.100000)" fill="#000000" stroke="none"><path fill="black" d="M1248 2785 c-2 -2 -23 -6 -48 -8 -310 -36 -645 -218 -857 -467 -183 -213 -302 -482 -331 -743 -13 -119 -6 -321 14 -427 25 -132 96 -328 141 -386 42 -56 119 -80 195 -62 38 10 76 44 315 281 l272 270 1 -208 c0 -149 4 -216 14 -239 18 -42 66 -85 112 -101 37 -12 50 -12 119 1 40 8 528 490 556 549 60 129 -44 271 -189 259 -52 -4 -87 -27 -170 -111 -35 -34 -65 -63 -67 -63 -3 0 -5 90 -5 200 0 253 -12 290 -108 331 -55 24 -106 24 -157 0 -26 -13 -174 -153 -435 -415 -217 -218 -397 -396 -401 -396 -3 0 -10 21 -17 48 -57 234 -45 494 34 714 150 421 530 735 967 802 91 13 314 14 387 0 264 -48 493 -167 671 -347 267 -269 398 -643 354 -1008 -54 -446 -340 -824 -757 -997 -138 -57 -274 -84 -433 -86 -212 -2 -326 18 -490 83 -88 35 -102 37 -133 15 -35 -24 -44 -59 -28 -98 12 -28 25 -37 92 -64 99 -38 165 -59 229 -72 28 -5 59 -11 71 -14 115 -24 366 -22 484 5 25 6 56 13 70 15 64 12 224 72 310 116 415 216 701 632 747 1087 14 134 6 315 -18 420 -6 25 -13 56 -15 69 -13 67 -62 198 -109 288 -174 340 -485 602 -835 705 -68 20 -79 23 -195 44 -60 10 -348 19 -357 10z"/><path fill="black" d="M1900 1873 c-34 -7 -97 -59 -117 -97 -21 -41 -21 -122 1 -165 23 -44 43 -65 84 -85 141 -72 303 60 262 212 -26 98 -124 155 -230 135z"/></g></svg>'));
	add_submenu_page('nordot-menu-settings', __('Nordot Settings','nordot-text-domain'), __("Settings",'nordot-text-domain'), 'manage_options', 'nordot-menu-settings' , 'nordot_plugin_settings_page' );
	add_submenu_page('nordot-menu-settings', __('Nordot Curate','nordot-text-domain'), __("Find Articles",'nordot-text-domain'), 'publish_posts', 'nordot-submenu-search', 'nordot_plugin_search_page' );
	
	//call register settings function
	add_action( 'admin_init', 'register_nordot_plugin_settings' );
}

add_action( 'admin_bar_menu', 'nordot_admin_bar', 999 );
function nordot_admin_bar($wp_admin_bar) {
	$url = admin_url( 'admin.php?page=nordot-submenu-search');
	$current_user = wp_get_current_user();
	// only show the Curate tab for users who are logged in and are higher than subscriber
	if ($current_user && !$current_user->has_cap('subscriber')) {
		$args = array(
				'href' => $url,
				'title' => __('Nordot Articles', 'nordot-text-domain'),
				'parent' => false,
				'id' => 'nordot-find-1',
				// array of any of the following options: array( 'html' => '', 'class' => '', 'onclick' => '', 'target' => '', 'title' => '' );
				'meta' => array() 
		);
		$wp_admin_bar->add_node($args);	
	}
}

add_action( 'admin_enqueue_scripts', 'nordot_admin_scripts_enqueue');
function nordot_admin_scripts_enqueue($hook) {
	if ('toplevel_page_nordot-menu-settings' !== $hook && 'nordot_page_nordot-submenu-search' !== $hook) {
		return;  // Only want enqueue the rest if on settings or search page
	}
	
	// CSS
	wp_register_style('nordot_search_style_bootstrap', plugins_url( 'css/nordot-bootstrap-min.css', __FILE__ ));
	wp_enqueue_style('nordot_search_style_bootstrap', NORDOT_PLUGIN_VERSION);
	
	wp_register_style('nordot_search_font_awesome', plugins_url( 'css/nordot-font-awesome.min.css', __FILE__ ));
	wp_enqueue_style('nordot_search_font_awesome');

	// JS
	if (!wp_script_is( 'nordot_popper_min', $list = 'enqueued' )) {
		wp_register_script('nordot_popper_min', plugins_url( 'js/nordot-popper.min.js', __FILE__ ));
		wp_enqueue_script('nordot_popper_min', '', array('jquery'), NORDOT_PLUGIN_VERSION);
	}

	if (!wp_script_is( 'nordot_search_bootstrap', $list = 'enqueued' )) {
		wp_register_script('nordot_search_bootstrap', plugins_url( 'js/nordot-bootstrap.min.js', __FILE__ ));
		wp_enqueue_script('nordot_search_bootstrap', '', array('jquery'), NORDOT_PLUGIN_VERSION);
	}
	
	if (!wp_script_is( 'nordot_luxon_js', $list = 'enqueued' )) {
		wp_register_script('nordot_luxon_js', plugins_url( 'js/nordot-luxon.min.js', __FILE__ ));
		wp_enqueue_script('nordot_luxon_js', '', array(), NORDOT_PLUGIN_VERSION);
	}	
	
	// Page-specific
	if ('toplevel_page_nordot-menu-settings' === $hook) {
		wp_register_style('nordot_settings_style', plugins_url( 'css/nordot-settings.css', __FILE__ ));
		wp_enqueue_style('nordot_settings_style', '', array("nordot_search_style_bootstrap"), NORDOT_PLUGIN_VERSION);	

		if (!wp_script_is( 'nordot_settings_js', $list = 'enqueued' )) {
			wp_register_script('nordot_settings_js', plugins_url( 'js/nordot-settings.js', __FILE__ ));
			wp_enqueue_script('nordot_settings_js', '', array('jquery'), NORDOT_PLUGIN_VERSION);
		}		
	} else {
		wp_register_style('nordot_search_style', plugins_url( 'css/nordot-search.css', __FILE__ ));
		wp_enqueue_style('nordot_search_style', '', array("nordot_search_style_bootstrap"), NORDOT_PLUGIN_VERSION);

		if (!wp_script_is( 'nordot_plugin_js', $list = 'enqueued' )) {
			wp_register_script('nordot_plugin_js', plugins_url( 'js/nordot-plugin.js', __FILE__ ));
			wp_enqueue_script('nordot_plugin_js', '', array('jquery', 'nordot_luxon_js'), NORDOT_PLUGIN_VERSION);
		}
		
		if (!wp_script_is( 'nordot_autocomplete_js', $list = 'enqueued' )) {
			wp_register_script('nordot_autocomplete_js', plugins_url( 'js/nordot-autocomplete.js', __FILE__ ));
			wp_enqueue_script('nordot_autocomplete_js', '', array('jquery'), NORDOT_PLUGIN_VERSION);
		}		

		$admin_url = is_ssl() ? admin_url( 'admin-ajax.php', 'https') : admin_url( 'admin-ajax.php');

		// In JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.xyz
		wp_localize_script( 'nordot_plugin_js', 'ajax_object', array( 'ajax_url' => $admin_url, 'ajax_nonce' => wp_create_nonce('nordot_noncename_ajax'), 'ajax_can_publish' => current_user_can('publish_posts')) );		
	}
}

add_filter ('get_canonical_url', 'nordot_canonical_url', 100, 2);
add_filter ('rank_math/frontend/canonical', 'nordot_canonical_url_other_seo_plugins', 100);
add_filter ('wpseo_canonical', 'nordot_canonical_url_other_seo_plugins', 100);

function nordot_canonical_url($canonical_url, $post) {
	if ( is_single() ) {
		$nordot_curated_post_source_url = get_post_meta($post->ID, 'nordot_curated_post_source_url', TRUE);
		return $nordot_curated_post_source_url != null ? $nordot_curated_post_source_url : $canonical_url;
	}

	return $canonical_url;
}

function nordot_canonical_url_other_seo_plugins($canonical_url) {
    if ( is_single() ) {
        // Get the post id using the get_the_ID(); function:
        $nordot_curated_post_source_url = get_post_meta( get_the_ID(), 'nordot_curated_post_source_url', TRUE );
		return $nordot_curated_post_source_url != null ? $nordot_curated_post_source_url : $canonical_url;		
    }

	return $canonical_url;
}

add_action('wp_head', 'nordot_head_meta');
function nordot_head_meta(){
    if( is_single() ) {
		$nordot_curated_post = get_post_meta( get_the_ID(), 'nordot_curated_post', TRUE );
		if ($nordot_curated_post != null) {
			$thumbUrl = get_the_post_thumbnail_url(get_the_ID(),'full');
			if ($thumbUrl != NULL || $thumbUrl != '') {
        		echo '<meta property="og:image" content="'. esc_url($thumbUrl)   .'" />';
			}
		}
    }
}


/**
 * Add Settings sections and fields.  Register settings.
 */
function register_nordot_plugin_settings() {
	
	add_settings_section(
			'nordot-settings-content-owner-group',
			__('','nordot-text-domain'),
			'display_nordot_content_owner_message',
			'nordot_plugin_settings_page'
			);
	
	add_settings_field(
			'nordot-content-owner-media-unit',
			__('','nordot-text-domain'),
			'render_nordot_content_owner_media_unit_field',
			'nordot_plugin_settings_page',
			'nordot-settings-content-owner-group'
			);
	
	add_settings_field(
			'nordot-content-owner-unit-api-key',
			__('Token','nordot-text-domain'),
			'render_nordot_content_owner_apikey_field',
			'nordot_plugin_settings_page',
			'nordot-settings-content-owner-group'
			);
	
	add_settings_field(
			'nordot-content-owner-auto-upload',
			__('Automatically Upload Posts','nordot-text-domain'),
			'render_nordot_content_owner_autoupload_field',
			'nordot_plugin_settings_page',
			'nordot-settings-content-owner-group'
			);
	
	register_setting( 'nordot-settings', 'nordot-content-owner-media-unit' );
	register_setting( 'nordot-settings', 'nordot-content-owner-unit-api-key' );
	register_setting( 'nordot-settings', 'nordot-content-owner-auto-upload' );
	
	add_settings_section(
			'nordot-settings-curator-group',
			__('Curator Unit','nordot-text-domain'),
			'display_nordot_curator_message',
			'nordot_plugin_settings_page'
			);
	
	add_settings_field(
			'nordot-curator-media-unit',
			__('Curator Media Unit ID','nordot-text-domain'),
			'render_nordot_curator_media_unit_field',
			'nordot_plugin_settings_page',
			'nordot-settings-curator-group'
			);
	
	add_settings_field(
			'nordot-curator-unit-api-key',
			__('Token','nordot-text-domain'),
			'render_nordot_curator_apikey_field',
			'nordot_plugin_settings_page',
			'nordot-settings-curator-group'
			);
	
	add_settings_field(
			'nordot-curator-language',
			__('Search Language','nordot-text-domain'),
			'render_nordot_curator_language_field',
			'nordot_plugin_settings_page',
			'nordot-settings-curator-group'
			);
	
	add_settings_field(
			'nordot-curator-read-more',
			__('Read More Style','nordot-text-domain'),
			'render_nordot_curator_readmore_field',
			'nordot_plugin_settings_page',
			'nordot-settings-curator-group'
			);
	
	//register our settings
	register_setting( 'nordot-settings', 'nordot-curator-media-unit' );
	register_setting( 'nordot-settings', 'nordot-curator-unit-api-key' );
	register_setting( 'nordot-settings', 'nordot-curator-language' );
	register_setting( 'nordot-settings', 'nordot-curator-read-more' );
	
}

/**
 * Render the Nordot Settings page
 */
function nordot_plugin_settings_page() {
	?>
<?php if (isset($_GET['settings-updated'])) : ?>
	<div class="notice notice-success is-dismissible"><p><?php esc_html_e('Settings updated'); ?>.</p></div>
<?php endif; ?>
<div class="wrap">

	<?php 	
		if (!current_user_can( 'manage_options' )) {
			?>
			<h4><?php esc_html_e("Error: Insufficient privileges", ''); ?></h4>
			</div>
			<?php 
			return;
		}

		$signupLink = get_locale() === 'en_US' ? "https://www.nordot.io/n/signupform?lang=en&from=wp" : "https://www.nordot.io/n/signupform?lang=ja&from=wp";

	?>

	<div class="container-fluid nordot_settings_part p-2">
		<img style="margin-top: 10px; margin-left: 10px; max-width: 150px;" src="<?php echo esc_url(plugins_url( 'images/NDT_Full.png', __FILE__ )); ?>"/>
		<div class="row nordot_settings_part_section shadow mx-5 my-4">
			<div class="w-100 ml-3 mr-3 pt-4">
				<p class="nordot-grey text-center" style="font-size: 18px; font-weight: 500;">
				<?php esc_html_e("Thank you for downloading the official Nordot plugin!  You are only a few steps away from sharing content and gaining revenue!  Please see below on how to get started.", 'nordot-text-domain')?>
				</p>
				<div class="container">
					<div class="row">
						<div class="col-5 text-center">
							<h3><?php esc_html_e("Get Started", 'nordot-text-domain');?></h3>
							<p class="mb-2 nordot-grey"><?php esc_html_e("New to Nordot?  Please sign up below", 'nordot-text-domain');?></p>
							<p class="nordot-grey">
								<a href="<?php echo esc_url($signupLink); ?>" target="_blank" class="btn btn-info">
								<i class="fa fa-edit" style="color: white !important;"></i>
								<?php esc_html_e("Sign Up", 'nordot-text-domain');?>
								</a>
							</p>
							<p class="mt-4 mb-2 nordot-grey"><?php esc_html_e("Already a user?  Please log in below.", 'nordot-text-domain');?></p>
							<p>
								<a href="https://nordot.app/cms/" target="_blank" class="btn btn-info"s>
								<i class="fa fa-sign-in" style="color: white !important;"></i>
								<?php esc_html_e("Log in", 'nordot-text-domain');?>
								</a>
							</p>				  
						</div>
						<div class="col text-center" style="margin-bottom: 30px;">
							<div class="mx-auto my-4 nordot-settings-spacer"></div>
						</div>
						<div class="col-5 text-center">
							<h3><?php esc_html_e("Need help?", 'nordot-text-domain');?></h3>
							<p class="nordot-grey"><?php esc_html_e("Help Center Guides", 'nordot-text-domain');?></p>
							<p style="position: relative;  top: -2px;"> 
							<?php
								$lang = esc_attr(get_option('nordot-curator-language'));
								if ($lang === '') $lang = str_replace('_', '-', get_locale());
								if ($lang === 'ja-JP') {
								echo '<a class="nordot-settings-link" href="https://help.nordot.link/hc/en-us/categories/360002362151" target="_blank">';
								} else {
									echo '<a class="nordot-settings-link" href="https://help.nordot.io/WordPress-becc857878cb4009b58d5b98306b0519" target="_blank">';
								}
								echo '<i class="fa fa-info-circle"></i>  ';
								esc_html_e("Get Started Guide", 'nordot-text-domain');
							?>
							</a>
							</p>
							<p style="position: relative;  top: 48px;">
								<a class="nordot-settings-link" href="mailto:support@nordot.io">
									<i class="fa fa-envelope"></i>  <?php esc_html_e("Contact Us", 'nordot-text-domain')?>
								</a>
							</p>
						</div>								
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="container-fluid nordot_settings_part p-2">
		<form id="nordot-settings" method="post" action="options.php">
		<?php settings_fields( 'nordot-settings' ); ?>
		<div class="row nordot_settings_part_section mx-5 my-4 shadow">
			<div class="w-100 ml-3 mr-3 pt-4">
					<div class="row">
						<div class="col-5 text-center">
							<h3 class="d-inline-block align-middle"><?php esc_html_e("Content Owner", 'nordot-text-domain');?></h3> 
							<button type="button" class="btn p-0 ml-1 pb-1" data-toggle="tooltip" data-placement="top" title="<?php esc_attr_e("To make your original content available for others to curate and republish, enter credentials for your Content Owner Unit.", 'nordot-text-domain');?>">
							<i class="fa fa-lg fa-question-circle nordot-blue"></i>
							</button>
							<input class="mb-4 mt-2" type="text" name="nordot-content-owner-media-unit" size="33" value="<?php echo esc_attr( get_option('nordot-content-owner-media-unit') ); ?>" placeholder="ID of your Content Owner Media Unit"/>
							<button type="button" class="btn align-middle p-0" data-toggle="modal" data-target="#nordotSettingsModal" data-title="Content Owner Media Unit ID" data-img="<?php echo esc_url(plugins_url( 'images/content_owner_media_unit.gif', __FILE__ )); ?>">
							<i class="fa fa-lg fa-info-circle nordot-blue align-middle"></i>
							</button>
							<textarea class="align-middle" rows="4" cols="32" name="nordot-content-owner-unit-api-key"><?php echo esc_textarea( get_option('nordot-content-owner-unit-api-key') ); ?></textarea>
							<button type="button" class="btn align-middle p-0" data-toggle="modal" data-target="#nordotSettingsModal" data-title="Content Owner Token" data-img="<?php echo esc_url(plugins_url( 'images/content_owner_token.gif', __FILE__ )); ?>">
							<i class="fa fa-lg fa-info-circle nordot-blue align-middle"></i>
							</button>
							<div class="custom-control custom-switch mt-3">
								<input type="checkbox" class="custom-control-input" id="nordotUploadPostsSwitch" name="nordot-content-owner-auto-upload" value="checked" <?php echo esc_attr( get_option('nordot-content-owner-auto-upload') ); ?>>
								<label class="custom-control-label" for="nordotUploadPostsSwitch">
								<?php esc_html_e("Automatically Upload Posts", 'nordot-text-domain');?>
								</label>
								<br/>
								<span class="text-wrap text-secondary" style="font-weight: bold; font-size: 8pt;  display: inline-block;"><?php esc_html_e("Flip this toggle to automatically upload posts to Nordot.  You can still override this option on the individual post.", 'nordot-text-domain');?></span>								
							</div>
						</div>
						<div class="col text-center" style="margin-bottom: 30px;">
							<div class="mx-auto my-4 nordot-settings-spacer" ></div>
						</div>
						<div class="col-5 text-center">
							<h3 class="d-inline-block align-middle"><?php esc_html_e("Content Curator", 'nordot-text-domain');?></h3> 
							<button type="button" class="btn p-0 ml-1 align-middle pb-1" data-toggle="tooltip" data-placement="top" title="<?php esc_attr_e("To republish content from other publications, enter credentials for your Curator Unit.", 'nordot-text-domain');?>">
							<i class="fa fa-lg fa-question-circle nordot-blue"></i>
							</button>
							<input type="text" class="mb-4 mt-2" name="nordot-curator-media-unit" size="33" value="<?php echo esc_attr( get_option('nordot-curator-media-unit') ); ?>" placeholder="<?php esc_attr_e('ID of your Curator Media Unit', 'nordot-text-domain');?>"/>
							<button type="button" class="btn align-middle p-0" data-toggle="modal" data-target="#nordotSettingsModal" data-title="Curator ID" data-img="<?php echo esc_url(plugins_url( 'images/curator_media_unit.gif', __FILE__ )); ?>">
							<i class="fa fa-lg fa-info-circle nordot-blue align-middle"></i>
							</button>
							<textarea class="align-middle" rows="4" cols="32" name="nordot-curator-unit-api-key"><?php echo esc_html( get_option('nordot-curator-unit-api-key') ); ?></textarea>							
							<button type="button" class="btn align-middle p-0" data-toggle="modal" data-target="#nordotSettingsModal" data-title="Curator Token" data-img="<?php echo esc_url(plugins_url( 'images/curator_token.gif', __FILE__ )); ?>">
							<i class="fa fa-lg fa-info-circle nordot-blue align-middle"></i>
							</button>
							<?php
							$selected = esc_attr(get_option('nordot-curator-language'));
							if ($selected === '') $selected = str_replace('_', '-', get_locale());
							?>
							<div class="input-group input-group-sm my-3" style="margin-bottom: 0 !important; width: 312px; margin: 0 auto; padding-right: 24px;">
								<select id="nordot-curator-language" name="nordot-curator-language" class="custom-select" >
								<option value="en-US" <?php if ($selected === 'en-US') {echo 'selected';}else {echo '';}?>>English</option>
								<option value="de-DE" <?php if ($selected === 'de-DE') {echo 'selected';}else {echo '';}?>>Deutsch</option>
								<option value="es-ES" <?php if ($selected === 'es-ES') {echo 'selected';}else {echo '';}?>>Español</option>
								<option value="fr-FR" <?php if ($selected === 'fr-FR') {echo 'selected';}else {echo '';}?>>Français</option>
								<option value="nl-NL" <?php if ($selected === 'nl-NL') {echo 'selected';}else {echo '';}?>>Nederlands</option>
								<option value="it-IT" <?php if ($selected === 'it-IT') {echo 'selected';}else {echo '';}?>>Italiano</option>
								<option value="pt-BR" <?php if ($selected === 'pt-BR') {echo 'selected';}else {echo '';}?>>Português</option>
								<option value="sv-SE" <?php if ($selected === 'sv-SE') {echo 'selected';}else {echo '';}?>>Svenska</option>
								<option value="ru-RU" <?php if ($selected === 'ru-RU') {echo 'selected';}else {echo '';}?>>Русский</option>
								<option value="th-TH" <?php if ($selected === 'th-TH') {echo 'selected';}else {echo '';}?>>ภาษาไทย</option>
								<option value="in-ID" <?php if ($selected === 'in-ID') {echo 'selected';}else {echo '';}?>>Bahasa Indonesia</option>
								<option value="ko-KR" <?php if ($selected === 'ko-KR') {echo 'selected';}else {echo '';}?>>한국어 (Korean)</option>
								<option value="ja-JP" <?php if ($selected === 'ja-JP') {echo 'selected';}else {echo '';}?>>日本語 (Japanese)</option>
								<option value="zh-CN" <?php if ($selected === 'zh-CN') {echo 'selected';}else {echo '';}?>>简体中文 (Simplified Chinese)</option>
								<option value="zh-TW" <?php if ($selected === 'zh-TW') {echo 'selected';}else {echo '';}?>>正體中文 (Traditional Chinese)</option>
								<option value="tr-TR" <?php if ($selected === 'tr-TR') {echo 'selected';}else {echo '';}?>>Türkçe (Turkey)</option>
								<option value="ar-AE" <?php if ($selected === 'ar-AE') {echo 'selected';}else {echo '';}?>>العربية (Alabian)</option>
								<option value="pl-PL" <?php if ($selected === 'pl-PL') {echo 'selected';}else {echo '';}?>>Polsk</option>
								<option value="ms-MY" <?php if ($selected === 'ms-MY') {echo 'selected';}else {echo '';}?>>Malayu</option>
								<option value="ta-IN" <?php if ($selected === 'ta-IN') {echo 'selected';}else {echo '';}?>>தமிழ் (Tamil)</option>	
								</select>
							</div>
							<span class="text-wrap text-secondary d-inline-block" style="font-size: 8pt; font-weight: bold;"><?php esc_html_e("Return articles written in the selected language.", 'nordot-text-domain'); ?></span>
							<textarea class="align-middle" style="margin-top: 10px; margin-right: 20px;" rows="4" cols="32" name="nordot-curator-read-more" ><?php echo esc_textarea( get_option('nordot-curator-read-more', 'a.nordot-read-more { }') ); ?></textarea>
							<p class="text-wrap text-secondary d-inline-block" style="font-weight: bold;font-size: 8pt; max-width: 725px;"><?php esc_html_e("Custom CSS for Read More link.  Leave blank for default style.", 'nordot-text-domain'); ?></p> 										
						</div>					
					</div>
			</div>
		</div>
		<div class="row mx-5">
			<div class="col-12 pr-0">
			<p style="text-align: right;">
				<a id="nordot-settings-save" href="#" class="btn btn-info"s>
				<i class="fa fa-save" style="color: white !important;"></i>
				<?php esc_html_e("Save Changes", 'nordot-text-domain');?>
				</a>
			</p>
			</div>
		</div>
		</form>		
	</div>

</div>
<div class="modal fade" id="nordotSettingsModal" tabindex="-1" aria-labelledby="nordotSettingsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="nordotSettingsModalLabel">Modal title</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <img style="width: 100%;" src=""/>
      </div>
      <div class="modal-footer" style="display: none;">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Save changes</button>
      </div>
    </div>
  </div>
</div>	

<?php 
}


function display_nordot_curator_message() {
	echo '<h3>' . esc_html__("To search for articles to curate and republish, enter credentials for your Curator Unit.", 'nordot-text-domain') . '</h3>';    
}

function render_nordot_curator_media_unit_field() {
	?>
	<input type="text" name="nordot-curator-media-unit" size="50" value="<?php echo esc_attr( get_option('nordot-curator-media-unit') ); ?>" placeholder="<?php esc_attr_e('ID of your Curator Media Unit', 'nordot-text-domain');?>"/>
	<p style="font-size: 9pt; max-width: 725px;"><?php esc_html_e("To find your Media Unit ID, log into your Nordot account and click on Curator Units in the left-hand menu. Select Settings and scroll down to Media Unit ID under the Development tab.", 'nordot-text-domain'); ?>
	</p>
	<?php
}

function render_nordot_curator_apikey_field() {
	?>
	<textarea rows="4" cols="100" name="nordot-curator-unit-api-key"><?php echo esc_html( get_option('nordot-curator-unit-api-key') ); ?></textarea>
	<p style="font-size: 9pt; max-width: 725px;"><?php esc_html_e("To find your Media Unit token, log into your Nordot account and click on Curator Units in the left-hand menu. Select Settings and scroll down to Token under the Development tab.  You may have to press Issue button.", 'nordot-text-domain'); ?></p> 		
	<?php
}

function render_nordot_curator_language_field() {
	$selected = esc_attr(get_option('nordot-curator-language'));
	if ($selected === '') $selected = str_replace('_', '-', get_locale());
	
	?>

	<select name="nordot-curator-language">
	<option value="en-US" <?php if ($selected === 'en-US') {echo 'selected';}else {echo '';}?>>English</option>
	<option value="de-DE" <?php if ($selected === 'de-DE') {echo 'selected';}else {echo '';}?>>Deutsch</option>
	<option value="es-ES" <?php if ($selected === 'es-ES') {echo 'selected';}else {echo '';}?>>Español</option>
	<option value="fr-FR" <?php if ($selected === 'fr-FR') {echo 'selected';}else {echo '';}?>>Français</option>
	<option value="nl-NL" <?php if ($selected === 'nl-NL') {echo 'selected';}else {echo '';}?>>Nederlands</option>
	<option value="it-IT" <?php if ($selected === 'it-IT') {echo 'selected';}else {echo '';}?>>Italiano</option>
	<option value="pt-BR" <?php if ($selected === 'pt-BR') {echo 'selected';}else {echo '';}?>>Português</option>
	<option value="sv-SE" <?php if ($selected === 'sv-SE') {echo 'selected';}else {echo '';}?>>Svenska</option>
	<option value="ru-RU" <?php if ($selected === 'ru-RU') {echo 'selected';}else {echo '';}?>>Русский</option>
	<option value="th-TH" <?php if ($selected === 'th-TH') {echo 'selected';}else {echo '';}?>>ภาษาไทย</option>
	<option value="in-ID" <?php if ($selected === 'in-ID') {echo 'selected';}else {echo '';}?>>Bahasa Indonesia</option>
	<option value="ko-KR" <?php if ($selected === 'ko-KR') {echo 'selected';}else {echo '';}?>>한국어 (Korean)</option>
	<option value="ja-JP" <?php if ($selected === 'ja-JP') {echo 'selected';}else {echo '';}?>>日本語 (Japanese)</option>
	<option value="zh-CN" <?php if ($selected === 'zh-CN') {echo 'selected';}else {echo '';}?>>简体中文 (Simplified Chinese)</option>
	<option value="zh-TW" <?php if ($selected === 'zh-TW') {echo 'selected';}else {echo '';}?>>正體中文 (Traditional Chinese)</option>
	<option value="tr-TR" <?php if ($selected === 'tr-TR') {echo 'selected';}else {echo '';}?>>Türkçe (Turkey)</option>
	<option value="ar-AE" <?php if ($selected === 'ar-AE') {echo 'selected';}else {echo '';}?>>العربية (Alabian)</option>
	<option value="pl-PL" <?php if ($selected === 'pl-PL') {echo 'selected';}else {echo '';}?>>Polsk</option>
	<option value="ms-MY" <?php if ($selected === 'ms-MY') {echo 'selected';}else {echo '';}?>>Malayu</option>
	<option value="ta-IN" <?php if ($selected === 'ta-IN') {echo 'selected';}else {echo '';}?>>தமிழ் (Tamil)</option>	
	</select>
	<p style="font-size: 9pt; max-width: 725px;"><?php esc_html_e("Return articles written in the selected language.", 'nordot-text-domain'); ?></p> 		
	<?php
}

function render_nordot_curator_readmore_field() {
	?>
	<textarea rows="4" cols="50" name="nordot-curator-read-more" ><?php echo esc_textarea( get_option('nordot-curator-read-more', 'a.nordot-read-more { }') ); ?></textarea>
	<p style="font-size: 9pt; max-width: 725px;"><?php esc_html_e("Custom CSS for Read More link.  Leave blank for default style.", 'nordot-text-domain'); ?></p> 		
	<?php
}

function display_nordot_content_owner_message() {
	echo '<h3>' . esc_html__("To make your original content available for others to curate and republish, enter credentials for your Content Owner Unit.", 'nordot-text-domain') . '</h3>';
}

function render_nordot_content_owner_media_unit_field() {
	?>
	<input type="text" name="nordot-content-owner-media-unit" size="50" value="<?php echo esc_attr( get_option('nordot-content-owner-media-unit') ); ?>" placeholder="ID of your Content Owner Media Unit"/>
	<p style="font-size: 9pt; max-width: 725px;"><?php esc_html_e("To find your Media Unit ID, log into your Nordot account and click on Content Owner Units in the left-hand menu. Select Settings and scroll down to Media Unit ID under the Development tab.", 'nordot-text-domain'); ?>
	</p>	
	<?php
}

function render_nordot_content_owner_apikey_field() {
	?>
    <textarea rows="4" cols="100" name="nordot-content-owner-unit-api-key"><?php echo esc_textarea( get_option('nordot-content-owner-unit-api-key') ); ?></textarea>
    	<p style="font-size: 9pt; max-width: 725px;"><?php esc_html_e("To find your Media Unit token, log into your Nordot account and click on Content Owner Units in the left-hand menu. Select Settings and scroll down to Token under the Development tab.  You may have to press Issue button.", 'nordot-text-domain'); ?></p>  	
	<?php
}

function render_nordot_content_owner_autoupload_field() {
	?>
    <input type="checkbox" name="nordot-content-owner-auto-upload" value="checked" <?php echo esc_attr( get_option('nordot-content-owner-auto-upload') ); ?> />
    	<p style="font-size: 9pt; max-width: 725px;"><?php esc_html_e("Check this box to automatically upload posts to Nordot.  You can still override this option on the individual post.", 'nordot-text-domain'); ?></p>  	
	<?php
}



add_action('wp_head', 'nordot_custom_styles', 100);

function nordot_custom_styles()
{
	$readMoreOption = get_option('nordot-curator-read-more');
	if ($readMoreOption) {
		echo '<style type="text/css">' . esc_html($readMoreOption) . '</style>';
	}
}


add_shortcode('nordot-modal-mode', 'nordot_modal_mode_shortcode');

function nordot_modal_mode_shortcode() {
	//wp_enqueue_script('nordot_modal_js', '', array('jquery'));
}

add_action( 'wp_enqueue_scripts', 'nordot_register_non_admin_scripts' );
function nordot_register_non_admin_scripts() {
	//wp_register_script( 'nordot_modal_js', plugins_url( 'js/nordot-modal.js' , __FILE__ ), array('jquery'), NORDOT_PLUGIN_VERSION, true);
}

add_shortcode('nordot-body-analytics', 'nordot_body_analytics_shortcode');

function nordot_body_analytics_shortcode($atts = [], $content = null, $tag = '' ) {
	// normalize attribute keys, lowercase
	$atts = array_change_key_case( (array) $atts, CASE_LOWER );
	
	// override default attributes with user attributes
	$final_atts = shortcode_atts(
		array(
		'pageviewURL' => 'https://log.nordot.jp/pageview',
		'opttype' => 'unknown',
		'pagetype' => "detail",
		'conttype' => "post",
		'uiid' => '',
		'postid' => '',
		'title' => '',
		'numimg' => 0,
		'cvrimg' => 0,
		'pubdate' => '',
		'chlang' => "",
	  	'chunitid' => '',
	  	'cuunitid' => ''
		), $atts, $tag
	);
	
	$o  = '<script type="text/javascript" src="https://log.nordot.jp/js/beacon-1.1.0.js"></script>';
	$o .= '<script type="text/javascript">';
	$o .= 'nor.pageviewURL = "' . esc_html__( $final_atts['pageviewURL'])  . '";';
	$o .= 'nor.setPageData({';
	$o .= 'opttype: "' . esc_html__( $final_atts['opttype']) . '",';
	$o .= 'pagetype: "' . esc_html__( $final_atts['pagetype']) . '",';
	$o .= 'conttype: "' . esc_html__( $final_atts['conttype']) . '",';
	$o .= 'uiid: "' . esc_html__( $final_atts['uiid']) . '",';
	$o .= 'postid: "' . esc_html__( $final_atts['postid']) . '",';
	$o .= 'contdata: {';
	$o .= 'title: "' . esc_html__( $final_atts['title']) . '",';
	$o .= 'numimg: ' . esc_html__( $final_atts['numimg']) . ',';
	$o .= 'cvrimg: ' . esc_html__( $final_atts['cvrimg']) . ',';
	$o .= 'pubdate: "' . esc_html__( $final_atts['pubdate']) . '",';
	$o .= 'chlang: "' . esc_html__( $final_atts['chlang']) . '"';
	$o .= '},';
	$o .= 'chunitid: "' . esc_html__( $final_atts['chunitid']) . '",';
	$o .= 'cuunitid: "' . esc_html__( $final_atts['cuunitid']) . '"';
	$o .= '});';
	$o .= 'nor.pageview();';
	$o .= '</script>';
	
	// enclosing tags
	if ( ! is_null( $content ) ) {
		// secure output by executing the_content filter hook on $content
		$o .= apply_filters( 'the_content', $content );
		
		// run shortcode parser recursively
		$o .= do_shortcode( $content );
	}
	
	// return output
	return $o;
}

