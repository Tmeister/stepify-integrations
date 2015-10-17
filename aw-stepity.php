<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://enriquechavez.co
 * @since             1.0.0
 * @package           Aw_Stepity
 *
 * @wordpress-plugin
 * Plugin Name:       Stepify - Mailing Integrations
 * Plugin URI:        https://enriquechavez.co
 * Description:       Stepify External Mailing Integrations as aWeber or getResponse.
 * Version:           1.0.1
 * Author:            Enrique Chavez
 * Author URI:        https://enriquechavez.co
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       aw-stepity
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-aw-stepity-activator.php
 */
function activate_aw_stepity() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-aw-stepity-activator.php';
	Aw_Stepity_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-aw-stepity-deactivator.php
 */
function deactivate_aw_stepity() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-aw-stepity-deactivator.php';
	Aw_Stepity_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_aw_stepity' );
register_deactivation_hook( __FILE__, 'deactivate_aw_stepity' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-aw-stepity.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_aw_stepity() {

	$plugin = new Aw_Stepity();
	$plugin->run();

}
run_aw_stepity();
