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

		// Add video in single product page
		add_filter('woocommerce_single_product_image_thumbnail_html', 'wc_single_product_video');

		// customize product data tabs of single product page
		add_filter('woocommerce_product_tabs' ,'load_single_product_data_tabs');

		// Change position of single meta (remove action then add action)
		remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);

		add_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 6);

		// Change title position to left (remove action then add action)
		remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title', 5);

		add_action('woocommerce_before_single_product_summary', 'woocommerce_template_single_title', 5);

		// customize product sale flash
		add_filter('woocommerce_sale_flash','wc_product_sale_flash', 10, 3);

		// single product sale msg after price
		add_action('woocommerce_single_product_summary','wc_single_product_sale_msg', 11);

		// show how many time quantity purchased on product and single product page  
		add_action('woocommerce_before_shop_loop_item_title','wc_show_purchased_quantity');
		
		add_action('woocommerce_single_product_summary','wc_show_purchased_quantity', 35);

		// empty cart message (Using action or filter hooks)
		// add_filter('wc_empty_cart_message', 'show_empty_cart_msg');
		// function show_empty_cart_msg($msg){
		// 	$msg = "You have no product in basket";
		// 	return $msg;
		// }
		remove_action('woocommerce_cart_is_empty', 'wc_empty_cart_message', 10);
		add_action('woocommerce_cart_is_empty', 'show_empty_cart_msg', 10);

		// add discount to cart without coupon
		add_action('woocommerce_cart_calculate_fees', 'wc_cart_calculate_fees');

		// add recommended products to empty cart page
		add_action('woocommerce_cart_is_empty', 'show_cart_recommended_products');

		// if price less than 100 then redirect to cart page instead of checkout page
		add_action('woocommerce_before_checkout_form', 'wc_before_checkout_form');

		/** (part-17)
			woocommerce settings tab array = 
		    add_filter( 'woocommerce_settings_tabs_array','callback_function');
		*/

		// remove addtional information field from checkout page
		add_filter('woocommerce_checkout_fields', 'wc_remove_checkout_fields');

		// change css class on checkout page input fields
		add_filter('woocommerce_form_field_args', 'wc_add_css_class_checkout_fields', 10, 3);

		// Add custom field to the checkout page
		add_action('woocommerce_after_order_notes', 'custom_checkout_field');

		// Process extra fields on checkout page
		add_action('woocommerce_checkout_process', 'wc_custom_checkout_field_process'); 

		/** (part-19) - 
		 * Custom media uploader for wordpress 
		 * */

		/** (part-21) - 
		 * Product filter using ajax 
		 * */


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
			echo "<li data-thumb='".wc_placeholder_video_thumb()."'><video controls width='100%' height='150'><source src='{$video['url']}' type='video/mp4' /></video></li>";
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

function wc_single_product_video($html){
	$video = get_field('video_file');
	if($video && isset($video['title']) && isset($video['url'])){	
		$html .= '<div data-thumb="' . wc_placeholder_video_thumb() . '" data-thumb-alt="' . esc_attr( $video['title'] ) . '" class="woocommerce-product-gallery__image"><video controls width="100%" height="350"><source src="' . $video['url'] . '" /></video></div>';
	}
	return $html;
}

// function for video thumbnail image
function wc_placeholder_video_thumb($size = 'woocommerce_thumbnail')
{
	$src = get_template_directory_uri() . '/images/download.jpg';
	return apply_filters('woocommerce_placeholder_video_thumb', $src);
}

// Single product data tabs
function load_single_product_data_tabs($tabs){
	global $product;
	// print_r($tabs);
	// unset($tabs['additional_information']);
	$tabs['additional_information']['priority'] = 9;
	$tabs['description']['title'] = $product->get_title() . " Description";

	// add new tab
	$video_review = array('title' => $product->get_title() . " Video", 'priority' => 11, 'callback' => 'wc_video_review_data');

	$video = get_field('video_file');
	if($video && isset($video['title']) && isset($video['url'])){
		$tabs['video'] = $video_review;
	}		
	return $tabs;
}

function wc_video_review_data(){
	$video = get_field('video_file');
	if($video && isset($video['title']) && isset($video['url'])){	
		echo '<video controls width="100%" height="350"><source src="' . $video['url'] . '" /></video>';
	}
}

function wc_product_sale_flash($html, $post, $product){
	global $product;
	if($product->is_on_sale() && $product->is_type('simple')):
		$discount = $product->get_regular_price() - $product->get_price();
		echo '<span class="onsale">' . __(sprintf('Saved %s', wc_price($discount)), 'woocommerce' ) . '</span>';
	else:
		return $html;
	endif;
}

function wc_single_product_sale_msg(){
	global $product;
	if($product->is_on_sale() && $product->is_type('simple')):
		$discount = $product->get_regular_price() - $product->get_price();
		echo '<h4 class="badge badge-info text-success">';
		_e(sprintf('Saved %s', wc_price($discount)), 'woocommerce');
		echo '</h4>';
	endif;
}

