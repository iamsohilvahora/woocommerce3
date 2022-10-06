<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Used for debugging 
 *
 * This class defines all code necessary to log plugin activty log messages
 *
 * @since      4.0.1
 * @author     Ankit Kumar <kumar@expertrec.com>
 */
class ExpLogger {

    private $version = null;
    private $upload_dir_info = null;
    private $log_file_dir_container = null;
    private $log_files_dir = null;
    private $indexing_log_file = null;
    private $general_log_file = null;
    private $log_file_dir_inside_plugin = null;
    private $expertrec_indexing_file_log = null;
    private $expertrec_general_file_log = null;


    /**
     * @desc    Will initialize all the required variables
     */
    public function __construct() {
        if ( defined( 'EXPERTREC_VERSION' ) ) {
            $this->version = EXPERTREC_VERSION;
        } else {
            $this->version = '4.0.0';
        }
        $this->set_error_log_location();
        $this->indexing_log_file = $this->log_files_dir . 'expertrec_indexing.gz';
        $this->general_log_file = $this->log_files_dir . 'expertrec_gen.gz';
        $this->expertrec_indexing_file_log = fopen( $this->indexing_log_file, 'a+' );
        $this->expertrec_general_file_log = fopen( $this->general_log_file, 'a+' );
    }

    public function __destruct() {
        fclose( $this->expertrec_indexing_file_log );
        fclose( $this->expertrec_general_file_log );
    }

    /**
     * @desc    Defines the log file directories
     */
    private function set_error_log_location() {
        $this->upload_dir_info = wp_upload_dir();
        $this->log_file_dir_container = $this->upload_dir_info['basedir'] . '/expertrec_search/';
        $this->log_files_dir = $this->log_file_dir_container . 'logs/';
        // $this->log_file_dir_inside_plugin = plugin_dir_path(__DIR__) . 'debug/';
        // Create error logs directories if they don't exist
        $this->_createAndSetErrorLogPermissions();
    }

    /**
     * @desc    If the log file directories are not there, then this will create the directories
     */
    private function _createAndSetErrorLogPermissions() {
        if( ! file_exists( $this->log_files_dir ) ){
            wp_mkdir_p( $this->log_files_dir );
        }
        // if( ! file_exists( $this->log_file_dir_inside_plugin ) ) {
        //     wp_mkdir_p( $this->log_file_dir_inside_plugin );
        // }
    }

    /**
     * @desc    Creates a formatted log msg for indexing logs
     * @param   str $fun_name   Name of the function that is writing the logs in the log file
     * @param   any $log_msg    Non-Formatted log message to write in the log file 
    */
    public function indexing( $fun_name, $log_msg ) {
        $timestamp = $this->get_timestamp();
        $log_msg = print_r($timestamp, true) . ' | ' . print_r($fun_name, true) . ' | ' . print_r($log_msg, true) . "\n";
        fwrite( $this->expertrec_indexing_file_log, $log_msg );
    }

    /**
     * @desc    Creates a formatted log msg for general logs
     * @param   str $fun_name   Name of the function that is writing the logs in the log file
     * @param   any $log_msg    Non-Formatted log message to write in the log file 
     */
    public function general( $fun_name, $log_msg ) {
        $timestamp = $this->get_timestamp();
        $log_msg = $timestamp . ' | ' . $fun_name . ' | ' . print_r($log_msg, true);
        fwrite( $this->expertrec_general_file_log, $log_msg . "\n" );
    }

    /**
     * @desc    Generate current date and time in readable format
     * @return  str $date Formatted date and time
     */
    private function get_timestamp() {
        $date = new DateTime();
        $date = $date->format('M d, Y - H:i:s');
        return $date;
    }

    /**
     * @desc    Truncates the given log file
     * @param   str $file_name  Name of the file that we want to truncate
     */
    public function truncate_log_file( $file_name ) {
        if( $file_name == 'expertrec_indexing' ) {
            $file = $this->expertrec_indexing_file_log;
        } else {
            $file = $this->expertrec_general_file_log;
        }
        ftruncate( $file, 0 );
        rewind( $file );
        // No need to close the file because we are doing that in destructor of the class
    }

}