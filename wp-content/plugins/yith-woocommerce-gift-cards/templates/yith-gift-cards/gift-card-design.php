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

global $post;

//dont show the design gallery in the physical gift cards
if ( is_product() && is_object($product) && ! $product->is_virtual() ) {
    return;
}

$desings_to_show = get_option('ywgc_template_design_number_to_show', '3' );

$categories_number = count( $item_categories );

$allow_templates = get_option ( "ywgc_template_design", 'yes');

if ( $allow_templates == 'yes' ){
    $display = "";
}
else{
    $display = "display: none";
}

$selected_categories = get_post_meta( $post->ID, 'selected_images_categories', true );

$selected_categories_unserialized = unserialize($selected_categories);

if ( $allow_templates == 'yes' ) : ?>

    <h3><?php echo get_option( 'ywgc_choose_design_title' , esc_html__( "Choose your image", 'yith-woocommerce-gift-cards') ); ?></h3>

    <?php do_action('yith_ywgc_before_choose_design_section'); ?>

    <div class="gift-card-content-editor step-appearance">

        <div id="ywgc-choose-design-preview" class="ywgc-choose-design-preview" style="<?php echo $display ?>" >
            <div class="ywgc-design-list">

                <?php $cnt = 0;

                ?><ul>

                    <!--        Default product image                -->
                    <?php if ( $product instanceof WC_Product_Gift_Card  ) :

                        $default_image_url = YITH_WooCommerce_Gift_Cards::get_instance()->get_default_header_image();
                        $default_image_id = ywgc_get_attachment_id_from_url($default_image_url);

                        $post_thumbnail_id = ! empty( get_post_thumbnail_id( $post->ID ) ) ? get_post_thumbnail_id( $post->ID ) : $default_image_id;
                        $post_thumbnail_url = ! empty( yith_get_attachment_image_url( intval( get_post_thumbnail_id( $post->ID ) ) ) ) ? yith_get_attachment_image_url( intval( get_post_thumbnail_id( $post->ID ) ), 'full' ) : $default_image_url;

                        ?>
                        <li>
                            <div class="ywgc-preset-image ywgc-default-product-image selected_image_parent" data-design-id="<?php echo $post_thumbnail_id; ?>" data-design-url="<?php echo $post_thumbnail_url; ?>" >
                                <?php echo wp_get_attachment_image( intval($post_thumbnail_id ), apply_filters('yith_ywgc_preset_image_size','thumbnail' ) ); ?>
                            </div>
                        </li>
                    <?php endif; ?>

                    <?php
                    foreach ( $item_categories as $item_id => $categories ):

                        $category_id = str_replace( "ywgc-category-", "", $categories );

                        $term_slug_array = array();

                        foreach ( $selected_categories_unserialized  as $selected_categories ) {

                            if ( $selected_categories != 0 ){
                                $term_slug_array[] = get_term( $selected_categories )->slug;
                            }

                        }

                        if ( in_array( 'none', $term_slug_array ) )
                            continue;

                        if ( in_array( $category_id, $selected_categories_unserialized  ) && $item_id != $post->ID || in_array( 'all', $term_slug_array ) || count($selected_categories_unserialized) == 1 ): ?>

                            <li><?php
                            if ($cnt <= ($desings_to_show ) ){ ?>
                                <div class="ywgc-preset-image" data-design-id="<?php echo $item_id; ?>"  data-design-url="<?php echo yith_get_attachment_image_url( intval( $item_id ), 'full' ); ?>" >

                                    <?php echo wp_get_attachment_image( intval( $item_id ), apply_filters('yith_ywgc_preset_image_size','thumbnail' ) ); ?>
                                </div>

                            <?php }

                            $cnt++;

                            if ($cnt == $desings_to_show ) break;

                            ?></li><?php

                        endif;
                    endforeach; ?>
                </ul>
            </div>
        </div>

        <?php do_action('yith_ywgc_after_choose_design_section'); ?>

    </div>
<?php endif;

