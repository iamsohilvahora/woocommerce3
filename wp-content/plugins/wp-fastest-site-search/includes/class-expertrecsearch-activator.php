<?php

require_once plugin_dir_path(__DIR__) . 'includes/class-expertrecsearch-logger.php';

/**
 * Fired during plugin activation
 *
 * @link       https://www.expertrec.com/
 * @since      1.0.0
 *
 * @package    Expertrecsearch
 * @subpackage Expertrecsearch/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Expertrecsearch
 * @subpackage Expertrecsearch/includes
 * @author     melchi <melchi@expertrec.com>
 */
class Expertrecsearch_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
    $log = new ExpLogger();
    $log->general( 'activate', 'Plugin activated.' );
    // error_log("activate function called");
    // Redirecting to our plugin Login/Home page
    add_option('expertrec_plugin_do_activation_redirect', true);
    // Creating search page
    $expertrec_args = array(
      'post_title'    => 'Search results',
      'post_type'     => 'page',
      'post_name'     => 'expertrec-search',
      'page_template' => 'expertrec-search-page.php',
      'post_status'   => 'publish'
    );
    $page_id = wp_insert_post( $expertrec_args, FALSE );
    if( !is_wp_error( $page_id ) && $page_id ) {
      $log->general( 'activate', 'Search page created with page ID: ' . print_r( $page_id, true ) );
      // Fetch created 'Search results' page if found then log full url of that page.
      if(get_post($page_id)){
        $log->general('activate','Full url of Search page is : ' . print_r(get_permalink($page_id),true));
      }
      // updating search path in local wp db
      $expertrec_options = get_option( "expertrec_options" );
      $expertrec_options["search_path"] = '/expertrec-search/';
      update_option( "expertrec_options", $expertrec_options );
    } else {
      $log->general( 'activate', 'Error while creating search page. ' . print_r( $page_id, true ) );
    }
    unset( $log );
	}


}
