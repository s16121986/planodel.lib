<?php
namespace Api\Service;

use Db;

abstract class Log{
	
	const PART_REQUEST = 'request';
	const PART_FILES = 'files';
	const PART_COOKIE = 'cookie';
	const PART_RESPONSE = 'response';
	
	private static $parts = array(
		self::PART_REQUEST => true,
		self::PART_FILES => true,
		self::PART_COOKIE => true,
		self::PART_RESPONSE => false
	);
	
	public static function enablePart($part) {
		self::$parts[$part] = true;
	}
	
	public static function disabledPart($part) {
		self::$parts[$part] = false;
	}

	public static function getsize($var) {
		if (is_string($var)) {
			return strlen($var);
		} elseif (is_array($var)) {
			$size = 0;
			foreach ($var as $v) {
				$size += self::getsize($v);
			}
			return $size;
		} else {
			return 1;
		}
	}

	public static function getUserAgent() {
		if (!isset($_SERVER['HTTP_USER_AGENT']))
			return '';
		return $_SERVER['HTTP_USER_AGENT'];
	}

	public static function getClientIp($checkProxy = true, $checkBanlist = false) {
		$ip = null;
		if ($checkProxy && isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP'] != null) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} else if ($checkProxy && isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != null) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif (isset($_SERVER['REMOTE_ADDR'])) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		if ($ip && $checkBanlist) {
			self::checkIp($ip);
		}
		return $ip;
	}

	public static function exec() {
		/*$responseSize = null;
		if ($response) {
			$responseSize = self::getsize($response);
		}*/
		$request = array(
			'host' => $_SERVER['HTTP_HOST'],
			//'uri' => $_SERVER['REQUEST_URI'],
			'request' => $_REQUEST,
			'cookie' => $_COOKIE,
			'files' => $_FILES
		);
		if (defined('UserId')) {
			$request['user_id'] = UserId;
		}
		if (function_exists('apache_request_headers')) {
			$request['headers'] = apache_request_headers();
		}
		return self::log($request);
		foreach ($valuesTemp as $value) {
			$values[] = Db::quote($value);
		}
		Db::query('INSERT INTO api_log (method, uri, request, ip, useragent) VALUES (' . implode(',', $values) . ')');
	}
	
	public static function log($data) {
		Db::insert('api_log', array(
			'method' => $_SERVER['REQUEST_METHOD'],
			'uri' => $_SERVER['REQUEST_URI'],
			'request' => print_r($data, true),
			//'response' => $responseSize,
			'ip' => self::getClientIp(),
			'useragent' => self::getUserAgent()
		));
	}

}
