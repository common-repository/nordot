<?php
require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ;
use League\HTMLToMarkdown\HtmlConverter;


add_action('publish_post', 'nordot_save_postdata_contentowner', 10, 2);
/*
 * Saves our custom fields when the post is published.  Create new Story on Nordot servers also.
 * Also deletes Story from Nordot. 
 */
function nordot_save_postdata_contentowner($post_id, $post) {
	if (!isset($_POST['nordot_noncename_contentowner']) || !wp_verify_nonce(sanitize_text_field($_POST['nordot_noncename_contentowner']), 'nordot_contentowner_admin_nonce')) {
		return $post_id;
	}
	
	// Check user permission
	if (isset($_POST['post_type']) && 'post' === sanitize_text_field($_POST['post_type'])) {
		if (!current_user_can('edit_post', $post_id)) {
			return $post_id;
		}
	} 

	// Make sure we don't create a Story using "republished" content.
	if (get_post_meta($post_id, 'nordot_curated_post', TRUE))
		return $post_id;
	
	// Update post meta with custom fields -- upload to nordot or not.
	$upload = isset($_POST['nordot_upload_post']) ? sanitize_text_field($_POST['nordot_upload_post']) : '';
	update_post_meta($post_id, 'nordot_upload_post', $upload === 'yes' ? $upload : 'no');	
	
	if ($upload === 'yes') {
		nordot_create_story($post);
	} else {
		// If box is unchecked, we may have to delete the content from Nordot servers if it has been previously uploaded
		nordot_delete_story($post_id);
	}
}

// This action gets fired when a scheduled post actually gets published
add_action('future_to_publish', 'nordot_future_to_publish');

function nordot_future_to_publish($post) {
	$post_id = $post->ID;
	
	$upload = get_post_meta($post_id, 'nordot_upload_post', TRUE);
	
	if ($upload === 'yes') {
		nordot_create_story($post);
	} else {
		// If box is unchecked, we may have to delete the content from Nordot servers if it has been previously uploaded
		nordot_delete_story($post_id);
	}
}

// This action gets fired when a post becomes scheduled
add_action('future_post', 'nordot_publish_to_future_post');

function nordot_publish_to_future_post($post_id) {
	
	if (!isset($_POST['nordot_noncename_contentowner']) || !wp_verify_nonce(sanitize_text_field($_POST['nordot_noncename_contentowner']), 'nordot_contentowner_admin_nonce')) {
		return $post_id;
	}
	
	// Make sure we don't create a Story using "republished" content.
	if (get_post_meta($post_id, 'nordot_curated_post', TRUE))
		return $post_id;
		
	// Update post meta with custom fields -- upload to nordot or not.
	$upload = isset($_POST['nordot_upload_post']) ? sanitize_text_field($_POST['nordot_upload_post']) : '';
	update_post_meta($post_id, 'nordot_upload_post', $upload === 'yes' ? $upload : 'no');
	
}

/**
 * 
 */
function nordot_create_story($post) {

	$nordotUnitId = get_nordot_media_unit(false);
	$nordotKey = get_nordot_unit_api_key(false);

	$postDate = new DateTime($post->post_date, nordot_get_wp_timezone());
	$postDateFormat = $postDate->format(DateTime::ATOM);
	$nordotPostId = get_post_meta($post->ID, 'nordot_post_id', TRUE);

	// Have to do preg_replace below because stupid block system adds a bunch of blocks like <!-- wp:heading -->
	// And Nordot servers do NOT like that.
	$postContent = preg_replace("~<\!--([^>]*)>~", '', $post->post_content);

	$converter = new HtmlConverter(array('header_style'=>'atx'));
	
	$postAfterShortCodes = do_shortcode($postContent);
	
	$postAfterShortCodes = apply_filters('the_content', $postAfterShortCodes);
	
	$markdown = $converter->convert(wpautop($postAfterShortCodes));
	
	// Check if the post has a featured image, and if that image is not in the body of the post (to the best of our knowledge),
	// then upload that image to Nordot and put within nordot content
	$post_thumbnail_id = get_post_thumbnail_id($post->ID);
	if ($post_thumbnail_id !== '') {
		$post_thumbnail_path = get_attached_file($post_thumbnail_id);
		
		$basename = explode(".", basename($post_thumbnail_path))[0];
		
		if (strpos($postAfterShortCodes, $basename) === false) {
			$featuredImageId = nordot_upload_image($post_thumbnail_path);
			
			if ($featuredImageId >= 0) {
				$markdown = '[[image]](' . $featuredImageId . ')' . "\n\n" . $markdown;
			}
		}
	}
	
	$args =  array(
			'title' => $post->post_title,
			'body' =>  $markdown,
			'source_url' => get_permalink($post),
			'published_at' => $postDateFormat,
			'status' => 'public'		
	);
	
	$action = 'contentsholder/posts.create';
	
	if ($nordotPostId !== null && $nordotPostId !== '') {
		$action = 'contentsholder/posts.update';
		$args['post_id'] = $nordotPostId;
	} else {
		$args['unit_id'] = $nordotUnitId;
	}
	
	$r = nordot_send_post($nordotUnitId, $nordotKey, $action, $args);
	
	
	if ($r === null || is_wp_error($r)) {

	} else {
		$returnObject = wp_remote_retrieve_body($r);
		$jsonObj = json_decode($returnObject);
		
		if ($jsonObj->ok) {
			// Update post meta to indicate this story has already been created
			update_post_meta($post->ID, 'nordot_post_id', $jsonObj->post->id);
		} 
	}
}


function nordot_delete_story($post_id) {
	$nordotPostId = get_post_meta($post_id, 'nordot_post_id', TRUE);
	
	// If the post doesn't have the meta data, then just return.  Nothing to do.
	if ($nordotPostId === null || $nordotPostId === '') 
		return;
	
	$nordotUnitId = get_nordot_media_unit(false);
	$nordotKey = get_nordot_unit_api_key(false);
	
	$args =  array(
		'post_id' => sanitize_key($nordotPostId)
	);

	$r = nordot_send_post($nordotUnitId, $nordotKey, 'contentsholder/posts.delete', $args);
	
	if ($r === null || is_wp_error($r)) {

	} else {
		$returnObject = wp_remote_retrieve_body($r);
		$jsonObj = json_decode($returnObject);
		
		if ($jsonObj->ok) {
			// Remove the meta data from the post
			delete_post_meta($post_id, 'nordot_post_id');
		}
	}
}
