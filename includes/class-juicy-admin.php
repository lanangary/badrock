<?php
/**
 * Hooks to improve the admin experience.
 */

use function Env\env;

class Juicy_Admin
{

    public function admin_css()
    {
        $release_path = str_replace('/wp/', '', ABSPATH) . '/release.json';
        $version = file_exists($release_path) ? json_decode(file_get_contents($release_path), true) : null;
        if(isset($version['version'])){
            $version = $version['version'];
        }
        if (file_exists(get_stylesheet_directory() . '/dist/css/admin.css')) {
            wp_enqueue_style( 'custom-admin', get_stylesheet_directory_uri() . '/dist/css/admin.css', [], $version );
        } else {
            wp_enqueue_style( 'custom-admin', get_stylesheet_directory_uri() . '/dist/css/admin.min.css', [], $version );
        }
    }

    public function login_css()
    {
        if (file_exists(get_stylesheet_directory() . '/dist/css/login.css')) {
            wp_enqueue_style( 'custom-login', get_stylesheet_directory_uri() . '/dist/css/login.css' );
        } else {
            wp_enqueue_style( 'custom-login', get_stylesheet_directory_uri() . '/dist/css/login.min.css' );
        }
    }


    /**
     * Clean up default admin dashboard
     */
    public function clean_admin_dashboard()
    {
        remove_meta_box('dashboard_right_now', 'dashboard', 'core');       // Right Now Widget
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'core'); // Comments Widget
        remove_meta_box('dashboard_incoming_links', 'dashboard', 'core');  // Incoming Links Widget
        remove_meta_box('dashboard_plugins', 'dashboard', 'core');         // Plug-ins Widget
        remove_meta_box('dashboard_quick_press', 'dashboard', 'core');     // Quick Press Widget
        remove_meta_box('dashboard_recent_drafts', 'dashboard', 'core');   // Recent Drafts Widget
        remove_meta_box('dashboard_primary', 'dashboard', 'core');         //
        remove_meta_box('dashboard_secondary', 'dashboard', 'core');       //
        remove_action('welcome_panel', 'wp_welcome_panel');                // Hide welcome panel WP 3.3 addition

