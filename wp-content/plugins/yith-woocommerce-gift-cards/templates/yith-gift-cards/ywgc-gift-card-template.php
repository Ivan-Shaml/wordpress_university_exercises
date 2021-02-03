<?php
/**
 * Gift Card product add to cart
 *
 * @author  Yithemes
 * @package YITH WooCommerce Gift Cards
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Context -> email or pdf
 */

$shop_page_url = apply_filters( 'yith_ywgc_shop_page_url_qr', get_permalink ( wc_get_page_id ( 'shop' ) ) ? get_permalink ( wc_get_page_id ( 'shop' ) ) : site_url () );

$date_format = apply_filters('yith_wcgc_date_format','Y-m-d');
$expiration_date = !is_numeric($object->expiration) ? strtotime( $object->expiration ) : $object->expiration ;


?>
<table cellspacing="0" class="ywgc-table-template">

    <?php do_action( 'yith_wcgc_template_before_main_image', $object, $context ); ?>

    <?php if ( $header_image_url = apply_filters( 'ywgc_custom_header_image_url', $header_image_url ) ):

        // This add the default gift card image when the image is lost
        if ( substr($header_image_url, -strlen('/'))=== '/' )
            $header_image_url = $default_header_image_url;

        ?>

        <tr>

            <td class="ywgc-main-image-td" colspan="2">
                <?php
                if ( $object->design_type == 'custom-modal' && $context == 'email' ){
                    $header_image_64 = $object->design;
                    ?>
                    <img src="<?php echo $header_image_64; ?>"
                         class="ywgc-main-image"
                         alt="<?php _e( "Gift card image", 'yith-woocommerce-gift-cards' ); ?>"
                         title="<?php _e( "Gift card image", 'yith-woocommerce-gift-cards' ); ?>">
                <?php }
                else { ?>
                    <img src="<?php echo $header_image_url; ?>"
                        class="ywgc-main-image"
                        alt="<?php _e( "Gift card image", 'yith-woocommerce-gift-cards' ); ?>"
                        title="<?php _e( "Gift card image", 'yith-woocommerce-gift-cards' ); ?>">
                <?php } ?>

            </td>

        </tr>
    <?php endif; ?>

    <?php do_action( 'yith_wcgc_template_after_main_image' , $object, $context ); ?>

    <tr>
        <td class="ywgc-card-product-name" style="float: left">
            <?php

            $product = wc_get_product( $product_id );

            $product_name_text =  is_object( $product ) && $product instanceof WC_Product_Gift_Card ? $product->get_name() : esc_html__( "Gift card", 'yith-woocommerce-gift-cards' );

            echo apply_filters( 'yith_wcgc_template_product_name_text', $product_name_text . ' ' . esc_html__( "on", 'yith-woocommerce-gift-cards' )  . ' ' . get_bloginfo( 'name' ) , $object, $context, $product_id ); ?>
        </td>

        <?php if ( apply_filters( 'ywgc_display_price_template', true, $formatted_price, $object, $context ) ) : ?>

            <td class="ywgc-card-amount" valign="bottom">

                <?php echo apply_filters( 'yith_wcgc_template_formatted_price', $formatted_price, $object, $context ); ?>

            </td>

        <?php endif; ?>

        <?php do_action( 'yith_wcgc_template_after_price', $object, $context ); ?>

    </tr>

    <?php do_action( 'yith_wcgc_template_after_logo_price', $object, $context ); ?>

    <tr>
        <td colspan="2"> <hr style="color: lightgrey"> </td>
    </tr>

    <?php if ( $message ): ?>
        <tr>
            <td class="ywgc-message-text" colspan="2"> <?php echo nl2br(str_replace( '\\','',$message )) ?> </td>
        </tr>
        <tr>
            <td><br></td>
        </tr>
    <?php endif; ?>

    <?php do_action( 'yith_wcgc_template_after_message', $object, $context ); ?>

        <tr>
            <td colspan="2" class="ywgc-card-code-column">
                <span class="ywgc-card-code-title"><?php echo apply_filters('ywgc_preview_code_title', esc_html__( "Gift card code:", 'yith-woocommerce-gift-cards' ) ); ?></span>
                <br>
                <br>
                <span class="ywgc-card-code">  <?php echo $gift_card_code; ?></span>
            </td>
        </tr>


    <?php do_action( 'yith_wcgc_template_after_code', $object, $context ); ?>

        <tr>
            <td colspan="2"> <hr style="color: lightgrey"> </td>
        </tr>

        <tr>
            <td colspan="2" class="ywgc-expiration-message" style="text-align: center"><?php echo get_option( 'ywgc_description_template_email_text', esc_html__( "To use this gift card, you can either enter the code in the gift card field on the cart page or click on the following link to automatically get the discount.", 'yith-woocommerce-gift-cards' ) ); ?></td>
        </tr>

</table>
