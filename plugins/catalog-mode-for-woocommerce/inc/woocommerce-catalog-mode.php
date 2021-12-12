<?php

class WooCommerce_Catalog_Mode{

  protected $options = '';

  /**
  * Construct for the class
  *
  * @param empty
  * @return mixed
  *
  */
	public function __construct() {
    //hide_add_to_cart_loop

    //Check if woocommerce plugin is installed.
    add_action( 'admin_notices', array( $this, 'wcpcm_catalog_check_required_plugins' ) );

    //Add setting link for the admin settings
    add_filter( "plugin_action_links_".WCPCM_BASE, array( $this, 'wcpcm_catalog_mode_settings_link' ) );

    $this->options = get_option( 'wcpcm_options' );

    if( $this->option('enabled') == 'yes' ) :

      //Add custom styles
      add_action( 'wp_head', array( $this, 'wcpcm_add_styles' ) );

      //filter for class names
      add_filter( 'wcpcm_css_classes', array( $this, 'wcpcm_add_to_cart_config' ) );

      //Page redirect when disable the cart feature
      add_action('template_redirect', array($this, 'wcpcm_check_page_redirects'));

      //Hide add to cart from shop or archve page
      add_action( 'woocommerce_after_shop_loop_item' , array($this, 'wcpcm_check_catalog_show') );

      //Disable review tabs
      if( $this->option('disable_review') == 'yes' ) :
        add_filter( 'woocommerce_product_tabs', array($this, 'wcpcm_remove_reviews_tab'), 98 );
      endif;
 

      //use custom button 
      if( $this->option('use_custom_button') == 'yes' ) :
        add_filter( 'woocommerce_product_add_to_cart_text', array($this, 'woo_custom_single_add_to_cart_text') );
        add_filter( 'woocommerce_product_single_add_to_cart_text', array($this, 'woo_custom_single_add_to_cart_text') );
      endif;

    endif;
    
	}