        // remove popular plug-in meta boxes
        remove_meta_box('rg_forms_dashboard', 'dashboard', 'normal');      // Gravity Forms Widget
        remove_meta_box('yoast_db_widget', 'dashboard', 'normal');         // Yoast's SEO Plug-in Widget
    }

    /**
     * Admin Toolbar
     */
    public function admin_toolbar()
    {
        global $wp_admin_bar;

        //Disable default admin bar items that we don't use
        $wp_admin_bar->remove_menu('about');                // Remove the about WordPress link
        $wp_admin_bar->remove_menu('comments');             // Remove the comments link
        $wp_admin_bar->remove_menu('customize');            // Remove the customize link
        $wp_admin_bar->remove_menu('documentation');        // Remove the WordPress documentation link
        $wp_admin_bar->remove_menu('feedback');             // Remove the feedback link
        $wp_admin_bar->remove_menu('itsec_admin_bar_menu'); // Remove iThemes Security Pro menu
        //$wp_admin_bar->remove_menu( 'my-account' );         // Remove the user details tab
        $wp_admin_bar->remove_menu('new-content');          // Remove the content link
        //$wp_admin_bar->remove_menu( 'site-name' );          // Remove the site name menu
        $wp_admin_bar->remove_menu('search');               // Search
        $wp_admin_bar->remove_menu('support-forums');       // Remove the support forums link
        $wp_admin_bar->remove_menu('updates');              // Remove the updates link
        $wp_admin_bar->remove_menu('view-site');            // Remove the view site link
        $wp_admin_bar->remove_menu('wp-logo');              // Remove the WordPress logo
        $wp_admin_bar->remove_menu('wporg');                // Remove the WordPress.org link
        $wp_admin_bar->remove_menu('wpseo-menu');           // If we use Yoast Wordpress SEO, remove the SEO link

        // Add site environment to the admin bar
        $env = env('WP_ENV');
        $dashicon = $env == 'production' ? 'site' : 'generic';
        $wp_admin_bar->add_menu([
            'id' => 'wp-admin-env',
            'title' => '<span class="wpadmin-env__dashicon dashicons dashicons-admin-' . $dashicon . '"></span> ENV: ' . ucwords($env) . '</span>',
            'meta'   => [ 'class' => 'wpadmin-env wpadmin-env--' . $env ]
        ]);

        // Replace the default Howdy message
        $newMessage = str_replace('Howdy,', 'Logged in as', $wp_admin_bar->get_node('my-account')->title);
        $wp_admin_bar->add_node(array(
            'id' => 'my-account',
            'title' => $newMessage,
        ));
    }

    /**
     * Admin Toolbar (frontend)
     */
    public function admin_bar_menu($wp_admin_bar){
        $wp_admin_bar->remove_node('customize');
        $wp_admin_bar->remove_menu('comments');
        $wp_admin_bar->remove_menu('wp-logo');
        $wp_admin_bar->remove_menu('wporg');
        $wp_admin_bar->remove_menu('search');

        // Replace the default Howdy message
        $node = $wp_admin_bar->get_node('my-account');
        if ($node !== null && isset($node->title)) {
            $newMessage = str_replace('Howdy,', 'Logged in as', $node->title);
        } else {
            $newMessage = 'Logged in as'; // Fallback if node is not found or title is missing
        }
        $wp_admin_bar->add_node(array(
            'id' => 'my-account',
            'title' => $newMessage,
        ));
    }

    /**
     * Disable some default WordPress control panel options for a cleaner interface
     */
    public function clean_admin_menu_links()
    {
        //remove_menu_page('edit.php');                    // Posts
        //remove_menu_page('edit.php?post_type=page');     // Pages
        remove_menu_page('edit-comments.php');             // Comments
        //remove_menu_page('index.php');                   // Dashboard
        remove_menu_page('link-manager.php');              // Links
        //remove_menu_page('options-general.php');         // Settings
        //remove_menu_page('plugins.php');                 // Plugins
        //remove_menu_page('themes.php');                    // Appearance
        //remove_menu_page('tools.php');                   // Tools
        //remove_menu_page('upload.php');                  // Media
        //remove_menu_page('users.php');                   // Users

        remove_submenu_page('themes.php', 'themes.php');

        global $submenu;

        if ( isset( $submenu[ 'themes.php' ] ) ) {
           foreach ( $submenu[ 'themes.php' ] as $index => $menu_item ) {
               if ( in_array( array( 'Customize', 'Customizer', 'customize' ), $menu_item ) ) {
                   unset( $submenu[ 'themes.php' ][ $index ] );
               }
           }
        }

        // Hide the following for all users except Super Administrator
        $user = wp_get_current_user();
        if ($user->ID !== 1) {
            remove_menu_page('amazon-web-services');
            remove_menu_page('tools.php');
            remove_menu_page('edit.php?post_type=acf-field-group');
        }
    }

    /**
     * Disable some default WordPress admin panel interface options for a cleaner interface
     */
    public function clean_admin_meta_boxes()
    {
        // On posts
        //remove_meta_box( 'authordiv', 'post', 'normal' );         // Author meta box
        //remove_meta_box( 'categorydiv', 'post', 'side' );         // Category meta box
        remove_meta_box('commentsdiv', 'post', 'normal');         // Comments meta box
        remove_meta_box('commentstatusdiv', 'post', 'normal');    // Comment status meta box
        remove_meta_box('formatdiv', 'post', 'normal');           // Post format meta box
        //remove_meta_box( 'postcustom', 'post', 'normal' );        // Custom fields meta box
        //remove_meta_box( 'postexcerpt', 'post', 'normal' );       // Excerpt meta box
        //remove_meta_box( 'postimagediv', 'post', 'side' );        // Featured image meta box
        //remove_meta_box( 'pageparentdiv', 'page', 'side' );       // Page attributes meta box
        //remove_meta_box( 'revisionsdiv', 'post', 'normal' );      // Revisions meta box
        //remove_meta_box( 'slugdiv', 'post', 'normal' );           // Slug meta box - Warning: if you enable this you wont be able to change permalinks
        //remove_meta_box( 'submitdiv', 'post', 'normal' );         // Publish meta box
        //remove_meta_box( 'tagsdiv-post_tag', 'post', 'side' );    // Post tags meta box
        remove_meta_box('trackbacksdiv', 'post', 'normal');       // Trackbacks meta box

        // On pages
        remove_meta_box('authordiv', 'page', 'normal');           // Author meta box
        remove_meta_box('commentsdiv', 'page', 'normal');         // Comments meta box
        remove_meta_box('commentstatusdiv', 'page', 'normal');    // Comment status meta box
        //remove_meta_box( 'postcustom', 'page', 'normal' );        // Custom fields meta box
        //remove_meta_box( 'postexcerpt', 'page', 'normal' );       // Excerpt meta box
        //remove_meta_box( 'revisionsdiv', 'page', 'normal' );      // Revisions meta box
        //remove_meta_box( 'slugdiv', 'page', 'normal' );           // Slug meta box - Warning: if you enable this you wont be able to change permalinks
        //remove_meta_box( 'submitdiv', 'page', 'normal' );         // Publish meta box
        remove_meta_box('trackbacksdiv', 'page', 'normal');       // Trackbacks meta box
    }

    /**
     * Changing the logo link from wordpress.org to your site
     */
    public function login_url()
    {
        return get_bloginfo('url');
    }

    /**
     * Changing the alt text on the logo to show your site name
     */
    public function login_title()
    {
        return get_bloginfo('name');;
    }

    /**
     * Add custom message to the admin footer
     */
    public function custom_admin_footer_text()
    {
        _e('<span id="footer-thankyou">Website by <a href="https://www.juicebox.com.au" target="_blank">Juicebox</a></span>.', 'wordpress');
    }
}
