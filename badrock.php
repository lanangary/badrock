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
 * Text Domain:       badrock
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
define( 'badrock_VERSION', '1.0.3' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-badrock-activator.php
 */
function activate_badrock() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-badrock-activator.php';
	badrock_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-badrock-deactivator.php
 */
function deactivate_badrock() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-badrock-deactivator.php';
	badrock_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_badrock' );
register_deactivation_hook( __FILE__, 'deactivate_badrock' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-badrock.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_badrock() {

	$plugin = new badrock();
	$plugin->run();

}
run_badrock();
