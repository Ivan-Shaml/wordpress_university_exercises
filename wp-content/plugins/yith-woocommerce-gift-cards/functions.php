<?php
if ( ! defined ( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

require_once( YITH_YWGC_DIR . 'lib/class-yith-woocommerce-gift-cards.php' );
require_once( YITH_YWGC_DIR . 'lib/class-yith-ywgc-backend.php' );
require_once( YITH_YWGC_DIR . 'lib/class-yith-ywgc-frontend.php' );
require_once( YITH_YWGC_DIR . 'lib/class-yith-ywgc-gift-card.php' );

/** Define constant values */
defined( 'YWGC_CUSTOM_POST_TYPE_NAME' ) || define( 'YWGC_CUSTOM_POST_TYPE_NAME', 'gift_card' );
defined( 'YWGC_GIFT_CARD_PRODUCT_TYPE' ) || define( 'YWGC_GIFT_CARD_PRODUCT_TYPE', 'gift-card' );
defined( 'YWGC_CATEGORY_TAXONOMY' ) || define( 'YWGC_CATEGORY_TAXONOMY', 'giftcard-category' );


/*  plugin actions */
defined( 'YWGC_ACTION_RETRY_SENDING' ) || define( 'YWGC_ACTION_RETRY_SENDING', 'retry-sending' );
defined( 'YWGC_ACTION_ENABLE_CARD' ) || define( 'YWGC_ACTION_ENABLE_CARD', 'enable-gift-card' );
defined( 'YWGC_ACTION_DISABLE_CARD' ) || define( 'YWGC_ACTION_DISABLE_CARD', 'disable-gift-card' );
defined( 'YWGC_ACTION_VERIFY_CODE' ) || define( 'YWGC_ACTION_VERIFY_CODE', 'ywcgc-verify-code' );
defined( 'YWGC_ACTION_PRODUCT_ID' ) || define( 'YWGC_ACTION_PRODUCT_ID', 'ywcgc-product-id' );

/*  gift card post_metas */
defined( 'YWGC_META_GIFT_CARD_ORDERS' ) || define( 'YWGC_META_GIFT_CARD_ORDERS', '_ywgc_orders' );
defined( 'YWGC_META_GIFT_CARD_CUSTOMER_USER' ) || define( 'YWGC_META_GIFT_CARD_CUSTOMER_USER', '_ywgc_customer_user' ); // Refer to user that use the gift card
defined( 'YWGC_ORDER_ITEM_DATA' ) || define( 'YWGC_ORDER_ITEM_DATA', '_ywgc_order_item_data' );

/*  order item metas    */
defined( 'YWGC_META_GIFT_CARD_POST_ID' ) || define( 'YWGC_META_GIFT_CARD_POST_ID', '_ywgc_gift_card_post_id' );
defined( 'YWGC_META_GIFT_CARD_CODE' ) || define( 'YWGC_META_GIFT_CARD_CODE', '_ywgc_gift_card_code' );
defined( 'YWGC_META_GIFT_CARD_STATUS' ) || define( 'YWGC_META_GIFT_CARD_STATUS', '_ywgc_gift_card_status' );

/* Gift card status */




if ( ! function_exists( 'ywgc_get_status_label' ) ) {
	/**
	 * Retrieve the status label for every gift card status
	 *
	 * @param YITH_YWGC_Gift_Card $gift_card
	 *
	 * @return string
	 */
	function ywgc_get_status_label( $gift_card ) {
		return $gift_card->get_status_label();
	}
}

if ( ! function_exists( 'ywgc_get_order_item_giftcards' ) ) {
	/**
	 * Retrieve the gift card ids associated to an order item
	 *
	 * @param int $order_item_id
	 *
	 * @return string|void
	 * @author Lorenzo Giuffrida
	 * @since  1.0.0
	 */
	function ywgc_get_order_item_giftcards( $order_item_id ) {

		/*
		 * Let third party plugin to change the $order_item_id
		 * 
		 * @since 1.3.7
		 */
		$order_item_id = apply_filters( 'yith_get_order_item_gift_cards', $order_item_id );
		$gift_ids      = wc_get_order_item_meta( $order_item_id, YWGC_META_GIFT_CARD_POST_ID );

		if ( is_numeric( $gift_ids ) ) {
			$gift_ids = array( $gift_ids );
		}

		if ( ! is_array( $gift_ids ) ) {
			$gift_ids = array();
		}

		return $gift_ids;
	}
}

if ( ! function_exists( 'ywgc_set_order_item_giftcards' ) ) {
	/**
	 * Retrieve the gift card ids associated to an order item
	 *
	 * @param int   $order_item_id the order item
	 * @param array $ids           the array of gift card ids associated to the order item
	 *
	 * @return string|void
	 * @author Lorenzo Giuffrida
	 * @since  1.0.0
	 */
	function ywgc_set_order_item_giftcards( $order_item_id, $ids ) {

        $ids = apply_filters( 'yith_ywgc_set_order_item_meta_gift_card_ids', $ids, $order_item_id );

        wc_update_order_item_meta( $order_item_id, YWGC_META_GIFT_CARD_POST_ID, $ids );

        $gift_card_codes = array();
        foreach ( $ids as $gc_id ){

            $gc = new YITH_YWGC_Gift_Card( array( 'ID' => $gc_id ) );
            $gc_code = $gc->get_code();

            $gift_card_codes[] = $gc_code;
        }

        wc_update_order_item_meta( $order_item_id, YWGC_META_GIFT_CARD_CODE, $gift_card_codes );

        do_action( 'yith_ywgc_set_order_item_meta_gift_card_ids_updated', $order_item_id, $ids );
	}
}

if ( ! function_exists( 'wc_help_tip' ) && function_exists( 'WC' ) && version_compare( WC()->version, '2.5.0', '<' ) ) {

	/**
	 * Display a WooCommerce help tip. (Added for compatibility with WC 2.4)
	 *
	 * @since  2.5.0
	 *
	 * @param  string $tip        Help tip text
	 * @param  bool   $allow_html Allow sanitized HTML if true or escape
	 *
	 * @return string
	 */
	function wc_help_tip( $tip, $allow_html = false ) {
		if ( $allow_html ) {
			$tip = wc_sanitize_tooltip( $tip );
		} else {
			$tip = esc_attr( $tip );
		}

		return '<span class="woocommerce-help-tip" data-tip="' . $tip . '"></span>';
	}

}


add_filter( 'yit_fw_metaboxes_type_args', 'ywgc_filter_balance_display' );

if ( ! function_exists( 'ywgc_filter_balance_display' ) ) {
	/**
	 * Fix the current balance display to match WooCommerce settings
	 * @param $args
	 *
	 * @return mixed
	 */
	function ywgc_filter_balance_display( $args ) {

		if ( $args['args']['args']['id'] == '_ywgc_balance_total' ) {
			$args['args']['args']['value'] = round( $args['args']['args']['value'], wc_get_price_decimals() );
		}

		return $args;
	}
}

if ( ! function_exists( 'ywgc_disallow_gift_cards_with_same_title' ) ) {
	/**
	 * Avoid new gift cards with the same name
	 *
	 * @param $messages
	 */
	function ywgc_disallow_gift_cards_with_same_title( $messages ) {
		global $post;
		global $wpdb;
		$title       = $post->post_title;
		$post_id     = $post->ID;

        do_action('yith_ywgc_before_disallow_gift_cards_with_same_title_query', $post_id, $messages );
        
        if (get_post_type($post_id) !== 'gift_card' || (get_post_type($post_id) == 'gift_card' && $title == '')) {
            return $messages;
        }

		$wtitlequery = "SELECT post_title FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'gift_card' AND post_title = %s AND ID != %d ";
		$wresults = $wpdb->get_results( $wpdb->prepare( $wtitlequery, $title, $post_id ) );

		if ( $wresults ) {
			$error_message = __('This title is already used. Please choose another one', 'yith-woocommerce-gift-cards');
			add_settings_error( 'post_has_links', '', $error_message, 'error' );
			settings_errors( 'post_has_links' );
			$post->post_status = 'draft';
			wp_update_post( $post );

			return;
		}

		return $messages;

	}

	add_action( 'post_updated_messages', 'ywgc_disallow_gift_cards_with_same_title' );
}


/* Shortcode to print gift card list */
add_shortcode('yith_wcgc_show_gift_card_list','yith_wcgc_show_gift_card_list');

function yith_wcgc_show_gift_card_list( $atts,$content = null ){
    YITH_YWGC_Frontend::get_instance ()->yith_ywgc_gift_cards_content();
    return ob_get_clean();
}


function ywgc_get_attachment_id_from_url( $attachment_url = '' ) {

    global $wpdb;
    $attachment_id = false;

    if ( '' == $attachment_url )
        return;

    $upload_dir_paths = wp_upload_dir();

    if ( false !== strpos( $attachment_url, $upload_dir_paths['baseurl'] ) ) {

        $attachment_url = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $attachment_url );

        $attachment_url = str_replace( $upload_dir_paths['baseurl'] . '/', '', $attachment_url );

        $attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT wposts.ID FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta 
                          WHERE wposts.ID = wpostmeta.post_id 
                          AND wpostmeta.meta_key = '_wp_attached_file' 
                          AND wpostmeta.meta_value = '%s' 
                          AND wposts.post_type = 'attachment'", $attachment_url ) );
    }

    return $attachment_id;
}


if ( ! function_exists( 'yith_get_attachment_image_url' ) ) {

    /**
     * @return string
     */
    function yith_get_attachment_image_url( $attachment_id, $size = 'thumbnail' ) {

        if ( function_exists( 'wp_get_attachment_image_url' ) ) {
            $header_image_url = wp_get_attachment_image_url( $attachment_id, $size );
        } else {
            $header_image     = wp_get_attachment_image_src( $attachment_id, $size );
            $header_image_url = $header_image['url'];
        }
        $header_image_url = apply_filters('yith_ywcgc_attachment_image_url',$header_image_url);
        return $header_image_url;
    }

}

