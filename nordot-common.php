<?php

$nordot_api_url = "https://api.nordot.jp/v1.0/";

// Hook in admin_menu action to create the custom meta box
add_action('admin_menu', 'nordot_add_custom_meta');

/**
 * Adds a custom meta box to the add or edit Post
 */
function nordot_add_custom_meta() {
	if (function_exists('add_meta_box')) {
		add_meta_box('nordot_sectionid', 'Nordot Options', 'render_nordot_custom_meta_box', 'post', 'normal', 'high');
	}
}

/**
 * Renders custom meta box in New/Edit Post page
 */
function render_nordot_custom_meta_box() {
	global $post;
	$content = $post->ID;
	
	echo '<p>';
	if (get_post_meta($content, 'nordot_curated_post', TRUE)) {
		//error_log(get_post_meta($content, 'nordot_curated_post_expired_at', TRUE));
		// Create nordot_admin_nonce for verification
		echo '<input type="hidden" name="nordot_noncename_curator" id="nordot_noncename_curator" value="' . esc_attr(wp_create_nonce('nordot_curator_admin_nonce')) . '" />';
		echo '<label style="font-size: 12pt;">' . esc_html(__("Annotation", "nordot-text-domain")) . ':</label><br/><textarea name="nordot_curated_post_annotation" rows="3" cols="80" style="margin-top: 10px;">' . esc_html(get_post_meta($content, 'nordot_curated_post_annotation', true)) . '</textarea>';
	} else {
		// Create nordot_admin_nonce for verification
		echo '<input type="hidden" name="nordot_noncename_contentowner" id="nordot_noncename_contentowner" value="' . esc_attr(wp_create_nonce('nordot_contentowner_admin_nonce')) . '" />';
		
		$featureEnabled = get_option('nordot-content-owner-media-unit' ) !== '' &&  get_option('nordot-content-owner-unit-api-key' ) !== '';
		
		// Retrieve custom field about uploading this original content to Nordot
		$data = get_post_meta($content, 'nordot_upload_post', TRUE);
		echo '<label>' . esc_html__("Upload this post to the Nordot CMS for syndication to other sites and aggregators.", 'nordot-text-domain') . '</label> <input name="nordot_upload_post" type="checkbox" value="yes"';
		if ($data === 'yes') {
			echo 'checked';
		} else if ($data === 'no'){
			echo '';
		} else if (get_option('nordot-content-owner-auto-upload') === 'checked') {
			echo 'checked';
		} else {
			echo '';
		}
		echo ($featureEnabled === true) ? '' : ' disabled ';
		echo ' />';
		
		if ($featureEnabled === false) {
			echo '<p>' . esc_html(__("To enable this feature, go to Nordot Settings and enter Content Owner credentials.", 'nordot-text-domain')) . '</p>';
		}
		
		$text = sprintf(
				wp_kses(__('To set the default value for this option, visit the <a target="_blank" href="%s">Settings</a> page.','nordot-text-domain'),
						array( 'a' => array( 'href'  => array(), 'target'  => array() ) )
						),
				esc_url( admin_url() . "admin.php?page=nordot-menu-settings" )
				);
		
		$text = '<p>' . $text . '</p>';
		echo wp_kses($text, array('p' => array(), 'a' => array( 'href'  => array(), 'target'  => array() )));
		
	}
	echo '</p>';
}

/**
 * Helper function to send a POST to nordot API
 */
function nordot_send_post($nordotUnitId, $nordotKey, $url_action, $body_args, $content_type = 'application/x-www-form-urlencoded') {
	global $nordot_api_url;
	
	if ($nordotUnitId === null || $nordotUnitId === '' || $nordotKey === null || $nordotKey === '')
		return null;

		$r = wp_safe_remote_post(
				$nordot_api_url . $url_action,
				array(
						'headers' => array(
								"Authorization" => $nordotKey,
								"Content-type" => $content_type
						),
						'body' => $body_args
				)
		);
		
	return $r;
}

/**
 * Upload image to Nordot API
 */
