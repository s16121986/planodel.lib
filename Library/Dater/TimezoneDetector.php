<?php

namespace Dater;

/**
 * Detect client timezone by JavaScript
 *
 * @see https://github.com/barbushin/dater
 * @author Sergey Barbushin
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @copyright © Sergey Barbushin, 2013. Some rights reserved.
 */
abstract class TimezoneDetector {

	protected static $cookieName;
	protected static $clientTimezone;

	public static function init($cookieName = 'dater_timezone') {
		self::$cookieName = $cookieName;
		self::$clientTimezone = self::initClientTimezone();
		//die(self::$clientTimezone);
		if (self::$clientTimezone) {
			\Dater::setClientTimezone(self::$clientTimezone);
		}
	}

	protected static function initClientTimezone() {
		if(isset($_COOKIE[self::$cookieName])) {
			return $_COOKIE[self::$cookieName];
		}
	}

	public static function getClientTimezone() {
		return self::$clientTimezone;
	}

	/**
	 *
	 * Detection method is based on jsTimezoneDetect library http://pellepim.bitbucket.org/jstz/ + COOKIE store
	 * @param bool $reloadPageOnTimezoneChanged
	 * @param int $refreshInterval
	 * @return string|null
	 */
	public static function getHtmlJsCode($reloadPageOnTimezoneChanged = true, $refreshInterval = 100) {
		return '
<script type="text/javascript" src="http://static.planodel.ru/v0/resources/js/timezone.js"></script>
<script type="text/javascript">
	function refreshTimezoneCookie() {
		var lastTimezone = (m = new RegExp(";\\\\s*' . self::$cookieName . '=(.*?);", "g").exec(";" + document.cookie + ";")) ? m[1] : null;
		var currentTimezone = jstz.determine().name();
		if(!lastTimezone || (lastTimezone != currentTimezone)) {
			document.cookie = "' . self::$cookieName . '=" + jstz.determine().name() + "; path=/";' .
		($reloadPageOnTimezoneChanged ? '
			location.reload(true);' : '') . '
		}
	}
	refreshTimezoneCookie();
	setInterval(refreshTimezoneCookie, ' . $refreshInterval . ' * 1000);
</script>';
	}
}
