<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * This file contains all the functions that are used by indexing through "db" way
 *
 * @link       https://www.expertrec.com/
 * @since      4.0.0
 * @author     Ankit <kumar@expertrec.com>
 *
 * @package    Expertrecsearch
 */

require_once plugin_dir_path(__DIR__) . 'includes/class-expertrecsearch-logger.php';
require_once plugin_dir_path(__DIR__) . 'hooks/expertrecsearch-caller.php';
require_once plugin_dir_path(__DIR__) . 'expertrecsearch.php';

class ExpClient {
	private $version = null;

	private $searchIndexUrl = "https://data.expertrec.com/1/";
	private $dummySiteId = "58c9e0e4-78e5-11ea-baf0-0242ac130002";

	public $write_api_key = NULL;
	public $siteId = NULL;
	private $defaultBatchSize = 50;
	private $max_cust_field_limit = 1000;

	private $log = null;
	public $acfs = null;

	private $stop_indexing = false;

	public function __construct() {
		if ( defined( 'EXPERTREC_VERSION' ) ) {
            $this->version = EXPERTREC_VERSION;
        } else {
            $this->version = '4.0.0';
        }
		$expertrec_options = get_option( 'expertrec_options' );
		//if array key (ecom_id) exist in an array then assign else $siteID="NA"
		if (array_key_exists('ecom_id', $expertrec_options) ){
			$this->siteId = $expertrec_options['ecom_id'];
		}
		$this->write_api_key = array_key_exists('write_api_key', $expertrec_options) ? $expertrec_options['write_api_key'] : 'NA';
		$this->log = new ExpLogger();
		if($this->acfs==null){
			$this->acfs = array('images' => array(), 'snippets' => array(), 'texts' => array(), 'titles' => array());
		}
	}

	public function __destruct() {
		unset( $this->log );
	}

	public function deleteDoc( $docId) {
		$url = $this->searchIndexUrl . "indexes/{$this->siteId}/$docId";
		$payload = null;
		$resp = $this->sendData($url, 'DELETE', $payload, true, 'deleteDoc');
		return $resp;
	}

	public function indexDoc($postId) {
		// update existing doc
		// create new doc
		$post = get_post($postId);
		// check if post_type is filtered.
		$docs = array();
		$docs[] = array("action"=>"addObject", "body" => $this->createDoc($post));
		$url = $this->searchIndexUrl . "indexes/{$this->siteId}/batch";
		$resp = $this->sendData($url, 'POST', $docs, false, 'indexDoc');
		return $resp;
	}

