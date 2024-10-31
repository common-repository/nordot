<?php

add_action( 'wp_ajax_nordot_search', 'nordot_search' );
/**
 * AJAX call for search
 */
function nordot_search() {
	global $nordot_api_url;
	
	check_ajax_referer( 'nordot_noncename_ajax', 'security');
	
	$offset = isset($_POST['nordotSearchOffset']) ? sanitize_text_field($_POST['nordotSearchOffset']) : 0;
	$query = isset($_POST['nordotSearchQuery']) ? stripslashes(sanitize_text_field($_POST['nordotSearchQuery'])) : '';
	
	$query = rawurlencode(htmlspecialchars_decode($query) . ' language:' . get_nordot_curator_language() );

	$nordotQueryString = "?query=" . $query . "&limit=20&offset=" . $offset ;

	$r = wp_safe_remote_get($nordot_api_url . 'search/contentsholder/posts.list' . $nordotQueryString, array( 'timeout' => 3, 'headers' => array("Authorization" => get_nordot_unit_api_key())));

	if (is_wp_error($r)) {
		wp_send_json_error($r);
	} else {
		wp_send_json_success(wp_remote_retrieve_body($r));
	}
}

add_action( 'wp_ajax_nordot_auto_complete_search', 'nordot_auto_complete_search' );
function nordot_auto_complete_search() {
	global $nordot_api_url;
	
	check_ajax_referer( 'nordot_noncename_ajax', 'security');
	
	$query = isset($_POST['nordotAutoQuery']) ? stripslashes(sanitize_text_field($_POST['nordotAutoQuery'])) : '';
	
	$query = rawurlencode(htmlspecialchars_decode($query));
	
	$nordotQueryString = "?query=" . $query . "&types=ch_units,ch_labels,ch_topics,ch_series,ch_tags&limit=10";
	
	$r = wp_safe_remote_get($nordot_api_url . 'search/objects.list' . $nordotQueryString, array( 'timeout' => 3, 'headers' => array("Authorization" => get_nordot_unit_api_key())));
	
	if (is_wp_error($r)) {
		wp_send_json_error($r);
	} else {
		wp_send_json_success(wp_remote_retrieve_body($r));
	}
}

// *** Save Search Section ***

add_action( 'wp_ajax_nordot_save_search', 'nordot_save_search' );
function nordot_save_search() {
	global $wpdb;
	
	check_ajax_referer( 'nordot_noncename_ajax', 'security');
	
	if (!isset($_POST['nordotSearchQuery']) || empty($_POST['nordotSearchQuery'])) {
		wp_send_json_error();
		return;
	}
	
	$nordotSearchQuery = sanitize_text_field(stripslashes($_POST['nordotSearchQuery']));

	$nordotSaveSearchName = isset($_POST['nordotSaveSearchName']) ? stripslashes(sanitize_text_field($_POST['nordotSaveSearchName'])) : '' ; 
	$nordotSetAutoPublish = isset($_POST['nordotSetAutoPublish']) ? stripslashes(sanitize_text_field($_POST['nordotSetAutoPublish'])) : 'false';  // true or false
	$nordotAutoPublishAmount = isset($_POST['nordotAutoPublishAmount']) ? stripslashes(sanitize_text_field($_POST['nordotAutoPublishAmount'])) : ''; // value
	$nordotAutoPublishFrequency = isset($_POST['nordotAutoPublishFrequency']) ? stripslashes(sanitize_text_field($_POST['nordotAutoPublishFrequency'])) : '' ; // value
	$nordotSetModalWindow = false;//isset($_POST['nordotSetModalWindow']) ? filter_var(sanitize_text_field($_POST['nordotSetModalWindow']), FILTER_VALIDATE_BOOLEAN) : 0;
	$nordotSetFeaturedImage = isset($_POST['nordotSetFeaturedImage']) ? filter_var(sanitize_text_field($_POST['nordotSetFeaturedImage']), FILTER_VALIDATE_BOOLEAN) : 0;
	$nordotSetBodyImage = isset($_POST['nordotSetBodyImage']) ? filter_var(sanitize_text_field($_POST['nordotSetBodyImage']), FILTER_VALIDATE_BOOLEAN) : 0;
	$nordotBodyImageStyle = isset($_POST['nordotBodyImageStyle']) ? sanitize_text_field($_POST['nordotBodyImageStyle']) : '';
	$nordotCategories = isset( $_POST['nordotCategories'] ) ? (array) $_POST['nordotCategories'] : array();
	$nordotCategories = array_map( 'esc_attr', $nordotCategories);
	$nordotCategories = implode(',', $nordotCategories);


	if ($wpdb->insert(
			get_nordot_save_search_table(),
			array('query' => $nordotSearchQuery, 
					'autoPublish' => ($nordotSetAutoPublish === 'false' ? 0 : 1), 
					'autoPublishAmount' => $nordotAutoPublishAmount, 
					'autoPublishFrequency' => $nordotAutoPublishFrequency,
					'autoPublishSetModalWindow' => $nordotSetModalWindow,
					'autoPublishSetFeaturedImage' => $nordotSetFeaturedImage,
					'autoPublishSetBodyImage' => $nordotSetBodyImage,
					'autoPublishSetBodyImageStyle' => $nordotBodyImageStyle,
					'autoPublishCategories' => $nordotCategories,
					'name' => $nordotSaveSearchName
			)
		)
	){
		$id = $wpdb->insert_id;
		wp_send_json_success(array ('id' => $id, 'query' => $nordotSearchQuery));
	} else {
		
		wp_send_json_error();
	}
}

