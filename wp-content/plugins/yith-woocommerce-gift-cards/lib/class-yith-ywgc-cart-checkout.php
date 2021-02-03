<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'YITH_YWGC_Cart_Checkout' ) ) {

	/**
	 *
	 * @class   YITH_YWGC_Cart_Checkout
	 *
	 * @since   1.0.0
	 * @author  Lorenzo Giuffrida
	 */
	class YITH_YWGC_Cart_Checkout {

		const ORDER_GIFT_CARDS = '_ywgc_applied_gift_cards';
		const ORDER_GIFT_CARDS_TOTAL = '_ywgc_applied_gift_cards_totals';

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

			$this->includes();
			$this->init_hooks();
		}

		public function includes() {

		}

		public function init_hooks() {

			/**
			 * set the price when a gift card product is added to the cart
			 */
			add_filter( 'woocommerce_add_cart_item', array($this, 'set_price_in_cart'), 10, 2 );

			add_filter( 'woocommerce_get_cart_item_from_session', array($this, 'get_cart_item_from_session'), 10, 2 );

			if ( version_compare( WC()->version, '2.7', '<' ) ) {
				add_action( 'woocommerce_add_order_item_meta', array($this, 'append_gift_card_data_to_order_item'), 10, 2 );
			} else {
				add_action( 'woocommerce_new_order_item', array($this, 'append_gift_card_data_to_new_order_item'), 10, 2 );
			}

			/**
			 * Custom add_to_cart handler for gift card product type
			 */
			add_action( 'woocommerce_add_to_cart_handler_gift-card', array($this, 'add_to_cart_handler') );

			/* Ajax action for applying a gift card to the cart */
			add_action( 'wp_ajax_ywgc_apply_gift_card_code', array($this, 'apply_gift_card_code_callback') );
			add_action( 'wp_ajax_nopriv_ywgc_apply_gift_card_code', array($this, 'apply_gift_card_code_callback') );

			/* Ajax action for applying a gift card to the cart */
			add_action( 'wp_ajax_ywgc_remove_gift_card_code', array($this, 'remove_gift_card_code_callback') );
			add_action( 'wp_ajax_nopriv_ywgc_remove_gift_card_code', array($this, 'remove_gift_card_code_callback') );
			/**
			 * Apply the discount to the cart using the gift cards submitted, is any exists.
			 */
			add_action( 'woocommerce_after_calculate_totals', array($this, 'apply_gift_cards_discount'), 20 );

			/**
			 * Show gift card amount usage on cart totals - checkout page
			 */
			add_action( 'woocommerce_review_order_before_order_total', array($this, 'show_gift_card_amount_on_cart_totals') );

			/**
			 * Show gift card amount usage on cart totals - cart page
			 */
			add_action( 'woocommerce_cart_totals_before_order_total', array($this, 'show_gift_card_amount_on_cart_totals') );

			add_action( 'woocommerce_new_order', array($this, 'register_gift_cards_usage') );

			add_filter( 'woocommerce_get_order_item_totals', array($this, 'show_gift_cards_total_applied_to_order'), 10, 2 );

            /**
             * Show gift card details in cart page
             */
            add_filter( 'woocommerce_cart_item_thumbnail', array($this, 'ywgc_custom_cart_product_image'), 10, 3 );

            add_action( 'init', array( $this, 'ywgc_apply_gift_card_on_coupon_form' ) );


        }

        /**
         *
         * Show the image chosen for a gift card
         *
         * @param           $product_image    The product title HTML
         * @param           $cart_item        The cart item array
         * @param bool      $cart_item_key    The cart item key
         *
         * @since    2.0.1
         * @author  Daniel Sanchez <daniel.sanchez@yithemes.com>
         * @return  string  The product title HTML
         * @use     woocommerce_cart_item_thumbnail hook
         */
        public function ywgc_custom_cart_product_image( $product_image, $cart_item, $cart_item_key = false ) {

            if ( ! isset( $cart_item[ 'ywgc_amount' ] ) )
                return $product_image;

            $deliminiter1 = apply_filters( 'ywgc_delimiter1_for_cart_image', 'src=' );
            $deliminiter2 = apply_filters( 'ywgc_delimiter2_for_cart_image', '"' );

            if ( ! empty( $cart_item[ 'ywgc_has_custom_design' ] ) ) {

                $design_type = $cart_item[ 'ywgc_design_type' ];

                if ( 'custom' == $design_type ) {

                    $image = YITH_YWGC_SAVE_URL . "/" . $cart_item[ 'ywgc_design' ];

                    $product_image = '<img width="300" height="300" src="' . $image .'" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail"
            alt="" srcset="' . $image .' 300w, ' . $image .' 600w, ' . $image .' 100w, ' . $image .' 150w, ' . $image .' 768w, ' . $image .' 1024w"
            sizes="(max-width: 300px) 100vw, 300px" />';

                }
                else if ( 'template' == $design_type ) {
                    $product_image = wp_get_attachment_image( $cart_item[ 'ywgc_design' ] );

                }
                else if ( 'custom-modal' == $design_type ) {

                    $image_url = $cart_item[ 'ywgc_design' ];

                    $product_image = '<img width="300" height="300" src="' . $image_url .'" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail"
            alt="" srcset="' . $image_url .' 300w, ' . $image_url .' 600w, ' . $image_url .' 100w, ' . $image_url .' 150w, ' . $image_url .' 768w, ' . $image_url .' 1024w"
            sizes="(max-width: 300px) 100vw, 300px" />';

                }

            }
            else{

                $_product = wc_get_product( $cart_item[ 'product_id' ] );

                    if ( get_class( $_product ) == 'WC_Product_Gift_Card' ){

                        $image_id = get_post_thumbnail_id( $_product->get_id() );
                        $header_image_url = wp_get_attachment_url( $image_id );

                        $array_product_image = explode( $deliminiter1, $product_image );
                        $array_product_image = explode( $deliminiter2, $array_product_image[ 1 ] );

                        $product_image = str_replace( $array_product_image[1], $header_image_url, $product_image );

                    }
            }

            return $product_image;
        }


		/**
		 * Show gift cards usage on order item totals
		 *
		 * @param array    $total_rows
		 * @param WC_Order $order
		 *
		 * @return array
		 */
		public function show_gift_cards_total_applied_to_order( $total_rows, $order ) {

            $gift_cards = yit_get_prop( $order, self::ORDER_GIFT_CARDS, true );
			if ( $gift_cards ) {
				$row_totals = $total_rows['order_total'];
				unset( $total_rows['order_total'] );

				$gift_cards_message = '';
				foreach ( $gift_cards as $code => $amount ) {
					$amount = apply_filters( 'yith_ywgc_gift_card_coupon_amount', $amount, YITH_YWGC()->get_gift_card_by_code( $code ) );
					$gift_cards_message .= apply_filters('yith_ywgc_gift_card_coupon_message',"-" . wc_price( $amount ) . ' (' . $code . ')', $amount, $code) ;
				}

				$total_rows['gift_cards'] = array(
					'label' => esc_html__( 'Gift cards:', 'yith-woocommerce-gift-cards' ),
					'value' => $gift_cards_message
				);

                $total_rows = apply_filters( 'ywgc_gift_card_thankyou_table_total_rows', $total_rows, $code );

				$total_rows['order_total'] = $row_totals;
			}

			return $total_rows;
		}

		/**
		 * Show gift card amount usage on cart totals
		 */
		public function show_gift_card_amount_on_cart_totals() {

			if ( isset( WC()->cart->applied_gift_cards ) ) {

				foreach ( WC()->cart->applied_gift_cards as $code ) :

					$label = apply_filters( 'yith_ywgc_cart_totals_gift_card_label', esc_html( __( 'Gift card:', 'yith-woocommerce-gift-cards' ) . ' ' . $code ), $code );
					$amount = isset( WC()->cart->applied_gift_cards_amounts[ $code ] ) ? - WC()->cart->applied_gift_cards_amounts[ $code ] : 0;
					$value = wc_price( $amount ) . ' <a href="' . esc_url( add_query_arg( 'remove_gift_card_code', urlencode( $code ),
							defined( 'WOOCOMMERCE_CHECKOUT' ) ? wc_get_checkout_url() : wc_get_cart_url() ) ) .
					         '" class="ywgc-remove-gift-card " data-gift-card-code="' . esc_attr( $code ) . '">' . apply_filters('ywgc_remove_gift_card_text',esc_html__( '[Remove]', 'yith-woocommerce-gift-cards' ) ) . '</a>';
					?>
					<tr class="ywgc-gift-card-applied">
						<th><?php echo $label; ?></th>
						<td><?php echo $value; ?></td>
					</tr>

                    <?php do_action('ywgc_gift_card_checkout_cart_table', $code, $amount ); ?>

				<?php endforeach;
			}
		}

		// Comparison function
		function cmp( $a, $b ) {
			if ( $a == $b ) {
				return 0;
			}

			return ( $a < $b ) ? - 1 : 1;
		}

		/**
		 * Apply a gift card discount to current cart
		 *
		 * @param string $code
		 */
		protected function add_gift_card_code_to_session( $code ) {
			$applied_gift_cards = $this->get_gift_cards_from_session();

            $code = strtoupper($code);

			if ( ! in_array( $code, $applied_gift_cards ) ) {
				$applied_gift_cards[] = $code;
				WC()->session->set( 'applied_gift_cards', $applied_gift_cards );
			}
		}

		/**
		 * Remove a gift card discount from current cart
		 *
		 * @param string $code
		 */
		protected function remove_gift_card_code_from_session( $code ) {
			$applied_gift_cards = $this->get_gift_cards_from_session();

			if ( ( $key = array_search( $code, $applied_gift_cards ) ) !== false ) {
				unset( $applied_gift_cards[ $key ] );
			}

			WC()->session->set( 'applied_gift_cards', $applied_gift_cards );
		}

		private function get_gift_cards_from_session() {
			$value = array();

			if ( isset( WC()->session ) ) {
				$value = WC()->session->get( 'applied_gift_cards', array() );
			}

			return $value;
		}

		private function empty_gift_cards_session() {
			if ( isset( WC()->session ) ) {
				WC()->session->__unset( 'applied_gift_cards' );
			}
		}

		/**
		 * Apply the gift cards discount to the cart
		 *
		 * @param WC_Cart $cart
		 *
		 */
		public function apply_gift_cards_discount( $cart ) {

			$cart->applied_gift_cards         = array();
			$cart->applied_gift_cards_amounts = array();

			$gift_card_codes = $this->get_gift_cards_from_session();
			if ( $gift_card_codes ) {

				$cart_total = version_compare(WC()->version, '3.2.0', '<') ? $cart->total : $cart->get_total('edit');

				$gift_card_amounts = array();
				foreach ( $gift_card_codes as $code ) {
					/** @var YITH_YWGC_Gift_Card $gift_card */
					$gift_card = YITH_YWGC()->get_gift_card_by_code( $code );

					if ( YITH_YWGC()->check_gift_card( $gift_card, true ) ) {
						$gift_card_amounts[ $code ] = apply_filters( 'yith_ywgc_gift_card_coupon_amount',
							$gift_card->get_balance(),
							$gift_card );
					} else {
						$this->remove_gift_card_code_from_session( $code );
						wc_print_notices();
					}
				}

				uasort( $gift_card_amounts, array( $this, 'cmp' ) );

                foreach ( $gift_card_amounts as $code => $amount ) {

					$cart->applied_gift_cards[] = $code;

					if ( ( $cart_total + $cart->shipping_total > 0 ) && ( $amount > 0 ) ) {

						$discount = min( $amount, $cart_total );

						$residue = $cart_total - $discount;


						if( $residue > 0 ){
							if( ( $cart->shipping_total - $residue) >= 0 ){
								if( apply_filters('yith_ywgc_detract_residue_to_shipping_total',true) ){

									$cart->set_shipping_total($residue);
								}
							}else{
								$residue = $residue - $cart->shipping_total;
							}

						}

						$cart->applied_gift_cards_amounts[ $code ] = $discount;
						$cart_total -= $discount;


					}
				}

                $discount= isset( $discount ) ? $discount : '';

                $cart->ywgc_original_cart_total = $cart->total;

                do_action( 'yith_ywgc_apply_gift_card_discount_before_cart_total', $cart, $discount );

                $cart->total                    = abs($cart_total);

                do_action( 'yith_ywgc_apply_gift_card_discount_after_cart_total', $cart, $discount );

			}
		}

		/**
		 * Check if the gift card code provided is valid and store the amount for
		 * applying the discount to the cart
		 */
		public function apply_gift_card_code_callback() {

			check_ajax_referer( 'apply-gift-card', 'security' );
			$code = sanitize_text_field( $_POST['code'] );

			if ( ! empty( $code ) ) {
				$gift = YITH_YWGC()->get_gift_card_by_code( $code );
				if ( YITH_YWGC()->check_gift_card( $gift )  ) {


					$this->add_gift_card_code_to_session( $code );


					wc_add_notice( $gift->get_gift_card_message( YITH_YWGC_Gift_Card::GIFT_CARD_SUCCESS ) );
				}
				wc_print_notices();
			}

			die();
		}

		/**
		 * Check if the gift card code provided is valid and store the amount for
		 * applying the discount to the cart
		 */
		public function remove_gift_card_code_callback() {

			check_ajax_referer( 'apply-gift-card', 'security' );

			$code = sanitize_text_field( $_POST['code'] );

			if ( ! empty( $code ) ) {

				$gift = YITH_YWGC()->get_gift_card_by_code( $code );
				if ( YITH_YWGC()->check_gift_card( $gift, true ) ) {
					$this->remove_gift_card_code_from_session( $code );

					wc_add_notice( $gift->get_gift_card_message( YITH_YWGC_Gift_Card::GIFT_CARD_REMOVED ) );
				}
				wc_print_notices();
			}

			die();
		}

		/**
		 * Update the balance for all gift cards applied to an order
		 *
		 * @throws Exception
		 *
		 * @param int $order_id
		 */
		public function register_gift_cards_usage( $order_id ) {

            /**
             * Adding two race condition fields to the order
             */

			$applied_gift_cards = array();
			$applied_discount   = 0.00;

			if ( isset( WC()->cart->applied_gift_cards_amounts ) ) {
				foreach ( WC()->cart->applied_gift_cards_amounts as $code => $amount ) {
					$gift = YITH_YWGC()->get_gift_card_by_code( $code );

					if ( $gift->exists() ) {
						$amount                      = apply_filters( 'yith_ywgc_gift_card_amount_before_deduct', $amount );
						$applied_gift_cards[ $code ] = $amount;
						$applied_discount += $amount;

						$new_balance = apply_filters( 'yith_ywgc_new_balance_before_update_balance', max( 0.00, $gift->get_balance() - $amount ) );

						$gift->update_balance( $new_balance );
						$gift->register_order( $order_id );
					}
				}
			}

			if ( $applied_gift_cards ) {
				$order = wc_get_order( $order_id );
				yit_save_prop( $order, self::ORDER_GIFT_CARDS, $applied_gift_cards );
				yit_save_prop( $order, self::ORDER_GIFT_CARDS_TOTAL, $applied_discount );
				$order->add_order_note( sprintf( esc_html__( 'Order paid with gift cards for a total amount of %s.', 'yith-woocommerce-gift-cards' ), wc_price( $applied_discount ) ) );
			}

			$this->empty_gift_cards_session();
		}

		/**
		 * @param int    $order_id
		 * @param string $code
		 * @param float  $discount
		 *
		 *
		 * @return bool|mixed
		 */
		public function add_order_gift_card( $order_id, $code, $discount ) {

			// Store gift card
			$item_id = wc_add_order_item( $order_id,
				array(
					'order_item_name' => $code,
					'order_item_type' => 'yith-gift-card'
				) );

			if ( ! $item_id ) {
				return false;
			}

			wc_add_order_item_meta( $item_id, 'discount_amount', $discount );

			do_action( 'yith_ywgc_order_add_gift_card', $order_id, $item_id, $code, $discount );

			return $item_id;
		}


		/**
		 * Build cart item meta to pass to add_to_cart when adding a gift card to the cart
		 * @since 1.5.0
		 */
		public function build_cart_item_data() {

			$cart_item_data = array();

			/**
			 * Check if the current gift card has a prefixed amount set
			 */

			$ywgc_is_preset_amount = isset( $_REQUEST['gift_amounts'] ) && ( floatval( $_REQUEST['gift_amounts'] ) > 0 );
			$ywgc_is_preset_amount = wc_format_decimal ( $ywgc_is_preset_amount );

			/**
			 * Neither manual or fixed? Something wrong happened!
			 */
			if ( ! $ywgc_is_preset_amount ) {
				wp_die( esc_html__( 'The gift card has an invalid amount', 'yith-woocommerce-gift-cards' ) );
			}

			/**
			 * Check if it is a physical gift card
			 */
			$ywgc_is_physical = isset( $_REQUEST['ywgc-is-physical'] ) && $_REQUEST['ywgc-is-physical'];
			if ( $ywgc_is_physical ) {

				/**
				 * Retrieve sender name
				 */
				$sender_name = isset( $_REQUEST['ywgc-sender-name'] ) ? $_REQUEST['ywgc-sender-name'] : '';

				/**
				 * Recipient name
				 */
				$recipient_name = isset( $_REQUEST['ywgc-recipient-name'] ) ? $_REQUEST['ywgc-recipient-name'] : '';

				/**
				 * Retrieve the sender message
				 */
				$sender_message = isset( $_REQUEST['ywgc-edit-message'] ) ? $_REQUEST['ywgc-edit-message'] : '';

			}

			/**
			 * Check if it is a digital gift card
			 */
			$ywgc_is_digital = isset( $_REQUEST['ywgc-is-digital'] ) && $_REQUEST['ywgc-is-digital'];
			if ( $ywgc_is_digital ) {

				/**
				 * Retrieve gift card recipient
				 */
				$recipients = apply_filters( 'ywgc-recipient-email', isset( $_REQUEST['ywgc-recipient-email'] ) ? $_REQUEST['ywgc-recipient-email'] : '');

				/**
				 * Retrieve sender name
				 */
				$sender_name = isset( $_REQUEST['ywgc-sender-name'] ) ? $_REQUEST['ywgc-sender-name'] : '';

				/**
				 * Recipient name
				 */
				$recipient_name = isset( $_REQUEST['ywgc-recipient-name'] ) ? $_REQUEST['ywgc-recipient-name'] : '';

				/**
				 * Retrieve the sender message
				 */
				$sender_message = isset( $_REQUEST['ywgc-edit-message'] ) ? $_REQUEST['ywgc-edit-message'] : '';

				/**
				 * Gift card should be delivered on a specific date?
				 */

                $delivery_date = isset( $_REQUEST['ywgc-delivery-date'] ) ? strtotime($_REQUEST['ywgc-delivery-date']) : '';

                $postdated = $delivery_date != '' ? true : false;

				$gift_card_design = - 1;
				$design_type      = isset( $_POST['ywgc-design-type'] ) ? $_POST['ywgc-design-type'] : 'default';

				if ( 'template' == $design_type ) {
                    if ( isset( $_POST['ywgc-template-design'] ) ) {
						$gift_card_design = $_POST['ywgc-template-design'];
					}
				}

            }

                if ( isset( $_POST['add-to-cart'] ) ) {
                    $cart_item_data['ywgc_product_id'] = absint($_POST['add-to-cart']);
                }
                else if ( isset( $_REQUEST["ywgc_product_id"] ) ) {
                    $cart_item_data['ywgc_product_id'] = $_REQUEST["ywgc_product_id"];
                }

				/**
				 * Set the gift card amount
				 */
                $on_sale = get_post_meta( $cart_item_data['ywgc_product_id'], '_ywgc_sale_discount_value', true );


					$ywgc_amount = $_REQUEST['gift_amounts'];

                    if ( $on_sale ) {

                        //save the real amount of the gift card
                        $cart_item_data['ywgc_amount_without_discount'] = $ywgc_amount;

                        $discount = apply_filters( 'yith_ywgc_discount_value', ( $ywgc_amount * (int)$on_sale ) / 100, $ywgc_amount, $on_sale );
                        $ywgc_amount = $ywgc_amount - $discount ;
                    }
                    else{
                        $ywgc_amount = apply_filters( 'yith_ywgc_submitting_select_amount', $ywgc_amount );
                    }




			$cart_item_data['ywgc_amount']           = $ywgc_amount;
			$cart_item_data['ywgc_is_digital']       = $ywgc_is_digital;
			$cart_item_data['ywgc_is_physical']       = $ywgc_is_physical;

			/**
			 * Retrieve the gift card recipient, if digital
			 */
			if ( $ywgc_is_digital ) {
				$cart_item_data['ywgc_recipients']     = $recipients;
				$cart_item_data['ywgc_sender_name']    = $sender_name;
				$cart_item_data['ywgc_recipient_name'] = $recipient_name;
				$cart_item_data['ywgc_message']        = $sender_message;
				$cart_item_data['ywgc_postdated']      = $postdated;

				if ( $postdated ) {
					$cart_item_data['ywgc_delivery_date'] = $delivery_date;
				}


				$cart_item_data['ywgc_design_type']       = $design_type;
				$cart_item_data['ywgc_has_custom_design'] = $gift_card_design != - 1;
				if ( $gift_card_design ) {
					$cart_item_data['ywgc_design'] = $gift_card_design;
				}

			}

			if ( $ywgc_is_physical ) {
				$cart_item_data['ywgc_recipient_name'] = $recipient_name;
				$cart_item_data['ywgc_sender_name']    = $sender_name;
				$cart_item_data['ywgc_message']        = $sender_message;
			}

			return $cart_item_data;
		}

		/**
		 * Custom add_to_cart handler for gift card product type
		 */
		public function add_to_cart_handler() {

			$item_data  = $this->build_cart_item_data();
			$product_id = $item_data['ywgc_product_id'];

            if ( ! $product_id ) {
				wc_add_notice( esc_html__( 'An error occurred while adding the product to the cart.', 'yith-woocommerce-gift-cards' ), 'error' );

				return false;
			}

			$added_to_cart = false;

			if ( $item_data['ywgc_is_digital'] ) {

				$recipients = $item_data['ywgc_recipients'];
				/**
				 * Check if all mandatory fields are filled or throw an error
				 */
				if ( YITH_YWGC()->mandatory_recipient() && is_array($recipients) && ! count( $recipients ) ) {
					wc_add_notice( esc_html__( 'Add a valid email address for the recipient', 'yith-woocommerce-gift-cards' ), 'error' );

					return false;
				}

				/**
				 * Validate all email addresses submitted
				 */
				$email_error = '';
				if ( YITH_YWGC()->mandatory_recipient() && $recipients ) {
					foreach ( $recipients as $recipient ) {

						if ( YITH_YWGC()->mandatory_recipient() && empty( $recipient ) ) {
							wc_add_notice( esc_html__( 'The recipient(s) email address is mandatory', 'yith-woocommerce-gift-cards' ), 'error' );

							return false;
						}

						if ( $recipient && ! filter_var( $recipient, FILTER_VALIDATE_EMAIL ) ) {
							$email_error .= '<br>' . $recipient;
						}
					}

					if ( $email_error ) {
						wc_add_notice( esc_html__( 'Email address not valid, please check the following: ', 'yith-woocommerce-gift-cards' ) . $email_error, 'error' );

						return false;
					}
				}

				/** The user can purchase 1 gift card with multiple recipient emails or [quantity] gift card for the same user.
				 * It's not possible to mix both, purchasing multiple instance of gift card with multiple recipients
				 * */
				$recipient_count = is_array( $item_data['ywgc_recipients'] ) ? count( $item_data['ywgc_recipients'] ) : 0;
				$quantity        = ( $recipient_count > 1 ) ? $recipient_count : ( isset( $_REQUEST['quantity'] ) ? intval( $_REQUEST['quantity'] ) : 1 );

				if ( $recipient_count > 1 ) {
					$item_data_to_card = $item_data;

					for ( $i = 0; $i < $recipient_count; $i++ ) {

						$item_data_to_card['ywgc_recipients'] = array( $item_data['ywgc_recipients'][$i] );
						$item_data_to_card['ywgc_recipient_name'] = $item_data['ywgc_recipient_name'][$i];

						$added_to_cart = WC()->cart->add_to_cart( $product_id, 1, 0, array(), $item_data_to_card );
					}

				} else {
                    $item_data['ywgc_recipient_name'] = is_array($item_data['ywgc_recipient_name']) ? $item_data['ywgc_recipient_name'][0] : $item_data['ywgc_recipient_name'];
					$added_to_cart = WC()->cart->add_to_cart( $product_id, $quantity, 0, array(), $item_data );

				}

			}else if ( $item_data['ywgc_is_physical'] ) {
					/** The user can purchase 1 gift card with multiple recipient names or [quantity] gift card for the same user.
					 * It's not possible to mix both, purchasing multiple instance of gift card with multiple recipients
					 * */

                    $recipient_name_count = is_array( $item_data['ywgc_recipient_name'] ) ? count( $item_data['ywgc_recipient_name'] ) : 0;
					$quantity        = ( $recipient_name_count > 1 ) ? $recipient_name_count : ( isset( $_REQUEST['quantity'] ) ? intval( $_REQUEST['quantity'] ) : 1 );

					if ( $recipient_name_count > 1 ) {
						$item_data_to_card = $item_data;

						for ( $i = 0; $i < $recipient_name_count; $i++ ) {

							$item_data_to_card['ywgc_recipient_name'] = $item_data['ywgc_recipient_name'][$i];

							$added_to_cart = WC()->cart->add_to_cart( $product_id, 1, 0, array(), $item_data_to_card );
						}

					} else {
                        $item_data['ywgc_recipient_name'] = is_array($item_data['ywgc_recipient_name']) ? $item_data['ywgc_recipient_name'][0] : $item_data['ywgc_recipient_name'];
						$added_to_cart = WC()->cart->add_to_cart( $product_id, $quantity, 0, array(), $item_data );

					}

				}
				else {
					$quantity      = isset( $_REQUEST['quantity'] ) ? intval( $_REQUEST['quantity'] ) : 1;
					$added_to_cart = WC()->cart->add_to_cart( $product_id, $quantity, 0, array(), $item_data );
				}

			if ( $added_to_cart ) {

                if ( isset($item_data['ywgc_product_id']) ){
                    $product_id = $item_data['ywgc_product_id'];
                }
				$this->show_cart_message_on_added_product( $product_id, $quantity );
			}

			// If we added the product to the cart we can now optionally do a redirect.
			if ( wc_notice_count( 'error' ) === 0 ) {

				$url = '';
				// If has custom URL redirect there
				if ( $url = apply_filters( 'woocommerce_add_to_cart_redirect', $url ) ) {
					wp_safe_redirect( $url );
					exit;
				} elseif ( get_option( 'woocommerce_cart_redirect_after_add' ) === 'yes' ) {
					if ( function_exists( 'wc_get_cart_url' ) ) {
						wp_safe_redirect( wc_get_cart_url() );
					} else {
						wp_safe_redirect( WC()->cart->get_cart_url() );
					}
					exit;
				}
			}

		}

		public function show_cart_message_on_added_product( $product_id, $quantity = 1 ) {
			//  From WC 2.6.0 the parameter format in wc_add_to_cart_message changed
			$gt_255 = version_compare( WC()->version, '2.5.5', '>' );
			$param  = $gt_255 ? array( $product_id => $quantity ) : $product_id;
			wc_add_to_cart_message( $param, true );
		}

		/**
		 * Set the real amount for the gift card product
		 *
		 * @param array $cart_item
		 *
		 * @since 1.5.0
		 * @return mixed
		 */
		public function set_price_in_cart( $cart_item ) {
			if ( isset( $cart_item['data'] ) ) {
				if ( $cart_item['data'] instanceof WC_Product_Gift_Card && isset($cart_item['ywgc_amount']) ) {

					yit_set_prop( $cart_item['data'], 'price', $cart_item['ywgc_amount'] );
				}
			}

			return $cart_item;
		}

		/**
		 * Update cart item when retrieving cart from session
		 *
		 * @param $session_data mixed Session data to add to cart
		 * @param $values       mixed Values stored in session
		 *
		 * @return mixed Session data
		 * @since 1.5.0
		 */
		public function get_cart_item_from_session( $session_data, $values ) {

			if ( isset( $values['ywgc_product_id'] ) && $values['ywgc_product_id'] ) {

				$session_data['ywgc_product_id']       = isset( $values['ywgc_product_id'] ) ? $values['ywgc_product_id'] : '';
				$session_data['ywgc_amount']           = isset( $values['ywgc_amount'] ) ? $values['ywgc_amount'] : '';
				$session_data['ywgc_amount_without_discount']           = isset( $values['ywgc_amount_without_discount'] ) ? $values['ywgc_amount_without_discount'] : '';
				$session_data['ywgc_is_digital']       = isset( $values['ywgc_is_digital'] ) ? $values['ywgc_is_digital'] : false;

				if ( $session_data['ywgc_is_digital'] ) {
					$session_data['ywgc_recipients']     = isset( $values['ywgc_recipients'] ) ? $values['ywgc_recipients'] : '';
					$session_data['ywgc_sender_name']    = isset( $values['ywgc_sender_name'] ) ? $values['ywgc_sender_name'] : '';
					$session_data['ywgc_recipient_name'] = isset( $values['ywgc_recipient_name'] ) ? $values['ywgc_recipient_name'] : '';
					$session_data['ywgc_message']        = isset( $values['ywgc_message'] ) ? $values['ywgc_message'] : '';

					$session_data['ywgc_has_custom_design'] = isset( $values['ywgc_has_custom_design'] ) ? $values['ywgc_has_custom_design'] : false;
					$session_data['ywgc_design_type']       = isset( $values['ywgc_design_type'] ) ? $values['ywgc_design_type'] : '';
					if ( $session_data['ywgc_has_custom_design'] ) {
						$session_data['ywgc_design'] = isset( $values['ywgc_design'] ) ? $values['ywgc_design'] : '';
					}

					$session_data['ywgc_postdated'] = isset( $values['ywgc_postdated'] ) ? $values['ywgc_postdated'] : false;
					if ( $session_data['ywgc_postdated'] ) {
						$session_data['ywgc_delivery_date'] = isset( $values['ywgc_delivery_date'] ) ? $values['ywgc_delivery_date'] : false;
					}
				}

				if ( isset( $values['ywgc_amount'] ) ) {
					$product_price = apply_filters( 'yith_ywgc_set_cart_item_price', $values['ywgc_amount'], $values );
					yit_set_prop( $session_data['data'], 'price', $product_price );
				}
			}

			return $session_data;
		}


		/**
		 * @param                       $item_id
		 * @param WC_Order_Item_Product $item
		 */

		public function append_gift_card_data_to_new_order_item( $item_id, $item ) {

			if ( 'line_item' == $item->get_type() ) {

			    if ( isset( $item->legacy_values ) )
				    $this->append_gift_card_data_to_order_item( $item_id, $item->legacy_values );
			}
		}

		/**
		 * Append data to order item
		 *
		 * @param int   $item_id
		 * @param array $values
		 *
		 * @return mixed
		 * @author Lorenzo Giuffrida
		 * @since  1.5.0
		 */
		public function append_gift_card_data_to_order_item( $item_id, $values ) {

			if ( ! isset( $values['ywgc_product_id'] ) ) {
				return;
			}

			/**
			 * Store all fields related to Gift Cards
			 */

			foreach ( $values as $key => $value ) {
				if ( strpos( $key, 'ywgc_' ) === 0 ) {
					$meta_key = '_' . $key;
					wc_update_order_item_meta( $item_id, $meta_key, $value );
				}
			}

			/**
			 * Store subtotal and subtotal taxes applied to the gift card
			 */
			wc_update_order_item_meta( $item_id, '_ywgc_subtotal', $values['line_subtotal'] );
			wc_update_order_item_meta( $item_id, '_ywgc_subtotal_tax', $values['line_subtotal_tax'] );

			/**
			 * Store the plugin version for future use
			 */
			wc_update_order_item_meta( $item_id, '_ywgc_version', YITH_YWGC_VERSION );

		}

        public function ywgc_apply_gift_card_on_coupon_form() {


            /**
             * Verify if a coupon code inserted on cart page or checkout page belong to a valid gift card.
             * In this case, make the gift card working as a temporary coupon
             */
            add_filter( 'woocommerce_get_shop_coupon_data', array( $this, 'verify_coupon_code' ), 10, 2 );

            /*
             * Check if a gift card discount code was used and deduct the amount from the gift card.
             */
            if ( version_compare( WC()->version, '3.0', '<' ) ) {
                add_action( 'woocommerce_order_add_coupon', array(
                    $this,
                    'deduct_amount_from_gift_card'
                ), 10, 5 );
            } else {
                add_action( 'woocommerce_new_order_item', array(
                    $this,
                    'deduct_amount_from_gift_card_wc_3_plus'
                ), 10, 3 );
            }

        }

        public function verify_coupon_code( $return_val, $code ) {

            $gift_card = YITH_YWGC()->get_gift_card_by_code( $code );

            if ( ! $gift_card instanceof YITH_YWGC_Gift_Card ) {
                return $return_val;
            }

            if ( $gift_card->ID && $gift_card->get_balance() > 0 ) {
                $temp_coupon_array = apply_filters( 'ywgc_temp_coupon_array' , array(
                    'discount_type' => 'fixed_cart',
                    'coupon_amount' => $gift_card->get_balance(),
                    'amount'        => $gift_card->get_balance(),
                    'id'            => true,
                ), $gift_card );

                return $temp_coupon_array;
            }

            return $return_val;
        }

        public function deduct_amount_from_gift_card( $id, $item_id, $code, $discount_amount, $discount_amount_tax ) {

            $gift = YITH_YWGC()->get_gift_card_by_code( $code );

            $total_discount_amount = $discount_amount + $discount_amount_tax;

            if ( $gift instanceof YITH_YWGC_Gift_Card ) {

                $gift->update_balance( $gift->get_balance() - $total_discount_amount );

            }

        }

        public function deduct_amount_from_gift_card_wc_3_plus( $item_id, $item, $order_id ) {

            if ( $item instanceof WC_Order_Item_Coupon ) {
                $this->deduct_amount_from_gift_card( $item->get_id(), $item_id, $item->get_code(), $item->get_discount(), $item->get_discount_tax() );
            }

        }




	}
}

YITH_YWGC_Cart_Checkout::get_instance();
