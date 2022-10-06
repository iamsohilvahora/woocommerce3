<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.expertrec.com/
 * @since      1.0.0
 *
 * @package    Expertrecsearch
 * @subpackage Expertrecsearch/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Expertrecsearch
 * @subpackage Expertrecsearch/includes
 * @author     ankit kumar <kumar@expertrec.com>
 */

require_once plugin_dir_path(__DIR__) . 'hooks/expertrecsearch-caller.php';
require_once plugin_dir_path(__DIR__) . 'includes/class-expertrecsearch-logger.php';

class Expertrecsearch {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Expertrecsearch_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * The templates present in client's website.
     *
     * @since    3.0.0
     * @access   protected
     * @var      array    $templates    Array of all the templates that are there in client's wordpress site.
     */
    protected $templates;

    public $main_file = null;
    private $log = null;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct( $main_file ) {

        $this->main_file = $main_file;
        if ( defined( 'EXPERTREC_VERSION' ) ) {
            $this->version = EXPERTREC_VERSION;
        } else {
            $this->version = '4.0.0';
        }
        $this->plugin_name = 'expertrecsearch';
        $this->log = new ExpLogger();

        // initialize expertrec_options
        $this->expertrec_init_data();

        register_activation_hook( $this->main_file, array($this, 'activate_expertrecsearch') );
        register_deactivation_hook( $this->main_file, array($this, 'deactivate_expertrecsearch') );

        add_action('admin_menu', array($this, 'load_expertrec_menus'));
        add_action('admin_init', array($this, 'expertrec_plugin_redirect'));

        // Loading admin ajax script files
        add_action('admin_enqueue_scripts', array($this, 'expertrec_ajax_load_scripts'));

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();

        $this->templates = array( 
            plugin_dir_path( __DIR__ ) . 'public/templates/expertrec-search-page.php' => 'Expertrec Search Page'
        );
        // This filter is used to register our template in client wordpress
        add_filter( 'theme_page_templates', array( $this, 'expertrec_template' ) );
        // This filter will load our template instead of the default template that the client have in our search page
        add_filter( 'template_include', array( $this, 'load_template' ) );
    }

    public function __destruct() {
        unset( $this->log );
    }

    public function load_template( $template ) {
        // If the post slug is 'expertrec-search', then load our template else load the client default template
        global $post;

        if ( $post ) {
            $post_slug = $post->post_name;

            $expertrec_temp_file = plugin_dir_path( __DIR__ ) . 'public/templates/expertrec-search-page.php';
            
            if ($post_slug == 'expertrec-search') {
                return $expertrec_temp_file;
            }
        }

        return $template;
    }

    public function expertrec_template( $templates ) {
        // Adding our template in the array of client templates if any
        $templates = array_merge( $templates, $this->templates );
        return $templates;
    }

    function activate_expertrecsearch() {
        Expertrecsearch_Activator::activate();
    }

    function deactivate_expertrecsearch() {
        Expertrecsearch_Deactivator::deactivate();
    }

    // Plugin redirection after successful installation
    function expertrec_plugin_redirect() {
        if (get_option('expertrec_plugin_do_activation_redirect', false)) {
            delete_option('expertrec_plugin_do_activation_redirect');

            wp_redirect("admin.php?page=Expertrec");

            wp_events( 'plugin_activated' );
            exit;
        }
    }

    public function load_expertrec_menus() {
        $options = get_option('expertrec_options');
        //checking whether the key is present in an array
        if(array_key_exists('expertrec_account_created',$options)){
            // Expected key is present
            $account_created = $options['expertrec_account_created'];
        }
        else{
            $account_created = NULL; 
        }
        add_menu_page(
            'WP Fastest Site Search',
            'Site Search',
            'manage_options',
            'Expertrec',
            array($this, 'expertrec_menu_content'),
            plugin_dir_url(__DIR__) . 'assets/images/expertrec.png'
        );

        // If customer created the account, then only show the submenus
        if (isset($account_created) and $account_created) {
            add_submenu_page(
                'Expertrec',
                'Home',
                'Home',
                'manage_options',
                'Expertrec',
                array($this, 'expertrec_menu_content')
            );

            add_submenu_page(
                'Expertrec',
                'Layout',
                'Layout',
                'manage_options',
                'expertrecsearch-layout',
                array($this, 'expertrec_layout_page')
            );

            add_submenu_page(
                'Expertrec',
                'Advanced',
                'Advanced',
                'manage_options',
                'expertrecsearch-advanced',
                array($this, 'expertrec_advanced_page')
            );

            add_submenu_page(
                'Expertrec',
                'Settings',
                'Settings',
                'manage_options',
                'expertrecsearch-settings',
                array($this, 'expertrec_settings_page')
            );
        }

        add_submenu_page(
            'Expertrec',
            'Help',
            'Help',
            'manage_options',
            'expertrecsearch-help',
            array($this, 'expertrec_help_page')
        );
    }