  /**
  * Checks whether woocommerce has been installed and active
  *
  * @param empty
  * @return mixed
  *
  */
  public function wcpcm_catalog_check_required_plugins() {
    if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) { ?>
      <div id="message" class="error">
        <?php echo __('WooCommerce Catalog Mode expects WooCommerce to be active. This plugin has been deactivated.', 'wc_catalog_mode') ?>
      </div>

      <?php
      deactivate_plugins( '/woo-catalog-mode/woo-catalog-mode.php' );
    } // if woocommerce

  } // check_required_plugins


  /**
  * Add dynamic styles through functions
  *
  * @param empty
  * @return mixed
  *
  */
  public function wcpcm_add_styles() {
    $this->wcpcm_remove_add_to_cart_buttons();
    $this->wcpcm_add_custom_styles();
  }


  /**
  * Add dynamic styles to the site for hiding cart and quantity button 
  *
  * @param empty
  * @return mixed
  *
  */
  public function wcpcm_add_custom_styles() {
    $enable_custom_button = $this->option('use_custom_button');

    if( $enable_custom_button == 'yes' ) :
      $button_text_color = $this->option('button_text_color') !== '' ? $this->option('button_text_color') : '#FFF';
      $button_text_hover_color = $this->option('button_text_hover_color') !== '' ? $this->option('button_text_hover_color') : '#666';

      $button_bg_color = $this->option('button_bg_color') !== '' ? $this->option('button_text_hover_color') : '#666';
      $button_hover_bg_color = $this->option('button_hover_bg_color') ? $this->option('button_text_hover_color') : '#ccc';
      ob_start();
      ?>
      <style type="text/css">
        .single-product .product .single_add_to_cart_button.button, .woocommerce .product .add_to_cart_button.button {
          background-color: <?php echo $button_bg_color; ?>;
          color: <?php echo $button_text_color; ?>;
        }
        .single-product .product .single_add_to_cart_button.button:hover, .woocommerce .product .add_to_cart_button.button:hover {
          background-color: <?php echo $button_hover_bg_color; ?>;
          color: <?php echo $button_text_hover_color; ?>;
        }
      </style>
      <?php
      echo ob_get_clean();
    endif;
  }

  /**
  * Get all the class name and make the cart button hide
  *
  * @param empty
  * @return mixed
  *
  */
  public function wcpcm_remove_add_to_cart_buttons() {
    $classes = apply_filters( 'wcpcm_css_classes', array() );
    
    if ( $classes ) {
      ob_start();
    ?>
    <style type="text/css">
      <?php echo implode( ', ', $classes ); ?>
      {
        display: none !important
      }
    </style>
    <?php
      echo ob_get_clean();
    }
  }

  /**
  * Creates an array of selector depending upon conditions
  *
  * @param $classes array
  * @return $classes array
  *
  */
  public function wcpcm_add_to_cart_config($classes) {    
    $atc_configuration = $this->option('add_to_cart_action');

    //$loop_get_product_id = 
    $excluded_products = $this->check_excluded_catalog_products($id = '');

    if( ! $excluded_products )
      return;

    $args = array();

      //Check in product details/single page
      if( isset($atc_configuration['hide_in_product_details']) && $atc_configuration['hide_in_product_details'] == '1' ) :
        $args[] = 'body.single-product .product-type-simple form.cart button.single_add_to_cart_button';
        $args[] = '.product-type-simple form.cart .quantity';
        $args[] = '.product-type-simple form.cart';
      endif;

      //Check in product variation
      if( isset($atc_configuration['hide_in_product_variation']) && $atc_configuration['hide_in_product_variation'] == '1' ) :
        $args[] = 'body.single-product .product-type-variable form.variations_form';
      endif;


    $classes = array_merge($classes, $args);
    return $classes;
  }


  /**
  * Adds a new link to the plugin settings
  *
  * @param $links array
  * @return $links outputs array of links
  *
  */
  public function wcpcm_catalog_mode_settings_link($links) {
    $settings_link = '<a href="'.admin_url('admin.php?page=wc-catalog-mode').'">Settings</a>';
    array_unshift( $links, $settings_link );
    return $links;
  }

  
  /**
  * Saves the option fields
  *
  * @param array 
  * @return mixed
  *
  */
  public function option( $option ) {
    if( isset( $this->options[$option] ) && $this->options[$option] != '' )
      return $this->options[$option];
    else 
    return '';
  }

  /**
  * Check user roles to set the product in catalog mode
  *
  * @param empty 
  * @return bool
  *
  */
  private function wcpcm_check_user_roles() {
    $catalog_mode_roles = !empty($this->option('catalog_mode_roles')) ? $this->option('catalog_mode_roles') : array();
    $cond = false;
    if( is_array($catalog_mode_roles) && !empty($catalog_mode_roles) ) {
      $user_id = get_current_user_id();
      $current_user_roles = $this->get_user_roles_by_user_id( $user_id );
      $matched_roles = array_intersect($current_user_roles, $catalog_mode_roles);
      if( count($matched_roles) > 0 ) {
        $cond = true;
      }
    }
    return $cond;
  }

  /**
  * Get user roles by user id
  *
  * @param user id 
  * @return array of roles
  *
  */
  public function get_user_roles_by_user_id( $user_id ) {
    $user = get_userdata( $user_id );
    return empty( $user ) ? array() : $user->roles;
  }

  /**
  * Set catalog mode as per user login status
  *
  * @param empty 
  * @return bool
  *
  */
  private function wcpcm_user_catalog_mode() {
    $catalog_mode_users = !empty($this->option('catalog_mode_users')) ? $this->option('catalog_mode_users') : 'all_users';
    
    $exclude_products = !empty($this->option('catalog_mode_exclude_products')) ? $this->option('catalog_mode_exclude_products') : array();
    
    $check_roles = $this->wcpcm_check_user_roles();

    $cond = false;
    switch ($catalog_mode_users) {
      case 'loggedin_users':
        if( is_user_logged_in() && $check_roles  ) 
          $cond = true;
        break;

      case 'guest_users':
        if( !is_user_logged_in() )
          $cond = true;
        break;

      case 'all_users' :
        if( is_user_logged_in() ) {
          if( $check_roles ) {
            $cond = true;
          }
          else {
            $cond = false;
          }
        }

        if( !is_user_logged_in() )
          $cond = true;
        break;
      
      default:
        $cond = true;
        break;
    }

    return $cond;

  }

  /**
  * Check the excluded products from catalog mode
  *
  * @param product id 
  * @return bool
  *
  */
  private function check_excluded_catalog_products($product_id) {
    global $woocommerce;
    global $product;
    $cond = true;

    return $cond;
  }

  /**
  * Page redirect on checkout and cart page
  *
  * @param empty
  * @return bool
  *
  */
  public function wcpcm_check_page_redirects() {
    if ( $this->option('disable_shop') == 'yes' ) {
      $cart         = is_page( wc_get_page_id( 'cart' ) );
      $checkout     = is_page( wc_get_page_id( 'checkout' ) );
      $redirect_url = !empty($this->option('redirect_url')) ? $this->option('redirect_url') : home_url();

      wp_reset_query();

      if ( $cart || $checkout ) {
        wp_redirect( $redirect_url );
        exit;
      }

    }
  }


  /**
  * Remove review tabs from product page
  *
  * @param array 
  * @return array
  *
  */
  public function wcpcm_remove_reviews_tab($tabs){
    unset( $tabs['reviews'] );
    return $tabs;
  }


  /**
  * makes the style to whether show add to cart in shop archive page
  *
  * @param empty 
  * @return mixed
  *
  */
  public function wcpcm_check_catalog_show() {
    global $woocommerce;
    global $product;

    if( empty($product) )
      return;

    $product_selectors = array();

    $product_id = !empty( get_the_ID() ) ? get_the_ID() : $product->get_id(); // Get woocommerce product id

    $atc_configuration = $this->option('add_to_cart_action');


    //Hide add to cart for these products
    if( isset($atc_configuration['hide_in_product_details']) && $atc_configuration['hide_in_shop_pages'] == '1' ) :
      array_push($product_selectors, 'ul.products .post-'.$product_id.' .add_to_cart_button');
    endif;


    $selectors_list = '';

    if( is_array($product_selectors) && !empty($product_selectors) ) {
      $selectors_list .= implode(', ', $product_selectors);
    }


    if( '' !== $selectors_list ) {
      ob_start();
      ?>
      <style type="text/css">
        <?php echo $selectors_list ?> {
          display: none;
        }
      </style>
      <?php
      echo ob_get_clean();
    }
    
  }

  /**
  * Change the add to cart text as per settings
  *
  * @param empty 
  * @return string
  *
  */
  public function woo_custom_single_add_to_cart_text() {
    $cart_button = !empty($this->option('catalog_mode_button_text')) ? $this->option('catalog_mode_button_text') : __('Read More', 'catalog_mode_for_woocommerce');
    return __( $cart_button, 'catalog_mode_for_woocommerce' );
  }

}