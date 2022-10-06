<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.expertrec.com/
 * @since             1.0.0
 * @package           Expertrecsearch
 *
 * @wordpress-plugin
 * Plugin Name:       WP Fastest Site Search
 * Plugin URI:        https://blog.expertrec.com/wordpress-search-not-working-how-to-fix/
 * Description:       Expertrec Search replaces your site search functionality with a fast <strong>Google like search</strong>.  It totally <strong>avoids the load on your wordpress server</strong> when your users are searching.
 * Version:           4.1.18
 * Author:            Expertrec
 * Author URI:        https://www.expertrec.com/wordpress-search-plugin/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       expertrecsearch
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
  die("You can't access this file directly");
}

require_once __DIR__ . '/vendor/autoload.php';

 define( 'EXPERTREC_VERSION', '4.1.18' );
 
// Initializing sentry
$sentry_client = new Raven_Client('https://6b79e8c7a8b34ffca53f01e467dccd08:98c641a4d5b5424aaf2f5a2d6cdd9d40@o1334740.ingest.sentry.io/6601655');
// providing a bit of additional context
$sentry_client->user_context(array('email' => get_option('admin_email', 'NA')));
$sentry_client->extra_context(array('plugin_version' => EXPERTREC_VERSION, 'php_version' => phpversion()));
// Excluding errors that are not from our plugin
$sentry_client->setSendCallback(function($data) {
  $plugin_error = false;
  $frames = $data['exception']['values'][0]['stacktrace']['frames'];
  foreach($frames as $frame) {
    if( strpos($frame['filename'], 'wp-fastest-site-search') ) {
      $plugin_error = true;
    }
  }
  if( !$plugin_error ) {
    return false;
  }
  return $data;
});
$error_handler = new Raven_ErrorHandler($sentry_client);
$error_handler->registerExceptionHandler();
$error_handler->registerErrorHandler();
$error_handler->registerShutdownFunction();

function get_sentry_client() {
  global $sentry_client;
  return $sentry_client;
}

$expPluginPath = plugin_dir_path( __DIR__ );

define( 'PLUGIN_DIR_PATH', $expPluginPath );

require 'includes/class-expertrecsearch.php';
require 'includes/class-expertrecsearch-activator.php';
require 'includes/class-expertrecsearch-deactivator.php';
require 'hooks/expertrecsearch-ajax.php';

$expsearch = new Expertrecsearch(__FILE__);
$expsearch->run();