function wc_show_purchased_quantity(){
	global $product, $wpdb;
	$date_from = date('Y-m-d H:i:s');
	$date_to = date('Y-m-d H:i:s', strtotime('+24 hours'));

	$result = $wpdb->get_row("SELECT SUM(`product_qty`) as total FROM {$wpdb->prefix}wc_order_product_lookup WHERE `product_id` = {$product->get_id()} AND `date_created` BETWEEN '{$date_from}' AND '{$date_to}'");

	if($result->total > 0){
		printf(_n("Purchased %s time in Last 24 Hours", "Purchased %s times in Last 24 Hours", $result->total, 'woocommerce'), $result->total);
	}
}

function show_empty_cart_msg(){
	echo '<p class="cart-empty alert alert-warning">' . wp_kses_post( apply_filters( 'wc_empty_cart_message', __( 'Your Basket is empty now.', 'woocommerce' ) ) ) . '</p>';
}

function wc_cart_calculate_fees($cart){
	// print_r($cart);
	// print_r($cart->get_cart_item_quantities());

	// hide proceed to checkout button if price less than 100
	$minimum_total = 100;
	$cart_total = $cart->get_totals()['subtotal'];

	if($cart_total < $minimum_total){
		if(is_cart()){
			wc_add_notice('You need to have min $100 in cart', 'error');
		}
		remove_action('woocommerce_proceed_to_checkout', 'wc_get_pay_buttons', 10);
		remove_action('woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20);
	}	

	foreach($cart->get_cart_item_quantities() as $id => $val){
		$quantity = get_field('product_quantity', $id);
		$percentage = get_field('discount_amount', $id);

		if(isset($quantity) && isset($percentage) && $val >= $quantity){
			// $product = wc_get_product($id);
			// $total = ($product->get_price() * $val) / 100 * $percentage;

			// $cart->add_fee("Discount on {$product->get_title()}", -$total);

			// Using coupon
			$coupon = new WC_Coupon('newyear_2022');
			$applied_coupons = $cart->get_applied_coupons();

			if(in_array($coupon->get_code(), $applied_coupons, true)){
				return;
			}
			$coupon->set_amount($percentage);
			$coupon->set_date_expires(date('Y-m-d H:i:s', strtotime('+48 hours')));
			$coupon->save();
			$cart->apply_coupon($coupon->get_code());
		}
	}
}

function show_cart_recommended_products(){
	echo "<h2>". __("Recommended Products", "woocommerce") ."</h2>";

	$query = new Wc_Product_Query(array(
		'meta_key' => "recommended_product",
		'meta_value' => true,
		'meta_comparison' => "===",
	));

	$products = $query->get_products();

	$recommended_products = [];
	if(count($products) > 0){
		foreach($products as $product){
			$id = $product->get_id();
			$recommended_products[] = $id;
		}
		$ids = implode(',', $recommended_products);
		echo do_shortcode("[products ids='{$ids}' orderby='rand' per_page='5']");	
	}
	else{
		_e("No recommeded products found", "woocommerce");		
	}
}

function wc_before_checkout_form(){
	// redirect to cart page if price less than 100
	$minimum_total = 100;
	$cart_total = wc()->cart->get_totals()['subtotal'];

	if($cart_total < $minimum_total){
		$site_url = get_site_url();
		wp_redirect($site_url.'/cart/');
	}	
}

function wc_remove_checkout_fields($woo_checkout_fields_array){
	// echo "<pre>";
	// print_r($woo_checkout_fields_array);
	// print_r($woo_checkout_fields_array['billing']);
	
	// unset( $woo_checkout_fields_array['billing']['billing_first_name'] );
	// unset( $woo_checkout_fields_array['billing']['billing_last_name'] );
	// unset( $woo_checkout_fields_array['billing']['billing_phone'] );
	// unset( $woo_checkout_fields_array['billing']['billing_email'] );
	// unset( $woo_checkout_fields_array['order']['order_comments'] ); // remove order notes
	
	// and to remove the billing fields below
	unset($woo_checkout_fields_array['billing']['billing_company']); // remove company field
	// unset( $woo_checkout_fields_array['billing']['billing_country'] );
	// unset( $woo_checkout_fields_array['billing']['billing_address_1'] );
	// unset( $woo_checkout_fields_array['billing']['billing_address_2'] );
	// unset( $woo_checkout_fields_array['billing']['billing_city'] );
	// unset( $woo_checkout_fields_array['billing']['billing_state'] ); // remove state field
	// unset( $woo_checkout_fields_array['billing']['billing_postcode'] ); // remove zip code field

	unset($woo_checkout_fields_array['order']['order_comments'] ); // remove additional info field

	return $woo_checkout_fields_array;
}

function wc_add_css_class_checkout_fields($args, $key, $value){
	if(isset($args['input_class']) && 'text' == $args['type']){
		 $args['input_class'] = array_merge($args['input_class'], array('form-control'));
	}
    return $args;
}

function custom_checkout_field($checkout){
	echo '<div id="custom_checkout_field">
			<h2>' . __('New Heading') . '</h2>';

	woocommerce_form_field('custom_field_name', array(
		'type' => 'text',
		'class' => array('my-field-class form-row-wide'),
		'label' => __('Custom Additional Field'),
		'placeholder' => __('New Custom Field'),
	),
	$checkout->get_value('custom_field_name'));
	echo '</div>';
}

function wc_custom_checkout_field_process(){
	// validate extra field
	if(isset($_POST['custom_field_name']) && empty($_POST['custom_field_name'])){
		wc_add_notice(__('Extra field is required', 'woocommerce'), 'error');
	}
}

?>