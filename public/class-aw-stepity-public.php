<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://enriquechavez.co
 * @since      1.0.0
 *
 * @package    Aw_Stepity
 * @subpackage Aw_Stepity/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Aw_Stepity
 * @subpackage Aw_Stepity/public
 * @author     Enrique Chavez <noone@tmeister.net>
 */
class Aw_Stepity_Public {

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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		global $stepify_options;

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$stepify_options = get_option( 'stepify_settings' );

		$this->aweber = new AweberProxy();
		$aweber_api_key = isset( $stepify_options['aweber_api_key']) ? $stepify_options['aweber_api_key'] : null;
		$this->aweber_ready = $this->aweber->verify_auth_data($aweber_api_key);

	}

	public function add_end_points(){

		global $wp_rewrite;

		add_rewrite_endpoint( 'api', EP_ROOT );

		if( ! get_option('aw-stepify-flused', false) ){

			$wp_rewrite->flush_rules( true );

			add_option( 'aw-stepify-flused', true );

		}
	}

	public function verify_end_point(){

		global $wp_query;

        if ( ! isset( $wp_query->query_vars['api'] )){

			return;
		}

		$call = $wp_query->query_vars['api'];

		switch ($call) {

			case 'add-user-email':

				$this->add_user_email();
				break;

			default:

				$this->default_call();

		}

	}

	public function add_user_email(){

		global $stepify_options;

		$data = $this->get_post_data();

		if( ! isset( $data['email'] ) ){

			$this->json_output(array('message' => 'No email found in the request.', 'status' => 'fail'));

		}

		$email = $data['email'];

		switch ($stepify_options['mailing_provider']) {
			case 'aweber':
				if( $this->aweber_ready ){
					//error_log("aWeber");
					//error_log($this->aweber_ready);
					//error_log($stepify_options['aweber_list']);

					if( isset( $stepify_options['aweber_list'] ) && !empty( $stepify_options['aweber_list'] ) ){

						$added = $this->aweber->add_subscriber( $email, $stepify_options['aweber_list']);

						if( $added === true ){

							$this->json_output(array('message' => 'AWeber Email Added', 'status' => 'success', 'provider' => 'AWeber'));

						}else{

							$this->json_output(array('message' => $added, 'status' => 'fail'));
						}
					}
				}
				break;
		}

	}

	public function default_call(){

		$this->json_output(array('message' => 'API Ready', 'status' => 'success'));

	}

	private function json_output($data){

		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Headers: accept, access-control-allow-headers, content-type');
		header('Access-Control-Allow-Methods: "POST, GET, OPTIONS"');
		header('Content-Type: application/json');

		echo json_encode($data);

		die();

	}

	private function get_post_data(){

		$request = $_SERVER['REQUEST_METHOD'];

		if( $request == 'POST' ){

			$data = json_decode(file_get_contents('php://input'), true);

			if( empty($data) ){

				$this->json_output(array('error' => 'The request is empty'));

			} else {

				return $data;
			}

		} else if( $request == 'OPTIONS'){

			$this->json_output(array('status' => 'success'));

		} else{

			$this->json_output(array('error' => 'The request should be a POST request'));

		}

	}

}