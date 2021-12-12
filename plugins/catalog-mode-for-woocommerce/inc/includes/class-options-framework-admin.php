<?php
/**
 * @package   WCPCM_Options_Framework
 * @author    Devin Price <devin@wptheming.com>
 * @license   GPL-2.0+
 * @link      http://wptheming.com
 * @copyright 2010-2014 WP Theming
 */
class WCPCM_Options_Framework_Admin {

	/**
     * Page hook for the options screen
     *
     * @since 1.7.0
     * @type string
     */
    protected $options_screen = null;

    /**
     * Hook in the scripts and styles
     *
     * @since 1.7.0
     */
    public function init() {

			// Gets options to load
    	$options = & WCPCM_Options_Framework::_wcpcm_optionsframework_options();

			// Checks if options are available
    	if ( $options ) {

				// Add the options page and menu item.
				add_action( 'admin_menu', array( $this, 'add_custom_options_page' ) );

				// Add the required scripts and styles
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

				// Settings need to be registered after admin_init
				add_action( 'admin_init', array( $this, 'settings_init' ) );

				// Adds options menu to the admin bar
				add_action( 'wp_before_admin_bar_render', array( $this, 'wcpcm_optionsframework_admin_bar' ) );

				add_action( 'wp_ajax_wcpcm_ajax_products', array( $this, 'wcpcm_get_products' ) );
			}

    }

