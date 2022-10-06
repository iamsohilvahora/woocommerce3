<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://www.expertrec.com/
 * @since      1.0.0
 *
 * @package    Expertrecsearch
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die("You can't access this file directly");
}

if ( ! defined( 'WPINC' ) ) {
    die("You can't access this file directly");
}

require_once __DIR__ . '/includes/class-expertrecsearch-logger.php';
$log = new ExpLogger();
$log->general( 'uninstall', 'Plugin uninstalled' );
unset( $log );

$options = get_option('expertrec_options');
$site_id = $options['site_id'];
$safe_id_in_url = rawurlencode($site_id);
$ecom_id = rawurldecode( $options['ecom_id'] );
$cse_id = rawurldecode( $options['cse_id'] );

delete_option('expertrec_options');
delete_option('expertrec_engine');
delete_option('expertrec_indexing_status');
delete_option('expertrec_stop_indexing');

$url = 'https://cseb.expertrec.com/api/plugin_uninstalled/'.$safe_id_in_url.'?platform=Wordpress&ecom_id='.
    $ecom_id.'&cse_id='.$cse_id;
$args = array(
    'method' => 'GET',
    'headers' => array("Content-type" => "application/json"),
    'timeout' => 10,
    'redirection' => 2,
    'httpversion' => '1.1',
    'blocking' => true
);
$response = wp_remote_get( $url, $args );