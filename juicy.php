<?php

/**
 * @wordpress-plugin
 * Plugin Name:       badrock
 * Plugin URI:        www.juicebox.com.au
 * Description:       Contains a combination of hooks and filters designed to work with Juicebox themes.
 * Version:           1.0.3
 * Author:            Juicebox
 * Author URI:        www.juicebox.com.au
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       juicy
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'JUICY_VERSION', '1.0.3' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-juicy-activator.php
 */
function activate_juicy() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-juicy-activator.php';
	Juicy_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-juicy-deactivator.php
 */
function deactivate_juicy() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-juicy-deactivator.php';
	Juicy_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_juicy' );
register_deactivation_hook( __FILE__, 'deactivate_juicy' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-juicy.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_juicy() {

	$plugin = new Juicy();
	$plugin->run();

}
run_juicy();
