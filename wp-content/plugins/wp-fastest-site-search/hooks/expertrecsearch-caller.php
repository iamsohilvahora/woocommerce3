<?php

/**
 * This file contains all the API calls to expertrec servers
 *
 * @link       https://www.expertrec.com/
 * @since      4.1.0
 * @author     Ankit <kumar@expertrec.com>
 *
 * @package    Expertrecsearch
 */

if ( ! defined( 'WPINC' ) ) {
    die("You can't access this file directly");
}

$base_url = "https://cseb.expertrec.com/api/";


function update_expertrec_config($site_id, $update_type, $body) {
	global $base_url;
	$url = $base_url . 'organisation/' . rawurlencode($site_id) . '/CSE/update_conf/' . rawurlencode($update_type);
	return call_expertrec_api($url, 'POST', null, $body);
}


function index_data($url, $method, $headers, &$payload) {
	$body = null;
	if(($method=='PUT' || $method=='POST') && $payload!=null){
		$body = $payload;
	}
	return call_expertrec_api($url, $method, $headers, $body);
}


function get_create_ecom_id( $options = null ) {
	// error_log("get_create_ecom_id got called");
	if ( !$options ) {
		$options = get_option('expertrec_options');
	}
	$current_id = $options['site_id'];
	global $base_url;
	$url = $base_url . '/7e70731cfb3a6fc453847f952906c82c/wp-generate-ecom-id';
	// Getting current user
	global $current_user;
	if (!isset($current_user)) {
		$current_user = wp_get_current_user();
	}
	$payload = array(
		"site_url" => get_site_url(),
		"cse_id" => $current_id,
		"name" => $current_user->display_name,
		"email" => $current_user->user_email
	);
	$response = call_expertrec_api($url, 'POST', null, $payload);
	$response = wp_remote_retrieve_body( $response );
	$json_data = json_decode( $response, true );
	$options = get_option('expertrec_options');
	$options['ecom_id'] = $json_data['ecom_id'];
	$options['write_api_key'] = $json_data['write_api_key'];
	update_option('expertrec_options', $options);
	return $json_data['ecom_id'];
}


function start_crawl() {
	global $base_url;
	$options = get_option('expertrec_options');
	$site_id = $options['site_id'];
	$payload = array("request" => "start_crawl");
	$url = $base_url . 'organisation/' . rawurlencode($site_id) . '/CSE/wp_start_crawl';
	return call_expertrec_api($url, 'POST', null, $payload);
}


function get_expertrec_conf( $site_id, $expertrec_engine ) {
	global $base_url;
	$url = $base_url . 'organisation/' . rawurlencode($site_id) . '/CSE/get_conf?migrated=true&expertrec_engine='
        . rawurlencode( $expertrec_engine );
	$response = call_expertrec_api($url, 'GET');
	if ( $response ) {
		return wp_remote_retrieve_body( $response );
	}
	return $response;
}


function crawl_status() {
	global $base_url;
	$options = get_option('expertrec_options');
	$site_id = $options['site_id'];
	$url = $base_url . 'organisation/' . rawurlencode($site_id) . '/CSE/wp_crawl_status';
	return call_expertrec_api($url, 'GET');
}


function stop_crawl() {
	global $base_url;
	$options = get_option('expertrec_options');
	$site_id = $options['site_id'];
	$payload = array("request" => "stop_crawl");
	$url = $base_url . 'organisation/' . rawurlencode($site_id) . '/CSE/wp_stop_crawl';
	return call_expertrec_api($url, 'POST', null, $payload);
}


function get_days_to_expire() {
	global $base_url;
	$options = get_option('expertrec_options');
	$site_id = $options['site_id'];
	$url = $base_url . 'organisation/' . rawurlencode($site_id) . '/CSE/days_to_expire';
	return call_expertrec_api($url, 'GET');
}


function wp_events( $event_type, $data=array() ) {
    global $base_url;
    $options = get_option('expertrec_options');
    $site_id = $options['site_id'];
    $url = $base_url . '/organisation/' . rawurlencode($site_id) . '/wp_events/' . rawurlencode($event_type);
    if ( defined( 'EXPERTREC_VERSION' ) ) {
        $version = EXPERTREC_VERSION;
    } else {
        $version = '4.0.0';
    }
    $data['plugin_version'] = $version;
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        $is_woocommerce = true;
    } else {
        $is_woocommerce = false;
    }
    $data['woocommerce'] = $is_woocommerce;
    $data['site_url'] = get_site_url();
    $data['admin_email'] = get_option('admin_email');
    $count_dict = getIndexableData();
    $data['doc_count'] = $count_dict['doc_count'];

    return call_expertrec_api($url, 'POST', null, $data);
}

function getIndexableData() {
    $data = array();
    require_once plugin_dir_path(__DIR__) . 'includes/class-expertrecsearch-client.php';
    $client = new ExpClient();
    $docTypes = $client->getPostTypes();
    foreach($docTypes as $docType) {
        $data[$docType] = $client->getPostCount($docType);
    }
    return array("doc_count" => $data);
}

function convert_to_json($data) {
	if(defined('JSON_INVALID_UTF8_IGNORE')) {
		$encoded_data = json_encode($data, JSON_INVALID_UTF8_IGNORE);
	} else {
		$encoded_data = json_encode($data);
	}
	// Encoded data can be malformed post json_encode. We should be handling at our server
	return $encoded_data;
}


function call_expertrec_api($url, $method, $headers=null, &$payload=null) {
	if (!$headers) {
		$headers = array("Content-type" => "application/json");
	}
	if ( defined( 'EXPERTREC_VERSION' ) ) {
        $version = EXPERTREC_VERSION;
    } else {
        $version = '4.0.0';
    }
    $headers = array_merge( $headers, array('User-Agent' => 'EXP Wordpress Plugin/' . $version) );
	$args = array(
		'method' => $method,
		'headers' => $headers,
		'timeout' => 10,
		'redirection' => 2,
		'httpversion' => '1.1',
		'blocking' => true
	);
	if ( $payload != null ) {
		$encoded_payload = convert_to_json( $payload );
		$args['body'] = $encoded_payload;
	}
	$response = wp_remote_get( $url, $args );
	// error_log( print_r( wp_remote_retrieve_body( $response ), true ) );
	if ( !is_wp_error($response) ) {
		return $response;
	} else {
		return false;
	}
}