    public function expertrec_menu_content() {
        $options = get_option('expertrec_options');
        $account_created = $options['expertrec_account_created'];
        if ( isset($account_created) and $account_created ) {
            include(plugin_dir_path(__DIR__) . 'views/expertrec-home.php');
        } else {
            include(plugin_dir_path(__DIR__) . 'views/expertrec-login.php');
        }
    }

    public function expertrec_layout_page() {
        $options = get_option('expertrec_options');
        $account_created = $options['expertrec_account_created'];
        if ( isset($account_created) and $account_created ) {
            include(plugin_dir_path(__DIR__) . 'views/expertrec-layout.php');
        } else {
            include(plugin_dir_path(__DIR__) . 'views/expertrec-login.php');
        }
    }

    public function expertrec_settings_page() {
        $options = get_option('expertrec_options');
        $account_created = $options['expertrec_account_created'];
        if ( isset($account_created) and $account_created ) {
            include(plugin_dir_path(__DIR__) . 'views/expertrec-settings.php');
        } else {
            include(plugin_dir_path(__DIR__) . 'views/expertrec-login.php');
        }
    }

    public function expertrec_advanced_page() {
        $options = get_option('expertrec_options');
        $account_created = $options['expertrec_account_created'];
        if ( isset($account_created) and $account_created ) {
            include(plugin_dir_path(__DIR__). 'views/expertrec-advanced.php');
        } else {
            include(plugin_dir_path(__DIR__) . 'views/expertrec-login.php');
        }
    }

    public function expertrec_help_page() {
        include(plugin_dir_path(__DIR__) . 'views/expertrec-help.php');
    }

