<?php

require_once plugin_dir_path(__DIR__) . 'includes/class-expertrecsearch-logger.php';

/**
 * Fired during plugin deactivation
 *
 * @link       https://www.expertrec.com/
 * @since      1.0.0
 *
 * @package    Expertrecsearch
 * @subpackage Expertrecsearch/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Expertrecsearch
 * @subpackage Expertrecsearch/includes
 * @author     melchi <melchi@expertrec.com>
 */
class Expertrecsearch_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		$log = new ExpLogger();
		$log->general( 'deactivate', 'Plugin deactivated' );
		unset( $log );
        // remove expertrec search page after deactivation
        $expertrec_search_page = get_page_by_path( '/expertrec-search/' , OBJECT );
        if ( isset($expertrec_search_page) ) {
            wp_delete_post($expertrec_search_page->ID, true);
        }
	}

}
