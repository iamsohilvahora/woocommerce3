<?php

/**
 * This file contains all the functions that are called by plugin ajax
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

require_once plugin_dir_path(__DIR__) . 'hooks/expertrecsearch-caller.php';
require_once plugin_dir_path(__DIR__) . 'includes/class-expertrecsearch-logger.php';


function expertrec_login() {
	// error_log( 'login post data: ' . print_r($_POST, true) );
	$site_id = sanitize_text_field($_POST['site_id']);
	$ecom_id = sanitize_text_field($_POST['ecom_id']);
	$cse_id = sanitize_text_field($_POST['cse_id']);
	$write_api_key = sanitize_text_field($_POST['write_api_key']);
	$expertrec_engine = sanitize_text_field($_POST['expertrec_engine']);
	$options = get_option('expertrec_options');
	if ( isset($options) and isset($site_id) and isset($write_api_key) ) {
		$options['site_id'] = $site_id;
		$options['ecom_id'] = $ecom_id;
		$options['cse_id'] = $cse_id;
		$options['write_api_key'] = $write_api_key;
		$options['expertrec_account_created'] = true;
		update_option('expertrec_options', $options);
		update_option('expertrec_engine', $expertrec_engine);
		$log = new ExpLogger();
		$log->general( 'login', 'User logged in' );
		unset( $log );
		wp_events("login_completed");
		exit();
	}
}


function get_site_info() {
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        $woocommerce = true;
    } else {
        $woocommerce = false;
    }
    $actual_site_url = get_site_url();
    $indexable_data = getIndexableData();
    $ret = array(
        "woocommerce" => $woocommerce,
        "actual_site_url" => $actual_site_url,
        "doc_count" => $indexable_data["doc_count"]
    );

    echo json_encode($ret);
    exit();
}


function get_index_stats() {
	$expertrec_options = get_option('expertrec_options');
    if ( array_key_exists('index_stats', $expertrec_options) ) {
        // error_log("Index_stats in get_index_stats are: " . print_r($expertrec_options['index_stats'], true));
        echo json_encode( $expertrec_options['index_stats'] );
    } else {
        echo json_encode( false );
    }
    exit();
}


function expertrec_is_expired() {
	$resp = get_days_to_expire();
	if( !is_wp_error( $resp ) and wp_remote_retrieve_response_code( $resp )=='200' ) {
		$json_data = wp_remote_retrieve_body( $resp );
		echo $json_data;
	} else {
		echo json_encode( false );
	}
	exit();
}


function update_expertrec_layout() {
	$template = sanitize_text_field($_POST['template']);
	$data = array(
		'layout' => array(
			'template' => $template
		)
	);
	if ($template=='separate') {
		$search_path = sanitize_text_field($_POST['search_path']);
		$query_parameter = sanitize_text_field($_POST['query_parameter']);
		$data['layout']['search_path'] = $search_path;
		$data['layout']['query_parameter'] = $query_parameter;
	}
	$site_id = get_option('expertrec_options')['site_id'];
	$resp = update_expertrec_config($site_id, 'layout', $data);
	// error_log(print_r( wp_remote_retrieve_body($resp), true ));
	if( !is_wp_error( $resp ) and wp_remote_retrieve_response_code( $resp )=='200' ) {
		$options = get_option('expertrec_options');
		$options['template'] = $template;
		if ($template=='separate') {
			$options['search_path'] = $search_path;
			$options['query_parameter'] = $query_parameter;
		}
		update_option('expertrec_options', $options);
		echo json_encode( true );
	} else {
		echo json_encode( false );
	}
	exit();
}


function expertrec_crawl() {
	$func = sanitize_text_field( $_POST['func_to_call'] );
	if ($func == 'start_crawl') {
		$resp = start_crawl();
	} elseif ($func == 'stop_crawl') {
		$resp = stop_crawl();
	} elseif ($func == 'crawl_status') {
		$resp = crawl_status();
	}
	if ( isset($resp) and !is_wp_error($resp) and wp_remote_retrieve_response_code($resp)=='200' ) {
		echo json_encode( wp_remote_retrieve_body($resp) );
	} else {
		echo json_encode( false );
	}
	exit();
}


function reset_indexing_progress() {
    update_option('expertrec_indexing_status', 'indexing');
	$options = get_option('expertrec_options');
	$options['index_stats']['product']['indexed'] = 0;
	$options['index_stats']['post']['indexed'] = 0;
	$options['index_stats']['page']['indexed'] = 0;
	update_option('expertrec_options', $options);
    // error_log("After resetting index stats: " . print_r(get_option('expertrec_options')['index_stats'], true));
}


function get_expertrec_engine() {
	$exp_eng = get_option('expertrec_engine');
	echo json_encode($exp_eng);
	exit();
}


function update_expertrec_settings() {
	$exp_eng = sanitize_text_field($_POST['engine']);
	// error_log($exp_eng);
	$options = get_option('expertrec_options');
	if ( $exp_eng == 'db' ) {
		$site_id = $options['ecom_id'];
		if ( !isset($site_id) and $options['site_id']!='58c9e0e4-78e5-11ea-baf0-0242ac130002' ) {
			$site_id = get_create_ecom_id( $options );
		}
		$resp_1 = update_option( 'expertrec_engine', 'db' );
	} else {
		$site_id = $options['cse_id'];
		$resp_1 = update_option( 'expertrec_engine', 'crawl' );
	}
	expertrec_update_conf( $site_id );
	if ( is_wp_error($resp_1) ) {
		echo json_encode( false );
	} else {
		echo json_encode( $site_id );
	}
	exit();
}


function expertrec_update_conf( $site_id = null ) {
	$expertrec_options = get_option('expertrec_options');
	if ( !$site_id ) {
		$site_id = $expertrec_options['site_id'];
	}
    $expertrec_engine = get_option('expertrec_engine');
	$resp = get_expertrec_conf( $site_id, $expertrec_engine );
	$json_data = json_decode($resp, true);
	$template = $json_data["template"];
	if ($json_data["disable_search_results"]) {
		$template = "dropdown";
	}
	$expertrec_options["site_id"] = $site_id;
	$expertrec_options["hook_on_existing_input_box"] = $json_data["hook_on_existing_input_box"];
	$expertrec_options["template"] = $template;
	$expertrec_options["search_path"] = $json_data["search_path"];
	$expertrec_options["query_parameter"] = $json_data["query_parameter"];
	// error_log("New Options: " . print_r($expertrec_options, true));
	update_option( "expertrec_options", $expertrec_options );
}


function expertrec_update_config() {
	$data = sanitize_post($_POST['data']);
	$data['modified_by'] = get_option('admin_email');
	if ( array_key_exists('hook_on_existing_input_box', $data) ) {
		if ( $data['hook_on_existing_input_box'] == 'true' or $data['hook_on_existing_input_box'] == 'True' ) {
			$data['hook_on_existing_input_box'] = true;
		} else {
			$data['hook_on_existing_input_box'] = false;
		}
	}
	$update_type = sanitize_text_field($_POST['update_type']);
	$site_id = get_option('expertrec_options')['site_id'];
	$resp = update_expertrec_config($site_id, $update_type, $data);
	if( !is_wp_error( $resp ) and wp_remote_retrieve_response_code( $resp )=='200' ) {
		$options = get_option('expertrec_options');
		foreach( $data as $key => $value ) {
			if ($key != 'modified_by') {
				$options[$key] = json_decode($value);
			}
		}
		// error_log("New Options are: " . print_r( $options, true ));
		update_option('expertrec_options', $options);
		echo json_encode(true);
	} else {
		echo json_encode(false);
	}
	exit();
}


function is_account_created() {
	$options = get_option('expertrec_options');
	if ( isset($options) and array_key_exists( 'expertrec_account_created', $options ) and array_key_exists('first_sync_done', $options) ) {
		echo( json_encode(array(
			'account_created' => $options['expertrec_account_created'],
			'first_sync_done'=> $options['first_sync_done']
		)) );
	} else {
		echo( json_encode(array(
			'account_created' => false,
			'first_sync_done'=> false
		)) );
	}
	exit();
}


function indexing_status() {
	$indexing_status = get_option('expertrec_indexing_status');
	if ( isset($indexing_status) ) {
		echo( $indexing_status );
	} else {
		echo( 'NA' );
	}
	exit();
}


function get_last_sync() {
	$options = get_option('expertrec_options');
	if ( array_key_exists('last_successful_sync', $options) ) {
		echo( $options['last_successful_sync'] );
	} else {
		echo( 'NA' );
	}
	exit();
}


function reindex_data() {
	// error_log("Indexing initited");
    $options = get_option('expertrec_options');
    $site_id = $options['site_id'];
    if( $site_id != '58c9e0e4-78e5-11ea-baf0-0242ac130002' ) {
        $options['first_sync_done'] = true;
        update_option('expertrec_options', $options);
        $client = new ExpClient();
        $client->start_sync();
        $client->indexDocs();
        $client->end_sync();
        unset( $client );
    }
    // error_log("Indexing Completed....");
    echo( 'complete' );
    exit();
}


function stop_indexing() {
	update_option('expertrec_stop_indexing', true);
	// error_log("Stopped indexing");
	exit();
}


function notify_deactivation() {
	$value = sanitize_text_field($_POST['value']);
	$selected_option = sanitize_text_field($_POST['selected_option']);
	$description = sanitize_text_field($_POST['description']);
	$data = array(
		'value' => $value,
		'reason_for_deactivation' => $selected_option,
		'description' => $description
	);
	wp_events('plugin_deactivated', $data);
	exit();
}