add_action( 'wp_ajax_nordot_edit_save_search', 'nordot_edit_save_search' );
function nordot_edit_save_search() {
	global $wpdb;
	
	check_ajax_referer( 'nordot_noncename_ajax', 'security');
	
	if (!isset($_POST['nordotSaveSearchId']) || empty($_POST['nordotSaveSearchId'])) {
		wp_send_json_error();
		return;
	}
	
	$nordotSaveSearchId = sanitize_key(stripslashes($_POST['nordotSaveSearchId']));
	
	$nordotSaveSearchName = isset($_POST['nordotSaveSearchName']) ? stripslashes(sanitize_text_field($_POST['nordotSaveSearchName'])) : '' ;
	$nordotSetAutoPublish = isset($_POST['nordotSetAutoPublish']) ? stripslashes(sanitize_text_field($_POST['nordotSetAutoPublish'])) : 'false';  // true or false
	$nordotAutoPublishAmount = isset($_POST['nordotAutoPublishAmount']) ? stripslashes(sanitize_text_field($_POST['nordotAutoPublishAmount'])) : ''; // value
	$nordotAutoPublishFrequency = isset($_POST['nordotAutoPublishFrequency']) ? stripslashes(sanitize_text_field($_POST['nordotAutoPublishFrequency'])) : '' ; // value
	$nordotSetModalWindow = false;//isset($_POST['nordotSetModalWindow']) ? filter_var(sanitize_text_field($_POST['nordotSetModalWindow']), FILTER_VALIDATE_BOOLEAN) : 0;
	$nordotSetFeaturedImage = isset($_POST['nordotSetFeaturedImage']) ? filter_var(sanitize_text_field($_POST['nordotSetFeaturedImage']), FILTER_VALIDATE_BOOLEAN) : 0;
	$nordotSetBodyImage = isset($_POST['nordotSetBodyImage']) ? filter_var(sanitize_text_field($_POST['nordotSetBodyImage']), FILTER_VALIDATE_BOOLEAN) : 0;
	$nordotBodyImageStyle = isset($_POST['nordotBodyImageStyle']) ? sanitize_text_field($_POST['nordotBodyImageStyle']) : '';
	$nordotCategories = isset( $_POST['nordotCategories'] ) ? (array) $_POST['nordotCategories'] : array();
	$nordotCategories = array_map( 'esc_attr', $nordotCategories);
	$nordotCategories = implode(',', $nordotCategories);
	
	$updateResult = $wpdb->update(get_nordot_save_search_table(),
					array('autoPublish' => ($nordotSetAutoPublish === 'false' ? 0 : 1), 'autoPublishAmount' => $nordotAutoPublishAmount, 'autoPublishFrequency' => $nordotAutoPublishFrequency,
							'autoPublishSetModalWindow' => $nordotSetModalWindow,
							'autoPublishSetFeaturedImage' => $nordotSetFeaturedImage,
							'autoPublishSetBodyImage' => $nordotSetBodyImage,
							'autoPublishSetBodyImageStyle' => $nordotBodyImageStyle,
							'autoPublishCategories' => $nordotCategories,
							'name' => $nordotSaveSearchName
					),
					array('id' => ($nordotSaveSearchId)));
	
	if (false === $updateResult) {		
		wp_send_json_error();
	} else {
		wp_send_json_success();
	}

}

add_action( 'wp_ajax_nordot_get_save_searches', 'nordot_get_save_searches' );
function nordot_get_save_searches() {
	global $wpdb;
	
	check_ajax_referer( 'nordot_noncename_ajax', 'security');
	$savedSearches = $wpdb->get_results($wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}nordot_save_searches`", []));
	
	wp_send_json_success($savedSearches);	
}

add_action( 'wp_ajax_nordot_remove_save_search', 'nordot_remove_save_search' );
function nordot_remove_save_search() {
	global $wpdb;
	
	check_ajax_referer( 'nordot_noncename_ajax', 'security');
	
	if (!isset($_POST['nordotSavedSearchId'])) return;
	$nordotSavedSearchId = sanitize_key($_POST['nordotSavedSearchId']);
	$wpdb->delete( get_nordot_save_search_table(), array( 'id' => $nordotSavedSearchId ) );
	
	wp_send_json_success();
}


// *** Read Later Section ***

add_action( 'wp_ajax_nordot_get_read_laters', 'nordot_get_read_laters' );
function nordot_get_read_laters() {
	global $wpdb;
	
	check_ajax_referer( 'nordot_noncename_ajax', 'security');
	
	$readLaters = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}nordot_read_later`", []));
	
	wp_send_json_success($readLaters);
}

add_action( 'wp_ajax_nordot_read_later', 'nordot_read_later' );
function nordot_read_later() {
	global $wpdb;
	
	check_ajax_referer( 'nordot_noncename_ajax', 'security');

	if (empty($_POST['nordotPostId']) || empty($_POST['nordotUrl'])) {
		wp_send_json_error();
		return;
	}
	
	$args = array (
			'postid' => sanitize_text_field($_POST['nordotPostId']),
			'url' => esc_url_raw($_POST['nordotUrl']),
			'img_url' => isset($_POST['nordotImgUrl']) ? esc_url_raw($_POST['nordotImgUrl']) : '',
			'title' => isset($_POST['nordotTitle']) ? stripslashes(sanitize_text_field($_POST['nordotTitle'])) : '',
			'description' => isset($_POST['nordotDescription']) ? stripslashes(sanitize_text_field($_POST['nordotDescription'])) : '',
			'publisher' => isset($_POST['nordotPublisher']) ? stripslashes(sanitize_text_field($_POST['nordotPublisher'])) : '',
			'date_published' => isset($_POST['nordotDatePublished']) ? sanitize_text_field($_POST['nordotDatePublished']) : ''
	);
	
	
	if ($wpdb->insert(get_nordot_read_later_table(), $args)) {
		$id = $wpdb->insert_id;
		wp_send_json_success(array ('id' => $id));
	} else {
		wp_send_json_error();
	}
}

