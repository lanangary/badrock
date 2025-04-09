<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.1
 * @package    Juicy
 * @subpackage Juicy/includes
 * @author     Juicebox <web@juicebox.com.au>
 */
class Juicy
{
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     */
    protected $loader;

    /**
     * The general hooks class.
     */
    protected $general;

    /**
     * The class to register hooks in the admin area.
     */
    protected $admin;

    /**
     * The class to register hooks in for gravity forms.
     */
    protected $gravity_forms;

    /**
     * The class to register hooks in for timber.
     */
    protected $timber;

    /**
     * The class to register hooks in for acf.
     */
    protected $acf;

    /**
     * The unique identifier of this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     */
    protected $version;

    /**
     * Stores the options from database settings
     */
    protected $options;

    /**
     * The class to register hooks in for juicy settings.
     */
    protected $settings;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        if (defined('JUICY_VERSION')) {
            $this->version = JUICY_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'juicy';
        $this->options = get_option('juicy_settings');

        $this->load_dependencies();
        $this->define_settings_hooks();
        $this->add_general_hooks();

        if (is_admin()) {
            $this->add_admin_hooks();
        }

        if (is_login()) {
            $this->add_login_hooks();
        }

        if (class_exists('\Timber\Timber')) {
            $this->add_timber_hooks();
        }
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        if (is_plugin_active('gravityforms/gravityforms.php')
            && isset($this->options['enable_gravity_forms_hooks'])
            && $this->options['enable_gravity_forms_hooks']
            ) {
            $this->add_gravity_forms_hooks();
        }

        if (is_plugin_active('advanced-custom-fields-pro/acf.php')) {
            $this->add_acf_hooks();
        }
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Juicy_Loader. Orchestrates the hooks of the plugin.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-juicy-settings.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-juicy-general.php';
        $this->general = new Juicy_General();

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-juicy-admin.php';
        $this->admin = new Juicy_Admin();

        if (class_exists('\Timber\Timber')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-juicy-timber.php';
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-juicy-timber-image-helper.php';
            $this->timber = new Juicy_Timber();
        }

        include_once(ABSPATH . 'wp-admin/includes/plugin.php');

        if (is_plugin_active('gravityforms/gravityforms.php')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-juicy-gravity-forms.php';
            $this->gravity_forms = new Juicy_Gravity_Forms();
        }

        if (is_plugin_active('advanced-custom-fields-pro/acf.php')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-juicy-acf.php';
            $this->acf = new Juicy_ACF();
        }

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-juicy-loader.php';
        $this->loader = new Juicy_Loader();
    }

    /**
     * Add hooks related to the admin areas.
     */
    private function add_admin_hooks()
    {
        // Admin dashboard
        $this->loader->add_action('admin_menu', $this->admin, 'clean_admin_dashboard', 99);
        $this->loader->add_action('admin_menu', $this->admin, 'clean_admin_menu_links', 99);
        $this->loader->add_action('admin_menu', $this->admin, 'clean_admin_meta_boxes', 99);
        $this->loader->add_action('wp_before_admin_bar_render', $this->admin, 'admin_toolbar', 99);
        $this->loader->add_filter('admin_footer_text', $this->admin, 'custom_admin_footer_text');

        // Admin css
        $this->loader->add_action('wp_head', $this->admin, 'admin_css', 1);
        $this->loader->add_action('admin_head', $this->admin, 'admin_css', 1);

        // Settings page
        $this->loader->add_action('admin_menu', $this->settings, 'plugin_admin_add_page');
        $this->loader->add_action('admin_init', $this->settings, 'register_settings');
    }

    /**
     * Add hooks related to the login page.
     */
    private function add_login_hooks(){
        // Login page customisations
        $this->loader->add_filter('login_headerurl', $this->admin, 'login_url', 99);
        $this->loader->add_filter('login_headertext', $this->admin, 'login_title', 99);
        $this->loader->add_action('login_enqueue_scripts', $this->admin, 'login_css');
    }


