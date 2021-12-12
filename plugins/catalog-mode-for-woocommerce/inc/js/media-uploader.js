jQuery(document).ready(function($){

	var wcpcm_optionsframework_upload;
	var wcpcm_optionsframework_selector;

	function wcpcm_optionsframework_add_file(event, selector) {

		var upload = $(".uploaded-file"), frame;
		var $el = $(this);
		wcpcm_optionsframework_selector = selector;

		event.preventDefault();

		// If the media frame already exists, reopen it.
		if ( wcpcm_optionsframework_upload ) {
			wcpcm_optionsframework_upload.open();
		} else {
			// Create the media frame.
			wcpcm_optionsframework_upload = wp.media.frames.wcpcm_optionsframework_upload =  wp.media({
				// Set the title of the modal.
				title: $el.data('choose'),

				// Customize the submit button.
				button: {
					// Set the text of the button.
					text: $el.data('update'),
					// Tell the button not to close the modal, since we're
					// going to refresh the page when the image is selected.
					close: false
				}
			});

			// When an image is selected, run a callback.
			wcpcm_optionsframework_upload.on( 'select', function() {
				// Grab the selected attachment.
				var attachment = wcpcm_optionsframework_upload.state().get('selection').first();
				wcpcm_optionsframework_upload.close();
				wcpcm_optionsframework_selector.find('.upload').val(attachment.attributes.url);
				if ( attachment.attributes.type == 'image' ) {
					wcpcm_optionsframework_selector.find('.screenshot').empty().hide().append('<img src="' + attachment.attributes.url + '"><a class="remove-image">Remove</a>').slideDown('fast');
				}
				wcpcm_optionsframework_selector.find('.upload-button').unbind().addClass('remove-file').removeClass('upload-button').val(wcpcm_optionsframework_l10n.remove);
				wcpcm_optionsframework_selector.find('.of-background-properties').slideDown();
				wcpcm_optionsframework_selector.find('.remove-image, .remove-file').on('click', function() {
					wcpcm_optionsframework_remove_file( $(this).parents('.section') );
				});
			});

		}

		// Finally, open the modal.
		wcpcm_optionsframework_upload.open();
	}

	function wcpcm_optionsframework_remove_file(selector) {
		selector.find('.remove-image').hide();
		selector.find('.upload').val('');
		selector.find('.of-background-properties').hide();
		selector.find('.screenshot').slideUp();
		selector.find('.remove-file').unbind().addClass('upload-button').removeClass('remove-file').val(wcpcm_optionsframework_l10n.upload);
		// We don't display the upload button if .upload-notice is present
		// This means the user doesn't have the WordPress 3.5 Media Library Support
		if ( $('.section-upload .upload-notice').length > 0 ) {
			$('.upload-button').remove();
		}
		selector.find('.upload-button').on('click', function(event) {
			wcpcm_optionsframework_add_file(event, $(this).parents('.section'));
		});
	}

	$('.remove-image, .remove-file').on('click', function() {
		wcpcm_optionsframework_remove_file( $(this).parents('.section') );
    });

    $('.upload-button').click( function( event ) {
    	wcpcm_optionsframework_add_file(event, $(this).parents('.section'));
    });

});