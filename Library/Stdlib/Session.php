<?php
namespace Stdlib;

abstract class Session{
	
	const REGENERATE_LIFETIME = 60;
	
	private static $ready = false;
	private static $closed = false;
	
	public static function getId() {
		return session_id();
	}
	
	public static function start() {
		if (!self::$ready) {
			self::$ready = true;
			session_set_cookie_params(0, '/', (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null), (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off'), true);
			//session_cache_limiter("private");
			//ini_set('session.use_only_cookies', false);
			//ini_set('session.use_cookies', false);
			//ini_set('session.cache_limiter', null);
		}
		if (!session_id() || self::$closed) {
			try {
				session_start();
			} catch (Exception $e) {
				session_regenerate_id(true);
				session_start();
			}
			self::$closed = false;
		}
		/*switch (session_status()) {
			case PHP_SESSION_NONE:session_start();break;
			case PHP_SESSION_DISABLED:
				throw new Exception('Session disabled');
		}*/
	}
	
	public static function close() {
		if (!self::$closed) {
			session_write_close();
			self::$closed = true;
		}
	}

	public static function clear() {
		self::start();
		$_SESSION = array();
		self::close();
	}
	
}