    function wcpcm_get_products() {
    	global $wpdb;
        $post_types = array( 'product' );
        ob_start();

        if ( empty( $term ) ) {
            $term = wc_clean( stripslashes( $_GET['q'] ) );
        } else {
            $term = wc_clean( $term );
        }


        if ( empty( $term ) ) {
            die();
        }

        $like_term = '%' . $wpdb->esc_like( $term ) . '%';

        if ( is_numeric( $term ) ) {
            $query = $wpdb->prepare( "
                SELECT ID FROM {$wpdb->posts} posts LEFT JOIN {$wpdb->postmeta} postmeta ON posts.ID = postmeta.post_id
                WHERE posts.post_status = 'publish'
                AND (
                    posts.post_parent = %s
                    OR posts.ID = %s
                    OR posts.post_title LIKE %s
                    OR (
                        postmeta.meta_key = '_sku' AND postmeta.meta_value LIKE %s
                    )
                )
            ", $term, $term, $term, $like_term );
        } else {
            $query = $wpdb->prepare( "
                SELECT ID FROM {$wpdb->posts} posts LEFT JOIN {$wpdb->postmeta} postmeta ON posts.ID = postmeta.post_id
                WHERE posts.post_status = 'publish'
                AND (
                    posts.post_title LIKE %s
                    or posts.post_content LIKE %s
                    OR (
                        postmeta.meta_key = '_sku' AND postmeta.meta_value LIKE %s
                    )
                )
            ", $like_term, $like_term, $like_term );
        }

        $query .= " AND posts.post_type IN ('" . implode( "','", array_map( 'esc_sql', $post_types ) ) . "')";

        if ( ! empty( $_GET['exclude'] ) ) {
            $query .= " AND posts.ID NOT IN (" . implode( ',', array_map( 'intval', explode( ',', $_GET['exclude'] ) ) ) . ")";
        }

        if ( ! empty( $_GET['include'] ) ) {
            $query .= " AND posts.ID IN (" . implode( ',', array_map( 'intval', explode( ',', $_GET['include'] ) ) ) . ")";
        }

        if ( ! empty( $_GET['limit'] ) ) {
            $query .= " LIMIT " . intval( $_GET['limit'] );
        }

        $posts          = array_unique( $wpdb->get_col( $query ) );
        $found_products = array();

        if ( ! empty( $posts ) ) {
            foreach ( $posts as $post ) {
                $product = wc_get_product( $post );

                if ( ! current_user_can( 'read_product', $post ) ) {
                    continue;
                }

                if ( ! $product || ( $product->is_type( 'variation' ) && empty( $product->parent ) ) ) {
                    continue;
                }

                $found_products[ $post ] = rawurldecode( $product->get_formatted_name() );
            }
        }

        wp_send_json( $found_products );
    }

		/**
		* Registers the settings
		*
		* @since 1.7.0
		*/
    function settings_init() {

   		// Load Options Framework Settings
      $wcpcm_optionsframework_settings = get_option( 'wcpcm_optionsframework' );

			// Registers the settings fields and callback
			register_setting( 'wcpcm_optionsframework', $wcpcm_optionsframework_settings['id'],  array ( $this, 'validate_options' ) );

			// Displays notice after options save
			add_action( 'wcpcm_optionsframework_after_validate', array( $this, 'save_options_notice' ) );

    }

	static function menu_settings() {

		$menu = array(
			// Modes: submenu, menu
      'mode' => 'menu',

      // Submenu default settings
      'page_title' 	=> __( 'Theme Options', 'textdomain'),
			'menu_title' 	=> __('Theme Options', 'textdomain'),
			'capability' 	=> 'edit_theme_options',
			'menu_slug' 	=> 'options-framework',
      'parent_slug' => 'themes.php',

      // Menu default settings
      'icon_url' 		=> 'dashicons-visibility',
      'position' 		=> '61'
		);

		return apply_filters( 'wcpcm_optionsframework_menu', $menu );
	}

	/**
	* Add a subpage called "Theme Options" to the appearance menu.
	*
	* @since 1.7.0
	*/
	function add_custom_options_page() {

		$menu = $this->menu_settings();

    	switch( $menu['mode'] ) {

				case 'menu':
					// http://codex.wordpress.org/Function_Reference/add_menu_page
					$this->options_screen = add_menu_page(
					$menu['page_title'],
					$menu['menu_title'],
					$menu['capability'],
					$menu['menu_slug'],
					array( $this, 'options_page' ),
					$menu['icon_url'],
					$menu['position']
					);
				break;

				default:
					// http://codex.wordpress.org/Function_Reference/add_submenu_page
					$this->options_screen = add_submenu_page(
					$menu['parent_slug'],
					$menu['page_title'],
					$menu['menu_title'],
					$menu['capability'],
					$menu['menu_slug'],
					array( $this, 'options_page' ) );
				break;
      }   
	}

	/**
	* Loads the required stylesheets
	*
	* @since 1.7.0
	*/

	function enqueue_admin_styles( $hook ) {

		if ( $this->options_screen != $hook )
	  	return;

		wp_enqueue_style( 'wcpcm_optionsframework', WCPCM_OPTIONS_FRAMEWORK_DIRECTORY . 'css/wc_catalog_mode_optionsframework.css', array(),  WCPCM_Options_Framework::VERSION );
		wp_enqueue_style( 'wp-color-picker' );
	}

	/**
	* Loads the required javascript
	*
	* @since 1.7.0
	*/
	function enqueue_admin_scripts( $hook ) {

		if ( $this->options_screen != $hook )
	  	return;

		wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css' );

	  wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js', array('jquery') );

		// Enqueue custom option panel JS
		wp_enqueue_script( 'options-custom', WCPCM_OPTIONS_FRAMEWORK_DIRECTORY . 'js/options-custom.js', array( 'jquery','wp-color-picker','select2' ), WCPCM_Options_Framework::VERSION );

		$get_settings = get_option( 'wcpcm_options' );


		$option_var = array( 
			'options_path' 			=> WCPCM_OPTIONS_FRAMEWORK_DIRECTORY,
			'ajax_url' 					=> admin_url( 'admin-ajax.php' ),
			'nonce'  						=> wp_create_nonce( "wcpcm-search-products" ),
		);
		wp_localize_script( 'options-custom', 'wcpcmOption' , $option_var );


		// Inline scripts from options-interface.php
		add_action( 'admin_head', array( $this, 'wcpcm_of_admin_head' ) );
	}

	function wcpcm_of_admin_head() {
		// Hook to add custom scripts
		do_action( 'wcpcm_optionsframework_custom_scripts' );
	}

	/**
	* Builds out the options panel.
	*
	* If we were using the Settings API as it was intended we would use
	* do_settings_sections here.  But as we don't want the settings wrapped in a table,
	* we'll call our own custom wcpcm_optionsframework_fields.  See options-interface.php
	* for specifics on how each individual field is generated.
	*
	* Nonces are provided using the settings_fields()
	*
	* @since 1.7.0
	*/
	function options_page() { ?>

		<div id="wcpcm_optionsframework-wrap" class="wrap">

		<?php $menu = $this->menu_settings(); ?>
		<h2><?php echo esc_html( $menu['page_title'] ); ?></h2>

	  <h2 class="nav-tab-wrapper">
	  	<?php echo WCPCM_Options_Framework_Interface::wcpcm_optionsframework_tabs(); ?>
	  </h2>

	  <?php settings_errors( 'options-framework' ); ?>

	  <div id="wcpcm_optionsframework-metabox" class="metabox-holder">
			<div id="wcpcm_optionsframework" class="postbox">
				<form action="options.php" method="post">
					<?php settings_fields( 'wcpcm_optionsframework' ); ?>
					<?php WCPCM_Options_Framework_Interface::wcpcm_optionsframework_fields(); /* Settings */ ?>
					<div id="wcpcm_optionsframework-submit">
						<input type="submit" class="button-primary" name="update" value="<?php esc_attr_e( 'Save Options', 'textdomain' ); ?>" />
						<input type="submit" class="reset-button button-secondary" name="reset" value="<?php esc_attr_e( 'Restore Defaults', 'textdomain' ); ?>" onclick="return confirm( '<?php print esc_js( __( 'Click OK to reset. Any theme settings will be lost!', 'textdomain' ) ); ?>' );" />
						<div class="clear"></div>
					</div>
				</form>
			</div> <!-- / #container -->
		</div>
		<?php do_action( 'wcpcm_optionsframework_after' ); ?>
		</div> <!-- / .wrap -->
	<?php
	}

	/**
	 * Validate Options.
	 *
	 * This runs after the submit/reset button has been clicked and
	 * validates the inputs.
	 *
	 * @uses $_POST['reset'] to restore default options
	 */
	function validate_options( $input ) {

		/*
		 * Restore Defaults.
		 *
		 * In the event that the user clicked the "Restore Defaults"
		 * button, the options defined in the theme's options.php
		 * file will be added to the option for the active theme.
		 */

		if ( isset( $_POST['reset'] ) ) {
			add_settings_error( 'options-framework', 'restore_defaults', __( 'Default options restored.', 'textdomain' ), 'updated fade' );
			return $this->get_default_values();
		}

		/*
		 * Update Settings
		 *
		 * This used to check for $_POST['update'], but has been updated
		 * to be compatible with the theme customizer introduced in WordPress 3.4
		 */

		$clean = array();
		$options = & WCPCM_Options_Framework::_wcpcm_optionsframework_options();
		foreach ( $options as $option ) {

			if ( ! isset( $option['id'] ) ) {
				continue;
			}

			if ( ! isset( $option['type'] ) ) {
				continue;
			}

			$id = preg_replace( '/[^a-zA-Z0-9._\-]/', '', strtolower( $option['id'] ) );

			// Set checkbox to false if it wasn't sent in the $_POST
			if ( 'checkbox' == $option['type'] && ! isset( $input[$id] ) ) {
				$input[$id] = false;
			}

			// Set each item in the multicheck to false if it wasn't sent in the $_POST
			if ( 'multicheck' == $option['type'] && ! isset( $input[$id] ) ) {
				foreach ( $option['options'] as $key => $value ) {
					$input[$id][$key] = false;
				}
			}

			// For a value to be submitted to database it must pass through a sanitization filter
			if ( has_filter( 'wcpcm_of_sanitize_' . $option['type'] ) ) {
				$clean[$id] = apply_filters( 'wcpcm_of_sanitize_' . $option['type'], $input[$id], $option );
			}
		}

		// Hook to run after validation
		do_action( 'wcpcm_optionsframework_after_validate', $clean );

		return $clean;
	}

	/**
	 * Display message when options have been saved
	 */

	function save_options_notice() {
		add_settings_error( 'options-framework', 'save_options', __( 'Alright Sparky! Options Saved For '.WCPCM_Plugin_Name.' .', 'catalog_mode_for_woocommerce' ), 'updated fade' );
	}

	/**
	 * Get the default values for all the theme options
	 *
	 * Get an array of all default values as set in
	 * options.php. The 'id','std' and 'type' keys need
	 * to be defined in the configuration array. In the
	 * event that these keys are not present the option
	 * will not be included in this function's output.
	 *
	 * @return array Re-keyed options configuration array.
	 *
	 */

	function get_default_values() {
		$output = array();
		$config = & WCPCM_Options_Framework::_wcpcm_optionsframework_options();
		foreach ( (array) $config as $option ) {
			if ( ! isset( $option['id'] ) ) {
				continue;
			}

			if ( ! isset( $option['std'] ) ) {
				continue;
			}

			if ( ! isset( $option['type'] ) ) {
				continue;
			}

			if ( has_filter( 'wcpcm_of_sanitize_' . $option['type'] ) ) {
				$output[$option['id']] = apply_filters( 'wcpcm_of_sanitize_' . $option['type'], $option['std'], $option );
			}
		}
		return $output;
	}

	/**
	 * Add options menu item to admin bar
	 */

	function wcpcm_optionsframework_admin_bar() {

		$menu = $this->menu_settings();

		global $wp_admin_bar;

		if ( 'menu' == $menu['mode'] ) {
			$href = admin_url( 'admin.php?page=' . $menu['menu_slug'] );
		} else {
			$href = admin_url( 'themes.php?page=' . $menu['menu_slug'] );
		}

		$args = array(
			'parent' => 'appearance',
			'id' => 'wcpcm_of_theme_options',
			'title' => $menu['menu_title'],
			'href' => $href
		);

		$wp_admin_bar->add_menu( apply_filters( 'wcpcm_optionsframework_admin_bar', $args ) );
	}

}