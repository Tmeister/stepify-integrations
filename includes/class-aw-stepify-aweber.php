<?php

/**
 * The aWeber SDK
 */
require_once plugin_dir_path( __FILE__ )  . '/vendors/aweber_api/aweber_api.php';

/**
* Aweber Main CLass
*/
class AweberProxy
{

	/**
	 * [$consumer_key description]
	 * @var [type]
	 */
	protected $consumer_key = '';

	/**
	 * [$consumer_secret description]
	 * @var [type]
	 */
	protected $consumer_secret = '';

	/**
	 * [$request_token description]
	 * @var [type]
	 */
	protected $request_token = null;

	/**
	 * [$token_secret description]
	 * @var [type]
	 */
	protected $token_secret = null;

	/**
	 * [$verifier_code description]
	 * @var [type]
	 */
	protected $verifier_code;

	/**
	 * [$access_token description]
	 * @var [type]
	 */
	protected $access_key;

	/**
	 * [$access_secret description]
	 * @var [type]
	 */
	protected $access_secret;

	/**
	 * [$app_id description]
	 * @var string
	 */
	protected $app_id = '2705efcc';

	protected $auth_url = 'https://auth.aweber.com/1.0/oauth/authorize_app/%s';

	/**
	 * [$app description]
	 * @var [type]
	 */
	protected $app = null;


	function __construct(){
		$this->consumer_key = get_option('stepify_aw_consumer_key', null);
		$this->consumer_secret = get_option('stepify_aw_consumer_secret', null );
		$this->access_key = get_option('stepify_aw_access_key', null );
		$this->access_secret = get_option('stepify_aw_access_secret', null );
		if( $this->consumer_key ){
			$this->app = new AWeberAPI($this->consumer_key, $this->consumer_secret);
		}
	}

	public function verify_auth_data($verify_code = null){
		if( $this->consumer_key == null ){
			if( $verify_code != null  && !empty( $verify_code )){
				try {
					////error_log( $verify_code );
					$auth = AWeberAPI::getDataFromAweberID($verify_code);
					list($this->consumer_key, $this->consumer_secret, $this->access_key, $this->access_secret) = $auth;
					update_option('stepify_aw_consumer_key', $this->consumer_key );
					update_option('stepify_aw_consumer_secret', $this->consumer_secret );
					update_option('stepify_aw_access_key', $this->access_key );
					update_option('stepify_aw_access_secret', $this->access_secret );
					return true;
				} catch (Exception $e) {
					var_dump($e->getMessage());
				}
			}
			return false;
		}else{
			return true;
		}
	}

	public function get_user_lists(){
		if( get_transient('stepify_aw_lists' )){
			return get_transient('stepify_aw_lists' );
		}
		try {
			if( $this->app ){
				$account = $this->app->getAccount($this->access_key, $this->access_secret);
				$lists = $account->lists->find(array());
				if(count($lists)) {
					set_transient( 'stepify_aw_lists', $lists, 48 * HOUR_IN_SECONDS );
					set_transient( 'stepify_aw_account_id', $account->id, 48 * HOUR_IN_SECONDS);
					return $lists;
				} else {
					return false;
				}
			}
			return false;
		} catch (Exception $e) {
			var_dump($e->getMessage());
		}

	}

	public function get_app_authorize_url()
	{
		return sprintf($this->auth_url, $this->app_id);
	}

	public function add_subscriber($email, $list_id){

		$list_url = sprintf('/accounts/%s/lists/%s', get_transient('stepify_aw_account_id' ), $list_id);

		$args = array(
			'email' => $email,
			'name' => '',
			'misc_notes' => 'Stepify'
		);

		$init  = new DateTime();

		try {

			$account = $this->app->getAccount($this->access_key, $this->access_secret);

			$list = $account->loadFromUrl( $list_url );

			$subscribers = $list->subscribers;

		    $subscribers->create($args);

		    $end = new DateTime();
			$diff = $init->diff( $end );
			//error_log( "Elapsed Time: " . $diff->format( '%H:%I:%S' ) );

		    return true;

		} catch (Exception $e) {

			//error_log( $e->getMessage() );

			$end = new DateTime();
			$diff = $init->diff( $end );
			//error_log( "Elapsed Time: " . $diff->format( '%H:%I:%S' ) );

			return $e;
		}
	}

	public function update_subscriber($email, $list_id){
		$list_url = sprintf('/accounts/%s/lists/%s', get_transient('stepify_aw_account_id' ), $list_id);
		try {
			$account = $this->app->getAccount($this->access_key, $this->access_secret);
			$list = $account->loadFromUrl( $list_url );
			$subscribers = $list->subscribers;
			$params = array('email' => $email);
    		foreach ($subscribers->find($params) as $key => $subscriber) {
    			try {
    				$subscriber->custom_fields['source'] = 'Stepify';
	    			$subscriber->save();
	    			return true;
    			} catch (Exception $e) {
    				return $e->getMessage();
    			}
    		}

		} catch (Exception $e) {
			return $e;
		}
	}
}