add_action( 'wp_ajax_nordot_remove_read_later', 'nordot_remove_read_later' );
function nordot_remove_read_later() {
	global $wpdb;
	
	check_ajax_referer( 'nordot_noncename_ajax', 'security');
	
	if (!isset($_POST['nordotPostId']) || empty($_POST['nordotPostId']))
		wp_send_json_error();
		
	$nordotReadLaterId = sanitize_key($_POST['nordotPostId']);
	$wpdb->delete( get_nordot_read_later_table(), array( 'postid' => $nordotReadLaterId ) );
	
	wp_send_json_success();
}



/**
 * Renders main Find Articles page
 */
function nordot_plugin_search_page() {
	$date_time = new DateTime('now', nordot_get_wp_timezone());
	$date_time_offset = $date_time->format('P');
	?>
	<div class="wrap">
	
		<div class="container-fluid">
			<div class="row mb-4 mt-4">
				<div class="col-md-3 text-center">
				<a href="#" class="nordot-help-link d-inline-block mt-2 pt-1 text-info" data-toggle="modal" data-target="#nordotHelpModal"><small><?php esc_html_e('Search Tips', 'nordot-text-domain');?> <i class="fa fa-question-circle"></i></small></a>
				<a href="#" class="nordot-publishers-link d-inline-block mt-2 pt-1 ml-3 text-info" data-toggle="modal" data-target="#nordotPublishersModal"><small><?php esc_html_e('Featured Media Units', 'nordot-text-domain');?> <i class="fa fa-th-list"></i></small></a>
				</div>			
				<div class="col">
				<form id="nordot-search-form">
			      <label class="sr-only" for="inlineFormInputGroup"><?php esc_html_e("Keyword", 'nordot-text-domain');?></label>
			      <div class=" input-group input-group-lg mb-2">
			        <a href="#" class="input-group-prepend text-decoration-none">
			          <div class="input-group-text" id="nordot-search-icon"><i class="fa fa-search"></i></div>
			        </a>
			        <input type="text" class="form-control" id="inlineFormInputGroup" placeholder="<?php esc_attr_e("Keyword", 'nordot-text-domain');?>" name="query" aria-label="Search" aria-describedby="nordot-search-icon"/>    			
			      </div>
			      <div style="position: relative;">
				    <div class="nordot-autocomplete-spinner" class="d-block pb-5 pt-5 pl-5">
				      <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
				    </div>	    			      
				  </div>
				</form>
				<div class="d-flex w-100 justify-content-end align-items-center">
			    		<div id="nordot-save-search-error" class="alert alert-danger mb-0 p-2" role="alert" style="display: none;">
			    		<?php esc_html_e("Error saving search.  Please refresh and try again.", 'nordot-text-domain');?>
			    		</div>				
					<button id="nordot-save-search" type="button" class="btn btn-info btn-sm ml-3" disabled data-toggle="modal" data-target="#nordotSaveSearchModal"><?php esc_html_e("Save Search", 'nordot-text-domain');?> <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span></button>
					<button id="nordot-edit-search" type="button" class="btn btn-info btn-sm btn-warning ml-3" style="display: none;" data-toggle="modal" data-target="#nordotSaveSearchModal"><?php esc_html_e("Edit Search", 'nordot-text-domain');?> <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span></button>
				</div>			
				</div>			
			</div>	  		

			<div class="row">
				<div class="col-md-3 border-right">
					<ul id="nordot-search-leftbar" class="list-group">
					  <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center mb-1" data-query="ReadLater">
					    <div><i class="fa fa-bookmark-o"></i><span><?php esc_html_e("Read Later", 'nordot-text-domain');?></span></div>
					    <span id="nordot_readmore" class="badge badge-info badge-pill"></span>
					  </li>					
					  <li class="list-group-item list-group-item-action list-group-item-info mb-1" data-query="">
					 	 <div><i class="fa fa-newspaper-o"></i><span><?php esc_html_e("All Stories", 'nordot-text-domain');?></span></div>
					  </li>
					  <div id="nordot_saved_searches">
						  <div id="nordot_saved_searches_spinner" class="d-flex justify-content-center" style="display: none !important;">
							  <div class="spinner-border" role="status">
		  						<span class="sr-only">Loading...</span>
							  </div>
						  </div>	
  						  <div class="alert alert-danger" role="alert" style="display: none !important;">
						    <?php esc_html_e("Error retrieving saved searches", 'nordot-text-domain');?>
						  </div>						  			  
					  </div>
					</ul>
				</div>	
				<div id="nordot-search-results" class="col-md">
					<div class="nordot-spinner text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>				
					<div class="list-group">		
					</div>										
					<button id="nordot-search-more-btn" type="button" class="btn btn-outline-info btn-block w-100" style="display: none;">
					<?php esc_html_e("More", 'nordot-text-domain');?> <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span>
					</button>
				</div>			  								
			</div>			
		</div>
		
		<!--  Curate Modal -->
		<div class="modal fade" id="nordotCurateModal" tabindex="-1" role="dialog" aria-labelledby="nordotCurateModalLabel" aria-hidden="true">
		  <div class="modal-dialog modal-lg" role="document">
		    <div class="modal-content">       
                        
		      <div class="modal-header">
		        <h5 class="modal-title" id="nordotCurateModalLabel"></h5>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
		          <span aria-hidden="true">&times;</span>
		        </button>
		      </div>
          
                <div id="curate-modal-spinner" class="text-center">
                  <div class="spinner-border spinner-border-lg" role="status">
                    <span class="sr-only">Loading...</span>
                  </div>
                </div>
                          
		      <div class="modal-body">
				<a href="#" class="text-body flex-grow-1" target="_blank">
					<div class="d-flex w-100 justify-content-start">
						<div class="nordot-searchItem-imgWrapper mr-3">
			   				<div class="nordot-thumbnail">
			 					<img class="nordot-thumbnail-img" src="" />
			 				</div> 							
						</div>
						<div class="pt-2">
	  						<h6 class="nordot-description"></h6> 
						</div>
					</div>
				</a>		      
		        <form id="nordot-publish" class="mt-4">
                  <input type="hidden" id="nordot_curate_body_id"/>
				  <input type="hidden" id="nordot_curate_source_url"/>
		          <div class="form-group">
		            <label for="nordot-annotation" class="col-form-label"><?php esc_html_e("Comment", 'nordot-text-domain');?>:</label>
		            <textarea class="form-control" id="nordot-annotation" name="nordot-annotation"></textarea>
		            <!--  <small><?php esc_html_e("Annotation is commentary that will display in the curated version of the article.", 'nordot-text-domain');?></small> -->     
		          </div>
	          
				  <div class="form-group form-check">
				    <input type="checkbox" class="form-check-input" id="nordot-set-featured-img" checked>
				    <label class="form-check-label" for="nordot-set-featured-img"><?php esc_html_e("Set image as Featured Image", 'nordot-text-domain');?></label>
				  </div>
				  <div class="form-group form-check form-inline">
				    <input type="checkbox" class="form-check-input" id="nordot-set-body-img" >
				    <label class="form-check-label" for="nordot-set-body-img"><?php esc_html_e("Insert image into post", 'nordot-text-domain');?></label>
				    
				    <select id="nordot-set-body-img-align" class="form-control ml-3" disabled>
				    <option value="alignleft" selected><?php esc_html_e("Left Align", 'nordot-text-domain');?></option>
				    <option value="aligncenter" ><?php esc_html_e("Center Align", 'nordot-text-domain');?></option>
				    <option value="alignright" ><?php esc_html_e("Right Align", 'nordot-text-domain');?></option>
				    </select>
				  </div>		
				  			
		          <div class="form-group w-50">
		            <label for="nordot-category" class="col-form-label"><?php esc_html_e("Category", 'nordot-text-domain');?>:</label>
		            <select id="nordot-category" multiple class="form-control" >
		            <?php 
		            $categories = get_categories(array(
		            		'orderby' => 'name',
		            		'order'   => 'ASC',
		            		'hide_empty' => 0
		            ));
		            foreach ($categories as $category) {
		            	echo '<option value="' . esc_attr($category->cat_ID) . '">' .esc_html( $category->name ) . '</option>';
		            }
		            ?>     
		            </select>
		          </div>
		          				  			  		          
				</form>
				<div class="alert alert-success font-weight-bold" role="alert" style="display: block;">
  				  <?php esc_html_e("Success", 'nordot-text-domain');?>
  				  <a id="nordot-alert-view-post" target="_blank" class="ml-3 mr-3" href=""><?php esc_html_e("View Post", 'nordot-text-domain');?></a>
                  <a id="nordot-alert-tweet-post" target="_blank" class="btn btn-primary" href="" role="button" style="background-color: #00aaec; border-color: #00aaec; fill: #fff;">
                    <svg aria-hidden="true" focusable="false" data-prefix="fab" data-icon="twitter" style="width: 16px; height: 16px;" class="svg-inline--fa fa-twitter fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M459.37 151.716c.325 4.548.325 9.097.325 13.645 0 138.72-105.583 298.558-298.558 298.558-59.452 0-114.68-17.219-161.137-47.106 8.447.974 16.568 1.299 25.34 1.299 49.055 0 94.213-16.568 130.274-44.832-46.132-.975-84.792-31.188-98.112-72.772 6.498.974 12.995 1.624 19.818 1.624 9.421 0 18.843-1.3 27.614-3.573-48.081-9.747-84.143-51.98-84.143-102.985v-1.299c13.969 7.797 30.214 12.67 47.431 13.319-28.264-18.843-46.781-51.005-46.781-87.391 0-19.492 5.197-37.36 14.294-52.954 51.655 63.675 129.3 105.258 216.365 109.807-1.624-7.797-2.599-15.918-2.599-24.04 0-57.828 46.782-104.934 104.934-104.934 30.213 0 57.502 12.67 76.67 33.137 23.715-4.548 46.456-13.32 66.599-25.34-7.798 24.366-24.366 44.833-46.132 57.827 21.117-2.273 41.584-8.122 60.426-16.243-14.292 20.791-32.161 39.308-52.628 54.253z"></path></svg>                    
                    Click to Tweet
                  </a>
				</div>		      
		      	<div class="alert alert-danger" role="alert" style="display: none;">
  				<?php esc_html_e("An error has occurred.  Please refresh and try again.  You may have already republished this story.", 'nordot-text-domain');?>
				</div>				

		      </div>
		      <div class="modal-footer">               
				<?php 
				$disablePublishPost = current_user_can('publish_posts') ? '' : ' disabled ';
				?>
		        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php esc_html_e("Close", 'nordot-text-domain');?></button>
		        <button id="nordot-draft-submit" type="button" class="btn btn-warning"><?php esc_html_e("Save as Draft", 'nordot-text-domain');?> <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span></button>
		        <button <?php echo esc_attr($disablePublishPost); ?> id="nordot-publish-submit" type="button" class="btn btn-info" ><?php esc_html_e("Publish", 'nordot-text-domain');?>  <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span></button>
		      </div>
		    </div>
		  </div>
		</div>		
		
		<!--  Help Modal -->
		<div class="modal fade" id="nordotHelpModal" tabindex="-1" role="dialog" aria-labelledby="nordotHelpModalLabel" aria-hidden="true">
		  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h5 class="modal-title" id="nordotHelpModalLabel"><?php esc_html_e('Search Tips', 'nordot-text-domain');?></h5>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
		          <span aria-hidden="true">&times;</span>
		        </button>
		      </div>
		      <div class="modal-body">
				<table class="table">
				  <thead>
				    <tr>
				      <th scope="col"><?php esc_html_e('Syntax', 'nordot-text-domain');?></th>
				      <th scope="col"><?php esc_html_e('Description', 'nordot-text-domain');?></th>
				    </tr>
				  </thead>
				  <tbody>
				    <tr>
					<td><?php esc_html_e('climate change', 'nordot-text-domain');?></td>
					<td><?php esc_html_e('Filter stories by keywords. Stories containing "climate" AND "change" are retrieved. Use blank space to separate two or more keywords.', 'nordot-text-domain');?></td>
				    </tr>

				    <tr>
					<td>"climate change"</td>
					<td><?php esc_html_e('Filter stories by exact phrase using quotes. Stories containing the phrase "climate change" are retrieved.', 'nordot-text-domain');?></td>
				    </tr>				    
				    	
				    <tr>
					<td><?php esc_html_e('climate change OR global warming', 'nordot-text-domain');?></td>
					<td><?php esc_html_e('The OR syntax will retrieve stories that satisfy either condition. Stories containing the words "climate" AND "change" OR "global" AND "warming" are retrieved.', 'nordot-text-domain');?></td>
				    </tr>				    				    
	
					<tr>
					<td><?php esc_html_e('carbon –tax', 'nordot-text-domain');?></td>
					<td><?php esc_html_e('The NOT (-) syntax will include stories containing the word(s) before the minus and exclude stories containing the word(s) after the minus. Stories that include the word "carbon" but exclude the word "tax" are retrieved.', 'nordot-text-domain');?></td>
				    </tr>	
				    
				    <tr>
					<td><?php esc_html_e('(climate change) OR (carbon –tax)', 'nordot-text-domain');?></td>
					<td><?php esc_html_e('Group search filters using brackets. Stories containing the words "climate" and "change" OR "carbon" but exclude the word "tax" are retrieved.', 'nordot-text-domain');?></td>
				    </tr>
				    
				    <tr>
					<td>title:<?php esc_html_e('climate', 'nordot-text-domain');?></td>
					<td><?php esc_html_e('Filter stories by value specified in title.', 'nordot-text-domain');?></td>
				    </tr>				     
				   				    
					<tr>
					<td>tag:<?php esc_html_e('climate', 'nordot-text-domain');?></td>
					<td><?php esc_html_e('Filter Stories by value specified in tag.', 'nordot-text-domain');?></td>
				    </tr>
				    
				    <tr>
					<td><?php echo esc_html("published_at:>=2019-11-01T00:00:00$date_time_offset");?></td>
					<td><?php esc_html_e('Filter stories by published date and time. You can use the following operators: >, < , >= and <=.', 'nordot-text-domain');?></td>
				    </tr>
				    
				    <tr>
					<td><?php echo esc_html("created_at:<=2019-01-01T00:00:00$date_time_offset"); ?></td>
					<td><?php esc_html_e('Filter stories by created date and time. You can use the following operators: >, < , >=, and <=.', 'nordot-text-domain');?></td>
				    </tr>
				    
					<tr>
					<td>has:image</td>
					<td><?php esc_html_e('Filter Stories by image.', 'nordot-text-domain');?></td>
				    </tr>
				    
				    <tr>
					<td>has:author</td>
					<td><?php esc_html_e('Filter Stories by author.', 'nordot-text-domain');?></td>
				    </tr>
				   			    				    				    		    

				  </tbody>
				</table>			
		      </div>
		      <div class="modal-footer">
		        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php esc_html_e("Close", 'nordot-text-domain');?></button>
		      </div>
		    </div>
		  </div>
		</div>
		
		<!--  Media Units Modal -->
		<div class="modal fade" id="nordotPublishersModal" tabindex="-1" role="dialog" aria-labelledby="nordotPublishersModal" aria-hidden="true">
		  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h5 class="modal-title" id="nordotPublishersModalLabel"><?php esc_html_e('Media Units', 'nordot-text-domain');?></h5>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
		          <span aria-hidden="true">&times;</span>
		        </button>
		      </div>
		      <div class="modal-body">
		      	<div id="media-unit-spinner" class="text-center">
		      		<div class="spinner-border spinner-border-lg" role="status">
				  	<span class="sr-only">Loading...</span>
					</div>
				</div>
				<table id="nordot-media-units-table" class="table table-borderless" style="display: none !important;">
				  <tbody>

				  </tbody>
				</table>			
		      </div>
		      <div class="modal-footer">
		        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php esc_html_e("Close", 'nordot-text-domain');?></button>
		      </div>
		    </div>
		  </div>
		</div>			
		
		<!--  Save Search Modal -->
		<div class="modal fade" id="nordotSaveSearchModal" tabindex="-1" role="dialog" aria-labelledby="nordotSaveSearchModal" aria-hidden="true">
		  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h5 class="modal-title" id="nordotSaveSearchModalLabel"><?php esc_html_e('Options', 'nordot-text-domain');?></h5>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
		          <span aria-hidden="true">&times;</span>
		        </button>
		      </div>
		      <div class="modal-body">
		      	<div id="save-search-spinner" class="text-center d-none">
		      		<div class="spinner-border spinner-border-lg" role="status">
				  	<span class="sr-only">Loading...</span>
					</div>
				</div>     
		      	<div class="alert alert-danger" role="alert" style="display: none;">
  					<?php esc_html_e("An error has occurred.  Please refresh and try again.", 'nordot-text-domain');?>
				</div>
	      
		        <form id="nordot-save-search-form" class="mt-4"> 
		        	  <input type="hidden" id="saveSearchId"/>
		          <div class="form-group">
		            <label for="nordot-save-search-name"><?php esc_html_e("Name of Save Search", 'nordot-text-domain');?></label>         
		            <input type="text" class="form-control col-sm-6" placeholder="<?php esc_attr_e("Enter Name", 'nordot-text-domain');?>" id="nordot-save-search-name" name="nordot-save-search-name" >
		          </div>  
                          
				  <div class="form-group form-check form-inline mt-4">
				    <input type="checkbox" class="form-check-input" id="nordot-set-auto-publish" >
				    <label class="form-check-label" for="nordot-set-auto-publish"><?php esc_html_e("Automatically publish articles from this search.", 'nordot-text-domain');?></label>			    
				  </div>		
				  
				 <div class="form-group form-inline ml-4">				    
				    
					<label for="nordot-set-auto-publish-amount"><?php esc_html_e("Number of articles:", 'nordot-text-domain');?></label>				    
				    <select id="nordot-set-auto-publish-amount" class="form-control ml-3" disabled>
					    <option value="1" selected>1</option>
			            <?php 
			            for ($x = 2; $x <= 20; $x++) {
			            		echo '<option value="' . esc_attr($x) . '">' .esc_html( $x ) . '</option>';
			            }
			            ?> 				    
				    </select>
				    
				    &nbsp;&nbsp;&nbsp; 
				    <label for="nordot-set-auto-publish-frequency"><?php esc_html_e("Frequency:", 'nordot-text-domain');?></label> 
				    <select id="nordot-set-auto-publish-frequency" class="form-control ml-3" disabled>
  						<option value="0">10 minutes</option>
  						<option value="1">30 minutes</option>				    
  						<option value="2">1 hour</option>
  						<option value="6">6 hours</option>				    
  						<option value="12" selected>12 hours</option>
  						<option value="24">24 hours</option>
				    </select>
				</div>
				
          
				  <div class="form-group form-check ml-4">
				    <input type="checkbox" class="form-check-input" id="nordot-set-featured-img-savesearch" checked disabled>
				    <label class="form-check-label" for="nordot-set-featured-img-savesearch"><?php esc_html_e("Set image as Featured Image", 'nordot-text-domain');?></label>
				  </div>
				  <div class="form-group form-check form-inline ml-4">
				    <input type="checkbox" class="form-check-input" id="nordot-set-body-img-savesearch" disabled>
				    <label class="form-check-label" for="nordot-set-body-img-savesearch"><?php esc_html_e("Insert image into post", 'nordot-text-domain');?></label>
				    
				    <select id="nordot-set-body-img-align-savesearch" class="form-control ml-3" disabled>
				    <option value="alignleft" selected><?php esc_html_e("Left Align", 'nordot-text-domain');?></option>
				    <option value="aligncenter" ><?php esc_html_e("Center Align", 'nordot-text-domain');?></option>
				    <option value="alignright" ><?php esc_html_e("Right Align", 'nordot-text-domain');?></option>
				    </select>
				  </div>		
				  			
		          <div class="form-group w-50 ml-4">
		            <label for="nordot-category-savesearch" class="col-form-label"><?php esc_html_e("Category", 'nordot-text-domain');?>:</label>
		            <select id="nordot-category-savesearch" multiple class="form-control" disabled>
		            <?php 
		            $categories = get_categories(array(
		            		'orderby' => 'name',
		            		'order'   => 'ASC',
		            		'hide_empty' => 0
		            ));
		            foreach ($categories as $category) {
		            	echo '<option value="' . esc_attr($category->cat_ID) . '">' .esc_html( $category->name ) . '</option>';
		            }
		            ?>     
		            </select>
		          </div>				
		          				  			  		          
		        </form>		
		      </div>
		      <div class="modal-footer">
		        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php esc_html_e("Close", 'nordot-text-domain');?></button>
		        <button id="nordot-create-save-search" type="button" class="btn btn-info" ><?php esc_html_e("Create", 'nordot-text-domain');?>  <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span></button>
		        <button id="nordot-edit-save-search" type="button" class="btn btn-info" style="display: none;"><?php esc_html_e("Save", 'nordot-text-domain');?>  <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span></button>
		      </div>
		    </div>
		  </div>
		</div>		
		
		
		<!--  Cloned objects in JS -->
	    <li class="nordot-li-to-copy list-group-item list-group-item-action d-flex justify-content-between align-items-center mb-1" style="display: none !important;">
	   		<div><i class="fa fa-search"></i><span></span></div>
			<button type="button" class="close"  aria-label="Close">
		    		<span aria-hidden="true">&times;</span>
		    </button>
	  	</li>	
	  	
		<div class="nordot-to-copy list-group-item list-group-item-action mb-2 p-0 d-flex w-100 justify-content-between" style="display: none !important;">
			<a href="#" class="text-body flex-grow-1 nordot-article-link" target="_blank">
				<div class="d-flex w-100 justify-content-start">
					<div class="nordot-searchItem-imgWrapper mr-3">
		   				<div class="nordot-thumbnail">
		 					<img src="" class="nordot-thumbnail-img"/>
		 				</div> 							
					</div>
					<div class="pt-2">
  						<h6></h6>					  
						<div class="d-flex flex-column">
							<small class="nordot-publisher text-muted"></small>					    
						    <small class="nordot-datepublished text-muted"></small>
						</div>									
					</div>
				</div>
			</a>
			<div class="d-flex flex-column justify-content-between p-2 ml-1 nordot-article-options">
				<a href="#" class="nordot-curate-link text-body" data-toggle="modal" data-target="#nordotCurateModal" title="<?php esc_attr_e('Curate', 'nordot-text-domain');?>"><i class="fa fa-pencil-square-o mb-2"></i></a>
				<a href="#" class="nordot-read-later-link text-body" title="<?php esc_attr_e('Read Later', 'nordot-text-domain');?>"><i class="fa fa-bookmark-o"></i></a>						  	
			</div>
		</div>	
					  
		<div class="list-group-item list-group-item-danger nordot-error-to-copy" style="display: none !important;">
		  <?php esc_html_e("There was an error while performing the search.  Please try again or refresh the page.", 'nordot-text-domain');?>
		  
		</div>	
		  
		<div class="list-group-item list-group-item-light nordot-noresults-to-copy" style="display: none !important;">
		  <?php esc_html_e("The search has no results.", 'nordot-text-domain');?>
		</div>					  
	</div>
	
	<?php 
}

