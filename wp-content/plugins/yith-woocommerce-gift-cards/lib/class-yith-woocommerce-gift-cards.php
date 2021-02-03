<?php
if ( ! defined ( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


if ( ! class_exists ( 'YITH_WooCommerce_Gift_Cards' ) ) {
	
	/**
	 *
	 * @class   YITH_WooCommerce_Gift_Cards
	 * @package Yithemes
	 * @since   1.0.0
	 * @author  Lorenzo Giuffrida
	 */
	class YITH_WooCommerce_Gift_Cards {
		
		const YWGC_DB_VERSION_OPTION = 'yith_gift_cards_db_version';

		/**
		 * Single instance of the class
		 *
		 * @since 1.0.0
		 */
		protected static $instance;

        /**
         * @var string Premium version landing link
         */
        protected $_premium_landing = '//yithemes.com/themes/plugins/yith-woocommerce-gift-cards/';

        /**
         * @var string Plugin official documentation
         */
        protected $_official_documentation = 'https://docs.yithemes.com/yith-woocommerce-gift-cards/';

        /**
         * @var string Plugin panel page
         */
        protected $_panel_page = 'yith_woocommerce_gift_cards_panel';

        /**
         * @var string Official plugin landing page
         */
        protected $_premium_live = 'https://plugins.yithemes.com/yith-woocommerce-gift-cards/';

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
			
			$this->includes ();
			$this->init_hooks ();
			$this->start ();
		}
		
		
		public function includes() {
			//todo check if free plugin contains them
			require_once ( YITH_YWGC_DIR . 'lib/class-yith-wc-product-gift-card.php' );
			require_once ( YITH_YWGC_DIR . 'lib/class-yith-ywgc-cart-checkout.php' );
			require_once ( YITH_YWGC_DIR . 'lib/class-yith-ywgc-emails.php' );
			require_once ( YITH_YWGC_DIR . 'lib/class-yith-ywgc-gift-cards-table.php' );
		}
		
		public function init_hooks() {
			/**
			 * Do some stuff on plugin init
			 */
			add_action ( 'init', array( $this, 'on_plugin_init' ) );
			

			add_filter ( 'yith_plugin_status_sections', array( $this, 'set_plugin_status' ) );

            /* === Show Plugin Information === */

            add_filter( 'plugin_action_links_' . plugin_basename( YITH_YWGC_DIR . '/' . basename( YITH_YWGC_FILE ) ), array( $this, 'action_links') );

            add_filter( 'yith_show_plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 5 );


            /**
             * Including the GDPR
             */
            add_action( 'plugins_loaded', array( $this, 'load_privacy' ), 20 );
            
            $this->register_custom_post_statuses ();

            $save_product_meta_hook = version_compare( WC()->version, '3.0.0', '>=' ) ? 'woocommerce_admin_process_product_object' : 'woocommerce_process_product_meta';

            /**
             * Add an option to let the admin set the gift card as a physical good or digital goods
             */
            add_filter ( 'product_type_options', array( $this, 'add_type_option' ) );

            /**
             * Append CSS for the email being sent to the customer
             */
            add_action ( 'yith_gift_cards_template_before_add_to_cart_form', array( $this, 'append_css_files' ) );

            /**
             * Add taxonomy and assign it to gift card products
             */
            add_action ( 'init', array( $this, 'create_gift_cards_category' ) );

            /**
             * remove the view button in the gift card taxonomy
             */
            add_filter( 'giftcard-category_row_actions',  array( $this, 'ywgc_taxonomy_remove_view_row_actions' ), 10, 1 );

            add_filter ( 'yith_ywgc_get_product_instance', array( $this, 'get_product_instance' ), 10, 2 );

            /**
             * Select the date format option
             */
            add_filter('yith_wcgc_date_format', array( $this, 'yith_ywgc_date_format_callback' ), 10, 1);

            //  Elementor Widgets integration
            if ( defined('ELEMENTOR_VERSION') ) {
                require_once ( YITH_YWGC_DIR . 'lib/third-party/elementor/class-ywgc-elementor.php' );
            }

        }

        public function start() {
            //  Init the backend
            $this->backend = YITH_YWGC_Backend::get_instance ();

            //  Init the frontend
            $this->frontend = YITH_YWGC_Frontend::get_instance ();
        }

		
		/**
		 * Execute update on data used by the plugin that has been changed passing
		 * from a DB version to another
		 */
		public function update_database() {
			
			/**
			 * Init DB version if not exists
			 */
			$db_version = get_option ( self::YWGC_DB_VERSION_OPTION );
			
			if ( ! $db_version ) {
				//  Update from previous version where the DB option was not set
				global $wpdb;
				
				//  Update metakey from YITH Gift Cards 1.0.0
				$query = "Update {$wpdb->prefix}woocommerce_order_itemmeta
                        set meta_key = '" . YWGC_META_GIFT_CARD_POST_ID . "'
                        where meta_key = 'gift_card_post_id'";
				$wpdb->query ( $query );
				
				$db_version = '1.0.0';
			}
			
			/**
			 * Start the database update step by step
			 */
			
			if ( version_compare ( $db_version, '1.0.1', '<=' ) ) {
				
				//  extract the user_id from the order where a gift card is applied and register
				//  it so the gift card will be shown on my-account
				
				$args = array(
					'numberposts' => - 1,
					'meta_key'    => YWGC_META_GIFT_CARD_ORDERS,
					'post_type'   => YWGC_CUSTOM_POST_TYPE_NAME,
					'post_status' => 'any',
				);
				
				//  Retrieve the gift cards matching the criteria
				$posts = get_posts ( $args );
				
				foreach ( $posts as $post ) {
					$gift_card = new YITH_YWGC_Gift_Card( array( 'ID' => $post->ID ) );
					
					if ( ! $gift_card->exists () ) {
						continue;
					}
					
					/** @var WC_Order $order */
					$orders = $gift_card->get_registered_orders ();
					foreach ( $orders as $order_id ) {
						$order = wc_get_order ( $order_id );
						if ( $order ) {
							$gift_card->register_user ( yit_get_prop ( $order, 'customer_user' ) );
						}
					}
				}
				
				$db_version = '1.0.2';  //  Continue to next step...
			}

			if ( version_compare( $db_version, '1.0.2', '<=' ) ) {
				flush_rewrite_rules();
				$db_version = '1.0.3';  //  Continue to next step...
			}
			
			//  Update the current DB version
			update_option ( self::YWGC_DB_VERSION_OPTION, YITH_YWGC_DB_CURRENT_VERSION );
		}
		
		/**
		 *  Execute all the operation need when the plugin init
		 */
		public function on_plugin_init() {

			$this->init_post_type ();
			
			$this->init_plugin ();
			
			$this->update_database ();

            $is_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;
            if ( is_admin() && !$is_ajax ) {
                $this->init_metabox();
            }
		}

		/**
		 * Initialize plugin data, if any
		 */
		public function init_plugin() {
			//nothing to do
		}
		
		public function current_user_can_create() {
			return apply_filters ( 'ywgc_can_create_gift_card', true );
		}
		
		/**
		 * Register the custom post type
		 */
        public function init_post_type() {
            $args = array(
                'labels'        => array(
                    'name'               => _x ( 'All Gift Cards', 'post type general name', 'yith-woocommerce-gift-cards' ),
                    'singular_name'      => _x ( 'Gift Card', 'post type singular name', 'yith-woocommerce-gift-cards' ),
                    'menu_name'          => _x ( 'Gift Cards', 'admin menu', 'yith-woocommerce-gift-cards' ),
                    'name_admin_bar'     => _x ( 'Gift Card', 'add new on admin bar', 'yith-woocommerce-gift-cards' ),
                    'add_new'            => _x ( 'Create Code', 'admin menu item', 'yith-woocommerce-gift-cards' ),
                    'add_new_item'       => esc_html__( 'Create Gift Card Code', 'yith-woocommerce-gift-cards' ),
                    'new_item'           => esc_html__( 'New Gift Card', 'yith-woocommerce-gift-cards' ),
                    'edit_item'          => esc_html__( 'Edit Gift Card', 'yith-woocommerce-gift-cards' ),
                    'view_item'          => esc_html__( 'View Gift Card', 'yith-woocommerce-gift-cards' ),
                    'all_items'          => esc_html__( 'All gift cards', 'yith-woocommerce-gift-cards' ),
                    'search_items'       => esc_html__( 'Search gift cards', 'yith-woocommerce-gift-cards' ),
                    'parent_item_colon'  => esc_html__( 'Parent gift cards:', 'yith-woocommerce-gift-cards' ),
                    'not_found'          => esc_html__( 'No gift cards found.', 'yith-woocommerce-gift-cards' ),
                    'not_found_in_trash' => esc_html__( 'No gift cards found in Trash.', 'yith-woocommerce-gift-cards' )
                ),
                'label'         => esc_html__( 'Gift Cards', 'yith-woocommerce-gift-cards' ),
                'description'   => esc_html__( 'Gift Cards', 'yith-woocommerce-gift-cards' ),
                // Features this CPT supports in Post Editor
                'supports'      => array( 'title' ),
                'hierarchical'  => false,
                'capability_type'     => 'product',
                'capabilities'        => array(
                    'delete_post'        => 'edit_posts',
                    'delete_posts'       => 'edit_posts',
                ),
                'public'        => false,
                'show_in_menu'  => apply_filters('yith_wcgc_show_in_menu_cpt', false), //hide in the WordPress dashboard
                'show_ui'       => true,
                'menu_position' => 9,
                'can_export'    => true,
                'has_archive'   => false,
                'menu_icon'     => 'dashicons-clipboard',
                'query_var'     => false,
            );

            // Registering your Custom Post Type
            register_post_type ( YWGC_CUSTOM_POST_TYPE_NAME, $args );


        }

		/**
		 * Retrieve a gift card product instance from the gift card code
		 *
		 * @param string $code the gift card code to search for
		 *
		 * @return YITH_YWGC_Gift_Card
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function get_gift_card_by_code( $code ) {
			
			$args = array( 'gift_card_number' => $code );
			
			return new YITH_YWGC_Gift_Card( $args );
		}

        /**
         * Add option select the date format
         *
         * @author Francisco Mendoza
         * @since  2.0.5
         */

        public function yith_ywgc_date_format_callback( $date_format ) {

            $date_format_in_js = get_option( 'ywgc_plugin_date_format_option', 'yy-mm-dd' );

            $js_to_php_date_format = array(
                'd' => 'j',
                'dd' => 'd',
                'o' => 'z',
                'D' => 'D',
                'DD' => 'l',
                'm' => 'n',
                'mm' => 'm',
                'M' => 'M',
                'MM' => 'F',
                'y' => 'y',
                'yy' => 'Y',
            );

            $date_format_in_php = strtr($date_format_in_js, $js_to_php_date_format);


            return $date_format_in_php;
        }

        /**
         * Including the GDRP
         */
        public function load_privacy() {

            if ( class_exists( 'YITH_Privacy_Plugin_Abstract' ) )
                require_once( YITH_YWGC_DIR . 'lib/class.yith-woocommerce-gift-cards-privacy.php' );

        }

        // register new taxonomy which applies to attachments
        public function create_gift_cards_category() {

            $labels = array(
                'name'              => esc_html__('Gift card categories','yith-woocommerce-gift-cards'),
                'singular_name'     => esc_html__('Gift card category','yith-woocommerce-gift-cards'),
                'search_items'      => esc_html__('Search gift card categories','yith-woocommerce-gift-cards'),
                'all_items'         => esc_html__('All gift card categories','yith-woocommerce-gift-cards'),
                'parent_item'       => esc_html__('Parent gift card category','yith-woocommerce-gift-cards'),
                'parent_item_colon' => esc_html__('Parent gift card category:','yith-woocommerce-gift-cards'),
                'edit_item'         => esc_html__('Edit gift card category','yith-woocommerce-gift-cards'),
                'update_item'       => esc_html__('Update gift card category','yith-woocommerce-gift-cards'),
                'add_new_item'      => esc_html__('Add new gift card category','yith-woocommerce-gift-cards'),
                'new_item_name'     => esc_html__('New gift card category name','yith-woocommerce-gift-cards'),
                'menu_name'         => esc_html__('Gift card category','yith-woocommerce-gift-cards')
            );

            $args = array(
                'labels'            => $labels,
                'hierarchical'      => true,
                'query_var'         => true,
                'rewrite'           => true,
                'show_admin_column' => true,
                'show_in_menu'      => false, //hide in the WordPress dashboard
                'show_ui'           => true,
                'public'            => true,
            );

            register_taxonomy ( YWGC_CATEGORY_TAXONOMY, array( 'attachment', 'product' ), $args );

            wp_insert_term(
                esc_html__('None','yith-woocommerce-gift-cards'),
                YWGC_CATEGORY_TAXONOMY,
                array(
                    'description' => esc_html__('Select this category in your gift card product if you do not want to display images in your gift card gallery','yith-woocommerce-gift-cards'),
                    'slug' => 'none',
                )
            );

            wp_insert_term(
                esc_html__('All','yith-woocommerce-gift-cards'),
                YWGC_CATEGORY_TAXONOMY,
                array(
                    'description' => esc_html__('Select this category in your gift card product if you want to display all the images categories in your gift card gallery','yith-woocommerce-gift-cards'),
                    'slug' => 'all',
                )
            );
        }

        // remove the view button in the gift card taxonomy
        public function ywgc_taxonomy_remove_view_row_actions( $actions ){

            unset( $actions['view'] );
            return $actions;
        }


        /**
         * Register all the custom post statuses of gift cards
         *
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function register_custom_post_statuses() {

            register_post_status ( YITH_YWGC_Gift_Card::STATUS_DISABLED, array(
                    'label'                     => esc_html__( 'Disabled', 'yith-woocommerce-gift-cards' ),
                    'public'                    => true,
                    'exclude_from_search'       => false,
                    'show_in_admin_all_list'    => true,
                    'show_in_admin_status_list' => true,
                    'post_type'                 => array( 'gift_card' ),
                    'label_count'               => _n_noop ( esc_html__( 'Disabled', 'yith-woocommerce-gift-cards' ) . '<span class="count"> (%s)</span>', esc_html__( 'Disabled', 'yith-woocommerce-gift-cards' ) . ' <span class="count"> (%s)</span>' ),
                )
            );

            register_post_status ( YITH_YWGC_Gift_Card::STATUS_DISMISSED, array(
                    'label'                     => esc_html__( 'Dismissed', 'yith-woocommerce-gift-cards' ),
                    'public'                    => true,
                    'exclude_from_search'       => false,
                    'show_in_admin_all_list'    => true,
                    'show_in_admin_status_list' => true,
                    'post_type'                 => array( 'gift_card' ),
                    'label_count'               => _n_noop ( esc_html__( 'Dismissed', 'yith-woocommerce-gift-cards' ) . '<span class="count"> (%s)</span>', esc_html__( 'Dismissed', 'yith-woocommerce-gift-cards' ) . ' <span class="count"> (%s)</span>' ),
                )
            );

            register_post_status ( YITH_YWGC_Gift_Card::STATUS_CODE_NOT_VALID, array(
                    'label'                     => esc_html__( 'Code not valid', 'yith-woocommerce-gift-cards' ),
                    'public'                    => true,
                    'exclude_from_search'       => false,
                    'show_in_admin_all_list'    => true,
                    'show_in_admin_status_list' => true,
                    'post_type'                 => array( 'gift_card' ),
                    'label_count'               => _n_noop ( esc_html__( 'Code not valid', 'yith-woocommerce-gift-cards' ) . '<span class="count"> (%s)</span>', esc_html__( 'Code not valid', 'yith-woocommerce-gift-cards' ) . ' <span class="count"> (%s)</span>' ),
                )
            );
        }


        /**
         * Append CSS for the email being sent to the customer
         */
        public function append_css_files() {
            YITH_YWGC()->frontend->enqueue_frontend_style ();
        }


        /**
         * Hash the gift card code so it could be used for security checks
         *
         * @param YITH_YWGC_Gift_Card $gift_card
         *
         * @return string
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function hash_gift_card( $gift_card ) {

            return hash ( 'md5', $gift_card->gift_card_number . $gift_card->ID );
        }


        /**
         * Add an option to let the admin set the gift card as a physical good or digital goods.
         *
         * @param array $array
         *
         * @return mixed
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function add_type_option( $array ) {
            if ( isset( $array["virtual"] ) ) {
                $css_class     = $array["virtual"]["wrapper_class"];
                $add_css_class = 'show_if_gift-card';
                $class         = empty( $css_class ) ? $add_css_class : $css_class .= ' ' . $add_css_class;

                $array["virtual"]["wrapper_class"] = $class;
            }

            return $array;
        }

        /**
         * Getter option mandatory recipient
         *
         * @return bool
         * @author Carlos Rodríguez
         * @since  2.2.6
         */
        public function mandatory_recipient() {

            return ( "yes" == get_option ( 'ywgc_recipient_mandatory', 'no' ) );
        }


        /**
         * Generate a new gift card code
         *
         * @return string
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function generate_gift_card_code() {

            //  Create a new gift card number
            $numeric_code     = (string) mt_rand ( 99999999, mt_getrandmax () );
            $numeric_code_len = strlen ( $numeric_code );

            $code     = apply_filters( 'ywgc_random_generate_gift_card_code', strtoupper ( sha1 ( uniqid ( mt_rand () ) ) ) );
            $code_len = strlen ( $code );
            $pattern     = get_option ( 'ywgc_code_pattern', '****-****-****-****' );
            $pattern_len = strlen ( $pattern );

            for ( $i = 0; $i < $pattern_len; $i ++ ) {

                if ( '*' == $pattern[ $i ] ) {
                    //  replace all '*'s with one letter from the unique $code generated
                    $pattern[ $i ] = $code[ $i % $code_len ];
                } elseif ( 'D' == $pattern[ $i ] ) {
                    //  replace all 'D's with one digit from the unique integer $numeric_code generated
                    $pattern[ $i ] = $numeric_code[ $i % $numeric_code_len ];
                }
            }

            return $pattern;
        }

        /**
         * Retrieve if the gift cards should be updated on order refunded
         *
         * @return bool
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function change_status_on_refund() {
            return $this->disable_on_refund () || $this->dismiss_on_refund ();
        }

        /**
         * Retrieve if the gift cards should be updated on order cancelled
         *
         * @return bool
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function change_status_on_cancelled() {
            return $this->disable_on_cancelled () || $this->dismiss_on_cancelled ();
        }

        /**
         * Retrieve if a gift card should be set as dismissed if an order change its status
         * to refunded
         *
         * @return bool
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function dismiss_on_refund() {
            return 'dismiss' == $this->order_refunded_action();
        }

        /**
         * Retrieve if a gift card should be set as disabled if an order change its status
         * to refunded
         *
         * @return bool
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function disable_on_refund() {
            return 'disable' == $this->order_refunded_action();
        }

        /**
         * Retrieve if a gift card should be set as dismissed if an order change its status
         * to cancelled
         *
         * @return bool
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function dismiss_on_cancelled() {
            return 'dismiss' == $this->order_cancelled_action();
        }

        /**
         * Retrieve if a gift card should be set as disabled if an order change its status
         * to cancelled
         *
         * @return bool
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         */
        public function disable_on_cancelled() {
            return 'disable' == $this->order_cancelled_action();
        }


        public function init_metabox() {

            $args = array(
                'label'    => esc_html__( 'Gift card detail', 'yith-woocommerce-gift-cards' ),
                'pages'    => YWGC_CUSTOM_POST_TYPE_NAME,   //or array( 'post-type1', 'post-type2')
                'context'  => 'normal', //('normal', 'advanced', or 'side')
                'priority' => 'high',
                'tabs'     => array(
                    'General' => array( //tab
                        'label'  => esc_html__( 'General', 'yith-woocommerce-gift-cards' ),
                        'fields' => apply_filters ( 'yith_ywgc_gift_card_instance_metabox_custom_fields',
                            array(

                                YITH_YWGC_Gift_Card::META_AMOUNT_TOTAL  => array(
                                    'label'   => esc_html__( 'Purchased amount', 'yith-woocommerce-gift-cards' ),
                                    'desc'    => esc_html__( 'The amount purchased by the customer.', 'yith-woocommerce-gift-cards' ),
                                    'type'    => 'text',
                                    'private' => false,
                                    'std'     => ''
                                ),
                                YITH_YWGC_Gift_Card::META_BALANCE_TOTAL => array(
                                    'label'   => esc_html__( 'Current balance', 'yith-woocommerce-gift-cards' ),
                                    'desc'    => esc_html__( 'The current amount available for the customer.', 'yith-woocommerce-gift-cards' ),
                                    'type'    => 'text',
                                    'private' => false,
                                    'std'     => ''
                                ),
                                '_ywgc_is_digital'                      => array(
                                    'label'   => esc_html__( 'Digital', 'yith-woocommerce-gift-cards' ),
                                    'desc'    => esc_html__( 'Check if the gift card will be sent via email. Leave it unchecked to make this work as a physical product.', 'yith-woocommerce-gift-cards' ),
                                    'type'    => 'checkbox',
                                    'private' => false,
                                    'std'     => ''
                                ),
                                '_ywgc_sender_name'                     => array(
                                    'label'   => esc_html__( 'Sender\'s name', 'yith-woocommerce-gift-cards' ),
                                    'desc'    => esc_html__( 'The name of the digital gift card sender, if any.', 'yith-woocommerce-gift-cards' ),
                                    'type'    => 'text',
                                    'private' => false,
                                    'std'     => '',
                                    'css'     => 'width: 80px;',
                                    'deps'    => array(
                                        'ids'    => '_ywgc_is_digital',
                                        'values' => 'yes',
                                    ),
                                ),
                                '_ywgc_recipient'                       => array(
                                    'label'   => esc_html__( 'Recipient\'s email', 'yith-woocommerce-gift-cards' ),
                                    'desc'    => esc_html__( 'The email address of the digital gift card recipient.', 'yith-woocommerce-gift-cards' ),
                                    'type'    => 'text',
                                    'private' => false,
                                    'std'     => '',
                                    'deps'    => array(
                                        'ids'    => '_ywgc_is_digital',
                                        'values' => 'yes',
                                    ),
                                ),
                                '_ywgc_recipient_name'                       => array(
                                    'label'   => esc_html__( 'Recipient\'s name', 'yith-woocommerce-gift-cards' ),
                                    'desc'    => esc_html__( 'The name of the digital gift card recipient.', 'yith-woocommerce-gift-cards' ),
                                    'type'    => 'text',
                                    'private' => false,
                                    'std'     => '',
                                    'deps'    => array(
                                        'ids'    => '_ywgc_is_digital',
                                        'values' => 'yes',
                                    ),
                                ),
                                '_ywgc_message'                         => array(
                                    'label'   => esc_html__( 'Message', 'yith-woocommerce-gift-cards' ),
                                    'desc'    => esc_html__( 'The message attached to the gift card.', 'yith-woocommerce-gift-cards' ),
                                    'type'    => 'textarea',
                                    'private' => false,
                                    'std'     => '',
                                    'deps'    => array(
                                        'ids'    => '_ywgc_is_digital',
                                        'values' => 'yes',
                                    ),
                                ),

                                '_ywgc_internal_notes' =>array(
                                    'label'   => esc_html__( 'Internal notes', 'yith-woocommerce-gift-cards' ),
                                    'desc'    => esc_html__( 'Enter your notes here. This will only be visible to the admin.', 'yith-woocommerce-gift-cards' ),
                                    'type'    => 'textarea',
                                    'private' => false,
                                    'std'     => '',
                                ),

                            ) ),
                    )
                )
            ) ;

            $metabox = YIT_Metabox ( 'yit-metabox-id' );
            $metabox->init ( $args );

        }


        /**
         * Retrieve the real picture to be used on the gift card preview
         *
         * @param YITH_YWGC_Gift_Card $object
         *
         * @return string
         * @author Lorenzo Giuffrida
         * @since  1.0.0
         *
         */
        public function get_gift_card_header_url( $object ) {
            //  Choose a valid gift card image header
            if ( $object->has_custom_design ) {
                //  There is a custom header image or a template chosen by the customer?
                if ( is_numeric ( $object->design ) ) {
                    //  a template was chosen, retrieve the picture associated
                    $header_image_url = yith_get_attachment_image_url( $object->design, apply_filters( 'ywgc_email_image_size', 'full' ) );
                } else {
                    $header_image_url = YITH_YWGC_SAVE_URL . $object->design;
                }
            } else {
                if ( ! empty( $this->gift_card_header_url ) ) {
                    $header_image_url = $this->gift_card_header_url;
                } else {
                    $header_image_url = YITH_YWGC_ASSETS_IMAGES_URL . 'default-giftcard-main-image.jpg';
                }
            }

            return $header_image_url;
        }


        /**
         * Retrieve the image to be used as a main image for the gift card
         *
         * @param WC_product $product
         *
         * @return string
         */
        public function get_header_image_for_product( $product ) {
            $header_image_url = '';

            if ( $product ) {

                $product_id = yit_get_product_id ( $product );
                if ( $product instanceof WC_Product_Gift_Card ) {
                    $header_image_url = $product->get_manual_header_image ();
                }

                if ( ( '' == $header_image_url ) && has_post_thumbnail ( $product_id ) ) {
                    $image            = wp_get_attachment_image_src ( get_post_thumbnail_id ( $product_id ), apply_filters( 'ywgc_email_image_size', 'full' ) );
                    $header_image_url = $image[0];
                }
            }
            return $header_image_url;
        }

        public function get_default_header_image() {

            $default_header_image_url = get_option ( "ywgc_gift_card_header_url", YITH_YWGC_ASSETS_IMAGES_URL . 'default-giftcard-main-image.jpg' );

            return $default_header_image_url ? $default_header_image_url : YITH_YWGC_ASSETS_IMAGES_URL . 'default-giftcard-main-image.jpg';
        }

        /**
         * Retrieve the default image, configured from the plugin settings, to be used as gift card header image
         *
         * @param YITH_YWGC_Gift_Card|WC_Product $obj
         *
         * @return mixed|string|void
         */
        public function get_header_image( $obj = null ) {

            $header_image_url = '';
            if ( $obj instanceof YITH_YWGC_Gift_Card ) {

                if ( $obj->has_custom_design ) {
                    //  There is a custom header image or a template chosen by the customer?
                    if ( is_numeric ( $obj->design ) ) {
                        //  a template was chosen, retrieve the picture associated
                        $header_image_url = yith_get_attachment_image_url ( $obj->design, apply_filters( 'ywgc_email_image_size', 'full' ) );

                    } else {
                        $header_image_url = YITH_YWGC_SAVE_URL . $obj->design;

                    }
                } else {
                    $product          = wc_get_product ( $obj->product_id );
                    $header_image_url = $this->get_header_image_for_product ( $product );

                }
            }

            if ( is_object( $obj ) ){
                if ( get_class( $obj ) == 'WC_Product_Gift_Card' ){

                    $image_id = $obj->get_manual_header_image ( $obj->get_id(), 'id' );
                    $header_image_url = wp_get_attachment_url( $image_id );

                }
            }

            if ( ! $header_image_url ) {
                $header_image_url = $this->get_default_header_image ();

            }

            return $header_image_url;
        }

        /**
         * Output a gift cards template filled with real data or with sample data to start editing it
         * on product page
         *
         * @param WC_Product|YITH_YWGC_Gift_Card $object
         * @param string                            $context
         */
        public function preview_digital_gift_cards( $object, $context = 'shop', $case = 'recipient' ) {

            if ( $object instanceof WC_Product ) {
                $product_type = version_compare ( WC ()->version, '3.0', '<' ) ? $object->product_type : $object->get_type ();

                $header_image_url = $this->get_header_image ( $object );

                // check if the admin set a default image for gift card
                $amount = 0;
                if ( $object instanceof WC_Product_Simple || $object instanceof WC_Product_Variable || $object instanceof WC_Product_Yith_Bundle ) {
                    $amount = yit_get_display_price ( $object );
                }

                $amount = wc_format_decimal ( $amount );
                $formatted_price = wc_price ( $amount );

                $gift_card_code  = "xxxx-xxxx-xxxx-xxxx";
                $message         = apply_filters ( 'yith_ywgc_gift_card_template_message_text', esc_html__( "Your message will show up here…", 'yith-woocommerce-gift-cards' ) );
            } else if ( $object instanceof YITH_YWGC_Gift_Card ) {

                $header_image_url = $this->get_header_image ( $object );

                $amount          = $object->total_amount;
                $formatted_price = apply_filters ( 'yith_ywgc_gift_card_template_amount', wc_price ( $amount ), $object, $amount );

                $gift_card_code = $object->gift_card_number;
                $message        = $object->message;

            }

            // Checking if the image sent is a product image, if so then we set $header_image_url with correct url
            if ( isset( $header_image_url ) ){
                if ( strpos( $header_image_url, '-yith_wc_gift_card_premium_separator_ywgc_template_design-') !== false ) {
                    $array_header_image_url = explode( "-yith_wc_gift_card_premium_separator_ywgc_template_design-", $header_image_url );
                    $header_image_url = $array_header_image_url['1'];
                }
            }

            $product_id = isset($object->product_id) ? $object->product_id : '';

            $args = array(
                'company_logo_url' => ( "yes" == get_option ( "ywgc_shop_logo_on_gift_card", 'no' ) ) ? get_option ( "ywgc_shop_logo_url", YITH_YWGC_ASSETS_IMAGES_URL . 'default-giftcard-main-image.png' ) : '',
                'header_image_url' => $header_image_url,
                'default_header_image_url' => $this->get_default_header_image(),
                'formatted_price'  => $formatted_price,
                'gift_card_code'   => $gift_card_code,
                'message'          => $message,
                'context'          => $context,
                'object'		   => $object,
                'product_id'	   => $product_id,
                'case'             => $case,

            );

            wc_get_template ( 'yith-gift-cards/ywgc-gift-card-template.php', $args, '', trailingslashit ( YITH_YWGC_TEMPLATES_DIR ) );

        }

        /**
         * Perform some check to a gift card that should be applied to the cart
         * and retrieve a message code
         *
         * @param YWGC_Gift_Card $gift
         *
         * @return bool
         */
        public function check_gift_card( $gift, $remove = false ) {
            $err_code = '';

            if ( ! $gift->exists () ) {
                $err_code = YITH_YWGC_Gift_Card::E_GIFT_CARD_NOT_EXIST;
            } elseif ( ! $gift->is_owner ( get_current_user_id () ) ) {
                $err_code = YITH_YWGC_Gift_Card::E_GIFT_CARD_NOT_YOURS;
            } elseif ( isset( WC ()->cart->applied_gift_cards[ $gift->get_code () ] ) ) {
                $err_code = YITH_YWGC_Gift_Card::E_GIFT_CARD_ALREADY_APPLIED;
            } elseif ( $gift->is_expired () ) {
                $err_code = YITH_YWGC_Gift_Card::E_GIFT_CARD_EXPIRED;
            } elseif ( $gift->is_disabled () ) {
                $err_code = YITH_YWGC_Gift_Card::E_GIFT_CARD_DISABLED;
            } elseif ( $gift->is_dismissed () ) {
                $err_code = YITH_YWGC_Gift_Card::E_GIFT_CARD_DISMISSED;
            } elseif( apply_filters('yith_wcgc_deny_usage_of_gift_cards_to_purchase_gift_cards',false ) ){

                foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {

                    $product = $cart_item['data'];

                    if( $product instanceof WC_Product_Gift_Card ){
                        $err_code = YITH_YWGC_Gift_Card::GIFT_CARD_NOT_ALLOWED_FOR_PURCHASING_GIFT_CARD;
                        break;
                    }

                }
            }
            /**
             * If the flag $remove is true and there is an error,
             * the gift card will be removed from the cart, then we set the general
             * error message here.
             * */
            if ( $err_code && $remove ) {
                $err_code = YITH_YWGC_Gift_Card::E_GIFT_CARD_INVALID_REMOVED;
            }

            $err_code = apply_filters ( 'yith_ywgc_check_gift_card', $err_code, $gift );
            if ( $err_code ) {

                if ( $err_msg = $gift->get_gift_card_error ( $err_code ) ) {

                    wc_add_notice ( $err_msg, 'error' );
                }

                return false;
            }

            if ( $gift->get_balance() < pow( 10, - wc_get_price_decimals() ) ) {
                $err_code = YITH_YWGC_Gift_Card::E_GIFT_CARD_EXPIRED;
                $err_msg  = $gift->get_gift_card_error( $err_code );
                wc_add_notice( $err_msg, 'error' );

                return false;
            }

            if ( ! $remove ){

                $ywgc_minimal_car_total = get_option ( 'ywgc_minimal_car_total' );

                if ( WC()->cart->total < $ywgc_minimal_car_total ) {
                    wc_add_notice( esc_html__( 'In order to use the gift card, the minimum total amount in the cart has to be ' . $ywgc_minimal_car_total . get_woocommerce_currency_symbol(), 'yith-woocommerce-gift-cards'), 'error' );

                    return false;
                }

            }

            $items = WC()->cart->get_cart();
            foreach ( $items as $cart_item_key => $values ) {
                $product = $values['data'];
                if ( apply_filters('yith_ywgc_check_subscription_product_on_cart',true) && class_exists('WC_Subscriptions_Product') && WC_Subscriptions_Product::is_subscription( $product ) ) {
                    wc_add_notice( esc_html__( 'It is not possible to add any gift card if the cart contains a subscription-based product', 'yith-woocommerce-gift-cards'), 'error' );

                    return false;
                }
            }


            foreach( WC()->cart->get_coupons() as $coupon ){

                $coupon_code = strtoupper($coupon->get_code()) ;
                $gift_code = strtoupper($gift->get_code()) ;

                if ( $gift_code ==  $coupon_code ) {
                    wc_add_notice( esc_html__( 'This code is already applied', 'yith-woocommerce-gift-cards'), 'error' );

                    return false;
                }
            }


            return apply_filters('yith_ywgc_check_gift_card_return', true );
        }

        /**
         * Retrieve the product instance
         *
         * @param WC_Product_Gift_Card $product
         *
         * @return null|WC_Product
         */
        public function get_product_instance( $product ) {

            global $sitepress;

            if ( $sitepress ) {
                $_wcml_settings = get_option ( '_wcml_settings' );
                if ( isset( $_wcml_settings['trnsl_interface'] ) && '1' == $_wcml_settings['trnsl_interface'] ) {
                    $product_id = yit_get_prop ( $product, 'id' );

                    if ( $product_id ) {
                        $id = yit_wpml_object_id ( $product_id, 'product', true, $sitepress->get_default_language () );

                        if ( $id != $product_id ) {
                            $product = wc_get_product ( $id );
                        }
                    }
                }
            }

            return $product;
        }



        /**
         * Action links
         *
         *
         * @return void
         * @since    1.3.2
         * @author   Daniel Sanchez <daniel.sanchez@yithemes.com>
         */
        public function action_links( $links ) {

            $links = is_array($links) ? $links : array();
            $links = yith_add_action_links( $links, $this->_panel_page, false );

            return $links;

        }

        /**
         * Plugin Row Meta
         *
         *
         * @return void
         * @since    1.3.2
         * @author   Daniel Sanchez <daniel.sanchez@yithemes.com>
         */
        public function plugin_row_meta( $new_row_meta_args, $plugin_meta, $plugin_file, $plugin_data, $status, $init_file = 'YITH_YWGC_FREE_INIT' ) {

            if ( defined( $init_file ) && constant( $init_file ) == $plugin_file ) {
                $new_row_meta_args['slug'] = YITH_YWGC_SLUG;
            }

            return $new_row_meta_args;
        }

	}
}