function nordot_upload_image($img_path) {

	$upload_dir = wp_get_upload_dir();
	
	// If this is an image on our own servers, get the full server path
	if (nordotStartsWith($img_path, $upload_dir['baseurl'])) {
		$img_path = $upload_dir['basedir'] . substr($img_path, strlen($upload_dir['baseurl']));
	}
	
	// Not an image on our servers.  Download it
	$isExternal = false;
	if (nordotStartsWith($img_path, 'http')) {
		$download = download_url($img_path);
		
		if (is_wp_error($download)) {
			return -2;
		}
		
		$img_path = $download;
		$isExternal = true;
	}
	
	$nordotUnitId = get_nordot_media_unit(false);
	$nordotKey = get_nordot_unit_api_key(false);
	
	$boundary = md5( time() );
	
	$payload  = '';
	$payload .= '--' . $boundary;
	$payload .= "\r\n";
	$payload .= 'Content-Disposition: form-data; name="file"; filename="' . basename($img_path) . '"' . "\r\n";
	$payload .= 'Content-Type: ' . wp_get_image_mime($img_path) . "\r\n";
	$payload .= 'Content-Transfer-Encoding: binary' . "\r\n";
	$payload .= "\r\n";
	$payload .= $isExternal === true && function_exists('wpcom_vip_file_get_contents') ? wpcom_vip_file_get_contents($img_path) : file_get_contents( $img_path );
	$payload .= "\r\n";
	$payload .= '--' . $boundary . "\r\n";
	$payload .= 'Content-Disposition: form-data; name="unit_id"' . "\r\n";
	$payload .= 'Content-Type: application/json' . "\r\n\r\n";
	$payload .= $nordotUnitId . "\r\n";
	$payload .= '--' . $boundary . '--';
	$payload .= "\r\n\r\n";
	
	if ($isExternal) {
		unlink( $img_path );
	}
	
	$r = nordot_send_post($nordotUnitId, $nordotKey, 'contentsholder/images.upload', $payload, 'multipart/form-data;boundary=' . $boundary);
	
	if ($r === null || is_wp_error($r)) {
		// Error log?
	} else {
		$returnObject = wp_remote_retrieve_body($r);
		$jsonObj = json_decode($returnObject);
		
		if ($jsonObj->ok) {
			// Get the newly assigned image id
			return $jsonObj->image->id;
		} 
	}
	
	return -2;
}

function nordotStartsWith ($string, $startString)
{
	$len = strlen($startString);
	return (substr($string, 0, $len) === $startString);
} 


function get_nordot_media_unit($curator = true) {
	if ($curator)
		return sanitize_text_field(get_option('nordot-curator-media-unit'));
	
	return sanitize_text_field(get_option('nordot-content-owner-media-unit'));
}

function get_nordot_unit_api_key($curator = true) {
	if ($curator)
		return sanitize_text_field(get_option('nordot-curator-unit-api-key'));
	
	return sanitize_text_field(get_option('nordot-content-owner-unit-api-key'));
}

function get_nordot_curator_language() {
	return sanitize_text_field(get_option('nordot-curator-language'));
}

function get_nordot_save_search_table() {
	global $wpdb;
	
	return $wpdb->prefix . "nordot_save_searches";
}

function get_nordot_read_later_table() {
	global $wpdb;
	
	return $wpdb->prefix . "nordot_read_later";
}

function nordot_get_wp_timezone() {
	$timezone_string = get_option( 'timezone_string' );
	if ( ! empty( $timezone_string ) ) {
		return new DateTimeZone( $timezone_string );
	}
	$offset  = get_option( 'gmt_offset' );
	$hours   = (int) $offset;
	$minutes = abs( ( $offset - (int) $offset ) * 60 );
	$offset  = sprintf( '%+03d:%02d', $hours, $minutes );
	return new DateTimeZone( $offset );
}

function nordot_send_email($subject = "", $emailBody = "") {
	try {
		$body = " " . get_bloginfo('url') . "\n\n" . "plugin version: " . NORDOT_PLUGIN_VERSION . "\n\n" . $emailBody;
		//wp_mail('wordpresskevinwebb@gmail.com', $subject . " " . get_bloginfo('name'), $body);
	} catch (Exception $e) {
	}
}