    /**
     * Add general hooks to improve integration with Juicebox themes.
     */
    private function add_general_hooks()
    {
        $this->loader->add_filter('robots_txt', $this->general, 'dev_robots_disallow', 10, 2);
        $this->loader->add_action('admin_bar_menu', $this->admin, 'admin_bar_menu', 99);

        // W3C Validation fix.
        remove_action('wp_head', 'rest_output_link_wp_head', 10, 0);

        // Security - Remove Wordpress Version.
        remove_action('wp_head', 'wp_generator');
    }

    /**
     * Add hooks related to gravity forms.
     */
    private function add_gravity_forms_hooks()
    {
        $this->loader->add_filter('gform_enable_credit_card_field', $this->gravity_forms, 'enable_cc', 11);
        $this->loader->add_action('gform_field_appearance_settings', $this->gravity_forms, 'add_bootstrap_cols', 10, 2);
        $this->loader->add_action('gform_editor_js', $this->gravity_forms, 'field_settings_js');
        $this->loader->add_action('gform_enqueue_scripts', $this->gravity_forms, 'remove_gravityforms_style', 99);
        $this->loader->add_filter('gform_field_content', $this->gravity_forms, 'edit_markup_input', 99, 5);
        $this->loader->add_filter('gform_field_container', $this->gravity_forms, 'edit_markup_container', 99, 6);
        $this->loader->add_filter('gform_get_form_filter', $this->gravity_forms, 'edit_form_markup', 99, 2);
        $this->loader->add_filter('gform_submit_button', $this->gravity_forms, 'form_create_btns', 10, 2);
        $this->loader->add_filter('gform_next_button', $this->gravity_forms, 'form_create_btns', 10, 2);
        $this->loader->add_filter('gform_prev_button', $this->gravity_forms, 'form_create_btns', 10, 2);
        $this->loader->add_filter('gform_field_content', $this->gravity_forms, 'disable_honeypot_autocomplete', 10, 2);
    }

    /**
     * Add hooks related to timber/twig.
     */
    private function add_timber_hooks()
    {
        $this->loader->add_filter('init', $this->timber, 'remove_original_timber_filters', 100);
        $this->loader->add_action('init', $this->timber, 'timber_init');
        $this->loader->add_filter('timber/twig', $this->timber, 'add_to_twig');
        $this->loader->add_filter('timber/context', $this->timber, 'add_to_context');
    }

    /**
     * Add hooks related to ACF.
     */
    private function add_acf_hooks()
    {
        $this->loader->add_action('acf/fields/google_map/api', $this->acf, 'my_acf_google_map_api', 10, 1);
        $this->loader->add_filter('acf/format_value/type=post_object', $this->acf, 'field_to_jb_post', 99, 3);
        $this->loader->add_filter('acf/format_value/type=relationship', $this->acf, 'field_to_jb_post', 99, 3);
        $this->loader->add_filter('acf/format_value/type=taxonomy', $this->acf, 'field_to_jb_term', 99, 3);
        $this->loader->add_filter('acf/format_value/type=image', $this->acf, 'field_to_jb_image', 99, 3);
        $this->loader->add_filter('acf/format_value/type=swatch', $this->acf, 'colour_swatch_array_format', 99, 3);
        $this->loader->add_filter('acf/format_value/type=gallery', $this->acf, 'field_to_jb_gallery', 99, 3);
        $this->loader->add_filter('acf/format_value/type=oembed', $this->acf, 'wrap_embed', 99, 1);
    }

    /**
     * Register all of the hooks related to the settings functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_settings_hooks()
    {
        $this->settings = new Juicy_Settings($this->get_plugin_name(), $this->get_version(), $this->options);

        $this->loader->add_action('admin_enqueue_scripts', $this->settings, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $this->settings, 'enqueue_scripts');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Juicy_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }
}
