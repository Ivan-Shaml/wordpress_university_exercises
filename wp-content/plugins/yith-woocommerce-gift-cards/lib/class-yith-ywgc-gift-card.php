<?php
if ( ! defined ( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists ( 'YITH_YWGC_Gift_Card' ) ) {

    /**
     *
     * @class   YITH_YWGC_Gift_Card
     *
     * @since   1.0.0
     * @author  Lorenzo Giuffrida
     */
    class YITH_YWGC_Gift_Card {

        const E_GIFT_CARD_NOT_EXIST = 100;
        const E_GIFT_CARD_NOT_YOURS = 101;
        const E_GIFT_CARD_ALREADY_APPLIED = 102;
        const E_GIFT_CARD_EXPIRED = 103;
        const E_GIFT_CARD_DISABLED = 104;
        const E_GIFT_CARD_DISMISSED = 105;
        const E_GIFT_CARD_INVALID_REMOVED = 106;

        const GIFT_CARD_SUCCESS = 200;
        const GIFT_CARD_REMOVED = 201;
        const GIFT_CARD_NOT_ALLOWED_FOR_PURCHASING_GIFT_CARD = 202;

        const META_ORDER_ID = '_ywgc_order_id';
        const META_AMOUNT_TOTAL = '_ywgc_amount_total';
        const META_BALANCE_TOTAL = '_ywgc_balance_total';

        const META_CUSTOMER_ID = '_ywgc_customer_id'; // Refers to id of the customer that purchase the gift card

        const STATUS_ENABLED = 'publish';
        const STATUS_DISMISSED = 'ywgc-dismissed';

        const META_SENDER_NAME = '_ywgc_sender_name';
        const META_RECIPIENT_NAME = '_ywgc_recipient_name';
        const META_RECIPIENT_EMAIL = '_ywgc_recipient';
        const META_MESSAGE = '_ywgc_message';
        const META_CURRENCY = '_ywgc_currency';
        const META_VERSION = '_ywgc_version';
        const META_IS_POSTDATED = '_ywgc_postdated';
        const META_DELIVERY_DATE = '_ywgc_delivery_date';
        const META_SEND_DATE = '_ywgc_delivery_send_date';
        const META_IS_DIGITAL = '_ywgc_is_digital';
        const META_HAS_CUSTOM_DESIGN = '_ywgc_has_custom_design';
        const META_DESIGN_TYPE = '_ywgc_design_type';
        const META_DESIGN = '_ywgc_design';
        const META_EXPIRATION = '_ywgc_expiration';
        const META_INTERNAL_NOTES = '_ywgc_internal_notes';

        const STATUS_PRE_PRINTED = 'ywgc-pre-printed';
        const STATUS_DISABLED = 'ywgc-disabled';
        const STATUS_CODE_NOT_VALID = 'ywgc-code-not-valid';

        /**
         * @var int the gift card id
         */
        public $ID = 0;

        /**
         * @var int  the product id
         */
        public $product_id = 0;

        /**
         * @var int the order id
         */
        public $order_id = 0;

        /**
         * @var string the gift card code
         */
        public $gift_card_number = '';

        /**
         * @var float the gift card amount
         */
        public $total_amount = 0.00;

        /**
         * @var float the gift card current balance
         */
        protected $total_balance = 0.00;

        /**
         * @var string the gift card post status
         */
        public $status = 'publish';

        /**
         * @var string the recipient for digital gift cards
         */
        public $recipient = '';

        public $customer_id = 0;

        /**
         * @var bool the gift card has a postdated delivery date
         */
        public $postdated_delivery = false;

        /**
         * @var string the expected delivery date
         */
        public $delivery_date = '';

        /**
         * @var string the real delivery date
         */
        public $delivery_send_date = '';


        /**
         * @var string the sender for digital gift cards
         */
        public $sender_name = '';

        /**
         * @var string the sender for digital gift cards
         */
        public $recipient_name = '';

        /**
         * @var string the message for digital gift cards
         */
        public $message = '';

        /**
         * @var bool the digital gift cards use the default image
         */
        public $has_custom_design = true;

        /**
         * @var string the type of design chosen by the user. Could be :
         *             'default' for standard image
         *             'custom' for image uploaded by the user
         *             'template' for template chosen from the desing list
         */
        public $design_type = 'default';

        /**
         * @var string the custom image for digital gift cards
         */
        public $design = null;

        /**
         * @var string the currency used when the gift card is created
         */
        public $currency = '';

        /**
         * Plugin version that created the gift card
         */
        public $version = '';

        /**
         * @var bool the gift card is digital
         */
        public $is_digital = false;


        /**
         * @var int the timestamp for gift card valid use
         */
        public $expiration = 0;

        /**
         * @var string internal note
         */
        public $internal_notes = '';


        /**
         * Constructor
         *
         * Initialize plugin and registers actions and filters to be used
         *
         * @param  array $args the arguments
         *
         * @since  1.0
         * @author Lorenzo Giuffrida
         */
        public function __construct( $args = array() ) {

            /**
             *  if $args['ID'] is set, retrieve the post with the same ID
             *  if $args['gift_card_number'] is set, retrieve the post with the same post_title
             */
            if ( isset( $args['ID'] ) ) {
                $post = get_post ( $args['ID'] );
            } elseif ( isset( $args['gift_card_number'] ) ) {
                $this->gift_card_number = $args['gift_card_number'];

                $post = get_page_by_title ( $args['gift_card_number'], OBJECT, YWGC_CUSTOM_POST_TYPE_NAME );
            }

            //  Load post data, if exists
            if ( isset( $post ) ) {

                $this->ID               = $post->ID;
                $this->gift_card_number = $post->post_title;
                $this->product_id       = $post->post_parent;
                //  Backward compatibility check with gift cards created with free version
                $old_order_id = get_post_meta ( $post->ID, '_gift_card_order_id', true );
                if ( ! empty( $old_order_id ) ) {
                    $this->order_id = $old_order_id;
                } else {
                    $this->order_id = get_post_meta ( $post->ID, self::META_ORDER_ID, true );
                }

                $total_amount = get_post_meta ( $post->ID, self::META_AMOUNT_TOTAL, true );
                if ( ! empty( $total_amount ) ) {
                    $this->total_amount = $total_amount;
                } else {
                    $amount     = get_post_meta ( $post->ID, '_ywgc_amount', true );
                    $amount_tax = get_post_meta ( $post->ID, '_ywgc_amount_tax', true );
                    $this->update_amount ( (float)$amount + (float)$amount_tax );
                }

                $total_balance = get_post_meta ( $post->ID, self::META_BALANCE_TOTAL, true );

                if ( ! empty( $total_balance ) ) {
                    $this->total_balance = $total_balance;
                } else {
                    $balance     = get_post_meta ( $post->ID, '_ywgc_amount_balance', true );
                    $balance_tax = get_post_meta ( $post->ID, '_ywgc_amount_balance_tax', true );
                    $balance = empty( $balance ) ? 0 : $balance;
                    $balance_tax = empty( $balance_tax ) ? 0 : $balance_tax;
                    $this->update_balance ( $balance + $balance_tax );
                }

                $this->customer_id = get_post_meta ( $post->ID, self::META_CUSTOMER_ID, true );

                $this->status = $post->post_status;
            }

            //  If $args is related to an existent gift card, load their data
            if ( $this->ID ) {
                $this->sender_name          = get_post_meta( $this->ID, self::META_SENDER_NAME, true );
                $this->recipient_name       = get_post_meta( $this->ID, self::META_RECIPIENT_NAME, true );
                $this->recipient            = get_post_meta( $this->ID, self::META_RECIPIENT_EMAIL, true );
                $this->message              = get_post_meta( $this->ID, self::META_MESSAGE, true );
                $this->currency             = get_post_meta( $this->ID, self::META_CURRENCY, true );
                $this->version              = get_post_meta( $this->ID, self::META_VERSION, true );
                $this->postdated_delivery   = get_post_meta( $this->ID, self::META_IS_POSTDATED, true );
                $this->delivery_date        = get_post_meta( $this->ID, self::META_DELIVERY_DATE, true );
                $this->delivery_send_date   = get_post_meta( $this->ID, self::META_SEND_DATE, true );
                $this->is_digital           = get_post_meta( $this->ID, self::META_IS_DIGITAL, true );
                $this->has_custom_design    = get_post_meta( $this->ID, self::META_HAS_CUSTOM_DESIGN, true );
                $this->design_type          = get_post_meta( $this->ID, self::META_DESIGN_TYPE, true );
                $this->design               = get_post_meta( $this->ID, self::META_DESIGN, true );
                $this->expiration           = get_post_meta( $this->ID, self::META_EXPIRATION, true );
                $this->internal_notes       = get_post_meta( $this->ID, self::META_INTERNAL_NOTES, true );
            }
        }


        /**
         * Register the order in the list of orders where the gift card was used
         *
         * @param int $order_id
         *
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function register_order( $order_id ) {
            if ( $this->ID ) {
                //  assign the order to this gift cards...
                $orders   = $this->get_registered_orders ();
                $orders[] = $order_id;
                update_post_meta ( $this->ID, YWGC_META_GIFT_CARD_ORDERS, $orders );

                //  assign the customer to this gift cards...
                $order = wc_get_order ( $order_id );
                $this->register_user ( yit_get_prop ( $order, 'customer_user' ) );
            }
        }

        /**
         * Check if the user is registered as the gift card owner
         *
         * @param int $user_id
         *
         * @return bool
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function is_registered_user( $user_id ) {
            $customer_users = get_post_meta ( $this->ID, YWGC_META_GIFT_CARD_CUSTOMER_USER );

            return in_array ( $user_id, $customer_users );
        }

        /**
         * Register an user as the gift card owner(may be one or more)
         *
         * @param int $user_id
         *
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function register_user( $user_id ) {
            if ( $user_id == 0 ) {
                return;
            }

            if ( $this->is_registered_user ( $user_id ) ) {
                //  the user is a register user
                return;
            }

            add_post_meta ( $this->ID, YWGC_META_GIFT_CARD_CUSTOMER_USER, $user_id );
        }

        /**
         * Retrieve the list of orders where the gift cards was used
         *
         * @return array|mixed
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function get_registered_orders() {
            $orders = array();

            if ( $this->ID ) {
                $orders = get_post_meta ( $this->ID, YWGC_META_GIFT_CARD_ORDERS, true );
                if ( ! $orders ) {
                    $orders = array();
                }
            }

            return array_unique ( $orders );
        }

        /**
         * Check if the gift card has enough balance to cover the amount requested
         *
         * @param $amount int the amount to be deducted from current gift card balance
         *
         * @return bool the gift card has enough credit
         */
        public function has_sufficient_credit( $amount ) {
            return $this->total_balance >= $amount;
        }

        /**
         * retrieve the gift card code
         *
         * @return string
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function get_code() {
            return $this->gift_card_number;
        }

        /**
         * The gift card exists
         *
         * @return bool
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function exists() {
            return $this->ID > 0;
        }

        /**
         * Retrieve if a gift card is enabled
         *
         * @return bool
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function is_enabled() {

            return self::STATUS_ENABLED == $this->status;
        }

        /**
         * Check the gift card ownership
         *
         * @param int|string $user user id or user email
         *
         * @return bool
         */
        public function is_owner( $user ) {
            //todo perform a real check for gift card ownership
            return true;
        }

        /**
         * Check if the gift card can be used
         * @return bool
         */
        public function can_be_used() {
            $can_use = $this->exists ();

            return apply_filters ( 'yith_ywgc_gift_card_can_be_used', $can_use, $this );
        }

        /**
         * Update and store the new balance
         *
         * @param float $new_amount
         */
        public function update_balance( $new_amount ) {
            $this->total_balance = $new_amount;
            if ( $this->ID ) {
                update_post_meta ( $this->ID, self::META_BALANCE_TOTAL, $this->total_balance );
            }
        }

        /**
         * Update and store the new amount
         *
         * @param float $new_amount
         */
        public function update_amount( $new_amount ) {
            $this->total_amount = $new_amount;
            if ( $this->ID ) {
                update_post_meta ( $this->ID, self::META_AMOUNT_TOTAL, $this->total_amount );
            }
        }

        /**
         * Retrieve the current gift card balance
         * @return float|mixed
         */
        public function get_balance() {
            return round( $this->total_balance, wc_get_price_decimals() );
        }

        /**
         * The gift card product is virtual
         */
        public function is_virtual() {

            return $this->is_digital;
        }


        public function get_gift_card_error( $err_code ) {
            $err = '';

            switch ( $err_code ) {
                case self::E_GIFT_CARD_NOT_EXIST:
                    $err = sprintf( esc_html__( 'The gift card code %s does not exist!', 'yith-woocommerce-gift-cards' ), $this->gift_card_number );
                    break;
                case self::E_GIFT_CARD_NOT_YOURS:
                    $err = sprintf( esc_html__( 'Sorry, it seems that the gift card code "%s" is not yours and cannot be used for this order.', 'yith-woocommerce-gift-cards' ), $this->gift_card_number );
                    break;
                case self::E_GIFT_CARD_ALREADY_APPLIED:
                    $err = sprintf( esc_html__( 'The gift card code %s has already been applied!', 'yith-woocommerce-gift-cards' ), $this->gift_card_number );
                    break;
                case self::E_GIFT_CARD_EXPIRED:
                    $err = sprintf( esc_html__( 'Sorry, the gift card code %s is expired and cannot be used.', 'yith-woocommerce-gift-cards' ), $this->gift_card_number );
                    break;
                case self::E_GIFT_CARD_DISABLED:
                    $err = sprintf( esc_html__( 'Sorry, the gift card code %s is currently disabled and cannot be used.', 'yith-woocommerce-gift-cards' ), $this->gift_card_number );
                    break;
                case self::E_GIFT_CARD_DISMISSED:
                    $err = sprintf( esc_html__( 'Sorry, the gift card code %s is no longer valid!', 'yith-woocommerce-gift-cards' ), $this->gift_card_number );
                    break;
                case self::E_GIFT_CARD_INVALID_REMOVED:
                    $err = sprintf( esc_html__( 'Sorry, it seems that the gift card code %s is invalid - it has been removed from your cart.', 'yith-woocommerce-gift-cards' ), $this->gift_card_number );
                    break;
                case self::GIFT_CARD_NOT_ALLOWED_FOR_PURCHASING_GIFT_CARD:
                    $err = esc_html__( 'Gift card codes cannot be used to purchase other gift cards', 'yith-woocommerce-gift-cards' );
                    break;

            }

            return apply_filters( 'yith_ywgc_get_gift_card_error', $err, $err_code, $this );
        }

        /**
         * Retrieve a message for a successful gift card status
         *
         * @param string $err_code
         *
         * @return string
         */
        public function get_gift_card_message( $err_code ) {

            $err = '';

            switch ( $err_code ) {

                case self::GIFT_CARD_SUCCESS:
                    $err = esc_html__( 'Gift card code successfully applied.', 'yith-woocommerce-gift-cards' );
                    break;
                case self::GIFT_CARD_REMOVED:
                    $err = esc_html__( 'Gift card code successfully removed.', 'yith-woocommerce-gift-cards' );
                    break;

                case self::GIFT_CARD_NOT_ALLOWED_FOR_PURCHASING_GIFT_CARD:
                    $err = esc_html__( 'Gift card codes cannot be used to purchase other gift cards', 'yith-woocommerce-gift-cards' );
                    break;
            }

            return apply_filters( 'yith_ywgc_get_gift_card_message', $err, $err_code, $this );
        }

        /**
         * Check if the gift card has been sent
         */
        public function has_been_sent() {
            return $this->delivery_send_date;
        }

        /**
         * Set the gift card as sent
         */
        public function set_as_sent() {
            $this->delivery_send_date = current_time( 'timestamp' );
            update_post_meta( $this->ID, self::META_SEND_DATE, $this->delivery_send_date );
        }

        public function set_as_code_not_valid() {
            $this->gift_card_number = 'NOT VALID';
            $this->set_status( self::STATUS_CODE_NOT_VALID );

        }

        /**
         * Set the gift card as pre-printed i.e. the code is manually entered instead of being auto generated
         */
        public function set_as_pre_printed() {
            $this->set_status( self::STATUS_PRE_PRINTED );
        }

        /**
         * Check if the gift card is pre-printed
         */
        public function is_pre_printed() {

            return self::STATUS_PRE_PRINTED == $this->status;
        }

        /**
         * Check if the gift card is expired
         */
        public function is_expired() {

            if ( ! $this->expiration ) {
                return false;
            }

            return time() > $this->expiration;
        }

        /**
         * Retrieve if a gift card is disabled
         *
         * @return bool
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function is_disabled() {

            return self::STATUS_DISABLED == $this->status;
        }

        /**
         * Set the gift card enabled status
         *
         * @param bool|false $enabled
         *
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function set_enabled_status( $enabled = false ) {

            $current_status = $this->is_enabled();

            if ( $current_status == $enabled ) {
                return;
            }

            //  If the gift card is dismissed, stop now
            if ( $this->is_dismissed() ) {
                return;
            }

            $this->set_status( $enabled ? 'publish' : self::STATUS_DISABLED );
        }

        /**
         * Set the gift card status
         *
         * @param string $status
         *
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function set_status( $status ) {

            $this->status = $status;

            if ( $this->ID ) {
                $args = array(
                    'ID'          => $this->ID,
                    'post_status' => $status,
                );

                wp_update_post( $args );
            }
        }

        /**
         * Retrieve all scheduled gift cards to be sent on a specific day or up to the specific day if $include_old is true
         *
         * @param string $send_date the gift card scheduled day
         * @param string $relation  the conditional relation for gift cards date specified
         *
         * @return array
         */
        public static function get_postdated_gift_cards( $send_date, $relation = '<=' ) {

            $args = array(
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key'     => self::META_DELIVERY_DATE,
                        'value'   => $send_date,
                        'compare' => $relation,
                    ),
                    array(
                        'key'   => self::META_SEND_DATE,
                        'value' => '',
                    ),
                ),

                'post_type'      => YWGC_CUSTOM_POST_TYPE_NAME,
                'fields'         => 'ids',
                'post_status'    => 'publish',
                'posts_per_page' => - 1,
            );


            return get_posts( $args );
        }

        /**
         * Save the current object
         */
        public function save() {


            // Create post object args
            $args = array(
                'post_title'  => $this->gift_card_number,
                'post_status' => $this->status,
                'post_type'   => YWGC_CUSTOM_POST_TYPE_NAME,
                'post_parent' => $this->product_id,
            );

            if ( $this->ID == 0 ) {
                // Insert the post into the database
                $this->ID = wp_insert_post ( $args );

            } else {
                $args["ID"] = $this->ID;
                $this->ID   = wp_update_post ( $args );
            }

            $total_balance_rounded = round($this->total_balance, 2);
            $total_amount_rounded = round($this->total_amount, 2);

            //  Save Gift Card post_meta
            update_post_meta ( $this->ID, self::META_ORDER_ID, $this->order_id );
            update_post_meta ( $this->ID, self::META_CUSTOMER_ID, $this->customer_id );
            update_post_meta ( $this->ID, self::META_BALANCE_TOTAL, $total_balance_rounded );
            update_post_meta ( $this->ID, self::META_AMOUNT_TOTAL, $total_amount_rounded );


            $order_user_id = get_post_meta($this->order_id, '_customer_user', true);

            update_post_meta ( $this->ID, '_ywgc_sender_user_id', $order_user_id );

            $date_format = apply_filters('yith_wcgc_date_format','Y-m-d');

            update_post_meta( $this->ID, self::META_SENDER_NAME, $this->sender_name );
            update_post_meta( $this->ID, self::META_RECIPIENT_NAME, $this->recipient_name );
            update_post_meta( $this->ID, self::META_RECIPIENT_EMAIL, $this->recipient );
            update_post_meta( $this->ID, self::META_MESSAGE, str_replace( '\\','',$this->message ) );
            update_post_meta( $this->ID, self::META_CURRENCY, $this->currency );
            update_post_meta( $this->ID, self::META_VERSION, $this->version );
            update_post_meta( $this->ID, self::META_IS_POSTDATED, $this->postdated_delivery );

            if ( $this->postdated_delivery ) {

                update_post_meta( $this->ID, self::META_DELIVERY_DATE, $this->delivery_date );

                //Update also the delivery date with format
                $delivery_date_format = date_i18n ( $date_format, $this->delivery_date );
                update_post_meta( $this->ID, '_ywgc_delivery_date_formatted', $delivery_date_format );

                update_post_meta( $this->ID, self::META_SEND_DATE, $this->delivery_send_date );
            }
            else{

                $delivery_date_format = date_i18n ( $date_format, time() );
                update_post_meta( $this->ID, '_ywgc_delivery_date_formatted', $delivery_date_format );
            }

            update_post_meta( $this->ID, self::META_HAS_CUSTOM_DESIGN, $this->has_custom_design );

            $expiration_in_timestamp = strtotime( $this->expiration ) != '' ? strtotime( $this->expiration ) : $this->expiration;

            $expiration_date_format = $this->expiration != '0' ? date_i18n ( $date_format, $this->expiration ) : '';

            update_post_meta( $this->ID, self::META_EXPIRATION,  $expiration_in_timestamp );
            update_post_meta( $this->ID, '_ywgc_expiration_date_formatted', $expiration_date_format );


            update_post_meta( $this->ID, self::META_DESIGN_TYPE, $this->design_type );
            update_post_meta( $this->ID, self::META_DESIGN, $this->design );

            update_post_meta( $this->ID, self::META_IS_DIGITAL, $this->is_digital );
            update_post_meta( $this->ID, self::META_INTERNAL_NOTES, $this->internal_notes );

            return $this->ID;

        }

        /**
         * The gift card is nulled and no more usable
         *
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function set_dismissed_status() {
            $this->set_status( self::STATUS_DISMISSED );
        }

        /**
         * The gift card code is duplicate and the gift card is not usable until a new, valid, code is set
         *
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function set_duplicated_status() {
            $this->set_status( self::STATUS_DISMISSED );
        }

        /**
         * Check if the gift card is dismissed
         *
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function is_dismissed() {

            return self::STATUS_DISMISSED == $this->status;
        }


        /**
         * Retrieve the status label for every gift card status
         *
         * @return string
         */
        public function get_status_label() {
            $label = '';

            switch ( $this->status ) {
                case self::STATUS_DISABLED:
                    $label = esc_html__( "The gift card has been disabled", 'yith-woocommerce-gift-cards' );
                    break;
                case self::STATUS_ENABLED:
                    $label = esc_html__( "Valid", 'yith-woocommerce-gift-cards' );
                    break;
                case self::STATUS_DISMISSED:
                    $label = esc_html__( "No longer valid, replaced by another code", 'yith-woocommerce-gift-cards' );
                    break;
            }

            return $label;
        }

        /**
         * Clone the current gift card using the remaining balance as new amount
         *
         * @param string $new_code the code to be used for the new gift card
         *
         * @return YWGC_Gift_Card_Premium
         *
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function clone_gift_card( $new_code = '' ) {

            $new_gift = new YWGC_Gift_Card_Premium();

            $new_gift->product_id           = $this->product_id;
            $new_gift->order_id             = $this->order_id;
            $new_gift->sender_name          = $this->sender_name;
            $new_gift->recipient_name       = $this->recipient_name;
            $new_gift->recipient            = $this->recipient;
            $new_gift->message              = $this->message;
            $new_gift->postdated_delivery   = $this->postdated_delivery;
            $new_gift->delivery_date        = $this->delivery_date;
            $new_gift->delivery_send_date   = $this->delivery_send_date;
            $new_gift->has_custom_design    = $this->has_custom_design;
            $new_gift->expiration           = $this->expiration;
            $new_gift->design_type          = $this->design_type;
            $new_gift->design               = $this->design;
            $new_gift->currency             = $this->currency;
            $new_gift->status               = $this->status;

            $new_gift->gift_card_number = $new_code;

            //  Set the amount of the cloned gift card equal to the balance of the old one
            $new_gift->total_amount = $this->get_balance();
            $new_gift->update_balance( $new_gift->total_amount );

            return $new_gift;
        }


        public function get_formatted_date( $date ){

            $date_format = apply_filters( 'yith_wcgc_date_format','Y-m-d' );

            $date = !is_numeric($date) ? strtotime( $date ) : $date ;

            $formatted_date = date_i18n( $date_format, $date );
            return $formatted_date;
        }


    }
}