<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://enriquechavez.co
 * @since      1.0.0
 *
 * @package    Aw_Stepity
 * @subpackage Aw_Stepity/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Aw_Stepity
 * @subpackage Aw_Stepity/includes
 * @author     Enrique Chavez <noone@tmeister.net>
 */
class Aw_Stepity_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {

		delete_option('aw-stepify-flused');

	}

}
