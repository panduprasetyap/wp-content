<?php
/**
 * A unique identifier is defined to store the options in the database and reference them from the theme.
 * By default it uses the theme name, in lowercase and without spaces, but this can be changed if needed.
 * If the identifier changes, it'll appear as if the options have been reset.
 */

function wcpcm_optionsframework_option_name() {
	$wcpcm_optionsframework_settings = get_option( 'wcpcm_optionsframework' );
	$wcpcm_optionsframework_settings['id'] = 'wcpcm_options';
	update_option( 'wcpcm_optionsframework', $wcpcm_optionsframework_settings );
}


add_filter( 'wcpcm_optionsframework_menu', 'add_wcpcm' );

function add_wcpcm( $menu ) {
	$menu['page_title']  	= 'WooCommerce Catalog Mode';
	$menu['menu_title']  	= 'Catalog Mode';
	$menu['mode']		 	= 'menu';
	$menu['menu_slug']   	= 'wc-catalog-mode';
	$menu['position']    	= '30';
	return $menu;
}


$options = get_option('wcpcm_options');

function wcpcm_optionsframework_options() {

	$shop_page_url = get_permalink( wc_get_page_id( 'shop' ) );

	$categories = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
	$cats = array();

	if ( $categories ) foreach ( $categories as $cat ) $cats[$cat->term_id] = esc_html( $cat->name );

	$coupon = get_posts( array( 'post_type' => 'shop_coupon', 'post_status' => 'publish', 'posts_per_page' => -1 ) );
	$coupons = array();

	if( is_array($coupon) ) {
	  foreach ( $coupon as $cpn ) {
	   $coupons[$cpn->post_title] = $cpn->post_title;
	  }
	}


  $options 		= array();

  $options[] 	= array('name' => __('General Settings', 'catalog_mode_for_woocommerce'),
  	'type' => 'heading');
		
	$options[] 	= array('name' => __('Enable Product Catalog Mode?', 'wc_catalog_mode'),
		'desc' 		=> __('Check this if you want to activate Product Catalog Mode.', 'catalog_mode_for_woocommerce'),
		'id' 			=> 'enabled',
		'std' 		=> '',
		'type' 		=> 'checkbox');

	$options[] 	= array('name' => __('Add To Cart Button Configuration', 'wc_catalog_mode'),
		'desc' 		=> __('Check the action for add to cart button on your store', 'catalog_mode_for_woocommerce'),
		'id' 			=> 'add_to_cart_action',
		'options' => array('hide_in_product_details' => 'Hide In Product Detail Page', 'hide_in_shop_pages' => 'Hide From Shop Pages', 'hide_in_product_variation' => 'Hide In Product Variation'),
		'type' 		=> 'multicheck');

	$options[] 	= array('name' => __('Disable Shop?', 'wc_catalog_mode'),
		'desc' 		=> __('Check this if you want to disable shop feature like disable cart and checkout page .', 'catalog_mode_for_woocommerce'),
		'id' 			=> 'disable_shop',
		'std' 		=> '',
		'type' 		=> 'checkbox');

	$options[] 	= array('name' => __('Redirect Url', 'wc_catalog_mode'),
		'desc' 		=> __('Enter the url where you want to redirect users if they try to access cart or checkout page', 'catalog_mode_for_woocommerce'),
		'id' 			=> 'redirect_url',
		'std' 		=> home_url(),
		'type' 		=> 'text');

	$options[] 	= array('name' => __('Disable Review?', 'wc_catalog_mode'),
		'desc' 		=> __('Check this if you want to disable review.', 'catalog_mode_for_woocommerce'),
		'id' 			=> 'disable_review',
		'std' 		=> '',
		'type' 		=> 'checkbox');


	$options[] 	= array('name' => __('Custom Button', 'catalog_mode_for_woocommerce'),
  	'type' => 'heading');

	$options[] 	= array('name' => __('Enable Custom Button', 'wc_catalog_mode'),
		'desc' 		=> __('Check the checkbox to use your own custom text on buttons', 'catalog_mode_for_woocommerce'),
		'id' 			=> 'use_custom_button',
		'std' 		=> '',
		'type' 		=> 'checkbox');

	$options[] 	= array('name' => __('Button Text', 'wc_catalog_mode'),
		'desc' 		=> __('Enter the text on the button which would be shows in place of add to cart button', 'catalog_mode_for_woocommerce'),
		'id' 			=> 'catalog_mode_button_text',
		'std' 		=> 'Read More',
		'type' 		=> 'text');

	$options[] 	= array('name' => __('Button Text Color', 'wc_catalog_mode'),
		'desc' 		=> __('Select the text color for add to cart button', 'catalog_mode_for_woocommerce'),
		'id' 			=> 'button_text_color',
		'std' 		=> '#FFF',
		'type' 		=> 'color');

	$options[] 	= array('name' => __('Button Text Hover Color', 'wc_catalog_mode'),
		'desc' 		=> __('Select the text hover color for add to cart button', 'catalog_mode_for_woocommerce'),
		'id' 			=> 'button_text_hover_color',
		'std' 		=> '#666',
		'type' 		=> 'color');

	$options[] 	= array('name' => __('Button Background Color', 'wc_catalog_mode'),
		'desc' 		=> __('Select the background color for add to cart button', 'catalog_mode_for_woocommerce'),
		'id' 			=> 'button_bg_color',
		'std' 		=> '#666',
		'type' 		=> 'color');

	$options[] 	= array('name' => __('Button Hover Background Color', 'wc_catalog_mode'),
		'desc' 		=> __('Select the hover background color for add to cart button', 'catalog_mode_for_woocommerce'),
		'id' 			=> 'button_hover_bg_color',
		'std' 		=> '#FFF',
		'type' 		=> 'color');

    return $options;
}