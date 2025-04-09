<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.juicebox.com.au
 * @since      1.0.0
 *
 * @package    Juicy
 * @subpackage Juicy/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Juicy
 * @subpackage Juicy/admin
 * @author     Juicebox <web@juicebox.com.au>
 */
class Juicy_Settings {

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

    /**
     * Options passed from the database
     */
    private $options;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version, $options ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->options = $options;

    }

    /**
     * Create an admin page for the plugin.
     */
    public function plugin_admin_add_page() {
        add_options_page('Juicy Settings', 'Juicy', 'manage_options', 'juicy', [$this, 'plugin_options_page']);
    }

    /**
     * Return the settings page.
     */
    public function plugin_options_page() {
        include 'partials/juicy-admin-display.php';
    }

    /**
     * Register the settings for the plugin.
     */
    public function register_settings() {
        register_setting('juicy_settings', 'juicy_settings', ['type' => 'boolean']);
        add_settings_section('juicy_third_party', 'Third Party Integrations', [$this, 'juicy_third_party_settings'], 'juicy');

        if (is_plugin_active('gravityforms/gravityforms.php')) {
            add_settings_field('enable_gravity_forms_hooks', 'Gravity Forms Hooks', [$this, 'enable_gravity_forms_hooks'], 'juicy', 'juicy_third_party');
        }
    }

    /**
     * Validate the settings for juicy.
     */
    public function juicy_settings_validate($input) {

    }

    public function juicy_third_party_settings() {
        echo '<p>Settings for enabling or disabling third party integrations.</p>';
    }

    public function enable_gravity_forms_hooks() {
        $option = !empty($this->options['enable_gravity_forms_hooks']) ? $this->options['enable_gravity_forms_hooks'] : false;
        echo '<input id="enable_gravity_forms_hooks" name="juicy_settings[enable_gravity_forms_hooks]" type="checkbox" value="1"' . checked( 1, $option, false ) . '" />';
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
         * defined in Juicy_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Juicy_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
         $cache_version = $this->version;
         $release_path = str_replace('/wp/', '', ABSPATH) . '/release.json';
         if( file_exists($release_path) ){
             $version = json_decode(file_get_contents($release_path), true);
             if(isset($version['version'])){
                 $cache_version = $version['version'];
             }
         }

        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/juicy-admin.css', array(), $cache_version, 'all' );

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Juicy_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Juicy_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/juicy-admin.js', array( 'jquery' ), $this->version, false );

    }

}
