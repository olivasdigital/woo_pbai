<?php
/**
 * Plugin Name: WooCommerce - List Products by Attributes Individually
 * Plugin URI: #url: woo_pbai
 * Description: List WooCommerce products individually filtered by attributes using a shortcode, ex: [woo_pbai attribute="colour" values="red,black" per_page="5"]
 * Version: 1.0
 * Author: Fred Carbonare
 * Author URI: https://www.olivas.digital/
 * Requires at least: 3.5
 * Tested up to: 3.5
 *
 * Text Domain: -
 * Domain Path: -
 *
 */
 
/*
 * List WooCommerce Products by attributes Individually
 *
 * ex: [woo_pbai attribute="colour" values="red,black" per_page="5"]
 */
function woo_pbai_shortcode( $atts, $content = null ) {

  global $woocommerce, $woocommerce_loop;

	// Get attribuets
	extract(shortcode_atts(array(
		'attribute' => '',
		'values'     => '',
		'per_page'  => '12',
		'columns'   => '4',
	  	'orderby'   => 'title',
	  	'order'     => 'desc',
	), $atts));
	
	// return if parameter is not found
	if ( ! $attribute ) return;

	// Default ordering args
	$ordering_args = $woocommerce->query->get_catalog_ordering_args( $orderby, $order );
	$values = explode(",",$values);
	
	// Define Query Arguments
	$args = array( 
				'post_type'				=> 'product',
				'post_status' 			=> 'publish',
				'ignore_sticky_posts'	=> 1,
				'orderby' 				=> $ordering_args['orderby'],
				'order' 				=> $ordering_args['order'],
				'posts_per_page' 		=> $per_page,
				/*
				'meta_query' 			=> array(
					array(
						'key' 			=> '_visibility',
						'value' 		=> array('catalog', 'visible'),
						'compare' 		=> 'IN'
					)
				), */
				'tax_query' 			=> array(
			    	'relation' => 'AND',
			    	array(
				    	'taxonomy' 		=> 'pa_' . $attribute,
						'terms' 		=> $values,
						'field' 		=> 'slug',
						'operator' 		=> 'IN'
					),
					array(
				    	'taxonomy' 		=> 'product_visibility',
						'terms' 		=> array('exclude-from-search','exclude-from-catalog'),
						'field' 		=> 'slug',
						'operator' 		=> 'NOT IN'
					)
			    )
			);
	
	ob_start();
	
	$products = new WP_Query( $args );

	$woocommerce_loop['columns'] = $columns;

	if ( $products->have_posts() ) : ?>

		<?php woocommerce_product_loop_start(); ?>

			<?php while ( $products->have_posts() ) : $products->the_post(); ?>
				<?php /*
				<div style="overflow: hidden;height: 0px;visibility: hidden;">
<?php print_r($products); ?>
				</div>
				*/ ?>

				<?php
				global $product, $woo_pbai_attribute_name, $woo_pbai_price, $woo_pbai_product_link, $woo_pbai_add_to_cart_url, $woo_pbai_thumb_id, $product_child_item_id;
				if( $product->has_child() )
				{
					$product_childs = $product->get_available_variations();
					foreach ($product_childs as $product_child_item)
					{
						if( isset($product_child_item["attributes"]["attribute_pa_".$attribute]) && in_array($product_child_item["attributes"]["attribute_pa_".$attribute], $values ) )
						{
							
							$product_child_item_id = $product_child_item['variation_id'];
							if( !empty($product_child_item["image_id"]) )
							{
								$woo_pbai_thumb_id = $product_child_item["image_id"];
								// $product->set_gallery_image_ids( $product_child_item["image_id"] );
								remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
								add_action( 'woocommerce_before_shop_loop_item_title', 'woo_pbai_woocommerce_get_product_thumbnail', 10 );
							}

							$taxonomy = 'pa_'.$attribute;
							$meta = get_post_meta($product_child_item['variation_id'], 'attribute_'.$taxonomy, true);
							$term = get_term_by('slug', $meta, $taxonomy);
							$woo_pbai_attribute_name = $term->name;

							$woo_pbai_price = $product_child_item["price_html"];

							$woo_pbai_product_link = $product->get_permalink( );
							$woo_pbai_product_link .= "?attribute_pa_".$attribute."=".$term->slug;

							$woo_pbai_add_to_cart_url ="?add-to-cart=". $product->get_id() ."&variation_id=". $product_child_item['variation_id'];
							
							add_filter( 'woocommerce_loop_product_link', 'woo_pabi_woocommerce_loop_product_link' );
							add_filter( 'woocommerce_variable_price_html', 'woo_pbai_woocommerce_variable_price_html' );
							add_filter( 'the_title', 'woo_pbai_the_title' );
							add_filter( 'woocommerce_product_add_to_cart_url', 'woo_pbai_woocommerce_product_add_to_cart_url' );
							add_filter( 'woocommerce_product_add_to_cart_text', 'woo_pbai_woocommerce_product_add_to_cart_text');

							?>

							<?php wc_get_template_part( 'content', 'product' ); ?>

							<?php
							unset($woo_pbai_attribute_name, $woo_pbai_price, $woo_pbai_product_link, $woo_pbai_add_to_cart_url, $woo_pbai_thumb_id, $product_child_item_id);
							remove_filter( 'woocommerce_loop_product_link', 'woo_pabi_woocommerce_loop_product_link' );
							remove_filter( 'woocommerce_variable_price_html', 'woo_pbai_woocommerce_variable_price_html' );
							remove_filter( 'the_title', 'woo_pbai_the_title' );
							remove_filter( 'woocommerce_product_add_to_cart_url', 'woo_pbai_woocommerce_product_add_to_cart_url' );
							remove_filter( 'woocommerce_product_add_to_cart_text', 'woo_pbai_woocommerce_product_add_to_cart_text');

							if( !empty($product_child_item["image_id"]) )
							{
								add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
							}
							

						}
					}
				}
				else
				{
					?>

					<?php wc_get_template_part( 'content', 'product' ); ?>

					<?php
				}
				?>

			<?php endwhile; // end of the loop. ?>

		<?php woocommerce_product_loop_end(); ?>

	<?php endif;

	wp_reset_postdata();

	return '<div class="woocommerce">' . ob_get_clean() . '</div>';
 
}
add_shortcode("woo_pbai", "woo_pbai_shortcode");