add_action( 'wp_ajax_nordot_list_media_units', 'nordot_list_media_units' );
function nordot_list_media_units() {

	check_ajax_referer( 'nordot_noncename_ajax', 'security');

	$url = 'https://nordot.app/cms/search/chUnits?language=' . get_nordot_curator_language() . '&lang=' . get_nordot_curator_language() . '&label=';
	
	$r = wp_safe_remote_get($url, array( 'timeout' => 3, 
			'user-agent' => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.135 Safari/537.36"
	));
	
	if (is_wp_error($r)) {
		wp_send_json_error($r);
	} else {
		wp_send_json_success(wp_remote_retrieve_body($r));
	}
}


add_action( 'wp_ajax_nordot_curate', 'nordot_curate' );
function nordot_curate() {
	
	check_ajax_referer( 'nordot_noncename_ajax', 'security');
	
	$nordotStatus = isset($_POST['nordotStatus']) ? sanitize_text_field($_POST['nordotStatus']) : 'public';
	if ($nordotStatus === 'public' && !current_user_can('publish_posts')) {
		wp_send_json_error("Article not public or user cannot publish");
		return;
	}	
	
	
	$nordotTitle = isset($_POST['nordotTitle']) ? stripslashes(sanitize_text_field($_POST['nordotTitle'])) : '';
	$nordotSetModalWindow = false;//isset($_POST['nordotSetModalWindow']) ? filter_var(sanitize_text_field($_POST['nordotSetModalWindow']), FILTER_VALIDATE_BOOLEAN) : false;
	$nordotSetFeaturedImage = isset($_POST['nordotSetFeaturedImage']) ? filter_var(sanitize_text_field($_POST['nordotSetFeaturedImage']), FILTER_VALIDATE_BOOLEAN) : true;
	$nordotSetBodyImage = isset($_POST['nordotSetBodyImage']) ? filter_var(sanitize_text_field($_POST['nordotSetBodyImage']), FILTER_VALIDATE_BOOLEAN) : false;
	$nordotBodyImageStyle = isset($_POST['nordotBodyImageStyle']) ? sanitize_text_field($_POST['nordotBodyImageStyle']) : '';
	$nordotCategories = isset( $_POST['nordotCategories'] ) ? (array) $_POST['nordotCategories'] : array();
	$nordotCategories = array_map( 'esc_attr', $nordotCategories);
	$nordotBodyId = isset( $_POST['nordotBodyId'] ) ? sanitize_text_field($_POST['nordotBodyId']) : '';
	$nordotCurateSourceUrl = isset( $_POST['nordotCurateSourceUrl'] ) ? sanitize_text_field($_POST['nordotCurateSourceUrl']) : '';
	$nordotAnalytics = '';
	$nordotPublisherName = '';
	$nordotPublisherLogo = '';
	
	if ($nordotBodyId !== '') {
		// This republisher is able to publish full text of article.
		$r = nordot_do_get_post_info($nordotBodyId);
		
		if (!is_wp_error($r)) {
			$body = wp_remote_retrieve_body($r);
			$jsonObj = json_decode($body);
			
			if ($jsonObj->ok && $jsonObj->post && $jsonObj->post->body) {
				$nordotBodyId = $jsonObj->post->body;
				$nordotAnalytics = $jsonObj->post->analytics;
				$nordotPublisherName = $jsonObj->post->unit->name;
			}
		}
	}
	
	$nordotContentUrl = isset($_POST['nordotContentUrl']) ? sanitize_text_field($_POST['nordotContentUrl']) : '';
	$nordotAnnotation = isset($_POST['nordotAnnotation']) ? stripslashes(sanitize_text_field($_POST['nordotAnnotation'])) : '';

	// Create an empty post so that we get the permalink to send to Nordot's create curation call
	$postId = nordot_prime_post($nordotTitle, '');
	$postPermalink = get_permalink($postId);
	
	$r = nordot_do_curate($nordotContentUrl, $nordotAnnotation, $nordotStatus, $postPermalink);
	 
	if (is_wp_error($r)) {
		wp_delete_post($postId, true);
		nordot_send_email("Error Curate:", $r->get_error_message());
		wp_send_json_error($r);
	} else {
		$returnObject = wp_remote_retrieve_body($r);
		$jsonObj = json_decode($returnObject);

		if ($jsonObj->ok) {
			nordot_publish_wp_post($postId, $nordotTitle, $jsonObj->curation, $nordotStatus === 'public' ? 'publish' : 'draft', $nordotSetFeaturedImage, $nordotSetBodyImage, $nordotBodyImageStyle, $nordotCategories, $nordotSetModalWindow, $nordotBodyId, $nordotAnalytics, $nordotPublisherName, $nordotCurateSourceUrl);
			$jsonObj->nordotPostUrl = $postPermalink;
			
			wp_send_json($jsonObj);
		} else {
			wp_delete_post($postId, true);
			wp_send_json_error($jsonObj);
		}
	}
}

