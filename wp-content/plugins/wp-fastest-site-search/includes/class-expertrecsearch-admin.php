<?php 
require_once plugin_dir_path( __DIR__ ) . 'includes/class-expertrecsearch-client.php';
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.expertrec.com/
 * @since      1.0.0
 *
 * @package    Expertrecsearch
 * @subpackage Expertrecsearch/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Expertrecsearch
 * @subpackage Expertrecsearch/admin
 * @author     melchi <melchi@expertrec.com>
 */
class Expertrecsearch_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

    private $demo_site_id;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
        $this->demo_site_id = '58c9e0e4-78e5-11ea-baf0-0242ac130002';

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Expertrecsearch_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Expertrecsearch_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __DIR__ ) . 'assets/css/expertrecsearch-admin.css', array(), $this->version, 'all' );
	}


	public function expertrec_transition_post_status( $new_status, $old_status, $post ) {
	    // error_log("transition_post_status {$old_status} => {$new_status}");
		if ( $old_status == "publish" && $new_status != "publish" ) {
			// Delete post if it was published and now moved to trash
			$site_id = get_option( 'expertrec_options' )['site_id'];
			if( $site_id != $this->demo_site_id ) {
				$client = new ExpClient();
				$client->deleteDoc($post->ID);
			}
		}
	}


	public function expertrec_future_to_publish( $post ) {
		// error_log("future_to_publish {$post->post_status}");
		if( "publish" == $post->post_status ) {
			// Index post if it is published
			$site_id = get_option( 'expertrec_options' )['site_id'];
			if( $site_id != $this->demo_site_id ) {
				$client = new ExpClient();
				$client->indexDoc($post->ID);
			}
		}
	}

	public function expertrec_save_post( $postId ) {
		$post = get_post( $postId );
		// error_log("save_post {$post->post_status}");
		if( "publish" == $post->post_status ) {
			// Index post if it is published
			$site_id = get_option( 'expertrec_options' )['site_id'];
			if( $site_id != $this->demo_site_id ) {
				$client = new ExpClient();
				$client->indexDoc($postId);
			}
		}
	}

	public function expertrec_trashed_post($postId) {
		// Deletion of post is handled in expertrec_transition_post_status(), so not handling it here again
		// error_log("trashed_post {$postId}");
	}

    public function expertrec_stock_status_change( $product_id, $product_stock_status, $product ) {
        // If stock status changes, then this function will be called
        $site_id = get_option( 'expertrec_options' )['site_id'];
        error_log("Stock status: " . $product_stock_status);
        if( $product_stock_status == "instock" ) {
            if( $site_id != $this->demo_site_id ) {
                $client = new ExpClient();
                $client->indexDoc( $product_id );
            }
        } elseif( $product_stock_status == "outofstock" ) {
            if( $site_id != $this->demo_site_id ) {
                $client = new ExpClient();
                $client->deleteDoc( $product_id );
            }
        }
    }

}

