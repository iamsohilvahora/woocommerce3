<?php
/**** Perfect_WC- WooCommerce Customization ****/
// 1.Overriding template file
// 2.Using hooks (filter hooks/action hooks)
// 3.Adding extra css/js to apply changes
// 4.function_exists() / class_exists()
// 5.don't forget to write priority
/*************************************************/

// load woocommerce features
add_action('init', 'load_wc_features');
if(!function_exists('load_wc_features')){
	function load_wc_features(){
		// add classes to product title
		// add_filter('woocommerce_product_loop_title_classes', 'custom_woocommerce_product_title');

		// remove result count on shop page
		remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);

		// remove sort order on shop page
		remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);

		// add quantity input on shop page
		add_filter('woocommerce_loop_add_to_cart_link', 'wc_loop_add_to_cart_link', 10, 3);

		// add classes to quantity input
		add_filter('woocommerce_quantity_input_classes', 'wc_quantity_input_class', 10, 2);

		// Make slider from product thumbnail or add video
		add_action('woocommerce_before_shop_loop_item_title', 'wc_product_thumbnail_slide', 9);



		


	}
}

// add custom classes to product title (find hooked function in plugin folder)
if(!function_exists('custom_woocommerce_product_title')){
	function custom_woocommerce_product_title($classes){
		$classes = $classes . ' text-center';
		return $classes;
	}
}

/*** Show the product title in the product loop. By default this is an h2 (change to h3 and also add classes). ***/
function woocommerce_template_loop_product_title(){
	echo '<h3 class="' . esc_attr(apply_filters('woocommerce_product_loop_title_classes', 'woocommerce-loop-product__title text-center text-success')) . '">' . get_the_title() . '</h3>'; 
}

function wc_loop_add_to_cart_link($html, $product, $args){
	global $product;
	if($product->is_type('simple') && $product->is_visible() && $product->is_purchasable()){
		$html = "<div class='text-center'>";
		$html .= "<form method='post' enctype='multipart/form-data' action='".$product->add_to_cart_url()."'>";
		$html .= "<div class='d-flex'>";
		$html .= woocommerce_quantity_input(array(), $product, false);
		$html .= "<button type='submit' class='btn btn-sm btn-outline-primary' name='mini_add_to_cart'>";
		$html .= esc_html($product->add_to_cart_text());
		$html .= "</button>";
		$html .= "</div>";
		$html .= "</form>";
		$html .= "</div>";
	}
	return $html;
}

function wc_quantity_input_class($class, $product){
	if($product->is_type('simple')){
		$class[] = 'mini_input form-control mx-2';
	}
	return $class;
}

function wc_product_thumbnail_slide(){
	global $product;
	$attachment_ids = $product->get_gallery_image_ids();
	$video = get_field('video_file');

	if(($attachment_ids || $video) && $image = $product->get_image_id()){
		$image_thumb = wp_get_attachment_image_src($image, 'thumbnail', false);

		remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail');
		echo "<div class='flexslider'><ul class='slides'>";

		if($video){
			echo "<li data-thumb='{$video['icon']}'><video controls width='100%' height='150'><source src='{$video['url']}' type='video/mp4' /></video></li>";
		}

		echo "<li data-thumb='{$image_thumb[0]}'><img src='{$image_thumb[0]}' /></li>";
		foreach($attachment_ids as $attachment_id){
			$image_src = wp_get_attachment_image_src($attachment_id, 'thumbnail', false);

			echo "<li data-thumb='{$image_src[0]}'><img src='{$image_src[0]}' /></li>";
		}
		echo "</ul></div>";
	}
	else{
		add_action('woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail');
	}
}


?>