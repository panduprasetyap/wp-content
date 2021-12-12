jQuery(document).ready(function($) {

	//select2
	$('.wcpcm-select2').select2();

	$(".ajax_products").find('.wcpcm-select2').select2({
		minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '3',
    ajax: {
    	url: wcpcmOption.ajax_url,
     	dataType: 'json',
     	quietMillis: 250,
     	data: function (params) {
      	return {
        	 q: params.term, // search query
        	 action: 'wcpcm_ajax_products' // AJAX action for admin-ajax.php
      	};
    	},
        
      processResults: function( data ) {
				var terms = [];
				if ( data ) {
					$.each( data, function( id, text ) {
						terms.push( { id: id, text: text } );
					});
				}
				return {
					results: terms
				};
			},
			cache: true
		},
	});

	// Loads the color pickers
	$('.of-color').wpColorPicker();

	$('.of-radio-img-label').hide();
	$('.of-radio-img-img').show();
	$('.of-radio-img-radio').hide();


	// Loads tabbed sections if they exist
	if ( $('.nav-tab-wrapper').length ) {
		options_framework_tabs();
	}

	function options_framework_tabs() {

		var $group = $('.group'),
			$navtabs = $('.nav-tab-wrapper a'),
			active_tab = '';

		// Hides all the .group sections to start
		$group.hide();

		// Find if a selected tab is saved in localStorage
		if ( typeof(localStorage) != 'undefined' ) {
			active_tab = localStorage.getItem('active_tab');
		}

		// If active tab is saved and exists, load it's .group
		if ( active_tab != '' && $(active_tab).length ) {
			$(active_tab).fadeIn();
			$(active_tab + '-tab').addClass('nav-tab-active');
		} else {
			$('.group:first').fadeIn();
			$('.nav-tab-wrapper a:first').addClass('nav-tab-active');
		}

		// Bind tabs clicks
		$navtabs.click(function(e) {

			e.preventDefault();

			// Remove active class from all tabs
			$navtabs.removeClass('nav-tab-active');

			$(this).addClass('nav-tab-active').blur();

			if (typeof(localStorage) != 'undefined' ) {
				localStorage.setItem('active_tab', $(this).attr('href') );
			}

			var selected = $(this).attr('href');

			$group.hide();
			$(selected).fadeIn();

		});
	}

});