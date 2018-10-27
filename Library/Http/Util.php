<?php
namespace Http;

abstract class Util {

	public static function isSearchBot($botname = '') {
		/* Эта функция будет проверять, является ли посетитель роботом поисковой системы */
		$bots = array(
			'bot',
			'slurp',
			'crawler',
			'spider',
			'curl',
			'facebook',
			'fetch',
		);
		foreach ($bots as $bot) {
			if (isset($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], $bot) !== false) {
				//$botname = $bot;
				return true;
			}
		}
		return false;
	}
	
	protected static function getHeader($name) {

        // Try to get it from the $_SERVER array first
        $temp = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (isset($_SERVER[$temp]) && $_SERVER[$temp])
            return $_SERVER[$temp];
		elseif (isset($_SERVER['REDIRECT_' . $temp]) && $_SERVER['REDIRECT_' . $temp])
			return $_SERVER['REDIRECT_' . $temp];

        // This seems to be the only way to get the Authorization header on
        // Apache
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
			$name = strtolower($name);
			foreach ($headers as $k => $v) {
				if (strtolower($k) == $name) {
					return $v;
				}
			}
        } elseif (function_exists('getallheaders')) return getallheaders();

        return false;
    }

}
