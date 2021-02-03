<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'YITH_YWGC_Frontend' ) ) {
	/**
	 * @class   YITH_YWGC_Frontend
	 *
	 * @since   1.0.0
	 * @author  Lorenzo Giuffrida
	 */
	class YITH_YWGC_Frontend {

		/**
		 * Single instance of the class
		 *
		 * @since 1.0.0
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @since 1.0.0
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since  1.0
		 * @author Lorenzo Giuffrida
		 */
		protected function __construct() {

            add_action( 'init', array(
                $this,
                'frontend_init'
            ) );

			/**
			 * Enqueue frontend scripts
			 */
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_script' ) );

			/**
			 * Enqueue frontend styles
			 */
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_style' ) );

			/**
			 * Show the gift card product frontend template
			 */
			add_action( 'woocommerce_single_product_summary', array( $this, 'show_gift_card_product_template' ), 60 );

			/** show element on gift card product template */
			add_action( 'yith_gift_cards_template_after_gift_card_form', array( $this, 'show_gift_card_add_to_cart_button' ), 20 );

			add_action( 'yith_ywgc_show_gift_card_amount_selection', array( $this, 'show_amount_selection' ) );

            add_action ( 'yith_ywgc_gift_card_design_section', array( $this, 'show_design_section' ) );

            add_action ( 'yith_ywgc_gift_card_delivery_info_section', array( $this,  'show_gift_card_details' ), 15 );

            //Register new endpoint to use for My Account page
            add_action( 'init', array( $this, 'yith_ywgc_add_endpoint' ) );

            //Add new query var
            add_filter( 'query_vars', array( $this, 'yith_ywgc_gift_cards_query_vars' ) );

            //Insert the new endpoint into the My Account menu
            add_filter( 'woocommerce_account_menu_items', array( $this, 'yith_ywgc_add_gift_cards_link_my_account' ) );

            //Add content to the new endpoint
            add_action( 'woocommerce_account_gift-cards_endpoint', array( $this, 'yith_ywgc_gift_cards_content' ) );

            add_action ( 'woocommerce_order_item_meta_start', array( $this, 'show_gift_card_code_on_order_item' ), 10, 3 );

            add_shortcode( 'yith_ywgc_display_gift_card_form' , array( $this,  'yith_ywgc_display_gift_card_form' ) );

            add_action( 'woocommerce_product_thumbnails', array( $this, 'yith_ywgc_display_gift_card_form_preview_below_image' ) );

            add_action( 'wp',  array( $this, 'yith_ywgc_remove_image_zoom_support' ), 100 );

        }

        /**
         * initiate the frontend
         *
         * @since 2.0.2
         */
        public function frontend_init() {

                $ywgc_cart_hook = apply_filters( 'ywgc_gift_card_code_form_cart_hook', 'woocommerce_before_cart' );
                /**
                 * Show the gift card section for entering the discount code in the cart page
                 */

                add_action( $ywgc_cart_hook, array(
                    $this,
                    'show_field_for_gift_code'
                ) );


                $ywgc_checkout_hook = apply_filters( 'ywgc_gift_card_code_form_checkout_hook', 'woocommerce_before_checkout_form' ) ;
                /**
                 * Show the gift card section for entering the discount code in the cart page
                 */
                add_action( $ywgc_checkout_hook, array(
                    $this,
                    'show_field_for_gift_code'
                ) );

        }


		public function show_amount_selection( $product ) {

			wc_get_template( 'single-product/add-to-cart/gift-card-amount-selection.php',
				array(
					'product' => $product,
					'amounts' => $product->get_amounts_to_be_shown(),
				),
				'',
				trailingslashit( YITH_YWGC_TEMPLATES_DIR ) );
		}


		/**
		 * Output the add to cart button for variations.
		 */
		public function show_gift_card_add_to_cart_button() {
			global $product;
			if ( 'gift-card' == $product->get_type() ) {

				// Load the template
				wc_get_template( 'single-product/add-to-cart/gift-card-add-to-cart.php',
					'',
					'',
					trailingslashit( YITH_YWGC_TEMPLATES_DIR ) );
			}
		}

		/**
		 * Show the gift card product frontend template
		 */
		public function show_gift_card_product_template() {
			global $product;
			if ( 'gift-card' == $product->get_type() ) {

				// Load the template
				wc_get_template( 'single-product/add-to-cart/gift-card.php',
					'',
					'',
					trailingslashit( YITH_YWGC_TEMPLATES_DIR ) );
			}
		}

		/**
		 * Show gift card field
		 */
		public function show_field_for_gift_code() {

			wc_get_template( 'checkout/form-gift-cards.php',
				array(),
				'',
				YITH_YWGC_TEMPLATES_DIR );
		}


        //Register new endpoint to use for My Account page
        public function yith_ywgc_add_endpoint() {
            add_rewrite_endpoint( 'gift-cards', EP_ROOT | EP_PAGES );
        }

        //Add new query var
        public function yith_ywgc_gift_cards_query_vars( $vars ) {
            $vars[] = 'gift-cards';

            return $vars;
        }

        //Insert the new endpoint into the My Account menu
        public function yith_ywgc_add_gift_cards_link_my_account( $items ) {


            $item_position = ( array_search( 'orders', array_keys( $items ) ) );

            $items_part1 = array_slice( $items, 0, $item_position + 1 );
            $items_part2 = array_slice( $items, $item_position );

            $items_part1['gift-cards'] = apply_filters( 'yith_wcgc_my_account_menu_item_title', esc_html__( 'Gift Cards', 'yith-woocommerce-gift-cards' ) );

            $items = array_merge( $items_part1, $items_part2 );


            return $items;
        }

        //Add content to the new endpoint
        public function yith_ywgc_gift_cards_content() {
            wc_get_template( 'myaccount/my-giftcards.php',
                array(),
                '',
                trailingslashit( YITH_YWGC_TEMPLATES_DIR ) );
        }


        /**
         * Add frontend style to gift card product page
         *
         * @since  1.0
         * @author Lorenzo Giuffrida
         */
        public function enqueue_frontend_script() {

            if ( is_product () || is_cart () || is_checkout () ||  is_account_page() || apply_filters ( 'yith_ywgc_do_eneuque_frontend_scripts', false ) ) {
                wp_register_script ( 'accounting', WC ()->plugin_url () . yit_load_js_file ( '/assets/js/accounting/accounting.js' ), array( 'jquery' ), '0.4.2' );

                $frontend_deps = array(
                    'jquery',
                    'woocommerce',
                    'jquery-ui-datepicker',
                    'accounting',
                );

                if ( is_cart () ) {
                    $frontend_deps[] = 'wc-cart';
                }
                //  register and enqueue ajax calls related script file
                wp_register_script ( "ywgc-frontend-script",
                    apply_filters( 'yith_ywgc_enqueue_script_source_path', YITH_YWGC_SCRIPT_URL . yit_load_js_file ( 'ywgc-frontend.js' ) ),
                    $frontend_deps,
                    YITH_YWGC_VERSION,
                    true );

                $default_color = defined( 'YITH_PROTEO_VERSION' ) ? get_theme_mod( 'yith_proteo_main_color_shade', '#448a85' ) : '#000000';
                $plugin_main_color = get_option( 'ywgc_plugin_main_color', $default_color);

                global $post;

                $date_format = get_option( 'ywgc_plugin_date_format_option', 'yy-mm-dd' );

                wp_localize_script ( 'ywgc-frontend-script',
                    'ywgc_data',
                    array(
                        'loader'                       => apply_filters ( 'yith_gift_cards_loader', YITH_YWGC_ASSETS_URL . '/images/loading.gif' ),
                        'ajax_url'                     => admin_url ( 'admin-ajax.php' ),
                        'currency'                     => get_woocommerce_currency_symbol (),
                        'default_gift_card_image'      => YITH_YWGC ()->get_header_image ( is_product () ? wc_get_product ( $post ) : null ),
                        'wc_ajax_url'                  => WC_AJAX::get_endpoint ( "%%endpoint%%" ),
                        'gift_card_nonce'              => wp_create_nonce ( 'apply-gift-card' ),
                        // For accounting JS
                        'currency_format'              => esc_attr ( str_replace ( array( '%1$s', '%2$s' ), array(
                            '%s',
                            '%v'
                        ), get_woocommerce_price_format () ) ),
                        'mon_decimal_point'            => wc_get_price_decimal_separator (),
                        'currency_format_num_decimals' => apply_filters ( "yith_gift_cards_format_number_of_decimals", wc_get_price_decimals () ),
                        'currency_format_symbol'       => get_woocommerce_currency_symbol (),
                        'currency_format_decimal_sep'  => esc_attr ( wc_get_price_decimal_separator () ),
                        'currency_format_thousand_sep' => esc_attr ( wc_get_price_thousand_separator () ),
                        'email_bad_format'             => esc_html__( "Please enter a valid email address", 'yith-woocommerce-gift-cards' ),
                        'mandatory_email'              => YITH_YWGC ()->mandatory_recipient(),
                        'notice_target'                => apply_filters ( 'yith_ywgc_gift_card_notice_target', 'div.ywgc_enter_code' ),
                        'date_format'   => $date_format,
                        'plugin_main_color'   => $plugin_main_color,
                    ) );

                wp_enqueue_script ( "ywgc-frontend-script" );

            }
        }

        /**
         * Add frontend style to gift card product page
         *
         * @since  1.0
         * @author Lorenzo Giuffrida
         */
        public function enqueue_frontend_style() {

            if ( is_product () || is_cart () || is_checkout () || apply_filters ( 'yith_ywgc_do_eneuque_frontend_scripts', false ) ) {
                wp_enqueue_style ( 'ywgc-frontend',
                    YITH_YWGC_ASSETS_URL . '/css/ywgc-frontend.css',
                    array(),
                    YITH_YWGC_VERSION );

                if ( apply_filters ( 'yith_ywgc_enqueue_jquery_ui_css', true ) ) {
                    wp_enqueue_style ( 'jquery-ui-css',
                        '//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css' );
                }

                wp_add_inline_style('ywgc-frontend', $this->get_custom_css());

            }

            if ( is_product () ){
                wp_enqueue_style('dashicons');
            }

        }


        public function get_custom_css(){

            $custom_css         = '';
            $default_color = defined( 'YITH_PROTEO_VERSION' ) ? get_theme_mod( 'yith_proteo_main_color_shade', '#448a85' ) : '#000000';
            $plugin_main_color = get_option( 'ywgc_plugin_main_color', $default_color);

            list($r, $g, $b) = sscanf($plugin_main_color, "#%02x%02x%02x");



            $form_button_colors_default = Array(
                'default' => '#448a85',
                'hover' => '#4ac4aa',
                'default_text' => '#ffffff',
                'hover_text' => '#ffffff'
            );

            $form_colors_default = Array(
                'default' => '#ffffff',
                'hover' => '#ffffff',
                'default_text' => '#000000',
                'hover_text' => '#000000'
            );

            $form_button_colors_array = get_option( 'ywgc_apply_gift_cards_button_colors', $form_button_colors_default );
            $form_colors_array = get_option( 'ywgc_apply_gift_cards_colors', $form_colors_default );


            $custom_css .= "
                    .ywgc_apply_gift_card_button{
                        background-color:{$form_button_colors_array['default']} !important;
                        color:{$form_button_colors_array['default_text']}!important;
                    }
                    .ywgc_apply_gift_card_button:hover{
                        background-color:{$form_button_colors_array['hover']}!important;
                        color:{$form_button_colors_array['hover_text']}!important;
                    }
                    .ywgc_enter_code{
                        background-color:{$form_colors_array['default']};
                        color:{$form_colors_array['default_text']};
                    }
                    .ywgc_enter_code:hover{
                        background-color:{$form_colors_array['default']};
                        color: {$form_colors_array['default_text']};
                    }
                    .gift-cards-list button{
                        border: 1px solid {$plugin_main_color};
                    }
                    .selected_image_parent{
                        border: 2px dashed {$plugin_main_color} !important;
                    }
                    .ywgc-preset-image.selected_image_parent:after{
                        background-color: {$plugin_main_color};
                    }
                    .ywgc-predefined-amount-button.selected_button{
                        background-color: {$plugin_main_color};
                    }
                    .ywgc-on-sale-text{
                        color:{$plugin_main_color};
                    }
                    .ywgc-choose-image.ywgc-choose-template:hover{
                        background: rgba({$r}, {$g}, {$b}, 0.9);
                    }
                    .ywgc-choose-image.ywgc-choose-template{
                        background: rgba({$r}, {$g}, {$b}, 0.8);
                    }
                    .ui-datepicker a.ui-state-active, .ui-datepicker a.ui-state-hover {
                        background:{$plugin_main_color} !important;
                        color: white;
                    }
                    .ywgc-form-preview-separator{
                        background-color: {$plugin_main_color};
                    }
                    .ywgc-form-preview-amount{
                        color: {$plugin_main_color};
                    }
                    #ywgc-manual-amount{
                        border: 1px solid {$plugin_main_color};
                    }
                    .ywgc-template-categories a:hover, 
                    .ywgc-template-categories a.ywgc-category-selected{
                        color: {$plugin_main_color};
                    }
                    .ywgc-design-list-modal .ywgc-preset-image:before {
                        background-color: {$plugin_main_color};
                    }
                    .ywgc-custom-upload-container-modal .ywgc-custom-design-modal-preview-close {
                        background-color: {$plugin_main_color};
                    }

                
           ";


            return apply_filters( 'yith_ywgc_custom_css', $custom_css );
        }


        /**
         * Show custom design area for the product
         *
         * @param WC_Product $product
         */
        public function show_design_section( $product ) {

            $args = apply_filters( 'yith_wcgc_design_presets_args',
                array(
                    'hide_empty' => 1
                )
            );

            $categories = get_terms ( YWGC_CATEGORY_TAXONOMY, $args );

            $item_categories = array();
            foreach ( $categories as $item ) {
                $object_ids = get_objects_in_term ( $item->term_id, YWGC_CATEGORY_TAXONOMY );
                foreach ( $object_ids as $object_id ) {
                    $item_categories[ $object_id ] = isset( $item_categories[ $object_id ] ) ? $item_categories[ $object_id ] . ' ywgc-category-' . $item->term_id : 'ywgc-category-' . $item->term_id;
                }
            }

            // Load the template
            wc_get_template('yith-gift-cards/gift-card-design.php',
                array(
                    'categories' => $categories,
                    'item_categories' => $item_categories,
                    'product' => $product,
                ),
                '',
                trailingslashit(YITH_YWGC_TEMPLATES_DIR));
        }

        /**
         * Show Gift Cards details
         *
         * @param WC_Product $product
         */
        public function show_gift_card_details( $product ) {

            if ( ( $product instanceof WC_Product_Gift_Card ) && $product->is_virtual () ) { //load virtual gift cards template
                wc_get_template('yith-gift-cards/gift-card-details.php',
                    array(
                        'mandatory_recipient' => apply_filters('yith_wcgc_gift_card_details_mandatory_recipient', YITH_YWGC()->mandatory_recipient()),
                    ),
                    '',
                    trailingslashit(YITH_YWGC_TEMPLATES_DIR));
            }
            else{ //load physical gift cards template
                wc_get_template ( 'yith-gift-cards/physical-gift-card-details.php',
                    array(
                        'ywgc_physical_details_mandatory'       => ( "yes" == get_option ( 'ywgc_physical_details_mandatory' ) ) ,
                    ),
                    '',
                    trailingslashit ( YITH_YWGC_TEMPLATES_DIR ) );
            }


        }

        /**
         * Show my gift cards status on myaccount page
         *
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function show_my_gift_cards_table() {
            wc_get_template ( 'myaccount/my-giftcards.php',
                '',
                '',
                trailingslashit ( YITH_YWGC_TEMPLATES_DIR ) );
        }



        /**
         * Retrieve the number of templates available
         *
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function template_design_count() {
            global $wp_version;
            if ( version_compare ( $wp_version, '4.5', '<' ) ) {
                $media_terms = get_terms ( YWGC_CATEGORY_TAXONOMY, array( 'hide_empty' => 1 ) );
            } else {
                $media_terms = get_terms ( array( 'taxonomy' => YWGC_CATEGORY_TAXONOMY, 'hide_empty' => 1, 'hierarchical' => false) );
            }
            $ids = array();
            foreach ( $media_terms as $media_term ) {
                $ids[] = $media_term->term_id;
            }

            $template_ids = array_unique ( get_objects_in_term ( $ids, YWGC_CATEGORY_TAXONOMY ) );

            return count ( $template_ids );
        }

        /**
         * Show the gift card code under the order item, in the order admin page
         *
         * @param int        $item_id
         * @param array      $item
         * @param WC_product $_product
         *
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function show_gift_card_code_on_order_item( $item_id, $item, $_product ) {

            global $theorder;
            $gift_ids = ywgc_get_order_item_giftcards ( $item_id );


            if ( empty( $gift_ids ) ) {
                return;
            }

            foreach ( $gift_ids as $gift_id ) {

                $gc = new YITH_YWGC_Gift_Card( array( 'ID' => $gift_id ) );

                if ( ! $gc->is_pre_printed () ):
                    ?>
                    <div>
                        <span class="ywgc-gift-code-label"><?php _e ( "Gift card code: ", 'yith-woocommerce-gift-cards' ); ?></span>
                        <span class="ywgc-card-code"><?php echo $gc->get_code (); ?></span>

                        <?php

                        if ( $gc->delivery_send_date ){
                            $status_class = "sent";
                            $message      = sprintf ( esc_html__( "Sent on %s", 'yith-woocommerce-gift-cards' ), $gc->get_formatted_date( $gc->delivery_send_date ) );
                        } else if ( $gc->delivery_date >= current_time ( 'timestamp' ) ) {
                            $status_class = "scheduled";
                            $message      = esc_html__( "Scheduled", 'yith-woocommerce-gift-cards' );
                        } else if ( $gc->has_been_sent() == '' ) {
                            $status_class = "not-sent";
                            $message      = esc_html__( "Not yet sent", 'yith-woocommerce-gift-cards' );
                        }else{
                            $status_class = "failed";
                            $message      = esc_html__( "Failed", 'yith-woocommerce-gift-cards' );
                        }
                        ?>

                        <div>
                            <span><?php echo sprintf ( esc_html__( "Recipient: %s", 'yith-woocommerce-gift-cards' ), $gc->recipient ); ?></span>
                        </div>
                        <div>
                            <?php if( $gc->delivery_date != '' ): ?>
                                <span><?php echo sprintf ( esc_html__( "Delivery date: %s", 'yith-woocommerce-gift-cards' ), $gc->get_formatted_date( $gc->delivery_date ) ); ?></span>
                                <br>
                            <?php endif; ?>
                            <span class="ywgc-delivery-status <?php echo $status_class; ?>"><?php echo $message; ?></span>

                        </div>
                        <?php


                        ?>
                    </div>
                <?php endif;
            }
        }

        /**
         * Shortcode to include the necessary hook to display the gift card form
         */
        function yith_ywgc_display_gift_card_form( $atts, $content ){

            global $product;

            if ( is_object($product) && $product instanceof WC_Product_Gift_Card && 'gift-card' == $product->get_type() ) {

                ob_start();

                wc_get_template( 'single-product/add-to-cart/gift-card.php',
                    '',
                    '',
                    trailingslashit( YITH_YWGC_TEMPLATES_DIR ) );

                $content = ob_get_clean();

            }
            return $content;
        }



        /**
         * Display a preview of the form under the gift card image
         */
        function yith_ywgc_display_gift_card_form_preview_below_image( ) {

            if (is_product()) {

                $product = wc_get_product(get_the_ID());

                if (is_object($product) && $product->is_type('gift-card') && $product->is_virtual()) {

                    wc_get_template('single-product/form-preview.php',
                        array(
                            'product' => $product,
                        ),
                        '',
                        trailingslashit(YITH_YWGC_TEMPLATES_DIR));
                }
            }
        }


        /**
         * Remove zoom in gift card product pages
         */
        function yith_ywgc_remove_image_zoom_support( ) {

            if ( is_product() ){

                $product = wc_get_product( get_the_ID() );

                if ( is_object( $product ) && $product->is_type( 'gift-card' ) ) {
                    remove_theme_support( 'wc-product-gallery-zoom' );
                    remove_theme_support( 'wc-product-gallery-lightbox' );
                }
            }
        }

	}
}