function nordot_do_expire_content() {
	// Get the articles that have been curated and have ane expiration
	$query_args = array(
		'posts_per_page' => -1,
		'meta_query'     => array(
			'relation' => 'AND',
			array(
				'key'     => 'nordot_curated_post',
				'value' => TRUE
			),
			array(
				'key'     => 'nordot_curated_post_expired_at',
				'compare' => 'EXISTS'
			)
		)
	);	
	
	$query = new WP_Query($query_args);	

	if (isset($query->posts)) {
		$curated_posts = $query->posts;
		foreach ($curated_posts as $cp) {
			$expired_data = get_post_meta($cp->ID, 'nordot_curated_post_expired_at', TRUE);
			
			$dt_expired_date = new DateTime($expired_data);
			$date_time_now = new DateTime('now');

			$secondsDiff = $dt_expired_date->getTimestamp() - $date_time_now->getTimestamp();

			if ($secondsDiff <= 0) {
				// Time to delete this article because it has expired
				wp_delete_post($cp->ID, true);
			}
		}
	}
}

function nordot_do_auto_publish($interval) {
	global $wpdb;
	$nordotAutoPublishTimeKey = 'nordot_auto_publish_' . $interval;
	
	$nordotAutoPublishTimeValue = get_option($nordotAutoPublishTimeKey);

	$tablename = get_nordot_save_search_table();
	$savedSearches = $wpdb->get_results($wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}nordot_save_searches` where `autoPublish` = 1 AND `autoPublishFrequency` = %d", [$interval]));
	
	foreach ($savedSearches as $saveSearch) {
		nordot_do_auto_publish_search($saveSearch, $nordotAutoPublishTimeValue);
	}
	
	update_option($nordotAutoPublishTimeKey , new DateTime('now', nordot_get_wp_timezone()));
}

function nordot_do_auto_publish_search($saveSearch, $nordotAutoPublishTimeValue) {
	global $nordot_api_url;
	
	$query = sanitize_text_field(stripslashes($saveSearch->query));
	
	if ($nordotAutoPublishTimeValue) {
		if ($nordotAutoPublishTimeValue->date) {
			$dateArr = explode(".", $nordotAutoPublishTimeValue->date);
			$date = str_replace(" ", "T", $dateArr[0]);
						
			$date_time = new DateTime('now', nordot_get_wp_timezone());
			$date_time_offset = $date_time->format('P');

			$date .= $date_time_offset;
			
			$query .= " published_at:>=" . $date;
		}
	}
	
	$query = rawurlencode(htmlspecialchars_decode($query) . ' language:' . get_nordot_curator_language() );
	
	$nordotSetModalWindow = $saveSearch->autoPublishSetModalWindow;
	$nordotSetFeaturedImage = $saveSearch->autoPublishSetFeaturedImage;
	$nordotSetBodyImage = $saveSearch->autoPublishSetBodyImage;
	$nordotBodyImageStyle = $saveSearch->autoPublishSetBodyImageStyle;
	$nordotCategories = $saveSearch->autoPublishCategories;
	if ($nordotCategories !== null && $nordotCategories !== '') {
		$nordotCategories = explode(",", $nordotCategories);
	} else {
		$nordotCategories = array();
	}
	
	$nordotQueryString = "?query=" . $query . "&limit=" . $saveSearch->autoPublishAmount;

	$r = wp_safe_remote_get($nordot_api_url . 'search/contentsholder/posts.list' . $nordotQueryString, array( 'timeout' => 3, 'headers' => array("Authorization" => get_nordot_unit_api_key())));
	
	if (!is_wp_error($r)) {
		$returnObject = wp_remote_retrieve_body($r);
		$jsonObj = json_decode($returnObject);
		
		if ($jsonObj->ok) {
			$articles = $jsonObj->posts;
			
			if ($articles) {
				foreach ($articles as $article) {
					$nordotTitle = $article->title;

					// Create an empty post so that we get the permalink to send to Nordot's create curation call
					$postId = nordot_prime_post($nordotTitle, '');
					$postPermalink = get_permalink($postId);

					$curateResult = nordot_do_curate($article->url, '', 'public', $postPermalink);
					
					if (!is_wp_error($curateResult)) {
						$returnObject = wp_remote_retrieve_body($curateResult);
						$jsonObj = json_decode($returnObject);
						
						if ($jsonObj->ok) {
							// Get the post info to see if we get full body back
							$nordotBody = '';
							$nordotAnalytics = '';
							$nordotPublisherName = '';
							$nordotCurateSourceUrl = '';
							$r = nordot_do_get_post_info($article->id);
							
							if (!is_wp_error($r)) {
								$body = wp_remote_retrieve_body($r);
								$jsonObjPostInfo = json_decode($body);
							
								if ($jsonObjPostInfo->ok && $jsonObjPostInfo->post) {
									$nordotCurateSourceUrl = $jsonObjPostInfo->post->source_url;

									if ($jsonObjPostInfo->post->body) {
										$nordotBody = $jsonObjPostInfo->post->body;
										$nordotAnalytics = $jsonObjPostInfo->post->analytics;
										$nordotPublisherName = $jsonObj->post->unit->name;
									}
								}
							}
							
							nordot_publish_wp_post($postId, $nordotTitle, $jsonObj->curation, 'publish', $nordotSetFeaturedImage, $nordotSetBodyImage, $nordotBodyImageStyle, $nordotCategories, $nordotSetModalWindow, $nordotBody, $nordotAnalytics, $nordotPublisherName, $nordotCurateSourceUrl);
						} else {
							wp_delete_post($postId, true);
						}
					} else {
						wp_delete_post($postId, true);
					}

				}
			}
		}
	} 
}

function nordot_do_curate($nordotContentUrl, $nordotAnnotation, $nordotStatus, $nordotRepublishUrl) {
	$nordotUnitId = get_nordot_media_unit();
	$nordotContentUrl = sanitize_text_field($nordotContentUrl);
	$nordotAnnotation = sanitize_text_field(stripslashes($nordotAnnotation));
	
	$args = array( 'unit_id' => $nordotUnitId, 'content_url' => $nordotContentUrl, 'annotation' =>  $nordotAnnotation, 'status' => $nordotStatus, 'repubpage_url' => $nordotRepublishUrl);
	$r = nordot_send_post($nordotUnitId, get_nordot_unit_api_key(), 'curator/curations.create', $args);
	
	return $r;
}

function nordot_do_get_post_info($nordotContentId) {
	global $nordot_api_url;
	
	$r = wp_safe_remote_get($nordot_api_url . 'search/contentsholder/posts.info?post_id=' . $nordotContentId, array( 'timeout' => 3, 'headers' => array("Authorization" => get_nordot_unit_api_key())));
	
	return $r;
}

function nordot_prime_post($nordotTitle, $postContent) {

	$postArray = array (
		'post_title' => $nordotTitle,
		'post_content' => $postContent
	);

	$nordotPostId = wp_insert_post($postArray);	

	return $nordotPostId;
}

function nordot_publish_wp_post($nordotPostId, $nordotTitle, $curatedObj, $publishStatus, $setFeaturedImage, $setBodyImage, $bodyImageStyle, $categories, $setModalWindow, $body = '', $analytics = '', $publisherName = '', $nordotCurateSourceUrl = '') {	
	$setModalWindow = false;
	//$modalWindowClass = ($setModalWindow) ? 'nordot-modal' :  '';
	$postContent = '<div style="width: 75px; height: 75px; border-radius: 50%; display: inline-block; background: url(' . $curatedObj->content->unit->profile_image->square_200 . '); background-position: center; background-repeat: no-repeat; background-size: cover; vertical-align: middle;"></div><div style="margin-left: 5px; display: inline-block; font-size: 14px; font-weight: bold; vertical-align: middle;">Published by <br />' . $curatedObj->content->unit->name . '</div>';
	$postContent .= '<div style="margin-top: 10px;"></div>';
	if ($body === '') {
		$postContent .= $curatedObj->content->description . '<!--more-->'. '<p><a class="nordot-read-more " target="_blank" href="' . $curatedObj->content->url_curation . '">' . __("Read More",'nordot-text-domain') . '</a></p>';
	} else {
		$postContent .=  $body . '<!--more-->';
	}
	
	if ($analytics !== '') {		
		$output_array = [];
		preg_match('/nor.pageviewURL = (.*);/', $analytics, $output_array);
		$pageViewUrl = $output_array[1];

		$output_array = [];
		preg_match('/opttype: (.*),/', $analytics, $output_array);
		$opttype = $output_array[1];
		
		$output_array = [];
		preg_match('/pagetype: (.*),/', $analytics, $output_array);
		$pagetype = $output_array[1];
		
		$output_array = [];
		preg_match('/conttype: (.*),/', $analytics, $output_array);
		$conttype = $output_array[1];
		
		$output_array = [];
		preg_match('/uiid: (.*),/', $analytics, $output_array);
		$uiid = $output_array[1];
		
		$output_array = [];
		preg_match('/postid: (.*),/', $analytics, $output_array);
		$analyticsPostId = $output_array[1];
		
		$output_array = [];
		preg_match('/title: (.*),/', $analytics, $output_array);
		$analyticsTitle = str_replace('"', '', $output_array[1]);
		
		$output_array = [];
		preg_match('/numimg: (.*),/', $analytics, $output_array);
		$numimg = $output_array[1];
		
		$output_array = [];
		preg_match('/cvrimg: (.*),/', $analytics, $output_array);
		$cvrimg = $output_array[1];
		
		$output_array = [];
		preg_match('/pubdate: (.*),/', $analytics, $output_array);
		$pubdate = $output_array[1];
		
		$output_array = [];
		preg_match('/chlang: (.*)/', $analytics, $output_array);
		$chlang = $output_array[1];
		
		$output_array = [];
		preg_match('/chunitid: (.*),/', $analytics, $output_array);
		$chunitid = $output_array[1];
		
		$output_array = [];
		preg_match('/cuunitid: (.*)/', $analytics, $output_array);
		$cuunitid = $output_array[1];
		
		$postContent .= '[nordot-body-analytics pageviewURL=' . $pageViewUrl . ' '  
		. 'opttype=' . $opttype . ' ' 
		. 'pagetype=' . $pagetype . ' '
		. 'conttype=' . $conttype . ' '
		. 'uiid=' . $uiid . ' '
		. 'postid=' . $analyticsPostId . ' '
		. 'title="' . $analyticsTitle . '" '
		. 'numimg=' . $numimg . ' '
		. 'cvrimg=' . $cvrimg . ' '
		. 'pubdate=' . $pubdate . ' '
		. 'chlang=' . $chlang . ' '
		. 'chunitid=' . $chunitid . ' '
		. 'cuunitid=' . $cuunitid . ' '
		. ']';
	}
	
	$nordotExpiredAt = isset($curatedObj->content->expired_at) ? $curatedObj->content->expired_at : '';
	$postArray = array (
			'ID' => $nordotPostId,
			'post_title' => $nordotTitle,
			'post_content' => $postContent,
			'post_status' => $publishStatus,
			'post_category' => $categories,
			'meta_input' => array ('nordot_curated_post_id' => $curatedObj->id, 
									'nordot_curated_post' => true, 'nordot_modal_window' => $setModalWindow, 
									'nordot_curated_post_annotation' => $curatedObj->annotation,
									'nordot_curated_post_source_url' => $nordotCurateSourceUrl,
									'nordot_curated_post_expired_at' => $nordotExpiredAt)
	);
	
	if ($body === '' && $setModalWindow) {
		// Modify allowed HTML through custom filter.  This is for people who can't have script tag in post
		add_filter( 'wp_kses_allowed_html', 'nordot_wpse_kses_allowed_html', 10, 2 );
	}
	
	$nordotPostId = wp_update_post($postArray);
	
	if ($body === '' &&  $setModalWindow) {
		// Remove custom filter
		remove_filter( 'wp_kses_allowed_html', 'nordot_wpse_kses_allowed_html', 10 );
	}
	
	if ($nordotPostId !== 0 && ($setFeaturedImage || $setBodyImage || $body !== '')) {
		$imgUrl = $curatedObj->content->image_url;
		if ($imgUrl === null || $imgUrl === '') {
			if (isset($curatedObj->content->unit->profile_image)) {
				$imgUrl = $curatedObj->content->unit->profile_image->url;
			}
		} else {
			// Get larger image
			$imgUrl = str_replace("w_360", "w_800", $imgUrl);
		}
		
		// We have no image url, so just stop execution
		if ($imgUrl === null || $imgUrl === '') {
			return $nordotPostId;
		}
		
		$result = nordot_generate_image($imgUrl, $nordotPostId);
		
		$newPostContent = '';
		
		
		// Image was successfully uploaded to media
		if ($result) {
			if ($setFeaturedImage) {
				set_post_thumbnail( $nordotPostId, $result );
			}

			$imageLink = wp_get_attachment_url($result);
			
			if ($setBodyImage) {
				if ($body === '') {					
					$imageDiv = '<div class="wp-block-image"><figure class="' . $bodyImageStyle . '"><img class="nordot-featured" src="' . $imageLink . '" alt="" class="wp-image-' . $result . '"/></figure></div><p></p>';
					$newPostContent = $imageDiv . $postContent;
				} else {
					$imageDiv = '<div class="wp-block-image"><figure class="' . $bodyImageStyle . '"><img class="nordot-featured" src="' . $imageLink . '" alt="" class="wp-image-' . $result . '"/></figure><p></p>';
					$newPostContent = str_replace("<figure><img src=\"" . $imgUrl . "\" />", $imageDiv, $postContent);
				}
			} else if (!$setBodyImage && $body !== '') {
				$newPostContent = str_replace("<img src=\"" . $imgUrl . "\" />", "", $postContent);
			}
		}
		
		// Find all nordot images in the body, and download them locally.
		if ($body !== '') {
			$changeMade = false;
			$html = $newPostContent === '' ? $postContent : $newPostContent;
			
			$document = new \DOMDocument();
			libxml_use_internal_errors(true);
			
			// Hack to load utf-8 HTML (from http://bit.ly/pVDyCt)
			$document->loadHTML('<?xml encoding="UTF-8">' . $html);
			$document->encoding = 'UTF-8';
			
			libxml_clear_errors();
			
			$xpath = new \DOMXpath($document);
			$divNordotImages = $xpath->query('//div[@class="nordot-image"]');
			
			foreach($divNordotImages as $divNordotImage) {
				$arr = $divNordotImage->getElementsByTagName("img");
				foreach($arr as $image) {
					$class = $image->getAttribute("class");
					if (strpos($class, "nordot-featured") === FALSE) {
						$src =  $image->getAttribute("src");
						$imgResult = nordot_generate_image($src, $nordotPostId);
						
						if ($imgResult) {
							$imageResultLink = wp_get_attachment_url($imgResult);
							$image->setAttribute("src", $imageResultLink);
							
							$divClass = $divNordotImage->getAttribute("class");
							$divNordotImage->setAttribute("class", $divClass . " wp-block-image");
							
							$changeMade = true;
						}
					}
				}
			}
			
			
			if ($changeMade === true) {
				$body = $document->getElementsByTagName('body')->item(0);
				$newPostContent = '';
				foreach ($body->childNodes as $childNode) {
					$newPostContent .= $document->saveHTML($childNode);
				}
			}
		}
		
		if ($newPostContent !== '') {
			
			$postArray = array (
					'ID' => $nordotPostId,
					'post_content' => $newPostContent
			);
			
			wp_update_post($postArray);
		}
	}
	
	return $nordotPostId;
}

function nordot_wpse_kses_allowed_html( $allowed, $context )
{
	if( 'post' !== $context )
		return $allowed;
		
		$allowed['script'] = array('src' => true);
		
		return $allowed;
}

