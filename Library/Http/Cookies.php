<?php
namespace Http;

class Cookies{
	
	public static function get($name, $default = null) {
		return (isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default);
	}
	
	public static function clear($name, $path = '/') {
		unset($_COOKIE[$name]);
		return self::set($name, '', time() - 3600, $path);
    }
	
	public static function set($name, $cookie, $time, $path = '/', $host = null, $secure = null, $httpOnly = false) {
		if (null === $secure) {
			$secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off');
		}
		if (null === $host && isset($_SERVER['HTTP_HOST'])) {
			$host = $_SERVER['HTTP_HOST'];
		}
		return setcookie($name, $cookie, $time, $path, $host, $secure, $httpOnly);
	}
	
}