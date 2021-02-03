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


global $product;

?>

<h3><?php echo get_option( 'ywgc_delivery_info_title' , esc_html__( "Delivery info", 'yith-woocommerce-gift-cards') ); ?></h3>

<div class="gift-card-content-editor step-content">

    <h5 class="ywgc_recipient_info_title">
        <?php echo get_option( 'ywgc_recipient_info_title' , esc_html__( "RECIPIENT INFO", 'yith-woocommerce-gift-cards') ) ;?>
    </h5>


    <div class="ywgc-single-recipient">
        <div class="ywgc-recipient-name">
            <label for="ywgc-recipient-name"><?php echo apply_filters('ywgc_recipient_name_label',esc_html__( "Name: ", 'yith-woocommerce-gift-cards' )); ?></label>
            <input type="text" id="ywgc-recipient-name" name="ywgc-recipient-name[]" placeholder="<?php _e( "ENTER THE RECIPIENT'S NAME", 'yith-woocommerce-gift-cards' ); ?>" <?php echo ( $mandatory_recipient ) ? 'required' : ''; ?> class="yith_wc_gift_card_input_recipient_details">
        </div>

        <div class="ywgc-recipient-email">
            <label for="ywgc-recipient-email"><?php echo apply_filters('ywgc_recipient_name_label',esc_html__( "Email: ", 'yith-woocommerce-gift-cards' )); ?></label>
            <input type="email" id="ywgc-recipient-email" name="ywgc-recipient-email[]" <?php echo ( $mandatory_recipient ) ? 'required' : ''; ?>
                   class="ywgc-recipient yith_wc_gift_card_input_recipient_details" placeholder="<?php _e( "ENTER THE RECIPIENT'S EMAIL ADDRESS", 'yith-woocommerce-gift-cards' ); ?>"/>
        </div>
    </div>

    <?php if ( ! $mandatory_recipient ): ?>
        <span class="ywgc-empty-recipient-note"><?php echo apply_filters( 'ywgc_empty_recipient_note', esc_html__( "If empty, will be sent to your email address", 'yith-woocommerce-gift-cards' ) ); ?></span>
    <?php endif; ?>


    <?php if ( 'yes' == get_option('ywgc_ask_sender_name', 'yes') ) : ?>

        <h5 class="ywgc-sender-info-title">
            <?php echo get_option( 'ywgc_sender_info_title' , esc_html__( "YOUR INFO", 'yith-woocommerce-gift-cards') ); ;?>
        </h5>

        <div class="ywgc-sender-name">
            <label for="ywgc-sender-name"><?php echo apply_filters('ywgc_sender_name_label',esc_html__( "Name: ", 'yith-woocommerce-gift-cards' )); ?></label>
            <input type="text" name="ywgc-sender-name" id="ywgc-sender-name" value="<?php echo apply_filters('ywgc_sender_name_value','') ?>"
                   placeholder="<?php _e( "ENTER YOUR NAME", 'yith-woocommerce-gift-cards' ); ?>">
        </div>
    <?php endif; ?>
    <div class="ywgc-message">
        <label for="ywgc-edit-message"><?php echo apply_filters('ywgc_edit_message_label',esc_html__( "Message: ", 'yith-woocommerce-gift-cards' )); ?></label>
        <textarea id="ywgc-edit-message" name="ywgc-edit-message" rows="5" placeholder="<?php echo  get_option( 'ywgc_sender_message_placeholder' , esc_html__( 'ENTER A MESSAGE FOR THE RECIPIENT', 'yith-woocommerce-gift-cards' ) ); ?>" ></textarea>
    </div>
</div>