	public function indexDocs() {
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'admin' );
		}
		// Setting stopIndexing flag to false if it was set to true, to start indexing again
		update_option('expertrec_stop_indexing', false);
		$this->stop_indexing = false;
		update_option('expertrec_indexing_status', 'indexing');
		$this->log->truncate_log_file( 'expertrec_indexing' );

		$this->log->indexing( 'indexDocs', 'Indexing Docs started' );
		$docTypes = $this->getPostTypes();
		$url = $this->searchIndexUrl . "indexes/{$this->siteId}/batch";
		// Marking every doctype indexed value to 0
		$expertrec_options = get_option( 'expertrec_options' );
		foreach($docTypes as $docType) {
			$expertrec_options['index_stats'][$docType]['indexed'] = 0;
		}
		update_option('expertrec_options', $expertrec_options);

		foreach($docTypes as $docType) {
			// If stopIndexing is set to true, then return
			$this->stop_indexing = get_option('expertrec_stop_indexing');
			if ( $this->stop_indexing ) {
				$this->log->indexing( 'indexDocs', "User stopped indexing" );
				break;
			}
			$this->log->indexing( 'indexDocs', "Indexing for Doctype ".$docType );
			$expertrec_options = get_option( 'expertrec_options' );

			if ( !empty($expertrec_options['batch_size'][$docType])) {
				$batchSize = $expertrec_options['batch_size'][$docType];
			} else {
				$batchSize = 5;
			}
			$docCount = $this->getPostCount($docType);
			$this->log->indexing( 'indexDocs', "Doc Count for Doctype ". $docType . " is: ". $docCount );
			// Updating index stats
			$indexed_post_count = 0;
			$expertrec_options['index_stats'][$docType]['indexable'] = $docCount;
			$expertrec_options['index_stats'][$docType]['indexed'] = $indexed_post_count;
			$expertrec_options['index_stats']['currently_indexing'] = $docType;
			update_option( "expertrec_options", $expertrec_options );
			// $totalBatches = ceil($docCount / $batchSize);
			$offset = 0;
			// decrementing docCount because in worst case, the while loop will run for $docCount times
			while($docCount--) {
				wp_cache_flush();
				$this->stop_indexing = get_option('expertrec_stop_indexing');
				if ( $this->stop_indexing ) {
					$this->log->indexing( 'indexDocs', "User stopped indexing" );
					break;
				}
				$this->log->indexing( 'indexDocs', "Variable offset, batchsize, docType are: ".$offset.", ".$batchSize.", ".$docType );
				// find all posts for this docType with offset
				$posts = get_posts(array(
					'posts_per_page' => $batchSize,
					'offset' => $offset,
					'orderby' => 'date',
					'order' => 'DESC',
					'fields' => 'ids',
					'post_type' => $docType,
					'post_status' => 'publish'
				));
				$this->log->indexing( 'indexDocs', "got ".count($posts)." Posts from DB" );
				if (count($posts) < 1) {
					break;
				}
				$offset = $offset + count($posts);
				$docs = array();
				$pdfDocs = array();
				//iterating through array of posts to get array of objects
            	foreach($posts as $post) {
                	$docs[] = array("action"=>"addObject", "body" => $this->createDoc($post));
					$pdfs = get_attached_media("application/pdf", $post);
					//check whether there is any array of pdfs
					if(count($pdfs) > 0){
					    $pdfDocs[] = $pdfs;
					}
            	}
				try {
					$resp = $this->sendData($url, 'POST', $docs, false, 'indexDocs');
					if (count($pdfDocs) > 0) {
						$pdf_list = array();
						//iterating through array of object($pdfDocs) which contains array of pdfs
						foreach($pdfDocs as $pdf){
							$new_pdf = $this->createPdfDoc($pdf);
							$pdf_list = array_merge($pdf_list,$new_pdf);
						}
						$resp = $this->sendData($url, 'POST', $pdf_list, false, 'indexDocs');
					}
					
				} catch(Exception $e) {
					$this->log->indexing( 'indexDocs', $e->getMessage() );
				}
				// Updating indexed post count
				$indexed_post_count += count($posts);
				$expertrec_options = get_option( 'expertrec_options' );
				$expertrec_options['index_stats'][$docType]['indexed'] = $indexed_post_count;
				update_option( "expertrec_options", $expertrec_options );
			}
		}
		update_option('expertrec_indexing_status', 'complete');
		$expertrec_options = get_option( 'expertrec_options' );
		$expertrec_options['last_successful_sync'] = time();
		$expertrec_options['first_sync_done'] = true;
		$expertrec_options['index_stats']['currently_indexing'] = 'NA';
		update_option( "expertrec_options", $expertrec_options );
		$this->log->indexing( 'indexDocs', "Indexing completed" );
	}
	/**
     * this function returns the array which contains Pdf meta data.
     *
     * @return list of array
	 * @param  array $pdfID  list of pdfs
     */
    
	public function createPdfDoc($pdfID) {
		$doc = array();
		//iterating through list of pdfs
		foreach($pdfID as $pdf_meta){
			$temp = array();
			$temp['title'] = $pdf_meta->post_title;
			$temp['id']=$pdf_meta->ID;
			$temp['author'] = $pdf_meta->post_author;
			$temp['published_date'] = $this->convert_to_tz_format($pdf_meta->post_date);
			$temp['post_status'] = $pdf_meta->post_status;
			$temp['parent_id'] = $pdf_meta->post_parent;
			$temp['url'] = $pdf_meta->guid;
			$temp['post_type'] = "document";
			$temp['post_mime_type'] = $pdf_meta->post_mime_type;
			$doc[] = array("action"=>"addObject", "body" => $temp);		
		}
		return $doc;
	}

	public function getPostCount($post_type){
        $post_count = wp_count_posts($post_type);
        if(!isset($post_count->publish)){
            return 0;
        }
        $count = $post_count->publish;
        if($count==null){
            return 0;
        }
        return $count;
    }

	public function getPostTypes(){
        $post_types = get_post_types( array('public' => true, '_builtin' => false, 'exclude_from_search' => false), 'names', 'and' );
        if($post_types == NULL){
            $post_types = array();
        }
        $post_types[] = 'post';
        $post_types[] = 'page';
        if(array_search('product', $post_types)===FALSE){
            $post_types[] = 'product';
        }
        if(array_search('scheduled-action', $post_types)!==FALSE){
            $idx = array_search('scheduled_action', $post_types);
            array_splice($post_types, $idx, 1);
        }
        if(array_search('nav_menu_item', $post_types)!==FALSE){
            $idx = array_search('nav_menu_item', $post_types);
            array_splice($post_types, $idx, 1);
        }
        return $post_types;
    }

	public function getAllPostCount(){
        return $this->getPostCount('post') + $this->getPostCount('page') +$this->getPostCount('product');
    }

	/**
     * Removes shortcodes from the given string
     *
     * @param string $content
     *
     * @return string
     */
	private function removeShortcodes( $content ) {
		// regex to replace the shortcodes which is written in [] with '' empty string
		// For example : 
		// shortcode like : [et_pb_section fb_built=”1″ ... ] <content> [/et_pb_section]
		// will replace with '' empty string, it will not replace or modified <content>
		$str = preg_replace( '#\[[^\]]+\]#', '', $content );
		return $str;
	}

	/**
     * Removes html, shortcodes and converts data in UTF-8 encoding
     *
     * @param any $data
     * @param boolean $remove_shortcodes
     *
     * @return string
     */
    private function getSanitizedData($data, $remove_shortcodes=false, $depth=0) {
		if( is_array($data) ) {
			if($depth == 1) {
				// If we get array inside an array, then we will return empty string. Otherwise it will give error while indexing
				return '';
			}
			$sanitized_data = array();
			foreach( $data as $d ) {
				array_push( $sanitized_data, $this->getSanitizedData($d, $remove_shortcodes, $depth+1) );
			}
			return $sanitized_data;
		}
		try {
			$data = wp_strip_all_tags( $data );

			if( $remove_shortcodes ) {
				$data = strip_shortcodes( $data );
				$data = $this->removeShortcodes($data);
			}

			$data = html_entity_decode( $data, ENT_QUOTES, "UTF-8" );
		} catch(Exception $e) {
			$this->log->indexing( 'getSanitizedData', "Exception while sanitizing the data: " . $e->getMessage() );
		}
        
		return $data;
    }

	public function getDocpath($url){
		$new_url = parse_url($url);
		$new_url = $new_url["path"];
		$parts = explode('/', $new_url);
		//if path contains any extention (like .html) , to ignore this case check whether the path count is greater than 2
		if (count($parts)<=2) {
			return;
		}
		//first level path of the url
		$new_url =$parts[1];
		if ($new_url != null) {
			//trimmed special character and multiple spaces with single space
			$new_url = preg_replace("/\W/", ' ', $new_url);
			return  $new_url;
		}
	}

	public function createDoc($postId) {
		try {
			$post = get_post($postId);
			$doc = array();
			// mandatory fields
			$doc['id'] = $post->ID;
			$doc['title'] = $this->getSanitizedData($post->post_title, true);
			$post_url = get_permalink($post);
			$doc['url'] = $post_url;
			//calulate doc path
			$value = $this->getDocpath($post_url,true);
			if ($value != null){
				$doc['DocPath'] = $value;
			}
			$this->log->indexing( 'createDoc', "Creating doc for ". $post->ID ." having url: ". $post_url );
			$publish_date = $this->convert_to_tz_format( $post->post_date );
			$doc['published_date'] = $publish_date;

			$cust_fields = $this->getCustomFields( $post->ID );
			$doc = array_merge($doc, $cust_fields);

			$excerpt = $post->post_excerpt;
			$content = apply_filters('the_content', $post->post_content);
			if(function_exists('get_field')) {
				foreach($this->acfs['texts'] as $text_field_id) {
					$cust_field = get_field(str_replace('xxx','_',$text_field_id), $post->ID);
					if($cust_field) {
						$content = $content . $cust_field;
					}
				}  
				foreach($this->acfs['snippets'] as $text_field_id) {
					$cust_field = get_field(str_replace('xxx','_' ,$text_field_id), $post->ID);
					if($cust_field) {
						$excerpt = $cust_field;
					}
				}  
				foreach($this->acfs['titles'] as $text_field_id) {
					$cust_field = get_field(str_replace('xxx','_',$text_field_id), $post->ID);
					if($cust_field) {
						$doc['title'] = $this->getSanitizedData($cust_field, true);
					}
				}
			}

			$content = $this->getSanitizedData( $content, true );

			$categories = get_the_category($post->ID);

			$post_type = $post->post_type;
			$doc['post_type'] = $post_type;
			// if product then add categories
			if($post_type == "product") {
				$woo_categories = get_the_terms($post->ID, 'product_cat');
				//checking if woo_categories is an array or not empty
				if (is_array($woo_categories) || !empty($woo_categories)){
					foreach($woo_categories as $woo_category) {
						$categories[] = $woo_category;
				  	}
				}
			}
			$category_values = array();
			foreach($categories as $category){
				$category_values[] = $category->name;
			}
			$doc["category"] = $this->getSanitizedData( $category_values, true );
			$topCategory = $this->getTopCategory($categories);
			$doc['categories'] = $this->getSanitizedData($topCategory, true);

			$author = get_the_author_meta('display_name', $post->post_author);
			$doc['author'] = $author;

			$tags = wp_get_post_tags($post->ID);
			$tag_values = array();
			foreach($tags as $tag){
				$tag_values[] = $tag->name;
			}
			$doc['tags'] = $tag_values;

			
			$doc['description'] = $excerpt ? $this->getSanitizedData($excerpt, true) : substr($content, 0, 350);
			$doc['content'] = $content;
			

			$post_image = get_the_post_thumbnail_url($post);
			$original_post_image = $post_image;
			if(function_exists('get_field')) {
				foreach($this->acfs['images'] as $image_field_id) {
					$cust_field = get_field(str_replace('xxx', '_', $image_field_id), $post->ID);
					if($cust_field && isset($cust_field['sizes']) && isset($cust_field['sizes']['thumbnail'])) { 
						$post_image = $cust_field['sizes']['thumbnail'];
					} else {
						$img_obj = wp_get_attachment_image_src($cust_field, 'thumbnail');
						$post_images = $img_obj;
						if($img_obj !== FALSE and $img_obj !== "false") {
							$post_image = $img_obj[0];
						}
					}
				}
			}
			if( isset($post_images) and $post_images ) {
				$doc['images'] = $post_images;
			}
			if($post_image!==FALSE){
				$doc['image'] = $post_image;
			} elseif(isset($original_post_image)) {
				$doc['image'] = $original_post_image;
			}

			if($post_type == 'post' or $post_type == 'page') {
				$images = $this->get_images_from_content( $post );
				if(isset($images) and !empty($images)) {
					$doc['images'] = $images;
					$doc['image'] = $images[0];
				}
			}

			
			$imageTextsString = '';	
			$attached_images = get_attached_media('image', $post->ID);
			if($attached_images) {
				foreach($attached_images as $attached_image) {
					$image_alt = get_post_meta($attached_image->ID, '_wp_attachment_image_alt', TRUE);
					if($image_alt != NULL && $image_alt != '') {
						if($imageTextsString != '') {
							$imageTextsString = $imageTextsString . ', ';
						}
						$imageTextsString = $imageTextsString . $image_alt;
					}
				}
			}

			$doc["img_text"] = $imageTextsString;
			if($post_type == 'product'){
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
					$prod = $this->getProductFields( $post->ID );
					$doc = array_merge($doc, $prod);
				}

				if ( !array_key_exists( 'price', $doc ) || $doc['price'] == null ) {
					$product_price = get_post_meta($post->ID, '_price');
					if(sizeof($product_price) > 0){ // Price filter	
						$doc["price"] = $product_price[0];
					}
				}

				$woocommerce_attributes = get_option('exp_woocommerce_product_attributes');
				if ($woocommerce_attributes != NULL && sizeof($woocommerce_attributes) > 0) {
					$product_meta = get_post_meta($post->ID, '_product_attributes');
					if (sizeof($product_meta) > 0) {
						$keys = array_keys($product_meta[0]);
						foreach($keys as $key) {
							$meta_def = $product_meta[0][$key];
							if (in_array($meta_def['name'], $woocommerce_attributes)) {
								$doc[$meta_def['name']] = $meta_def['value'];
							}
						}
					}
				}
			}
			// Get Product Taxonomies
			$taxonomies = get_taxonomies(array('public'=>true, '_builtin'=>false), 'names');
			//check whether taxonomy is present or not	
			if($taxonomies !="" && is_array($taxonomies) && count($taxonomies) > 0){
				$doc["taxonomy"] = wp_get_post_terms($post->ID, $taxonomies, array("fields" => "names"));
			}
			// error_log("Post with id ");
			// error_log(print_r($post->ID, true));
			// error_log(print_r(mb_strlen(serialize((array)$doc), '8bit'), true));
			return $doc;
		}
		catch(Exception $e) {
			$this->log->indexing( 'createDoc', $e->getMessage() );
		}
	}

	private function get_images_from_content( $post ) {
		$images = array();
		if( $post->post_content ) {
			$dom_obj = new DOMDocument();
			@$dom_obj->loadHTML($post->post_content);
			foreach($dom_obj->getElementsByTagName('img') as $item){
				array_push($images, $item->getAttribute('src'));
			};
		}
		return $images;
	}

	private function getCustomFields( $postId ) {
		$cust_fields = array();
		$post_type_features = get_post_custom( $postId );
		foreach($post_type_features as $key => $value) {
			if ( '_' !== substr( $key, 0, 1) ) {
				$key = preg_replace("/[^A-Za-z0-9_]/", '', $key);
				$sanitized_value = $this->getSanitizedData($value);
				if( is_string($sanitized_value) && strlen($sanitized_value) > $this->max_cust_field_limit ) {
					// If the custom field length is more than 1k characters, then we will reduce it to 1k
					$sanitized_value = substr($sanitized_value, 0, $this->max_cust_field_limit);
				}
				$cust_fields['cust_' . $key] = $sanitized_value;
			}
		}
		return $cust_fields;
	}

	private function getProductFields( $postId ) {
		$prod = array();
		try {
			$product = wc_get_product( $postId );

			// Get product custom fields if exists
			if( function_exists('get_fields') ) {
				$post_cust_fields = get_fields( $postId );
				if ($post_cust_fields && array_key_exists('price_um', $post_cust_fields)) {
					$prod['price_um'] = $post_cust_fields['price_um'];
				}
				if ($post_cust_fields && array_key_exists('availability', $post_cust_fields)) {
					$prod['availability'] = $post_cust_fields['availability'];
				}
				if ($post_cust_fields && array_key_exists('brand_name', $post_cust_fields)) {
					$prod['brand'] = $this->getSanitizedData($post_cust_fields['brand_name'], true);
				}
			}
			// Get Product General Info
			$prod['type'] = $this->getSanitizedData($product->get_type(), true);
			$prod['wc_title'] = $product->get_name();
			$prod['slug'] = $product->get_slug();
			$date_created = $product->get_date_created();
			if($date_created != null) {
				$date_created = $date_created->__toString();
			}
			$prod['date_created'] = $this->convert_to_tz_format( $date_created );
			$date_modified = $product->get_date_modified();
			if($date_modified != null) {
				$date_modified = $date_modified->__toString();
			}
			$prod['date_modified'] = $this->convert_to_tz_format( $date_modified );
			$prod['status'] = $product->get_status();
			$prod['featured'] = $product->get_featured();
			$prod['catalog_visibility'] = $product->get_catalog_visibility();
			$prod['sku'] = $product->get_sku();
			$prod['menu_order'] = $product->get_menu_order();
			$prod['virtual'] = $product->get_virtual();
			$prod['permalink'] = get_permalink( $product->get_id() );
			// Get Product Prices
			$prod['price'] = $product->get_price();
			$prod['regular_price'] = $product->get_regular_price();
			$prod['sale_price'] = $product->get_sale_price();
			$date_on_sale_from = $product->get_date_on_sale_from();
			if($date_on_sale_from != null) {
				$date_on_sale_from = $date_on_sale_from->__toString();
			}
			$prod['date_on_sale_from'] = $this->convert_to_tz_format( $date_on_sale_from );
			$date_on_sale_to = $product->get_date_on_sale_to();
			if($date_on_sale_to != null) {
				$date_on_sale_to = $date_on_sale_to->__toString();
			}
			$prod['date_on_sale_to'] = $this->convert_to_tz_format( $date_on_sale_to );
			$prod['total_sales'] = $product->get_total_sales();
			// Get Product Tax, Shipping & Stock
			$prod['tax_status'] = $product->get_tax_status();
			$prod['tax_class'] = $product->get_tax_class();
			$prod['manage_stock'] = $product->get_manage_stock();
			$prod['stock_quantity'] = $product->get_stock_quantity();
			$prod['stock_status'] = $product->get_stock_status();
			$prod['backorders'] = $product->get_backorders();
			$prod['sold_individually'] = $product->get_sold_individually();
			$prod['purchase_note'] = $product->get_purchase_note();
			$prod['shipping_class_id'] = $product->get_shipping_class_id();
			// Get Product Dimensions
			$prod['weight'] = $product->get_weight();
			$prod['length'] = $product->get_length();
			$prod['width'] = $product->get_width();
			$prod['height'] = $product->get_height();
			// Get Linked Products
			$prod['upsell_ids'] = $product->get_upsell_ids();
			$prod['cross_sell_ids'] = $product->get_cross_sell_ids();
			$prod['parent_id'] = $product->get_parent_id();
			// Get currency
			$prod['currency'] = get_woocommerce_currency();
			$prod['currency_symbol'] = $this->getSanitizedData(get_woocommerce_currency_symbol());
			// Get Product Variations and Attributes
			// $prod['children'] = $product->get_children(); // get variations
			// $prod['attributes'] = $product->get_attributes();
			// $prod['default_attributes'] = $product->get_default_attributes();
			$prod['wc_categories'] = wp_strip_all_tags(wc_get_product_category_list( $postId, ',' ));
			$prod['category_ids'] = $product->get_category_ids();
			$prod['tag_ids'] = $product->get_tag_ids();
			$prod['wc_tags'] = wp_strip_all_tags( wc_get_product_tag_list( $postId, ',' ) );
			// Get Product Downloads
			// $prod['downloads'] = $product->get_downloads(); // This is giving a WC_Product_Download Object which is not indexable
			$prod['download_expiry'] = $product->get_download_expiry();
			$prod['downloadable'] = $product->get_downloadable();
			$prod['download_limit'] = $product->get_download_limit();
			// Get Product Images
			$prod['image_id'] = $product->get_image_id();
			$htmlImg = $product->get_image();
            $htmlImg = str_replace('\"', '"', $htmlImg);
            $wc_images = array();
            $dom_obj = new DOMDocument();
            @$dom_obj->loadHTML($htmlImg);
            foreach($dom_obj->getElementsByTagName('img') as $item){
                array_push($wc_images, $item->getAttribute('src'));
                array_push($wc_images, $item->getAttribute('srcset'));
            }
            $prod['wc_image'] = $wc_images;
			$prod['gallery_image_ids'] = $product->get_gallery_image_ids();
			// Get Product Reviews
			$prod['reviews_allowed'] = $product->get_reviews_allowed();
			$prod['rating_counts'] = $product->get_rating_counts();
			$prod['average_rating'] = $product->get_average_rating();
			$prod['review_count'] = $product->get_review_count();
			
			// error_log("Product with postId: ".$postId);
			// error_log( print_r( $prod, true ) );
			return $prod;
		}
		catch(Exception $e) {
			$this->log->indexing( 'getProductFields', $e->getMessage() );
		}
	}

	private function convert_to_tz_format( $date ) {
		$timestamp = strtotime( $date );
		$tz_date = date("Y-m-d\TH:i:s.000\Z", $timestamp);
		if (!$timestamp || !$tz_date) {
			return "";
		}
		return $tz_date;
	}

	private function getTopCategory($categories){
		$category_by_parent = array();
		foreach($categories as $category){
			$parentCategoryId = $category->category_parent;
			if($parentCategoryId==0 && strlen($category->name) > 0){
				return $category->name;
			}
			$categoryId = $category->cat_ID;
			$category_by_parent[$parentCategoryId] = $categoryId; 
			//TODO: might be an array (for future reference, but right now sufficient)
		}
		foreach($categories as $category){ 
			// try to find category which does not have any parent among the categories
			if(!isset($category_by_parent[$category->category_parent]) && strlen($category->name) > 0){
				return $category->name;
			}
		}
		return NULL;
	}

	public function start_sync() {
		$url = $this->searchIndexUrl . "indexes/{$this->siteId}/start_sync";
		$payload = null;
		return $this->sendData($url, 'POST', $payload, false, 'start_sync');
	}

	public function end_sync() {
		$url = $this->searchIndexUrl . "indexes/{$this->siteId}/end_sync";
		$payload = null;
		return $this->sendData($url, 'POST', $payload, false, 'end_sync');
	}

	private function sendData($url, $method, &$payload, $protected, $caller='NA') {
		$this->log->indexing( $caller, 'Calling sendData function' );

		if ($protected && ($this->write_api_key === NULL || strlen($this->write_api_key) === 0)) {
				$failure = array();
				$failure['status'] = 'failure';
				return $failure;
		}
		$headers = array(
			'User-Agent' => 'EXP Wordpress Plugin/' . $this->version,
			'X-Expertrec-API-Key' => $this->write_api_key,
			"Content-type" => "application/json"
		);
		$this->log->indexing( $caller, 'URL is: ' . print_r($url, true) );
		$this->log->indexing( $caller, 'Headers are: ' . print_r($headers, true) );
		$response = index_data($url, $method, $headers, $payload);
		$this->log->indexing( $caller, wp_remote_retrieve_body( $response) );
		if( !is_wp_error($response) and wp_remote_retrieve_response_code($response)=='200' ) {
			return true;
		} else {
			$this->log->indexing( $caller, "Error from Indexing API , Status code is : " . print_r(wp_remote_retrieve_response_code($response),true) );
			$sentry_client = get_sentry_client();
			if ($sentry_client) {
				$sentry_client->captureException( new Exception( print_r($response, true) ) );
			}
			return false;
		}
	}

}
