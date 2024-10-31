var readLaterArray = [];


jQuery(document).ready(function($) {

	nordotGetSavedSearches();

	$(".nordot-spinner").show();
	// This calls nordotDoSearch() so that we can line up the read later with the articles displayed
	nordotGetReadLaters();

	/**
	 * Saved Searches
	 */

	function nordotGetSavedSearches() {
		$("#nordot_saved_searches li").remove();
		$("#nordot_saved_searches_spinner").show();

		var data = {
			'action' : 'nordot_get_save_searches',
			'security' : ajax_object.ajax_nonce
		};

		$.post(ajax_object.ajax_url, data, function(response) {
			$("#nordot_saved_searches_spinner").attr('style', 'display: none !important;');

			if (!response["success"]) {
				// Do something
				$("#nordot_saved_searches div.alert-danger").show();
				return;
			}

			$.each(response["data"], function(index, value) {
				nordotAddSavedSearch(value);
			})
		});
	}

	function nordotAddSavedSearch(value) {
		var clone = $("li.nordot-li-to-copy").clone();
		clone.removeClass("nordot-li-to-copy");

		var name = value.name;
		
		clone.data("id", value.id);
		clone.data("query", value.query);
		clone.data("autoPublish", value.autoPublish);
		clone.data("autoPublishAmount", value.autoPublishAmount);
		clone.data("autoPublishFrequency", value.autoPublishFrequency);
		clone.data("autoPublishSetFeaturedImage", value.autoPublishSetFeaturedImage);
		clone.data("autoPublishSetModalWindow", false);
		clone.data("autoPublishSetBodyImage", value.autoPublishSetBodyImage);
		clone.data("autoPublishSetBodyImageStyle", value.autoPublishSetBodyImageStyle);
		clone.data("autoPublishCategories", value.autoPublishCategories);
		clone.data("name", name);

		$("button", clone).data("id", value.id);

		$("div span", clone).text((name == null || name == '') ? nordotDecodeHtml(value.query) : nordotDecodeHtml(name));

		$("#nordot_saved_searches").append(clone);
		clone.show();
	}

	function nordotRemoveSavedSearch(nordotRemoveSearchButton) {
		var nordotSavedSearchId = nordotRemoveSearchButton.data("id");
		nordotRemoveSearchButton.parent().fadeOut(400, function() {
			$(this).remove();
		});

		var data = {
			'action' : 'nordot_remove_save_search',
			'nordotSavedSearchId' : nordotSavedSearchId,
			'security' : ajax_object.ajax_nonce
		};

		$.post(ajax_object.ajax_url, data, function(response) {

			if (!response["success"]) {
				$("#nordot_saved_searches div.alert-danger").show();
				return;
			}
		});
	}

	$("#nordotSaveSearchModal").on('show.bs.modal', function(event) {
		var modal = $(this);
	})	;

	$("#nordot-save-search").click(function(event) {
		nordotSaveSearchModalReset();
	});

	$("#nordot-edit-search").click(function(event) {
		$("#nordot-create-save-search").hide();
		$("#nordot-edit-save-search").show();

		$("#saveSearchId").val($(this).data("saveSearchId"));

		if ($(this).data("autoPublish") == 1) {
			$("#nordot-set-auto-publish").prop("checked", "checked").change();
		} else {
			$("#nordot-set-auto-publish").prop("checked", false).change();
		}

		$("#nordot-set-auto-publish-amount").val($(this).data("autoPublishAmount")).change();
		$("#nordot-set-auto-publish-frequency").val($(this).data("autoPublishFrequency")).change();


		if ($(this).data("autoPublishSetFeaturedImage") == 1) {
			$("#nordot-set-featured-img-savesearch").prop("checked", "checked").change();
		} else {
			$("#nordot-set-featured-img-savesearch").prop("checked", false).change();
		}


		if ($(this).data("autoPublishSetBodyImage") == 1) {
			$("#nordot-set-body-img-savesearch").prop("checked", "checked").change();
		} else {
			$("#nordot-set-body-img-savesearch").prop("checked", false).change();
		}

		$("#nordot-set-featured-img-savesearch").val($(this).data("autoPublishSetFeaturedImage")).change();
		$("#nordot-set-body-img-savesearch").val($(this).data("autoPublishSetBodyImage")).change();
		$("#nordot-set-body-img-align-savesearch").val($(this).data("autoPublishSetBodyImageStyle")).change();

		if ($(this).data("autoPublishCategories")) {
			$("#nordot-category-savesearch").val($(this).data("autoPublishCategories").split(",")).change();
		} else {
			$("#nordot-category-savesearch").val('');
		}
		
		$("#nordot-save-search-name").val($(this).data("name")).change();
	});

	$("#nordot-edit-save-search").click(function(event) {
		var saveSearchId = $("#saveSearchId").val();

		if (saveSearchId != '') {
			$("#nordot-edit-save-search").prop("disabled", "disabled");
			$("#nordot-edit-save-search span").show();

			var setAutoPublish = $("#nordot-set-auto-publish").prop("checked");
			var autoPublishAmount = $("#nordot-set-auto-publish-amount").val();
			var autoPublishFrequency = $("#nordot-set-auto-publish-frequency").val();
			var setFeaturedImage = $("#nordot-set-featured-img-savesearch").prop("checked");
			var setModalWindow = false;//$("#nordot-set-modal-window-savesearch").prop("checked");
			var setBodyImage = $("#nordot-set-body-img-savesearch").prop("checked");
			var bodyImageStyle = $("#nordot-set-body-img-align-savesearch").val();
			var categories = $("#nordot-category-savesearch").val();
			var name = $("#nordot-save-search-name").val();

			var data = {
				'action' : 'nordot_edit_save_search',
				'nordotSaveSearchId' : saveSearchId,
				'nordotSaveSearchName' : name,
				'nordotSetAutoPublish' : setAutoPublish,
				'nordotAutoPublishAmount' : autoPublishAmount,
				'nordotAutoPublishFrequency' : autoPublishFrequency,
				'nordotSetFeaturedImage' : setFeaturedImage,
				'nordotSetModalWindow' : setModalWindow,
				'nordotSetBodyImage' : setBodyImage,
				'nordotBodyImageStyle' : bodyImageStyle,
				'nordotCategories' : categories,
				'security' : ajax_object.ajax_nonce
			};

			$.post(ajax_object.ajax_url, data, function(response) {
				$("#nordot-edit-save-search").prop("disabled", false);
				$("#nordot-edit-save-search span").hide();
				$('#nordotSaveSearchModal').modal('hide');

				if (!response["success"]) {
					// Do something
					$("#nordot-save-search-error").show();
					return;
				}
				$("#nordot-edit-search").hide();
				nordotGetSavedSearches();
			});
		}
	});

	$("#nordot-create-save-search").click(function(event) {

		var query = $("form#nordot-search-form input").val();

		if (query != '') {
			$("#nordot-create-save-search").prop("disabled", "disabled");
			$("#nordot-create-save-search span").show();

			var setAutoPublish = $("#nordot-set-auto-publish").prop("checked");
			var autoPublishAmount = $("#nordot-set-auto-publish-amount").val();
			var autoPublishFrequency = $("#nordot-set-auto-publish-frequency").val();
			var setFeaturedImage = $("#nordot-set-featured-img-savesearch").prop("checked");
			var setModalWindow = false;//$("#nordot-set-modal-window-savesearch").prop("checked");
			var setBodyImage = $("#nordot-set-body-img-savesearch").prop("checked");
			var bodyImageStyle = $("#nordot-set-body-img-align-savesearch").val();
			var categories = $("#nordot-category-savesearch").val();
			var name = $("#nordot-save-search-name").val();

			var data = {
				'action' : 'nordot_save_search',
				'nordotSaveSearchName' : name,
				'nordotSearchQuery' : query,
				'nordotSetAutoPublish' : setAutoPublish,
				'nordotAutoPublishAmount' : autoPublishAmount,
				'nordotAutoPublishFrequency' : autoPublishFrequency,
				'nordotSetFeaturedImage' : setFeaturedImage,
				'nordotSetModalWindow' : setModalWindow,
				'nordotSetBodyImage' : setBodyImage,
				'nordotBodyImageStyle' : bodyImageStyle,
				'nordotCategories' : categories,
				'security' : ajax_object.ajax_nonce
			};

			$.post(ajax_object.ajax_url, data, function(response) {
				$("#nordot-create-save-search").prop("disabled", false);
				$("#nordot-create-save-search span").hide();
				$('#nordotSaveSearchModal').modal('hide');

				if (!response["success"]) {
					// Do something
					$("#nordot-save-search-error").show();
					return;
				}

				nordotGetSavedSearches();
			});
		}
	});

	$("#nordot-set-auto-publish").change(function(event) {
		if (this.checked) {
			$("#nordot-set-auto-publish-amount").prop("disabled", false);
			$("#nordot-set-auto-publish-frequency").prop("disabled", false);
			$("#nordot-set-featured-img-savesearch").prop("disabled", false);
			$("#nordot-set-body-img-savesearch").prop("disabled", false);
			$("#nordot-set-body-img-align-savesearch").prop("disabled", false);
			$("#nordot-category-savesearch").prop("disabled", false);
		} else {
			$("#nordot-set-auto-publish-amount").prop("disabled", "disabled");
			$("#nordot-set-auto-publish-frequency").prop("disabled", "disabled");
			$("#nordot-set-featured-img-savesearch").prop("disabled", "disabled");
			$("#nordot-set-body-img-savesearch").prop("disabled", "disabled");
			$("#nordot-set-body-img-align-savesearch").prop("disabled", "disabled");
			$("#nordot-category-savesearch").prop("disabled", "disabled");
		}
	});

	function nordotSaveSearchModalReset() {
		$("#nordot-create-save-search").show();
		$("#nordot-edit-save-search").hide();

		$("#nordot-set-auto-publish").prop("checked", false).change();


		$("#nordot-set-auto-publish-amount").val("1").change();
		$("#nordot-set-auto-publish-frequency").val("12").change();

		$("#nordot-set-featured-img-savesearch").prop("checked", 'checked');
		$("#nordot-set-body-img-savesearch").prop("checked", false);
		$("#nordot-set-body-img-align-savesearch").val('alignleft');
		$("#nordot-set-body-img-align-savesearch").prop("disabled", "disabled");
		$("#nordot-category-savesearch").val('');
		$("#nordot-save-search-name").val('');

		$("#saveSearchId").val('');
	}

	$(document).on("click", "#nordot_saved_searches li button", function(event) {
		event.stopPropagation();
		nordotRemoveSavedSearch($(this));
	});


	/**
	 * Read Later 
	 */

	function nordotGetReadLaters() {
		var data = {
			'action' : 'nordot_get_read_laters',
			'security' : ajax_object.ajax_nonce
		};

		$.post(ajax_object.ajax_url, data, function(response) {
			$("#nordot-save-search").show();
			$("#nordot-edit-search").hide();
			nordotDoSearch();

			if (!response["success"]) {
				// Do something
				return;
			}

			readLaterArray = response["data"];

			if (readLaterArray.length > 0) {
				$("#nordot_readmore").text(readLaterArray.length);
			}
		});
	}

	function nordotRenderGetReadLaters() {
		$("#nordot-search-results div.list-group").empty();
		//$(".nordot-spinner").show();
		$("#nordot-search-more-btn").hide();

		if (readLaterArray.length <= 0) {
			nordotRenderNoResults();
			return;
		}


		$.each(readLaterArray, function(index, value) {
			var clone = $(".nordot-to-copy").clone();
			clone.removeClass("nordot-to-copy");

			var title = value.title;
			var publisher = value.publisher;
			var articleUrl = value.url;

			$("h6", clone).text(title);
			$("small.nordot-publisher", clone).text(publisher);
			$("small.nordot-datepublished", clone).text(value.date_published);
			$("a.nordot-article-link", clone).attr("href", articleUrl);
			$("img", clone).attr("src", value.img_url);

			var nordotArticleOptionsObject = $("div.nordot-article-options", clone);
			nordotArticleOptionsObject.data("content-img", value.img_url);

			nordotArticleOptionsObject.attr("id", value.postid);
			nordotArticleOptionsObject.data("content-url", articleUrl);
			nordotArticleOptionsObject.data("content-title", title);
			nordotArticleOptionsObject.data("content-desc", value.description);
			nordotArticleOptionsObject.data("content-pub", publisher);
			nordotArticleOptionsObject.data("content-pub-date", value.date_published);

			var actionObject = $("a.nordot-read-later-link", clone);

			actionObject.removeClass("text-body");
			actionObject.addClass("text-info");

			$("i", actionObject).removeClass("fa-bookmark-o");
			$("i", actionObject).addClass("fa-bookmark");
			$("#nordot-search-results div.list-group").append(clone);
			clone.show();

		});
	}


	$(document).on("click", "a.nordot-read-later-link", function(event) {
		event.preventDefault();

		var nordotButtonParent = $(this).parent();

		var contentId = nordotButtonParent.attr('id');
		var contentTitle = nordotButtonParent.data('content-title');
		var contentDescription = nordotButtonParent.data('content-desc');
		var contentPublisher = nordotButtonParent.data('content-pub');
		var contentDate = nordotButtonParent.data('content-pub-date');
		var contentUrl = nordotButtonParent.data('content-url');
		var contentImg = nordotButtonParent.data('content-img');

		var action = $(this).hasClass("text-body") ? 'nordot_read_later' : 'nordot_remove_read_later';
		var add = action == 'nordot_read_later';

		var actionObject = $(this);

		var data = {
			'action' : action,
			'nordotPostId' : contentId,
			'nordotUrl' : contentUrl,
			'nordotImgUrl' : contentImg,
			'nordotTitle' : contentTitle,
			'nordotPublisher' : contentPublisher,
			'nordotDatePublished' : contentDate,
			'nordotDescription' : contentDescription,
			'security' : ajax_object.ajax_nonce
		};

		if (add) {
			actionObject.removeClass("text-body");
			actionObject.addClass("text-info");

			$("i", actionObject).removeClass("fa-bookmark-o");
			$("i", actionObject).addClass("fa-bookmark");

			$("#nordot_readmore").text(readLaterArray.length + 1);
		} else {
			actionObject.removeClass("text-info");
			actionObject.addClass("text-body");

			$("i", actionObject).removeClass("fa-bookmark");
			$("i", actionObject).addClass("fa-bookmark-o");

			// If we are currently showing the Read More articles, then get rid of li item
			if ($("ul#nordot-search-leftbar li").first().hasClass("list-group-item-info")) {
				$(this).parents("div.list-group-item").fadeOut(400, function() {
					$(this).remove();
				});
			}

			$("#nordot_readmore").text(readLaterArray.length == 1 ? "" : readLaterArray.length - 1);
		}


		$.post(ajax_object.ajax_url, data, function(response) {
			if (!response["success"]) {
				// Do something
				return;
			}

			if (add) {
				var newItem = {
					'id' : response["data"].id,
					'postid' : data.nordotPostId,
					'url' : data.nordotUrl,
					'img_url' : data.nordotImgUrl,
					'title' : data.nordotTitle,
					'publisher' : data.nordotPublisher,
					'date_published' : data.nordotDatePublished,
					'description' : data.nordotDescription
				}

				// Add the new item to the readLater array
				readLaterArray.push(newItem);

			} else {
				// Remove the element from our readLaterArray
				readLaterArray = $.grep(readLaterArray, function(el, i) {
					if (el.postid == contentId) {
						return false;
					}

					return true; // keep the element in the array
				});
			}

			// Update the count badge
			$("#nordot_readmore").text(readLaterArray.length == 0 ? "" : readLaterArray.length);
		});

	});

	$('#inlineFormInputGroup').autocomplete({
		minChars : 3,
		onSearchStart : function(params) {
			$('.autocomplete-suggestions').hide();
			var inputWidth = $('#inlineFormInputGroup').outerWidth();
			var inputPosition = $('#inlineFormInputGroup').position();
			$('.nordot-autocomplete-spinner').width(inputWidth);
			$('.nordot-autocomplete-spinner').css("left", inputPosition.left);

			$('.nordot-autocomplete-spinner').show();
		},
		lookup : function(query, done) {
			var data = {
				'action' : 'nordot_auto_complete_search',
				'nordotAutoQuery' : query,
				'security' : ajax_object.ajax_nonce
			};
			$.post(ajax_object.ajax_url, data, function(response) {
				$('.nordot-autocomplete-spinner').hide();
				var result = {};
				if (!response["success"]) {
					// Do something
					done(result);
					return;
				}

				var resultObject = JSON.parse(response["data"]);

				result.suggestions = [];

				var autoResults = resultObject.objects;

				for (var i = 0; i < autoResults.length; i++) {
					var autoResult = autoResults[i];
					var underlyingData = "";
					var label = "";

					if (autoResult.type == "ch_units") {
						underlyingData = "unit_id:";
					} else if (autoResult.type == "ch_topics") {
						underlyingData = "topic_id:";
					} else if (autoResult.type == "ch_series") {
						underlyingData = "series_id:";
					} else if (autoResult.type == "ch_tags") {
						underlyingData = "tag:";
					} else if (autoResult.type == "related_words") {
						underlyingData = "label:";
					} else {
						continue;
					}

					underlyingData += (autoResult.type == "ch_tags" || autoResult.type == "related_words") ? autoResult.name : autoResult.unit.id;
					label += autoResult.name + " (" + underlyingData + ")"

					result.suggestions.push({
						"value" : label,
						"data" : underlyingData
					})
				}

				done(result);
			});

		},
		onSelect : function(suggestion) {
			$("form#nordot-search-form input").val(suggestion.data);
			nordotDoSearch();
		}
	});


	/**
	 * Searching Articles
	 */

	function nordotDoSearch(fromMorebtn = false, query = '') {
		var offset = 0;

		if (fromMorebtn) {
			query = $("#nordot-search-more-btn").data("query");
			offset = $("#nordot-search-more-btn").data("cur-offset") + 20;
		} else {
			$(".nordot-spinner").show();
			$("#nordot-search-results div.list-group").empty();
			$("#nordot-search-more-btn").hide();

			if (query == '')
				query = $("form#nordot-search-form input").val();
		}

		var data = {
			'action' : 'nordot_search',
			'nordotSearchQuery' : query,
			'nordotSearchOffset' : offset,
			'security' : ajax_object.ajax_nonce
		};

		// We can also pass the url value separately from ajaxurl for front end AJAX implementations
		$.post(ajax_object.ajax_url, data, function(response) {
			nordotSearchThingsToHide();

			if (!response["success"]) {
				// Do something
				nordotRenderSearchErrorResults();
				return;
			}

			var resultObject = JSON.parse(response["data"]);
			if (!resultObject.ok) {
				// Do something
				nordotRenderSearchErrorResults(resultObject.error_detail);
				return;
			}

			
			nordotRenderSearchResults(resultObject.posts, resultObject.paging, query);
		});
	}

	function nordotRenderSearchErrorResults(err = '') {
		var clone = $(".nordot-error-to-copy").clone();
		clone.removeClass("nordot-error-to-copy");
		var text = clone.text();
		clone.text(text + " " + err);
		$("#nordot-search-results div.list-group").append(clone);
		clone.show();
	}

	function nordotRenderNoResults() {
		var clone = $(".nordot-noresults-to-copy").clone();
		clone.removeClass("nordot-noresults-to-copy");

		$("#nordot-search-results div.list-group").append(clone);
		clone.show();
	}

	function nordotRenderSearchResults(results, paging, query) {
		if (results.length <= 0) {
			nordotRenderNoResults();
			return;
		}

		if (paging.has_next) {
			$("#nordot-search-more-btn").removeAttr("disabled");
			$("#nordot-search-more-btn").show();
			$("#nordot-search-more-btn").data("cur-offset", paging.offset);

			$("#nordot-search-more-btn").data("query", query);
		}

		for (i in results) {
			var postObj = results[i];
			
			var clone = $(".nordot-to-copy").clone();
			clone.removeClass("nordot-to-copy");

			var title = postObj.title;
			var publisher = postObj.unit.name;
			var articleUrl = postObj.url;

			var articleDateWithOffset = luxon.DateTime.fromISO(postObj.published_at);
			var articleDateLocal = articleDateWithOffset.toLocaleString(luxon.DateTime.DATE_SHORT) + " " + articleDateWithOffset.toLocaleString(luxon.DateTime.TIME_WITH_SHORT_OFFSET);

			$("h6", clone).text(title);
			$("small.nordot-publisher", clone).text(publisher);
			$("small.nordot-datepublished", clone).text(articleDateLocal);
			$("a.nordot-article-link", clone).attr("href", articleUrl);

			var nordotArticleOptionsObject = $("div.nordot-article-options", clone);

			var img = null;
			if (postObj.images.length > 0) {
				img = postObj.images[0].thumb_360;
			} else if (postObj.unit.profile_image && postObj.unit.profile_image.url) {
				img = postObj.unit.profile_image.url;
			}


			if (img) {
				$("img", clone).attr("src", img);
				nordotArticleOptionsObject.data("content-img", img);
			}

			var expiredAt = "";
			if (postObj.expired_at) {
				expiredAt = postObj.expired_at;
			}
			
			nordotArticleOptionsObject.attr("id", postObj.id);
			nordotArticleOptionsObject.data("content-url", articleUrl);
			nordotArticleOptionsObject.data("content-title", title);
			nordotArticleOptionsObject.data("content-desc", postObj.description);
			nordotArticleOptionsObject.data("content-pub", publisher);
			nordotArticleOptionsObject.data("content-pub-date", articleDateLocal);
			nordotArticleOptionsObject.data("content-expired-date", expiredAt);

			$.each(readLaterArray, function(index, value) {
				if (value.postid == postObj.id) {
					var actionObject = $("a.nordot-read-later-link", clone);

					actionObject.removeClass("text-body");
					actionObject.addClass("text-info");

					$("i", actionObject).removeClass("fa-bookmark-o");
					$("i", actionObject).addClass("fa-bookmark");

					return false;
				}
			});

			$("#nordot-search-results div.list-group").append(clone);
			clone.show();
		}
	}

	function nordotSearchThingsToHide() {
		$(".nordot-spinner").hide();
		$("#nordot-search-more-btn").hide();
		$("#nordot-search-more-btn .spinner-border").hide();
	}

	function nordotCurateModalReset(modal) {
		if (ajax_object.ajax_can_publish) {
			modal.find("#nordot-publish-submit").removeAttr("disabled");
		}

		modal.find("#nordot-publish-submit span").hide();

		modal.find("#nordot-draft-submit").removeAttr("disabled");
		modal.find("#nordot-draft-submit span").hide();

		modal.find("div.alert").hide();
		modal.find("form#nordot-publish textarea").val('');
		$("#nordot-set-featured-img").prop("checked", 'checked');
		$("#nordot-set-body-img").prop("checked", false);
		$("#nordot-set-body-img-align").val('alignleft');
		$("#nordot-set-body-img-align").prop("disabled", "disabled");
		$("#nordot-category").val('');
		$("#nordot_curate_body_id").val('');
		$("#nordot_curate_source_url").val('');
				
		//$("#nordot-set-body-img").parent().show();						
	}

	function nordotRenderCurateErrorResults() {
		$("#nordotCurateModal div.alert-danger").show();
	}

	function nordotDoCurate(draft = false) {
		if (draft)
			$("#nordot-draft-submit span").show();
		else
			$("#nordot-publish-submit span").show();

		$("#nordot-draft-submit").attr("disabled", true);
		$("#nordot-publish-submit").attr("disabled", true);


		var contentUrl = $("#nordotCurateModal .modal-body a").attr("href");
		var annotation = $("form#nordot-publish textarea").val();
		var setFeaturedImage = $("#nordot-set-featured-img").prop("checked");
		var setModalWindow = false;//$("#nordot-set-modal-window").prop("checked");
		var setBodyImage = $("#nordot-set-body-img").prop("checked");
		var bodyImageStyle = (setBodyImage) ? $("#nordot-set-body-img-align").val() : '';
		var articleStatus = draft ? 'draft' : 'public';
		var articleTitle = $("#nordotCurateModal .modal-title").text();
		var categories = $("#nordot-category").val();

		var data = {
			'action' : 'nordot_curate',
			'nordotContentUrl' : contentUrl,
			'nordotAnnotation' : annotation,
			'nordotSetFeaturedImage' : setFeaturedImage,
			'nordotSetModalWindow' : setModalWindow,
			'nordotSetBodyImage' : setBodyImage,
			'nordotBodyImageStyle' : bodyImageStyle,
			'nordotStatus' : articleStatus,
			'nordotTitle' : articleTitle,
			'nordotCategories' : categories,
			'nordotBodyId' : $("#nordot_curate_body_id").val(),
			'nordotCurateSourceUrl' : $("#nordot_curate_source_url").val(),
			'security' : ajax_object.ajax_nonce
		};

		$.post(ajax_object.ajax_url, data, function(response) {
			$("#nordot-publish-submit span").hide();
			$("#nordot-draft-submit span").hide();
			if (!response.ok) {
				// Do something
				
				nordotRenderCurateErrorResults();
				return;
			}

			var tweetTitle = articleTitle.length >= 85 ? (articleTitle.substring(0, 85)) : articleTitle;
			var tweetUrl = "https://twitter.com/intent/tweet?text=" + encodeURIComponent(tweetTitle + " | " + response.curation.content.site_name) + "&url=" + encodeURIComponent(response.curation.content.url_curation);
			$("#nordot-alert-view-post").attr("href", response.nordotPostUrl ? response.nordotPostUrl : "");
			$("#nordot-alert-tweet-post").attr("href", tweetUrl);
			$("#nordotCurateModal div.alert-success").show();

		});
	}

	function nordotDecodeHtml(html) {
		var txt = document.createElement("textarea");
		txt.textContent = html;
		return txt.value;
	}

	$(document).on("click", "ul#nordot-search-leftbar li", function(event) {
		$("ul#nordot-search-leftbar li").removeClass("list-group-item-info");
		$(this).addClass("list-group-item-info");

		$("form#nordot-search-form input").val('');
		$("form#nordot-search-form a i").removeClass("text-info");
		$("#nordot-save-search").prop("disabled", "disabled");

		var nordotQuery = $(this).data("query");

		if (nordotQuery == 'ReadLater') {
			nordotRenderGetReadLaters();
		} else {
			var nordotAutoPublish = $(this).data("autoPublish");
			var nordotAutoPublishAmount = $(this).data("autoPublishAmount");
			var nordotAutoPublishFrequency = $(this).data("autoPublishFrequency");

			if (nordotAutoPublish) {
				$("#nordot-save-search").hide();
				$("#nordot-edit-search").show();

				$("#nordot-edit-search").data("saveSearchId", $(this).data("id"));
				$("#nordot-edit-search").data("name", $(this).data("name"));
				$("#nordot-edit-search").data("autoPublish", nordotAutoPublish);
				$("#nordot-edit-search").data("autoPublishAmount", nordotAutoPublishAmount);
				$("#nordot-edit-search").data("autoPublishFrequency", nordotAutoPublishFrequency);

				$("#nordot-edit-search").data("autoPublishSetFeaturedImage", $(this).data("autoPublishSetFeaturedImage"));
				$("#nordot-edit-search").data("autoPublishSetModalWindow", false);//$(this).data("autoPublishSetModalWindow"));
				$("#nordot-edit-search").data("autoPublishSetBodyImage", $(this).data("autoPublishSetBodyImage"));
				$("#nordot-edit-search").data("autoPublishSetBodyImageStyle", $(this).data("autoPublishSetBodyImageStyle"));
				$("#nordot-edit-search").data("autoPublishCategories", $(this).data("autoPublishCategories"));


			} else {
				$("#nordot-save-search").show();
				$("#nordot-edit-search").hide();
			}

			nordotDoSearch(false, nordotQuery);
		}
	});


	$("form#nordot-search-form").submit(function(event) {
		$("ul#nordot-search-leftbar li").removeClass("list-group-item-info");
		$("form#nordot-search-form a i").addClass("text-info");
		$(".nordot-spinner").show();
		event.preventDefault();

		$("#nordot-save-search").show();
		$("#nordot-edit-search").hide();
		nordotDoSearch();
	});

	$("form#nordot-search-form a").click(function(event) {
		event.preventDefault();
		$("form#nordot-search-form").submit();
	});

	$("#nordot-search-more-btn").click(function(event) {
		$("#nordot-search-more-btn").attr("disabled", true);
		$("#nordot-search-more-btn .spinner-border").show();
		nordotDoSearch(true);
	});

	$(document).on("click", "a.nordot-curate-link", function(event) {
		event.preventDefault();
	});

	$("#nordotCurateModal").on('show.bs.modal', function(event) {
		$("#curate-modal-spinner").show();
		
		var nordotButton = $(event.relatedTarget); // Button that triggered the modal
		var nordotButtonParent = nordotButton.parent();

		var contentId = nordotButtonParent.attr('id');
		
		var modal = $(this);
		var modalHeader = modal.find('.modal-header');
		var modalBody = modal.find('.modal-body');
		var modalFooter = modal.find('.modal-footer');
		
		modalBody.hide();
		
		var contentTitle = nordotButtonParent.data('content-title');
		modal.find('.modal-title').text(contentTitle);		
		
  	 	var data = {
			'action' : 'nordot_get_post_info',
			'security' : ajax_object.ajax_nonce,
			'nordotContentId' : contentId,
		};

		$.post(ajax_object.ajax_url, data, function(response) {

			
			var contentDescription = nordotButtonParent.data('content-desc');
			var contentPublisher = nordotButtonParent.data('content-pub');
			var contentUrl = nordotButtonParent.data('content-url');
			var contentImg = nordotButtonParent.data('content-img');
			
			modal.find('.modal-body .nordot-description').text(contentDescription);
			modal.find('.modal-body .nordot-thumbnail-img').attr("src", contentImg);
			modal.find('.modal-body a').attr("href", contentUrl);

			nordotCurateModalReset(modal);
			
			$("#curate-modal-spinner").hide();
			
			modalBody.show();
			
			if (!response["success"]) {
				return;
			}

			var resultObject = JSON.parse(response["data"]);
			if (!resultObject.ok) {
				return;
			}

			
			
			var body = resultObject.post.body;
			if (body != null && body != '') {
				$("#nordot_curate_body_id").val(contentId);			
			}
			
			$("#nordot_curate_source_url").val(resultObject.post.source_url);
		});		
		
	})	;

	$("#nordot-publish-submit").click(function(event) {
		event.preventDefault();
		nordotDoCurate();
	});

	$("#nordot-draft-submit").click(function(event) {
		event.preventDefault();
		nordotDoCurate(true);
	});

	$("#nordot-search-form input").change(function(event) {
		if ($(this).val() == '') {
			$("#nordot-save-search").prop("disabled", "disabled");
		} else {
			$("#nordot-save-search").prop("disabled", false);
		}
	});

	$(".nordot-help-link").click(function(event) {
		event.preventDefault();
	})

	$("#nordot-set-body-img").change(function(event) {
		if (this.checked) {
			$("#nordot-set-body-img-align").prop("disabled", false);
		} else {
			$("#nordot-set-body-img-align").prop("disabled", "disabled");
		}
	})

	$("#nordotPublishersModal").on('show.bs.modal', function(event) {
		var modal = $(this);
		var data = {
			'action' : 'nordot_list_media_units',
			'security' : ajax_object.ajax_nonce
		};

		$.post(ajax_object.ajax_url, data, function(response) {
			$("#media-unit-spinner").hide();
			$("#nordot-media-units-table").show();

			
			if (!response["success"]) {
				return;
			}

			var resultObject = JSON.parse(response["data"]);
			if (!resultObject.ok) {
				return;
			}

			var units = resultObject.units;

			var currentTr = null;
			for (i in units) {
				var unit = units[i];
				if (i % 5 == 0) {
					if (currentTr != null) {
						$("#nordot-media-units-table tbody").append(currentTr);
					}

					currentTr = document.createElement("tr");
				}

			var newTd = document.createElement("td");
    	    var anchor = document.createElement("a");
    	    var div1 = document.createElement("div");
    	    var div2 = document.createElement("div");
    	    var img = document.createElement("img");

	  	    var divClass = document.createAttribute("class");
	 	    divClass.value = "nordot-media-units-mulogo";
	 	    div1.setAttributeNode(divClass);
	 	    
	  	    var imgSrc = document.createAttribute("src");
	 	    imgSrc.value = unit.profile_image.url;
	 	    img.setAttributeNode(imgSrc);
	 	    
	 	    div1.append(img);
    	   
	  	    var divClass2 = document.createAttribute("class");
	 	    divClass2.value = "nordot-media-units-muname";
	 	    div2.setAttributeNode(divClass2);	 	    
	 	    
    	    var small = document.createElement("small");
    	    small.textContent = unit.name;    	   
   	    
   	    div2.append(small);
   	        
    	    var attrHref = document.createAttribute("href");
   	    attrHref.value = unit.id;
   	    anchor.setAttributeNode(attrHref);
   	    
	  	    var attrClass = document.createAttribute("class");
	 	    attrClass.value = "nordot-media-units-muanchor text-body";
	 	    anchor.setAttributeNode(attrClass);   	    

 				
	 	    anchor.append(div1);
	 	    anchor.append(div2);
	 	    
	 	    newTd.append(anchor);
				currentTr.append(newTd);
			}

			$("#nordot-media-units-table tbody").append(currentTr);
		});
	})	;

	$(document).on("click", "a.nordot-media-units-muanchor", function(event) {
		event.preventDefault();
		var unitId = $(this).attr("href");
		$("form#nordot-search-form input").val("unit_id:" + unitId);
		$("form#nordot-search-form input").submit();
		$('#nordotPublishersModal').modal('hide');
		$("#nordot-save-search").prop("disabled", false);
	});

});