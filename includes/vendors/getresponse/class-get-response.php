<?php

/**
 * Get Response API Proxy.
 */
if( !class_exists('Get_Response_Proxy') ){

	require_once ( 'jsonRPCClient.php' );

	class Get_Response_Proxy {

		private $api_key = false;

		private $api_url = 'http://api2.getresponse.com';

		private $app;

		function __construct( $api_key = false){

			if( ! $api_key ){return;}

			$this->api_key = $api_key;

			$this->app = new jsonRPCClient( $this->api_url );

			$this->app->setDebug( false );

		}

		public function get_app(){

			return $this->app;

		}

		public function get_campaigns(){

			if( !$this->api_key ){
				return false;
			}

			try {

				$campaigns = $this->app->get_campaigns( $this->api_key );

			} catch (Exception $e) {

				/*add_action('admin_notices', function() use ($e) {

			        echo '<div class="error"><p>GET_RESPONSE: ', esc_html($e->getMessage()), '</p></div>';

			    });*/


				return false;

			}
		    return $campaigns;

		}

		public function add_contact($data){

			try {

				return $this->app->add_contact($this->api_key, $data);

			} catch (Exception $e) {

				error_log( $e->getMessage() );

			}

		}

	}
}
?>