    /**
     * @desc    Load jquery file and make the ajaxurl var available for that script
     * @since   4.1.0
     */
    public function expertrec_ajax_load_scripts() {
        // wp_enqueue_script('jquery');
        // load our jquery file that sends the $.post request
        wp_enqueue_script( "ajax-post-api", plugin_dir_url( __DIR__ ) . 'assets/js/post-api.js', array( 'jquery' ) );
        wp_enqueue_script( "ajax-expertrec-deactivate-form", plugin_dir_url( __DIR__ ) . 'assets/js/deactivate.js', array( 'jquery' ) );
        // wp_enqueue_style( $this->plugin_name, plugin_dir_url( __DIR__ ) . 'assets/css/expertrecsearch-admin.css', array(), $this->version, 'all' );
        
        // make the ajaxurl var available to the above script
        wp_localize_script( 'ajax-post-api', 'the_ajax_script', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
        wp_localize_script( 'ajax-expertrec-deactivate-form', 'the_ajax_script', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
  
        wp_localize_script( 'ajax-post-api', 'expertrecPath', array( 'pluginsUrl' => plugin_dir_url( __DIR__ ) ) );
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Expertrecsearch_Loader. Orchestrates the hooks of the plugin.
     * - Expertrecsearch_i18n. Defines internationalization functionality.
     * - Expertrecsearch_Admin. Defines all hooks for the admin area.
     * - Expertrecsearch_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path( __FILE__ ) . 'class-expertrecsearch-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path( __FILE__ ) . 'class-expertrecsearch-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path( __FILE__ ) . 'class-expertrecsearch-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path( __DIR__ ) . 'public/class-expertrecsearch-public.php';

        $this->loader = new Expertrecsearch_Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Expertrecsearch_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {

        $plugin_i18n = new Expertrecsearch_i18n();

        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

        add_action('wp_ajax_expertrec_login_response', 'expertrec_login');
        add_action('wp_ajax_expertrec_reindex_data', 'reindex_data');
        add_action('wp_ajax_expertrec_last_sync', 'get_last_sync');
        add_action('wp_ajax_expertrec_indexing_status', 'indexing_status');
        add_action('wp_ajax_expertrec_account_created', 'is_account_created');
        add_action('wp_ajax_expertrec_update_config', 'expertrec_update_config');
        add_action('wp_ajax_expertrec_index_stats', 'get_index_stats');
        add_action('wp_ajax_expertrec_layout_submit', 'update_expertrec_layout');
        add_action('wp_ajax_expertrec_settings_update', 'update_expertrec_settings');
        add_action('wp_ajax_expertrec_is_expired', 'expertrec_is_expired');
        add_action('wp_ajax_expertrec_reset_indexing_progress', 'reset_indexing_progress');
        add_action('wp_ajax_expertrec_engine', 'get_expertrec_engine');
        add_action('wp_ajax_expertrec_crawl', 'expertrec_crawl');
        add_action('wp_ajax_expertrec_stop_indexing', 'stop_indexing');
        add_action('wp_ajax_expertrec_get_site_info', 'get_site_info');
        add_action('wp_ajax_expertrec_deactivation', 'notify_deactivation');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {

        $plugin_admin = new Expertrecsearch_Admin( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );

        $this->loader->add_action( 'future_to_publish', $plugin_admin, 'expertrec_future_to_publish' );
        $this->loader->add_action( 'save_post', $plugin_admin, 'expertrec_save_post', 99, 1 );
        $this->loader->add_action( 'transition_post_status', $plugin_admin, 'expertrec_transition_post_status', 99, 3 );
        $this->loader->add_action( 'trashed_post', $plugin_admin, 'expertrec_trashed_post' );
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            $this->loader->add_action( 'woocommerce_product_set_stock_status', $plugin_admin, 'expertrec_stock_status_change', 10, 3 );
        }

    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {

        $plugin_public = new Expertrecsearch_Public( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action('wp_head', $plugin_public, 'expertrec_js_snippet', 4);
        $hook_on_existing_input_box = get_option('expertrec_options')['hook_on_existing_input_box'];
        if(!$hook_on_existing_input_box) {
            $this->loader->add_filter('get_search_form', $plugin_public, 'ci_search_form', 990);
        }
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Expertrecsearch_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    private function expertrec_options_init() {
    $settings = get_option( "expertrec_options" );
    if ( empty( $settings ) ) {
      $this->log->general("Install","Plugin Installed version : $this->version");
      $settings = array(
        'version' => $this->version,
        'site_id' => '58c9e0e4-78e5-11ea-baf0-0242ac130002',
        'hook_on_existing_input_box' => true,
        'template' => 'separate',
        'search_path' => '/expertrec-search/',
        'query_parameter' => 'q',
        'expertrec_account_created' => false,
        'first_sync_done' => false,
        'batch_size' => array(
          'product' => 5,
          'page' => 5,
          'post' => 5
        ),
        'index_stats' => array(
          'product' => array(
            'indexed' => 0,
            'indexable' => 0
          ),
          'page' => array(
            'indexed' => 0,
            'indexable' => 0
          ),
          'post' => array(
            'indexed' => 0,
            'indexable' => 0
          ),
          'currently_indexing' => 'NA'
          )
      );
      add_option( "expertrec_options", $settings, '', 'yes' );
      add_option( "expertrec_engine", 'db', '', 'yes' );
      add_option( "expertrec_indexing_status", 'NA', '', 'yes' );
    }
  }

  private function set_options_after_upgrade() {
    $options = get_option('expertrec_options');
    $options['version'] = $this->version;
    $options['cse_id'] = array_key_exists('cse_id', $options) ? $options['cse_id'] : $options['site_id'];
    $options['expertrec_account_created'] = true;
    $options['batch_size'] = array(
        'product' => 5,
        'page' => 5,
        'post' => 5
    );
    update_option( 'expertrec_options', $options );
  }

  private function expertrec_init_data() {
    $expertrec_options = get_option( 'expertrec_options');
    if ( $expertrec_options ) {
      $hook_on_existing_input_box = $expertrec_options["hook_on_existing_input_box"];
      if ( array_key_exists('version', $expertrec_options) ) {
          $version = $expertrec_options['version'];
      }
    }
    if (!isset($hook_on_existing_input_box)) {
      // defaulting it to use the existing search form
      $this->expertrec_options_init();
    }
    $expertrec_options = get_option( 'expertrec_options');
    if ( (!isset($version) or $version != $this->version) and $expertrec_options['site_id'] != '58c9e0e4-78e5-11ea-baf0-0242ac130002' ) {
        $log_msg = "Upgrading from : $version to $this->version";
        $this->log->general("Upgrade",$log_msg);
        // If client upgraded the plugin then, adding the new setting options that were not there in previous versions
        $this->set_options_after_upgrade();
        $data = array(
            'old_plugin_version' => isset($version) ? $version : 'NA'
        );
        wp_events( 'old_plugin_upgraded', $data );
        $this->log->general("Upgrade","Upgrade is done!");
    }
  }

}