function woo_pbai_the_title( $title )
{
	global $woo_pbai_attribute_name;
	$title .= ' - '. $woo_pbai_attribute_name;
	return $title;
}
function woo_pbai_woocommerce_variable_price_html()
{
	global $woo_pbai_price;
	return $woo_pbai_price;
}
function woo_pbai_woocommerce_product_add_to_cart_url()
{
	global $woo_pbai_add_to_cart_url;
	return $woo_pbai_add_to_cart_url;
}
function woo_pabi_woocommerce_loop_product_link()
{
	global $woo_pbai_product_link;
	return $woo_pbai_product_link;
}
function woo_pbai_woocommerce_product_add_to_cart_text()
{
	return "Comprar";
}
function woo_pbai_woocommerce_get_product_thumbnail( $size = 'woocommerce_thumbnail', $deprecated1 = 0, $deprecated2 = 0 ) {
	global $product, $woo_pbai_thumb_id, $product_child_item_id;

	$attr = array( 'class' => "attachment-woocommerce_thumbnail size-woocommerce_thumbnail wp-post-image" );
	$html = wp_get_attachment_image( $woo_pbai_thumb_id, $size, false, $attr  );
	do_action( 'end_fetch_post_thumbnail_html', $product_child_item_id, $woo_pbai_thumb_id, $size, $attr );
	echo($html);

	return apply_filters( 'post_thumbnail_html', $html, $product_child_item_id, $woo_pbai_thumb_id, $size, $attr );
}
