<?php
/**
 * This file belongs to the YIT Plugin Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$email_settings_url = site_url() . '/wp-admin/admin.php?page=wc-settings&tab=email';

$general_options = array(

	'general' => array(
        /**
         *
         * General settings
         *
         */
		array(
			'name' => esc_html__( 'General settings', 'yith-woocommerce-gift-cards' ),
			'type' => 'title',
		),
        'ywgc_code_pattern'           => array(
            'id'      => 'ywgc_code_pattern',
            'name'    => esc_html__( 'Gift card code pattern', 'yith-woocommerce-gift-cards' ),
            'desc'    => esc_html__( "Choose the pattern of new gift cards. If you set ***-*** your cards will have a code like: 1ME-D28.", 'yith-woocommerce-gift-cards' ),
            'type'    => 'yith-field',
            'yith-type' => 'text',
            'default' => '****-****-****-****',
        ),


        'ywgc_template_design'        => array(
            'name'    => esc_html__( 'Enable the images gallery', 'yith-woocommerce-gift-cards' ),
            'type'    => 'yith-field',
            'yith-type' => 'onoff',
            'id'      => 'ywgc_template_design',
            'desc'    => esc_html__( 'Allow users to pick the gift card image from those available in the gallery. Note: images that can be used by customers have to be uploaded through the Media gallery. To make the search easier, you can group images into categories (e.g. Christmas, Easter, Birthday, etc.) through this link: ', 'yith-woocommerce-gift-cards' ) . ' <a href="' . admin_url( 'edit-tags.php?taxonomy=giftcard-category&post_type=attachment' ) . '" title="' . esc_html__( 'Set your gallery categories.', 'yith-woocommerce-gift-cards' ) . '">' . esc_html__( 'Set your template categories', 'yith-woocommerce-gift-cards' ) . '</a>',
            'default' => 'yes',
        ),
        'ywgc_template_design_number_to_show'      => array(
            'id'      => 'ywgc_template_design_number_to_show',
            'name'    => esc_html__( 'How many images to show', 'yith-woocommerce-gift-cards' ),
            'desc'    => esc_html__( 'Set how many gift card images to show on the gift card page.', 'yith-woocommerce-gift-cards' ),
            'type'    => 'yith-field',
            'yith-type' => 'number',
            'min'      => 0,
            'step'     => 1,
            'default' => '3',
            'deps'      => array(
                'id'    => 'ywgc_template_design',
                'value' => 'yes',
            )
        ),

        'ywgc_recipient_mandatory'          => array(
            'name'    => esc_html__( 'Make recipient\'s info mandatory', 'yith-woocommerce-gift-cards' ),
            'type'    => 'yith-field',
            'yith-type' => 'onoff',
            'id'      => 'ywgc_recipient_mandatory',
            'desc'    => esc_html__( 'If enabled, the recipient\'s name and email fields will be mandatory in the virtual gift cards.', 'yith-woocommerce-gift-cards' ),
            'default' => 'yes'
        ),

        array(
            'type' => 'sectionend',
        ),

        /**
         *
         * E-mail options & customization
         *
         */

        array(
            'name' => esc_html__( 'Email options', 'yith-woocommerce-gift-cards' ),
            'type' => 'title',
            'desc' => esc_html__( 'You can manage and edit the YITH Gift Card emails in the ', 'yith-woocommerce-gift-cards' ) . '<a href="' . $email_settings_url . '" >' . esc_html__( 'WooCommerce emails settings', 'yith-woocommerce-gift-cards' ) . '</a> ',
        ),
        'ywgc_auto_discount_button_activation'          => array(
            'name'    => esc_html__( 'Show a button in the gift card email', 'yith-woocommerce-gift-cards' ),
            'type'    => 'yith-field',
            'yith-type' => 'onoff',
            'id'      => 'ywgc_auto_discount_button_activation',
            'desc'    => esc_html__( 'If enabled, the gift card dispatch email will contain a link to redirect your user to your site in one click.', 'yith-woocommerce-gift-cards' ),
            'default' => 'yes',
        ),
        'ywgc_email_button_label'           => array(
            'id'      => 'ywgc_email_button_label',
            'name'    => esc_html__( 'Button label', 'yith-woocommerce-gift-cards' ),
            'type'    => 'yith-field',
            'yith-type' => 'text',
            'default' => esc_html__( 'Apply your gift card code', 'yith-woocommerce-gift-cards' ),
            'deps'      => array(
                'id'    => 'ywgc_auto_discount_button_activation',
                'value' => 'yes',
            )
        ),

        array(
            'type' => 'sectionend',
        ),

    ),
);

return apply_filters( 'yith_ywgc_general_options_array', $general_options );
