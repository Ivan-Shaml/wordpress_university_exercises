<?php
if ( ! defined ( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


if ( ! class_exists ( 'YITH_YWGC_Emails' ) ) {
	
	/**
	 *
	 * @class   YITH_YWGC_Emails
	 *
	 * @since   1.0.0
	 * @author  Lorenzo Giuffrida
	 */
	class YITH_YWGC_Emails {
		
		
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
			 * Add an email action for sending the digital gift card
			 */
			add_filter ( 'woocommerce_email_actions', array( $this, 'add_gift_cards_trigger_action' ) );
			
			/**
			 * Locate the plugin email templates
			 */
			add_filter ( 'woocommerce_locate_core_template', array( $this, 'locate_core_template' ), 10, 3 );
			
			/**
			 * Add the email used to send digital gift card to woocommerce email tab
			 */
			add_filter ( 'woocommerce_email_classes', array( $this, 'add_woocommerce_email_classes' ) );
			
			/**
			 * Add entry on resend order email list
			 */
			add_filter ( 'woocommerce_resend_order_emails_available', array( $this, 'resend_gift_card_code' ) );
			
			/**
			 * Add information to the email footer
			 */
			add_action ( 'woocommerce_email_footer', array(
				$this,
				'add_footer_information'
			) );
			
			/**
			 * Add CSS style to gift card emails header
			 */
			add_action ( 'woocommerce_email_header', array(
				$this,
				'include_css_for_emails'
			), 10, 2 );
			
			/**
			 * Show an introductory text before the gift cards editor
			 */
			add_action ( 'ywgc_gift_cards_email_before_preview', array(
				$this,
				'show_introductory_text'
			), 10, 2 );
			
			/**
			 * Show the link for cart discount on the gift card email
			 */
			add_action ( 'ywgc_gift_card_email_after_preview', array(
				$this,
				'show_link_for_cart_discount'
			), 10 );
			

			add_action ( 'yith_ywgc_send_gift_card_email', array(
				$this,
				'send_gift_card_email'
			) );
		}
		

		/**
		 * send the gift card code email
		 *
		 * @param YITH_YWGC_Gift_Card|int $gift_card the gift card
		 * @param bool                            $only_new  choose if only never sent gift card should be used
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function send_gift_card_email( $gift_card, $only_new = true ) {

			if ( is_numeric ( $gift_card ) ) {
				$gift_card = new YITH_YWGC_Gift_Card( array( 'ID' => $gift_card ) );
			}
			
			if ( ! $gift_card->exists () ) {
				//  it isn't a gift card
				return;
			}

			if ( (! $gift_card->is_virtual () || empty( $gift_card->recipient )) || apply_filters('yith_wcgc_deny_gift_card_email',false, $gift_card ) ) {
				// not a digital gift card or missing recipient
				return;
			}
			
			if ( $only_new && $gift_card->has_been_sent () ) {
				//  avoid sending emails more than one time
				return;
			}

            $gift_card->recipient = apply_filters( 'ywgc_recipient_email_before_sent_email', $gift_card->recipient, $gift_card );

			do_action('ywgc_before_sent_email_gift_card_notification',$gift_card);

			WC ()->mailer ();
			
			do_action ( 'ywgc-email-send-gift-card_notification', $gift_card, 'recipient' );
			
			do_action ( 'yith_ywgc_gift_card_email_sent', $gift_card );

            $old_file = get_post_meta( $gift_card->ID, 'ywgc_pdf_file', true );

            if ( file_exists( $old_file ) )
                unlink( $old_file );
		}

		/**
		 * Show a link that let the customer to go to the website, adding the discount to the cart
		 *
		 * @param YITH_YWGC_Gift_Card $gift_card
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function show_link_for_cart_discount( $gift_card ) {

            if ( "no" != get_option ( "ywgc_auto_discount_button_activation", 'yes' ) ){

                $shop_page_url = apply_filters( 'yith_ywgc_shop_page_url', get_permalink ( wc_get_page_id ( 'shop' ) ) ? get_permalink ( wc_get_page_id ( 'shop' ) ) : site_url () );


                wc_get_template ( 'emails/automatic-discount.php',
                    array(
                        'apply_discount_url' => apply_filters ( 'yith_ywgc_email_automatic_cart_discount_url', $shop_page_url , $gift_card ),
                        'gift_card' => $gift_card,
                    ),
                    '',
                    YITH_YWGC_TEMPLATES_DIR );

            }

		}

		/**
		 * Show the introductory message on the email being sent
		 *
		 * @param string              $text
		 * @param YITH_YWGC_Gift_Card $gift_card
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function show_introductory_text( $text, $gift_card ) {
			?>
			<p class="center-email"><?php echo apply_filters ( 'ywgc_gift_cards_email_before_preview_text', $text, $gift_card ); ?></p>
			<?php
		}
		
		
		/**
		 * Add CSS style to gift card emails header
		 */
		public function include_css_for_emails( $email_heading, $email = null ) {
			if ( $email == null ) {
				return;
			}
			
			if ( ! isset( $email->object ) ) {
				return;
			}
			
			if ( ! $email->object instanceof YITH_YWGC_Gift_Card ) {
				return;
			}
			
			echo '<style type="text/css">';
			
			include ( YITH_YWGC_ASSETS_DIR . "/css/ywgc-frontend.css" );
			
			wc_get_template ( 'emails/style.css',
				'',
				'',
				YITH_YWGC_TEMPLATES_DIR );
			
			echo '</style>';
		}
		
		/**
		 * Add gift card email to the available email on resend order email feature
		 *
		 * @param array $emails current emails
		 *
		 * @return array
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function resend_gift_card_code( $emails ) {
			$emails[] = 'ywgc-email-send-gift-card';
			
			return $emails;
		}
		
		
		/**
		 * Append CSS for the email being sent to the customer
		 *
		 * @param WC_Email $email the email content
		 */
		public function add_footer_information( $email = null ) {
			if ( $email == null ) {
				return;
			}
			
			if ( ! isset( $email->object ) ) {
				return;
			}
			
			if ( ! $email->object instanceof YITH_YWGC_Gift_Card ) {
				return;
			}
			
			wc_get_template ( 'emails/gift-card-footer.php',
				array(
					'email'     => $email,
					'shop_name' => get_option ( 'ywgc_shop_name', '' ),
				),
				'',
				YITH_YWGC_TEMPLATES_DIR );
		}
		
		/**
		 * Add an email action for sending the digital gift card
		 *
		 * @param array $actions list of current actions
		 *
		 * @return array
		 */
		function add_gift_cards_trigger_action( $actions ) {
			//  Add trigger action for sending digital gift card
			$actions[] = 'ywgc-email-send-gift-card';
			$actions[] = 'ywgc-email-notify-customer';
			
			return $actions;
		}
		
		/**
		 * Locate the plugin email templates
		 *
		 * @param $core_file
		 * @param $template
		 * @param $template_base
		 *
		 * @return string
		 */
		public function locate_core_template( $core_file, $template, $template_base ) {
			$custom_template = array(
				'emails/send-gift-card.php',
				'emails/plain/send-gift-card.php',
				'emails/notify-customer.php',
				'emails/plain/notify-customer.php',
			);
			
			if ( in_array ( $template, $custom_template ) ) {
				$core_file = YITH_YWGC_TEMPLATES_DIR . $template;
			}
			
			return $core_file;
		}
		
		
		/**
		 * Add the email used to send digital gift card to woocommerce email tab
		 *
		 * @param string $email_classes current email classes
		 *
		 * @return mixed
		 */
		public function add_woocommerce_email_classes( $email_classes ) {
			// add the email class to the list of email classes that WooCommerce loads
			$email_classes['ywgc-email-send-gift-card']  = include ( 'emails/class-yith-ywgc-email-send-gift-card.php' );

			return $email_classes;
		}
		
		
	}
}

YITH_YWGC_Emails::get_instance ();