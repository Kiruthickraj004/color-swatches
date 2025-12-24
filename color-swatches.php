<?php
/**
 * Plugin Name: Product Color Swatches (Product Switcher)
 * Description: Shows color swatches based on category + size and redirects to color product.
 * Version: 1.0.0
 * Author: KiruthickRaj
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', function () {

    if ( ! is_product() ) {
        return;
    }

    wp_enqueue_style(
        'product-color-swatches',
        plugin_dir_url( __FILE__ ) . 'assets/css/color-swatches.css',
        array(),
        '1.0.0'
    );
});

add_action( 'custom_product_color_swatches', function () {

    global $product;

    if ( ! $product ) {
        return;
    }
    $product_id = $product->get_id();
    $sizes      = $product->get_meta( '_custom_sizes' );
    $colors     = $product->get_meta( '_custom_colors' );

    if ( empty( $sizes ) || empty( $colors ) ) {
        return;
    }
    $reference_size = $sizes[0];
    $terms = wp_get_post_terms( $product_id, 'product_cat' );
    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return;
    }
    usort( $terms, function ( $a, $b ) {
        return $b->parent - $a->parent;
    });

    $child_category = $terms[0];
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'post__not_in'   => array( $product_id ),
        'tax_query'      => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $child_category->term_id,
            ),
        ),
        'meta_query'     => array(
            array(
                'key'     => '_custom_sizes',
                'value'   => '"' . $reference_size . '"',
                'compare' => 'LIKE',
            ),
        ),
    );

    $related_products = get_posts( $args );

    if ( empty( $related_products ) ) {
        return;
    }
    $swatches = array();

    foreach ( $related_products as $post ) {

        $p_colors = get_post_meta( $post->ID, '_custom_colors', true );

        if ( ! is_array( $p_colors ) ) {
            continue;
        }

        foreach ( $p_colors as $color ) {
            if ( ! empty( $color['hex'] ) ) {
                $swatches[] = array(
                    'product_id' => $post->ID,
                    'hex'        => $color['hex'],
                    'name'       => $color['name'],
                    'url'        => get_permalink( $post->ID ),
                );
            }
        }
    }
    foreach ( $colors as $color ) {
        $swatches[] = array(
            'product_id' => $product_id,
            'hex'        => $color['hex'],
            'name'       => $color['name'],
            'url'        => get_permalink( $product_id ),
            'active'     => true,
        );
    }

    if ( empty( $swatches ) ) {
        return;
    }

    ?>
    <div class="product-color-swatches">
        <div class="swatches">
            <?php foreach ( $swatches as $swatch ) : ?>
                <a href="<?php echo esc_url( $swatch['url'] ); ?>"
                   class="swatch <?php echo ! empty( $swatch['active'] ) ? 'active' : ''; ?>"
                   title="<?php echo esc_attr( $swatch['name'] ); ?>"
                   style="background-color: <?php echo esc_attr( $swatch['hex'] ); ?>">
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php

}, 22 );