add_action( 'wp_ajax_nordot_get_post_info', 'nordot_get_post_info' );
function nordot_get_post_info() {
	check_ajax_referer( 'nordot_noncename_ajax', 'security');
	
	$nordotContentId = isset($_POST['nordotContentId']) ? sanitize_text_field($_POST['nordotContentId']) : 0;
	
	$r = nordot_do_get_post_info($nordotContentId);
	
	if (is_wp_error($r)) {
		wp_send_json_error($r);
	} else {
		wp_send_json_success(wp_remote_retrieve_body($r));
	}
}

add_action('pre_post_update','nordot_alter_post_contents_pre');
function nordot_alter_post_contents_pre($post_id) {
	$useModalWindow = get_post_meta($post_id, 'nordot_modal_window', true);
	if ($useModalWindow === true) {
		add_filter( 'wp_kses_allowed_html', 'nordot_wpse_kses_allowed_html', 10, 2 );
	}
}

add_action('publish_post', 'nordot_save_postdata_curator', 10, 2);

function nordot_save_postdata_curator($post_id, $post) {
	
	if (!isset($_POST['nordot_noncename_curator'])) {
		return $post_id;
	}
	
	// Check admin nonce
	if (!wp_verify_nonce(sanitize_text_field($_POST['nordot_noncename_curator']), 'nordot_curator_admin_nonce')) {
		return $post_id;
	}
	
	// Check user permission
	if (isset($_POST['post_type']) && 'post' === sanitize_text_field($_POST['post_type'])) {
		if (!current_user_can('edit_post', $post_id))
			return $post_id;
	}
	
	// Make sure we don't try and update a Curation that isn't actually a Nordot curated story.
	if (!get_post_meta($post_id, 'nordot_curated_post', TRUE))
		return $post_id;
		
	// Get the new annotation value
	$annotationNew = isset($_POST['nordot_curated_post_annotation']) ? stripslashes(sanitize_text_field($_POST['nordot_curated_post_annotation'])) : '';
	
	// Update the post meta
	update_post_meta($post_id, 'nordot_curated_post_annotation', $annotationNew);
	
	// Update the Curation
	nordot_curate_update(get_post_meta($post_id, 'nordot_curated_post_id', TRUE), $annotationNew, $post->post_status === 'publish' ? 'public' : 'draft');
	
	$useModalWindow = get_post_meta($post_id, 'nordot_modal_window', true);
	if ($useModalWindow === true) {
		remove_filter( 'wp_kses_allowed_html', 'nordot_wpse_kses_allowed_html', 10 );
	}
}

