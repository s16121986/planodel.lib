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

}
