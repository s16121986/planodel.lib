<?php
namespace Auth\Storage;

use Auth\User;
use Auth\Util;
use Exception;
use Stdlib\Session as AbstractSession;

class Session extends AbstractStorage{

	const MEMBER = 'Auth';
	const REGENERATE_LIFETIME = 60;
	
	private static $ready = false;
	private static $closed = false;
	
	public function __construct(User $user) {
		self::start();
		return parent::__construct($user);
	}

	public function hasIdentity() {
		return (isset($_SESSION[self::MEMBER])
			&& is_array($_SESSION[self::MEMBER])
			&& isset($_SESSION[self::MEMBER]['user_agent'])
			&& $_SESSION[self::MEMBER]['user_ip'] === Util::getClientIp()
			&& $_SESSION[self::MEMBER]['user_agent'] === Util::getUserAgent()
		);
	}

	public function setIdentity($identity, $redirect = null) {
		self::start();
		$_SESSION[self::MEMBER] = array(
			'identity' => $identity,
			'time' => time(),
			'user_ip' => Util::getClientIp(),
			'user_agent' => Util::getUserAgent()
		);
		//self::close();
		return parent::setIdentity($identity, $redirect);
	}

	public function getIdentity() {
		if ($this->hasIdentity()) {
			$time = time();
			if ($time > $_SESSION[self::MEMBER]['time'] + self::REGENERATE_LIFETIME) {
				self::start();
				session_regenerate_id(true);
				$_SESSION[self::MEMBER]['time'] = $time;
				//self::close();
			}
			return $_SESSION[self::MEMBER]['identity'];
		}
		return null;
	}

	public function clear($params = null, $redirect = null) {
		self::start();
		unset($_SESSION[self::MEMBER]);
		//self::close();
		return parent::clear($params, $redirect);
	}
	
	private static function start() {
		return AbstractSession::start();
	}
	
	private static function close() {
		AbstractSession::close();
	}

}