function nordot_curate_update($curationId, $nordotAnnotation, $nordotStatus) {

	$args = array( 'curation_id' => $curationId, 'annotation' => $nordotAnnotation, 'status' => $nordotStatus);
	$r = nordot_send_post(get_nordot_media_unit(), get_nordot_unit_api_key(), 'curator/curations.update', $args);
	
	if (is_wp_error($r)) {
		nordot_send_email("Error Curate Update:", $r->get_error_message());
	} else {
		$returnObject = wp_remote_retrieve_body($r);
		$jsonObj = json_decode($returnObject);
	}
}


add_action('before_delete_post', 'nordot_before_delete_post_curator');
add_action('wp_trash_post', 'nordot_before_delete_post_curator');

function nordot_before_delete_post_curator($post_id) {
	
	if (!current_user_can('edit_post', $post_id))
		return $post_id;
	
	// Make sure we don't try and update a Curation that isn't actually a Nordot curated story.
	if (!get_post_meta($post_id, 'nordot_curated_post', TRUE))
		return $post_id;
		
	
	// Delete the Curation
	nordot_curate_delete(get_post_meta($post_id, 'nordot_curated_post_id', TRUE));
		
}
function nordot_curate_delete($curationId) {
	
	$args = array( 'curation_id' => $curationId);
	$r = nordot_send_post(get_nordot_media_unit(), get_nordot_unit_api_key(), 'curator/curations.delete', $args);
	
	if (is_wp_error($r)) {
		nordot_send_email("Error Curate Delete:", $r->get_error_message());
	} else {
		$returnObject = wp_remote_retrieve_body($r);
		$jsonObj = json_decode($returnObject);
	}
}

