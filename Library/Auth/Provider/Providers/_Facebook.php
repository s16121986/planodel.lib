<?php
namespace Auth\Provider\Providers;

use Auth\Provider\Storage;

class Facebook extends AbstractProvider{

	private $sdk = null;

	public function __construct($params) {
		parent::__construct($params);

		require_once dirname(__FILE__) . '/Facebook/autoload.php';
		$this->sdk = new Facebook\Facebook(array(
			'app_id' => $this->id,
			'app_secret' => $this->secret,
		));
		if ($this->access_token) {
			$this->sdk->setDefaultAccessToken($this->access_token);
		}
	}
	
	public function loginBegin() {
		$parameters = array("scope" => $this->scope, "redirect_uri" => $this->endpoint, "display" => "page");
		$optionals = array("scope", "redirect_uri", "display", "auth_type");

		foreach ($optionals as $parameter) {
			if ($this->$parameter) {
				$parameters[$parameter] = $this->$parameter;

				//If the auth_type parameter is used, we need to generate a nonce and include it as a parameter
				if ($parameter == "auth_type") {
					$nonce = md5(uniqid(mt_rand(), true));
					$parameters['auth_nonce'] = $nonce;

					$this->storage->set('fb_auth_nonce', $nonce);
				}
			}
		}

		if ($this->force === true) {
			$parameters['auth_type'] = 'reauthenticate';
			$parameters['auth_nonce'] = md5(uniqid(mt_rand(), true));

			$this->storage->set('fb_auth_nonce', $parameters['auth_nonce']);
		}

		// get the login url
		$url = $this->sdk->getLoginUrl($parameters);

		// redirect to facebook
		self::redirect($url);
	}

	public function loginFinish() {
		// in case we get error_reason=user_denied&error=access_denied
		if (isset($_REQUEST['error']) && $_REQUEST['error'] == "access_denied") {
			throw new Exception("Authentication failed! The user denied your request.", 5);
		}

		// in case we are using iOS/Facebook reverse authentication
		if (isset($_REQUEST['access_token'])) {
			$this->access_token = $_REQUEST['access_token'];
			$this->sdk->setAccessToken($this->access_token);
			$this->sdk->setExtendedAccessToken();
			$access_token = $this->sdk->getAccessToken();

			if ($access_token) {
				$this->access_token = $access_token;
				$this->sdk->setAccessToken($access_token);
			}

			$this->sdk->setAccessToken($this->access_token);
		}


		// if auth_type is used, then an auth_nonce is passed back, and we need to check it.
		if (isset($_REQUEST['auth_nonce'])) {

			$nonce = $this->storage->get('fb_auth_nonce');

			//Delete the nonce
			$this->storage->delete('fb_auth_nonce');

			if ($_REQUEST['auth_nonce'] != $nonce) {
				throw new Exception("Authentication failed! Invalid nonce used for reauthentication.", 5);
			}
		}

		// try to get the UID of the connected user from fb, should be > 0
		if (!$this->sdk->getUser()) {
			throw new Exception("Authentication failed! {$this->provider} returned an invalid user id.", 5);
		}

		// set user as logged in
		$this->setUserConnected();

		// store facebook access token
		$this->access_token = $this->sdk->getAccessToken();
	}

	public function logout() {
		$this->sdk->destroySession();
		parent::logout();
	}

	public function getProfile() {
		$fields = array(
			'id', 'name', 'first_name', 'last_name', 'link', 'website',
			'gender', 'locale', 'about', 'email', 'hometown', 'location',
			'birthday'
		);
		$response = $this->sdk->get('/me?fields=' . implode(',', $fields));
		$userNode = $response->getGraphUser();
		foreach (array(
			'name' => 'presentation',
			'first_name' => 'name',
			'last_name' => 'lastname',
			'email' => 'email',
			'id' => 'identifier'
		) as $fbKey => $dataKey) {
			$dataKey = $userNode->getProperty($fbKey);
		}
		$data = $userNode->asArray();
		$data['presentation'] = $userNode->getName();
		unset($data['name']);
		
		//$this->profile->photoURL = "https://graph.facebook.com/" . $this->user->profile->identifier . "/picture?width=150&height=150";
		//$this->profile->coverInfoURL = "https://graph.facebook.com/" . $this->user->profile->identifier . "?fields=cover&access_token=" . $this->sdk->getAccessToken();

		if (isset($data['region']) && $data['region']) {
			$regionArr = explode(',', $data['region']);
			if (count($regionArr) > 1) {
				$data['city'] = trim($regionArr[0]);
				$data['country'] = trim($regionArr[1]);
			}
		}

		if (array_key_exists('birthday', $data)) {
			list($birthday_month, $birthday_day, $birthday_year) = explode("/", $data['birthday']);
			$data['birthday'] = $birthday_year . '-' . $birthday_month . '-' . $birthday_day;
		}
		$this->profile->setData($data);
		return parent::getProfile();
	}

}
