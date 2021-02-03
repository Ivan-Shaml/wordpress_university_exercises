<?php
if ( ! defined ( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


if ( ! class_exists ( 'YITH_YWGC_Backend' ) ) {

	/**
	 *
	 * @class   YITH_YWGC_Backend
	 *
	 * @since   1.0.0
	 * @author  Lorenzo Giuffrida
	 */
	class YITH_YWGC_Backend {

		const YWGC_GIFT_CARD_LAST_VIEWED_ID = 'ywgc_last_viewed';

		/**
		 * Single instance of the class
		 *
		 * @since 1.0.0
		 */
        protected static $instance;

        /**
         * race condition active
         *
         * @since 2.0.3
         */
        protected static $rc_active;

		/**
		 * Returns single instance of the class
		 *
		 * @since 1.0.0
		 */
		public static function get_instance() {
			if ( is_null ( self::$instance ) ) {
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

			/**
			 * Enqueue scripts and styles
			 */
			add_action ( 'admin_enqueue_scripts', array( $this, 'enqueue_backend_files' ) );

			/**
			 * Add the "Gift card" type to product type list
			 */
			add_filter ( 'product_type_selector', array($this, 'add_gift_card_product_type') );

			/**
			 * * Save gift card data when a product of type "gift card" is saved
			 */
			add_action ( 'save_post', array($this, 'save_gift_card'), 1, 2 );

			/**
			 * Ajax call for adding and removing gift card amounts on product edit page
			 */
			add_action ( 'wp_ajax_add_gift_card_amount', array($this, 'add_gift_card_amount_callback') );
			add_action ( 'wp_ajax_remove_gift_card_amount', array($this, 'remove_gift_card_amount_callback') );

			/**
			 * Hide some item meta from product edit page
			 */
			add_filter ( 'woocommerce_hidden_order_itemmeta', array($this, 'hide_item_meta') );

			if ( version_compare ( WC ()->version, '2.6.0', '<' ) ) {

				/**
				 * Append gift card amount generation controls to general tab of product page, below the SKU element
				 */
				add_action ( 'woocommerce_product_options_sku', array($this, 'show_gift_card_product_settings') );

			} else {
				/**
				 * Append gift card amount generation controls to general tab on product page
				 */
				add_action ( 'woocommerce_product_options_general_product_data', array($this, 'show_gift_card_product_settings') );
			}

			/**
			 * Generate a valid card number for every gift card product in the order
			 */
			add_action ( 'woocommerce_order_status_changed', array($this, 'order_status_changed'), 10, 3 );

			add_action ( 'woocommerce_before_order_itemmeta', array($this, 'show_gift_card_code_on_order_item'), 10, 3 );

			/**
			 * Set the CSS class 'show_if_gift-card in tax section
			 */
			add_action ( 'woocommerce_product_options_general_product_data', array($this, 'show_tax_class_for_gift_cards') );

            /**
             * Custom condition to create gift card on cash on delivery only on complete status
             */
            add_filter ( 'ywgc_custom_condition_to_create_gift_card', array($this, 'ywgc_custom_condition_to_create_gift_card_call_back'), 10, 2 );

            /**
             * Set the CSS class 'show_if_gift-card in 'sold indidually' section
             */
            add_action( 'woocommerce_product_options_inventory_product_data', array($this, 'show_sold_individually_for_gift_cards') );

            /**
             * manage CSS class for the gift cards table rows
             */
            add_filter( 'post_class', array( $this, 'add_cpt_table_class' ), 10, 3 );

            add_action( 'init', array( $this, 'redirect_gift_cards_link' ) );

            add_action( 'load-upload.php', array( $this, 'set_gift_card_category_to_media' ) );

            add_action( 'edited_term_taxonomy', array( $this, 'update_taxonomy_count' ), 10, 2 );

            /*
             * Save additional product attribute when a gift card product is saved
             */
            add_action( 'yith_gift_cards_after_product_save', array($this, 'save_gift_card_product') );

            /**
             * Show inventory tab in product tabs
             */
            add_filter( 'woocommerce_product_data_tabs', array($this, 'show_inventory_tab') );

            add_action( 'yith_ywgc_product_settings_after_amount_list', array($this, 'show_advanced_product_settings') );

            /**
             * Show gift cards code and amount in order's totals section, in edit order page
             */
            add_action( 'woocommerce_admin_order_totals_after_tax', array($this, 'show_gift_cards_total_before_order_totals') );

            /**
             * Add filters on the Gift Card Post Type page
             */
            add_filter( 'views_edit-gift_card', array( $this, 'add_gift_cards_filters' ) );
            add_action( 'pre_get_posts', array( $this, 'filter_gift_card_page_query' ) );

            /*
             * Filter display order item meta key to show
             */
            add_filter( 'woocommerce_order_item_display_meta_key',array($this,'show_as_string_order_item_meta_key'),10,1 );

            /*
             * Filter display order item meta value to show
             */
            add_filter( 'woocommerce_order_item_display_meta_value', array( $this,'show_formatted_date' ),10,3 );


            add_action('woocommerce_order_status_changed', array($this,'update_gift_card_amount_on_order_status_change'),10,4);

            /*
             * Recalculate order totals on save order items (in order to show always the correct total for the order)
             */
            add_action('woocommerce_saved_order_items', array($this,'update_totals_on_save_order_items'),10,2);

            add_action( 'add_meta_boxes' ,  array( $this, 'ywgc_remove_product_meta_boxes' ), 40 );

            add_action( 'save_post', array( $this, 'set_gift_card_category_to_product' ) );


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

					?>
					<div>
					<span class="ywgc-gift-code-label"><?php _e ( "Gift card code: ", 'yith-woocommerce-gift-cards' ); ?></span>
						<a href="<?php echo admin_url ( 'edit.php?s=' . $gc->get_code () . '&post_type=gift_card&mode=list' ); ?>"
						   class="ywgc-card-code"><?php echo $gc->get_code (); ?></a>
					</div>
                <?php
			}
		}


		/**
		 * Enqueue scripts on administration comment page
		 *
		 * @param $hook
		 */
		function enqueue_backend_files( $hook ) {
			global $post_type;

			$screen = get_current_screen ();

			//  Enqueue style and script for the edit-gift_card screen id
			if ( "edit-gift_card" == $screen->id ) {

				//  When viewing the gift card page, store the max id so all new gift cards will be notified next time
				global $wpdb;
				$last_id = $wpdb->get_var ( $wpdb->prepare ( "SELECT max(id) FROM {$wpdb->prefix}posts WHERE post_type = %s", YWGC_CUSTOM_POST_TYPE_NAME ) );
				update_option ( self::YWGC_GIFT_CARD_LAST_VIEWED_ID, $last_id );
			}

			if ( ( 'product' == $post_type ) || ( 'gift_card' == $post_type ) || ( 'shop_order' == $post_type ) ||  isset( $_REQUEST[ 'page' ] ) && $_REQUEST[ 'page' ] == 'yith_woocommerce_gift_cards_panel' ) {

				//  Add style and scripts
				wp_enqueue_style ( 'ywgc-backend-css',
					YITH_YWGC_ASSETS_URL . '/css/ywgc-backend.css',
					array(),
					YITH_YWGC_VERSION );
				
				wp_register_script ( "ywgc-backend",

					YITH_YWGC_SCRIPT_URL . yit_load_js_file ( 'ywgc-backend.js' ),
					array(
						'jquery',
						'jquery-blockui',
					),
					YITH_YWGC_VERSION,
					true );

                $date_format =get_option( 'ywgc_plugin_date_format_option', 'yy-mm-dd' );

				wp_localize_script ( 'ywgc-backend',
					'ywgc_data', array(
						'loader'            => apply_filters ( 'yith_gift_cards_loader', YITH_YWGC_ASSETS_URL . '/images/loading.gif' ),
						'ajax_url'          => admin_url ( 'admin-ajax.php' ),
						'choose_image_text' => esc_html__( 'Choose Image', 'yith-woocommerce-gift-cards' ),
                        'date_format'   => $date_format,
					)
				);

				wp_enqueue_script ( "ywgc-backend" );
			}

			if ( "upload" == $screen->id ) {

				wp_register_script ( "ywgc-categories",
					YITH_YWGC_SCRIPT_URL . yit_load_js_file ( 'ywgc-categories.js' ),
					array(
						'jquery',
						'jquery-blockui',
					),
					YITH_YWGC_VERSION,
					true );

				$categories1_id = 'categories1_id';
				$categories2_id = 'categories2_id';

				wp_localize_script ( 'ywgc-categories', 'ywgc_data', array(
					'loader'                => apply_filters ( 'yith_gift_cards_loader', YITH_YWGC_ASSETS_URL . '/images/loading.gif' ),
					'ajax_url'              => admin_url ( 'admin-ajax.php' ),
					'set_category_action'   => esc_html__( "Set gift card category", 'yith-woocommerce-gift-cards' ),
					'unset_category_action' => esc_html__( "Unset gift card category", 'yith-woocommerce-gift-cards' ),
					'categories1'           => $this->get_category_select ( $categories1_id ),
					'categories1_id'        => $categories1_id,
					'categories2'           => $this->get_category_select ( $categories2_id ),
					'categories2_id'        => $categories2_id,
				) );

				wp_enqueue_script ( "ywgc-categories" );
			}


            if ( "edit-giftcard-category" == $screen->id ) {

                wp_enqueue_media();
                wp_register_script ( "ywgc-media-button",
                    YITH_YWGC_SCRIPT_URL . yit_load_js_file ( 'ywgc-media-button.js' ),
                    array(
                        'jquery',
                    ),
                    YITH_YWGC_VERSION,
                    true );

                wp_localize_script( 'ywgc-media-button', 'ywgc_data', array(
                        'upload_file_frame_title' => esc_html__( 'Manage the Media library', 'yith-woocommerce-gift-cards' ),
                        'upload_file_frame_button' => esc_html__( 'Done', 'yith-woocommerce-gift-cards' )
                ) );

                wp_enqueue_script ( "ywgc-media-button" );
            }


		}

		public function get_category_select( $select_id ) {
			$media_terms = get_terms ( YWGC_CATEGORY_TAXONOMY, 'hide_empty=0' );

			$select = '<select id="' . $select_id . '" name="' . $select_id . '">';
			foreach ( $media_terms as $entry ) {
				$select .= '<option value="' . $entry->term_id . '">' . $entry->name . '</option>';
			}
			$select .= '</select>';

			return $select;

		}

		/**
		 * Add the "Gift card" type to product type list
		 *
		 * @param array $types current type array
		 *
		 * @return mixed
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function add_gift_card_product_type( $types ) {
			if ( YITH_YWGC ()->current_user_can_create () ) {
				$types[ YWGC_GIFT_CARD_PRODUCT_TYPE ] = esc_html__( "Gift card", 'yith-woocommerce-gift-cards' );
			}

			return $types;
		}

		/**
		 * Save gift card additional data
		 *
		 * @param $product_id
		 */
		public function save_gift_card_data( $product_id ) {

			$product = new WC_Product_Gift_Card( $product_id );

			/**
			 * Save custom gift card header image, if exists
			 */
			if ( isset( $_REQUEST['ywgc_product_image_id'] ) ) {
				if ( intval ( $_REQUEST['ywgc_product_image_id'] ) ) {

					$product->set_header_image ( $_REQUEST['ywgc_product_image_id'] );
				} else {

					$product->unset_header_image ();
				}
			}


			/**
			 * Save gift card amounts
			 */
			$amounts = isset( $_POST["gift-card-amounts"] ) ? $_POST["gift-card-amounts"] : array();
			$amounts = !empty( $amounts ) ? $amounts : ( isset( $_POST["gift_card-amount"] ) && $_POST["gift_card-amount"] ? array( $_POST["gift_card-amount"] ) : array() );

			$product->save_amounts ( $amounts );

			/**
			 * Save gift card settings about template design
			 */
			if ( isset( $_POST['template-design-mode'] ) ) {
				$product->set_design_status ( $_POST['template-design-mode'] );
			}
		}


		/**
		 * Save gift card amount when a product is saved
		 *
		 * @param $post_id int
		 * @param $post    object
		 *
		 * @return mixed
		 */
		function save_gift_card( $post_id, $post ) {

			$product = wc_get_product ( $post_id );

			if ( null == $product ) {
				return;
			}

			if ( ! isset( $_POST["product-type"] ) || ( YWGC_GIFT_CARD_PRODUCT_TYPE != $_POST["product-type"] ) ) {

				return;
			}

			// verify this is not an auto save routine.
			if ( defined ( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			/**
			 * Update gift card amounts
			 */
			$this->save_gift_card_data ( $post_id );


			do_action ( 'yith_gift_cards_after_product_save', $post_id, $post, $product );
		}


		/**
		 * Add a new amount to a gift card prdduct
		 *
		 * @since  1.0
		 * @author Lorenzo Giuffrida
		 */
		public function add_gift_card_amount_callback() {

			$amount = wc_format_decimal ( $_POST['amount'] );

			if ( ! is_numeric( $amount ) )
				return;

			$product_id = intval ( $_POST['product_id'] );
			$gift       = new WC_Product_Gift_Card( $product_id );
			$res        = false;

			if ( $gift->exists () ) {
				$res = $gift->add_amount ( $amount );
			}

			wp_send_json (
				array(
					"code"  => $res ? 1 : 0,
					"value" => $this->gift_card_amount_list_html ( $product_id )
				) );
		}

		/**
		 * Remove amount to a gift card prdduct
		 *
		 * @since  1.0
		 * @author Lorenzo Giuffrida
		 */
		public function remove_gift_card_amount_callback() {
			$amount     = wc_format_decimal ( $_POST['amount'] );
			$product_id = intval ( $_POST['product_id'] );

			$gift = new WC_Product_Gift_Card( $product_id );
			if ( $gift->exists () ) {
				$gift->remove_amount ( $amount );
			}

			wp_send_json ( array( "code" => '1' ) );
		}

		/**
		 * Retrieve the html content that shows the gift card amounts list
		 *
		 * @param $product_id int gift card product id
		 *
		 * @return string
		 */
		private function gift_card_amount_list_html( $product_id ) {

			ob_start ();
			$this->show_gift_card_amount_list ( $product_id );
			$html = ob_get_contents ();
			ob_end_clean ();

			return $html;
		}


		/**
		 * Hide some item meta from order edit page
		 */
		public function hide_item_meta( $args ) {
			$args[] = YWGC_META_GIFT_CARD_POST_ID;

			return $args;
		}

		/**
		 * Show controls on backend product page to let create the gift card price
		 */
		public function show_gift_card_product_settings() {

			if ( ! YITH_YWGC ()->current_user_can_create () ) {
				return;
			}

			global $post, $thepostid;
			?>
			<div class="options_group show_if_gift-card">
				<p class="form-field">
					<label for="gift_card-amount"><?php _e ( "Gift card amount", 'yith-woocommerce-gift-cards' ); ?></label>
					<span class="wrap add-new-amount-section">
                    <input type="text" id="gift_card-amount" name="gift_card-amount" class="short wc_input_price" style=""
                           placeholder="">
                    <a href="#" class="add-new-amount"><?php _e ( "Add", 'yith-woocommerce-gift-cards' ); ?></a>
                    </span>
				</p>

				<?php
				$this->show_gift_card_amount_list ( $thepostid );
				do_action ( 'yith_ywgc_product_settings_after_amount_list', $thepostid );

				?>
			</div>
			<?php
		}

		/**
		 * Show the gift card amounts list
		 *
		 * @param $product_id int gift card product id
		 */
		private function show_gift_card_amount_list( $product_id ) {

			$gift_card = new WC_Product_Gift_Card( $product_id );
			if ( ! $gift_card->exists () ) {
				return;
			}
			$amounts = $gift_card->get_product_amounts ();

			?>

			<p class="form-field _gift_card_amount_field">
				<?php if ( $amounts ): ?>
					<?php foreach ( $amounts as $amount ) : ?>
						<span class="variation-amount"><?php echo wc_price ( $amount ); ?>
							<input type="hidden" name="gift-card-amounts[]" value="<?php _e ( $amount ); ?>">
                        <a href="#" class="remove-amount"></a></span>
					<?php endforeach; ?>
				<?php else: ?>
					<span
						class="no-amounts"><?php _e ( "You haven't configured any gift card yet", 'yith-woocommerce-gift-cards' ); ?></span>
				<?php endif; ?>
			</p>
			<?php
		}


		/**
		 * When the order is completed, generate a card number for every gift card product
		 *
		 * @param int|WC_Order $order      The order which status is changing
		 * @param string       $old_status Current order status
		 * @param string       $new_status New order status
		 *
		 */
		public function order_status_changed( $order, $old_status, $new_status ) {

			if ( is_numeric ( $order ) ) {
				$order = wc_get_order ( $order );
			}

			$allowed_status = apply_filters ( 'yith_ywgc_generate_gift_card_on_order_status',
				array( 'completed', 'processing' ) );

			if ( in_array ( $new_status, $allowed_status ) ) {
				$this->generate_gift_card_for_order ( $order );

				$used_gift_cards = yit_get_prop($order, '_ywgc_applied_gift_cards', true);

				if ( isset($used_gift_cards) && ! empty($used_gift_cards) ) {
                    $checkout_instance = YITH_YWGC_Cart_Checkout::get_instance();
				    foreach ($used_gift_cards as $gift_card_code => $value) {
                        $gift_card = YITH_YWGC()->get_gift_card_by_code( $gift_card_code );
                    }
                }

			} elseif ( 'refunded' == $new_status ) {
				$this->change_gift_cards_status_on_order ( $order,'nothing' );
			} elseif ( 'cancelled' == $new_status ) {
				$this->change_gift_cards_status_on_order ( $order, 'nothing' );
			}
		}

		/**
		 * Generate the gift card code, if not yet generated
		 *
		 * @param WC_Order $order
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function generate_gift_card_for_order( $order ) {
			if ( is_numeric ( $order ) ) {
				$order = new WC_Order( $order );
			}

			if ( apply_filters ( 'yith_gift_cards_generate_on_order_completed', true, $order ) ) {

				$this->create_gift_cards_for_order ( $order );
			}
		}

        /**
         * Custom condition
         *
         * @param WC_Order $order
         * @return boolean
         *
         * @author Daniel Sanchez <daniel.sanchez@yithemes.com>
         * @since  2.0.6
         */
        public function ywgc_custom_condition_to_create_gift_card_call_back( $cond, $order ) {

            $gateway = wc_get_payment_gateway_by_order( $order );
            if ( $order->get_status() == 'processing' && is_object($gateway) && $gateway instanceof WC_Gateway_COD )
                return false;

            return true;

        }

		/**
		 * Create the gift cards for the order
		 *
		 * @param WC_Order $order
		 */
		public function create_gift_cards_for_order( $order ) {

            if ( ! apply_filters( 'ywgc_custom_condition_to_create_gift_card', true, $order ) )
                return;


			foreach ( $order->get_items ( 'line_item' ) as $order_item_id => $order_item_data ) {

				$product_id = $order_item_data["product_id"];
				$product    = wc_get_product ( $product_id );

				//  skip all item that belong to product other than the gift card type
				if ( ! $product instanceof WC_Product_Gift_Card ) {
					continue;
				}

				//  Check if current product, of type gift card, has a previous gift card
				// code before creating another
                if ( $gift_ids = ywgc_get_order_item_giftcards ( $order_item_id ) ) {
                    continue;
                }

                if ( ! apply_filters ( 'yith_ywgc_create_gift_card_for_order_item', true, $order, $order_item_id, $order_item_data ) ) {
                    continue;
                }

                $order_id = yit_get_order_id ( $order );

                $line_subtotal     = apply_filters ( 'yith_ywgc_line_subtotal', $order_item_data["line_subtotal"], $order_item_data, $order_id, $order_item_id );
                $line_subtotal_tax = apply_filters ( 'yith_ywgc_line_subtotal_tax', $order_item_data["line_subtotal_tax"], $order_item_data, $order_id, $order_item_id );

                //  Generate as many gift card code as the quantity bought
                $quantity      = $order_item_data["qty"];
                $single_amount = (float) ( $line_subtotal / $quantity );
                $single_tax    = (float) ( $line_subtotal_tax / $quantity );

                $new_ids = array();

                $order_currency = version_compare( WC()->version, '3.0', '<' ) ? $order->get_order_currency() : $order->get_currency();

                $product_id       = wc_get_order_item_meta ( $order_item_id, '_ywgc_product_id' );
                $amount           = wc_get_order_item_meta ( $order_item_id, '_ywgc_amount' );
                $is_digital       = wc_get_order_item_meta ( $order_item_id, '_ywgc_is_digital' );

                $is_postdated = false;

				if ( $is_digital ) {
					$recipients        = wc_get_order_item_meta ( $order_item_id, '_ywgc_recipients' );
					$recipient_count   = count ( (array)$recipients );
					$sender            = wc_get_order_item_meta ( $order_item_id, '_ywgc_sender_name' );
					$recipient_name    = wc_get_order_item_meta ( $order_item_id, '_ywgc_recipient_name' );
					$message           = wc_get_order_item_meta ( $order_item_id, '_ywgc_message' );
					$has_custom_design = wc_get_order_item_meta ( $order_item_id, '_ywgc_has_custom_design' );
					$design_type       = wc_get_order_item_meta ( $order_item_id, '_ywgc_design_type' );
					$postdated         = apply_filters('ywgc_postdated_by_default', wc_get_order_item_meta ( $order_item_id, '_ywgc_postdated' ));
					
                    $is_postdated = true == apply_filters('ywgc_is_postdated_delivery_date_by_default', wc_get_order_item_meta ( $order_item_id, '_ywgc_postdated', true ));
                    if ( $is_postdated ) {
                        $delivery_date = wc_get_order_item_meta ( $order_item_id, '_ywgc_delivery_date', true );
                    }
				}

				for ( $i = 0; $i < $quantity; $i ++ ) {

					//  Generate a gift card post type and save it
					$gift_card = new YITH_YWGC_Gift_Card();

					$gift_card->product_id       = $product_id;
					$gift_card->order_id         = $order_id;
					$gift_card->is_digital       = $is_digital;

					if ( $gift_card->is_digital ) {
						$gift_card->sender_name        = $sender;
						$gift_card->recipient_name     = $recipient_name;
						$gift_card->message            = $message;
						$gift_card->postdated_delivery = $is_postdated;
						if ( $is_postdated ) {
							$gift_card->delivery_date = $delivery_date;
						}

						$gift_card->has_custom_design = $has_custom_design;
						$gift_card->design_type       = $design_type;

						if ( $has_custom_design ) {
							$gift_card->design = wc_get_order_item_meta ( $order_item_id, '_ywgc_design' );
						}

						$gift_card->postdated_delivery = $postdated;
						if ( $postdated ) {
							$gift_card->delivery_date = $delivery_date;
						}

						/**
						 * If the user entered several recipient email addresses, one gift card
						 * for every recipient will be created and it will be the unique recipient for
						 * that email. If only one, or none if allowed, recipient email address was entered
						 * then create '$quantity' specular gift cards
						 */
						if ( ( $recipient_count == 1 ) && ! empty( $recipients[0] ) ) {
							$gift_card->recipient = $recipients[0];
						} elseif ( ( $recipient_count > 1 ) && ! empty( $recipients[ $i ] ) ) {
							$gift_card->recipient = $recipients[ $i ];
						} else {
							/**
							 * Set the customer as the recipient of the gift card
							 *
							 */
							$gift_card->recipient = apply_filters ( 'yith_ywgc_set_default_gift_card_recipient', yit_get_prop($order, 'billing_email') );
						}
					}

						$attempts = 100;
						do {
							$code       = apply_filters( 'yith_wcgc_generated_code', YITH_YWGC ()->generate_gift_card_code(), $order, $gift_card );
							$check_code = get_page_by_title ( $code, OBJECT, YWGC_CUSTOM_POST_TYPE_NAME );

							if ( ! $check_code ) {
								$gift_card->gift_card_number = $code;
								break;
							}
							$attempts --;
						} while ( $attempts > 0 );

						if ( ! $attempts ) {
							//  Unable to find a unique code, the gift card need a manual code entered
							$gift_card->set_as_code_not_valid ();
						}


					$gift_card->total_amount = $single_amount + $single_tax;

                    $on_sale = get_post_meta( $gift_card->product_id, '_ywgc_sale_discount_value', true );

                    if ( $on_sale ) {
                        $gift_card->total_amount =  wc_get_order_item_meta( $order_item_id, '_ywgc_amount_without_discount', true);
                    }

					$gift_card->update_balance ( $gift_card->total_amount );
					$gift_card->version  = YITH_YWGC_VERSION;
					$gift_card->currency = $order_currency;

                    $gift_card->expiration =  0;

                    do_action( 'yith_ywgc_before_gift_card_generation_save', $gift_card );

                    $gift_card->save ();

					do_action( 'yith_ywgc_after_gift_card_generation_save', $gift_card );

					//  Save the gift card id
					$new_ids[] = $gift_card->ID;

					//  ...and send it now if it's not postdated
					if ( (! $is_postdated && apply_filters( 'ywgc_send_gift_card_code_by_default', true, $gift_card) )|| apply_filters('yith_wcgc_send_now_gift_card_to_custom_recipient',false,$gift_card ) ) {

						YITH_YWGC_Emails::get_instance ()->send_gift_card_email ( $gift_card );
					}
				}

				// save gift card Post ids on order item
				ywgc_set_order_item_giftcards ( $order_item_id, $new_ids );

			}
            if ( apply_filters( 'ywgc_apply_race_condition', false ) )
                $this->end_race_condition( $order->get_id() );

		}


		/**
		 * The order is set to completed
		 *
		 * @param WC_Order $order
		 * @param string   $action
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function change_gift_cards_status_on_order( $order, $action ) {

			if ( 'nothing' == $action ) {
				return;
			}

			foreach ( $order->get_items () as $item_id => $item ) {
				$ids = ywgc_get_order_item_giftcards ( $item_id );

				if ( $ids ) {
					foreach ( $ids as $gift_id ) {

						$gift_card = new YITH_YWGC_Gift_Card( array( 'ID' => $gift_id ) );

						if ( ! $gift_card->exists () ) {
							continue;
						}

						if ( 'dismiss' == $action ) {
							$gift_card->set_dismissed_status ();
						} elseif ( 'disable' == $action ) {

							$gift_card->set_enabled_status ( false );
						}
					}
				}
			}
		}

		public function show_tax_class_for_gift_cards() {
			echo '<script>
                jQuery("select#_tax_status").closest(".options_group").addClass("show_if_gift-card");
            </script>';
		}


        /**
         * Show gift cards code and amount in order's totals section, in edit order page
         *
         * @param int $order_id
         */
        public function show_gift_cards_total_before_order_totals( $order_id ) {

            $order            = wc_get_order( $order_id );
            $order_gift_cards = yit_get_prop( $order, YITH_YWGC_Cart_Checkout::ORDER_GIFT_CARDS, true );
            $currency         = version_compare( WC()->version, '3.0', '<' ) ? $order->get_order_currency() : $order->get_currency();

            if ( $order_gift_cards ) :
                foreach ( $order_gift_cards as $code => $amount ): ?>
                    <?php $amount = apply_filters('ywgc_gift_card_amount_order_total_item', $amount, YITH_YWGC()->get_gift_card_by_code( $code ) ); ?>
                    <tr>
                        <td class="label"><?php _e( 'Gift card: ' . $code, 'yith-woocommerce-gift-cards' ); ?>:</td>
                        <td width="1%"></td>
                        <td class="total">
                            <?php echo wc_price( $amount, array( 'currency' => $currency ) ); ?>
                        </td>
                    </tr>
                <?php endforeach;
            endif;
        }

        /**
         * Show inventory section for gift card products
         *
         * @param array $tabs
         *
         * @return mixed
         */
        public function show_inventory_tab( $tabs ) {
            if ( isset( $tabs['inventory'] ) ) {

                array_push( $tabs['inventory']['class'], 'show_if_gift-card' );
            }

            return $tabs;

        }

        /**
         * Save additional product attribute when a gift card product is saved
         *
         * @param int $post_id current product id
         */
        public function save_gift_card_product( $post_id ) {

            if ( isset( $_POST["gift_card-sale-discount"] ) ) {
                update_post_meta( $post_id, '_ywgc_sale_discount_value', $_POST["gift_card-sale-discount"] );
            }

            if ( isset( $_POST["gift_card-sale-discount-text"] ) ) {
                update_post_meta( $post_id, '_ywgc_sale_discount_text', $_POST["gift_card-sale-discount-text"] );
            }

            if ( isset( $_POST["gift-card-expiration-date"] ) ) {

                $date_format = apply_filters('yith_wcgc_date_format','Y-m-d');

                $expiration_date = is_string($_POST["gift-card-expiration-date"]) ? strtotime($_POST["gift-card-expiration-date"]) : $_POST["gift-card-expiration-date"];

                $expiration_date_formatted = !empty( $expiration_date ) ? date_i18n( $date_format, $expiration_date ) : '';

                update_post_meta( $post_id, '_ywgc_expiration', $expiration_date );

                update_post_meta( $post_id, '_ywgc_expiration_date', $expiration_date_formatted );
            }


        }


        /**
         * Fix the taxonomy count of items
         *
         * @param $term_id
         * @param $taxonomy_name
         *
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function update_taxonomy_count( $term_id, $taxonomy_name ) {
            //  Update the count of terms for attachment taxonomy
            if ( YWGC_CATEGORY_TAXONOMY != $taxonomy_name ) {
                return;
            }

            //  update now
            global $wpdb;
            $count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts p1 WHERE p1.ID = $wpdb->term_relationships.object_id AND ( post_status = 'publish' OR ( post_status = 'inherit' AND (post_parent = 0 OR (post_parent > 0 AND ( SELECT post_status FROM $wpdb->posts WHERE ID = p1.post_parent ) = 'publish' ) ) ) ) AND post_type = 'attachment' AND term_taxonomy_id = %d", $term_id ) );

            $wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term_id ) );
        }


        public function set_gift_card_category_to_media() {

            //  Skip all request without an action
            if ( ! isset( $_REQUEST['action'] ) && ! isset( $_REQUEST['action2'] ) ) {
                return;
            }

            //  Skip all request without a valid action
            if ( ( '-1' == $_REQUEST['action'] ) && ( '-1' == $_REQUEST['action2'] ) ) {
                return;
            }

            $action = '-1' != $_REQUEST['action'] ? $_REQUEST['action'] : $_REQUEST['action2'];

            //  Skip all request that do not belong to gift card categories
            if ( ( 'ywgc-set-category' != $action ) && ( 'ywgc-unset-category' != $action ) ) {
                return;
            }

            //  Skip all request without a media list
            if ( ! isset( $_REQUEST['media'] ) ) {
                return;
            }

            $media_ids = $_REQUEST['media'];

            //  Check if the request if for set or unset the selected category to the selected media
            $action_set_category = ( 'ywgc-set-category' == $action ) ? true : false;

            //  Retrieve the category to be applied to the selected media
            $category_id = '-1' != $_REQUEST['action'] ? intval( $_REQUEST['categories1_id'] ) : intval( $_REQUEST['categories2_id'] );

            foreach ( $media_ids as $media_id ) {

                // Check whether this user can edit this post
                //if ( ! current_user_can ( 'edit_post', $media_id ) ) continue;

                if ( $action_set_category ) {
                    $result = wp_set_object_terms( $media_id, $category_id, YWGC_CATEGORY_TAXONOMY, true );
                } else {
                    $result = wp_remove_object_terms( $media_id, $category_id, YWGC_CATEGORY_TAXONOMY );
                }

                if ( is_wp_error( $result ) ) {
                    return $result;
                }
            }
        }

        /**
         * manage CSS class for the gift cards table rows
         *
         * @param array  $classes
         * @param string $class
         * @param int    $post_id
         *
         * @return array|mixed|void
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function add_cpt_table_class( $classes, $class, $post_id ) {

            if ( YWGC_CUSTOM_POST_TYPE_NAME != get_post_type( $post_id ) ) {
                return $classes;
            }

            $gift_card = new YITH_YWGC_Gift_Card( array( 'ID' => $post_id ) );

            if ( ! $gift_card->exists() ) {
                return $class;
            }

            $classes[] = $gift_card->status;

            return apply_filters( 'yith_gift_cards_table_class', $classes, $post_id );
        }


        /**
         * Make some redirect based on the current action being performed
         *
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function redirect_gift_cards_link() {

            /**
             * Check if the user ask for retrying sending the gift card email that are not shipped yet
             */
            if ( isset( $_GET[ YWGC_ACTION_RETRY_SENDING ] ) ) {

                $gift_card_id = $_GET['id'];

                YITH_YWGC_Emails::get_instance()->send_gift_card_email( $gift_card_id, false );
                $redirect_url = remove_query_arg( array( YWGC_ACTION_RETRY_SENDING, 'id' ) );

                wp_redirect( $redirect_url );
                exit;
            }

            /**
             * Check if the user ask for enabling/disabling a specific gift cards
             */
            if ( isset( $_GET[ YWGC_ACTION_ENABLE_CARD ] ) || isset( $_GET[ YWGC_ACTION_DISABLE_CARD ] ) ) {

                $gift_card_id = $_GET['id'];
                $enabled      = isset( $_GET[ YWGC_ACTION_ENABLE_CARD ] );

                $gift_card = new YITH_YWGC_Gift_Card( array( 'ID' => $gift_card_id ) );

                if ( ! $gift_card->is_dismissed() ) {

                    $current_status = $gift_card->is_enabled();

                    if ( $current_status != $enabled ) {

                        $gift_card->set_enabled_status( $enabled );
                        do_action( 'yith_gift_cards_status_changed', $gift_card, $enabled );
                    }

                    wp_redirect( remove_query_arg( array(
                        YWGC_ACTION_ENABLE_CARD,
                        YWGC_ACTION_DISABLE_CARD,
                        'id'
                    ) ) );
                    die();
                }
            }

            if ( ! isset( $_GET["post_type"] ) || ! isset( $_GET["s"] ) ) {
                return;
            }


            if ( 'shop_coupon' != ( $_GET["post_type"] ) ) {
                return;
            }

            if ( preg_match( "/(\w{4}-\w{4}-\w{4}-\w{4})(.*)/i", $_GET["s"], $matches ) ) {
                wp_redirect( admin_url( 'edit.php?s=' . $matches[1] . '&post_type=gift_card' ) );
                die();
            }
        }


        public function show_sold_individually_for_gift_cards() {
            ?>
            <script>
                jQuery("#_sold_individually").closest(".options_group").addClass("show_if_gift-card");
                jQuery("#_sold_individually").closest(".form-field").addClass("show_if_gift-card");
            </script>
            <?php
        }

        /**
         * Show advanced product settings
         *
         * @param int $thepostid
         */
        public function show_advanced_product_settings( $thepostid ) {

        }

        /**
         * Add filters on the Gift Card Post Type page
         *
         * @param $views
         *
         * @return mixed
         */

        public function add_gift_cards_filters( $views ) {
            global $wpdb;
            $args = array(
                'post_status' => 'published',
                'post_type'   => 'gift_card',
                'balance'     => 'active'
            );

            $count_active = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( DISTINCT( post_id ) ) FROM {$wpdb->postmeta} AS pm LEFT JOIN {$wpdb->posts} AS p ON p.ID = pm.post_id WHERE meta_key = %s AND meta_value <> 0 AND p.post_type= %s", '_ywgc_balance_total', 'gift_card' ) );
            $count_used   = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( DISTINCT( post_id ) ) FROM {$wpdb->postmeta} AS pm LEFT JOIN {$wpdb->posts} AS p ON p.ID = pm.post_id WHERE meta_key = %s AND ROUND(meta_value, %d) = 0 AND p.post_type= %s", '_ywgc_balance_total', wc_get_price_decimals(), 'gift_card' ) );

            $views['active'] = sprintf( '<a href="%s">%s <span class="count">(%d)</span></a>', add_query_arg( $args, admin_url( 'edit.php' ) ), esc_html__( 'Active', 'yith-woocommerce-gift-cards' ), $count_active );
            $args['balance'] = 'used';
            $views['used']   = sprintf( '<a href="%s">%s <span class="count">(%d)</span></a>', add_query_arg( $args, admin_url( 'edit.php' ) ), esc_html__( 'Used', 'yith-woocommerce-gift-cards' ), $count_used );

            return $views;
        }


        /**
         * Add filters on the Gift Card Post Type page
         *
         * @param $query
         */

        public function filter_gift_card_page_query( $query ) {
            global $pagenow, $post_type;

            if ( $pagenow == 'edit.php' && $post_type == 'gift_card' && isset( $_GET['balance'] ) && in_array( $_GET['balance'], array(
                    'used',
                    'active'
                ) ) ) {
                if ( 'active' == $_GET['balance'] ) {
                    $meta_query = array(
                        array(
                            'key'     => '_ywgc_balance_total',
                            'value'   => 0,
                            'compare' => '>'
                        )
                    );
                } else {
                    $meta_query = array(
                        array(
                            'key'     => '_ywgc_balance_total',
                            'value'   => pow( 10, - wc_get_price_decimals() ),
                            'compare' => '<'
                        )
                    );
                }

                $query->set( 'meta_query', $meta_query );
            }
        }


        /**
         * Localize order item meta and show theme as strings
         *
         * @param $display_key
         * @param $meta
         * @param $order_item
         * @return string|void
         */
        public function show_as_string_order_item_meta_key($display_key){
            if( strpos($display_key,'ywgc') !== false){
                if( $display_key == '_ywgc_product_id' ){
                    $display_key = esc_html__('Product ID','yith-woocommerce-gift-card');
                }

                elseif( $display_key == '_ywgc_amount' ){
                    $display_key = esc_html__('Amount','yith-woocommerce-gift-card');
                }
                elseif( $display_key == '_ywgc_is_digital' ){
                    $display_key = esc_html__('Digital','yith-woocommerce-gift-card');
                }
                elseif( $display_key == '_ywgc_sender_name' ){
                    $display_key = esc_html__('Sender\'s name','yith-woocommerce-gift-card');
                }
                elseif( $display_key == '_ywgc_recipient_name' ){
                    $display_key = esc_html__('Recipient\'s name','yith-woocommerce-gift-card');
                }
                elseif( $display_key == '_ywgc_message' ){
                    $display_key = esc_html__('Message','yith-woocommerce-gift-card');
                }
                elseif( $display_key == '_ywgc_design_type' ){
                    $display_key = esc_html__('Design type','yith-woocommerce-gift-card');
                }
                elseif( $display_key == '_ywgc_design' ){
                    $display_key = esc_html__('Design','yith-woocommerce-gift-card');
                }
                elseif( $display_key == '_ywgc_subtotal' ){
                    $display_key = esc_html__('Subtotal','yith-woocommerce-gift-card');
                }
                elseif( $display_key == '_ywgc_subtotal_tax' ){
                    $display_key = esc_html__('Subtotal tax','yith-woocommerce-gift-card');
                }
                elseif( $display_key == '_ywgc_version' ){
                    $display_key = esc_html__('Version','yith-woocommerce-gift-card');
                }
                elseif( $display_key == '_ywgc_delivery_date' ){
                    $display_key = esc_html__('Delivery date','yith-woocommerce-gift-card');
                }
                elseif( $display_key == '_ywgc_postdated' ){
                    $display_key = esc_html__('Postdated','yith-woocommerce-gift-card');
                }


            }
            return $display_key;
        }

        /**
         * Format date to show as meta value in order page
         * @param $meta_value
         * @param $meta
         * @return mixed
         */
        public function show_formatted_date( $meta_value, $meta ="", $item="" ){

            if( '_ywgc_delivery_date' == $meta->key ){
                $date_format = apply_filters( 'yith_wcgc_date_format','Y-m-d' );
                $meta_value = date_i18n( $date_format,$meta_value ) . ' (' . $date_format . ')';
            }

            return $meta_value;

        }

        /**
         * Update gift card amount in case the order is cancelled or refunded
         * @param $order_id
         * @param $from_status
         * @param $to_status
         * @param bool $order
         */
        public function update_gift_card_amount_on_order_status_change( $order_id, $from_status, $to_status, $order = false ){
            $is_gift_card_amount_refunded = yit_get_prop($order,'_ywgc_is_gift_card_amount_refunded');
            if( ($to_status == 'cancelled' || ( $to_status == 'refunded' ) || ( $to_status == 'failed' )) && $is_gift_card_amount_refunded != 'yes' ){
                $gift_card_applied = yit_get_prop( $order,'_ywgc_applied_gift_cards',true );
                if (empty($gift_card_applied)) {
                    return;
                }

                foreach ($gift_card_applied as $gift_card_code => $gift_card_value  ){
                    $args = array(
                        'gift_card_number' => $gift_card_code
                    );
                    $gift_card = new YITH_YWGC_Gift_Card( $args );
                    $new_amount = $gift_card->get_balance() + $gift_card_value;
                    $gift_card->update_balance( $new_amount );
                }

                yit_save_prop($order,'_ywgc_is_gift_card_amount_refunded','yes');
            }
        }


        public function update_totals_on_save_order_items( $order_id, $items ){

            if( isset( $items['order_status'] ) && $items[ 'order_status' ] == 'wc-refunded' )
                return;

            $order = wc_get_order( $order_id );

            $used_gift_cards = get_post_meta( $order_id, '_ywgc_applied_gift_cards', true);

            if (! $used_gift_cards )
                return;

            $cart_subtotal     = 0;
            $cart_total        = 0;
            $fee_total         = 0;
            $cart_subtotal_tax = 0;
            $cart_total_tax    = 0;

            $and_taxes = yit_get_prop( $order,'prices_include_tax' );

            if ( $and_taxes && apply_filters('yith_ywgc_update_totals_calculate_taxes',true) ) {
                $order->calculate_taxes();
            }

            // line items
            foreach ( $order->get_items() as $item ) {
                $cart_subtotal     += $item->get_subtotal();
                $cart_total        += $item->get_total();
                $cart_subtotal_tax += $item->get_subtotal_tax();
                $cart_total_tax    += $item->get_total_tax();
            }

            $applied_gift_card_amount = yit_get_prop( $order,'_ywgc_applied_gift_cards_totals' );

            if ( !empty($applied_gift_card_amount) ){
                $cart_total -= $applied_gift_card_amount;
            }

            $order->calculate_shipping();

            foreach ( $order->get_fees() as $item ) {
                $fee_total += $item->get_total();
            }

            $grand_total = round( $cart_total + $fee_total + $order->get_shipping_total() + $order->get_cart_tax() + $order->get_shipping_tax(), wc_get_price_decimals() );

            $order->set_discount_total( $cart_subtotal - $cart_total );
            $order->set_discount_tax( $cart_subtotal_tax - $cart_total_tax );
            $order->set_total( $grand_total );
            $order->save();

        }


        public function ywgc_edit_design_category_form() {
            global $current_screen;

            if ( $current_screen->id == 'edit-giftcard-category' ){
                ?>
                <div>
                    <h2><?php echo esc_html__('Manage the gift card images through the WordPress Media Library', 'yith-woocommerce-gift-cards');?></h2>
                    <br>
                    <button id="ywgc-media-upload-button" class="button" style="padding: 3px 15px;"><span class="dashicons dashicons-admin-media"></span><?php echo ' ' .  esc_html__('Manage media', 'yith-woocommerce-gift-cards'); ?></button>
                    <p><?php echo esc_html__('Upload/manage images in the WordPress Media Library and include them in the existing gift card categories.', 'yith-woocommerce-gift-cards');  ?></p>
                </div>
                <?php
            }
        }

        function ywgc_remove_product_meta_boxes(){

            $product = wc_get_product(get_the_ID());

            if (is_object($product) &&  $product->get_type() == 'gift-card' ) {
                remove_meta_box('woocommerce-product-images', 'product', 'side');
            }
            if (is_object($product) && $product->get_type() != 'gift-card' ) {
                remove_meta_box('giftcard-categorydiv', 'product', 'side');
            }
        }


        public function set_gift_card_category_to_product( $post_id ) {

            //  Skip all request without an action
            if ( ! isset( $_REQUEST['action'] ) && ! isset( $_REQUEST['action2'] ) ) {
                return;
            }

            //  Skip all request without a valid action
            if ( ( '-1' == $_REQUEST['action'] ) && ( '-1' == $_REQUEST['action2'] ) ) {
                return;
            }

            $selected_catergories = isset( $_REQUEST['tax_input']['giftcard-category'] ) ? $_REQUEST['tax_input']['giftcard-category'] : array();

            $selected_catergories_serialized = serialize($selected_catergories);

            update_post_meta( $post_id, 'selected_images_categories', $selected_catergories_serialized );
        }




	}
}