/**
 * Downloads an image from the specified URL and attaches it to a post as a post thumbnail.
 *
 * @param string $file    The URL of the image to download.
 * @param int    $post_id The post ID the post thumbnail is to be associated with.
 * @param string $desc    Optional. Description of the image.
 * @return string|WP_Error Attachment ID, WP_Error object otherwise.
 */
function nordot_generate_image( $file, $post_id, $desc = ''){
	require_once(ABSPATH . 'wp-admin/includes/image.php');
	require_once(ABSPATH . 'wp-admin/includes/file.php');
	require_once(ABSPATH . 'wp-admin/includes/media.php');
	
	// parsed path
	$path = wp_parse_url($file, PHP_URL_PATH);
	
	// extracted basename
	$basename = basename($path);
	
	$file_array = array();
	$file_array['name'] = $post_id . '-' . $basename;
	
	// Download file to temp location.
	$file_array['tmp_name'] = download_url( $file );
	
	// If error storing temporarily, return the error.
	if ( is_wp_error( $file_array['tmp_name'] ) ) {
		return false;
	}
	
	// Do the validation and storage stuff.
	$id = media_handle_sideload( $file_array, $post_id);
	
	// If error storing permanently, unlink.
	if ( is_wp_error( $id ) ) {
		return false;
	}
	@unlink( $file_array['tmp_name'] );

	return $id;
}

