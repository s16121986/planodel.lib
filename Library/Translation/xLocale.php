<?php
return;
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category  Zend
 * @package   Zend_Locale
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd     New BSD License
 * @version   $Id$
 */

/**
 * Base class for localization
 *
 * @category  Zend
 * @package   Zend_Locale
 * @copyright  Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Locale {

	

	/**
	 * Autosearch constants
	 */
	const BROWSER = 'browser';
	const ENVIRONMENT = 'environment';
	const ZFDEFAULT = 'default';

	/**
	 * Defines if old behaviour should be supported
	 * Old behaviour throws notices and will be deleted in future releases
	 *
	 * @var boolean
	 */
	public static $compatibilityMode = false;

	/**
	 * Internal variable
	 *
	 * @var boolean
	 */
	private static $_breakChain = false;

	/**
	 * Actual set locale
	 *
	 * @var string Locale
	 */
	protected $_locale;

	/**
	 * Automatic detected locale
	 *
	 * @var string Locales
	 */
	protected static $_auto;

	/**
	 * Browser detected locale
	 *
	 * @var string Locales
	 */
	protected static $_browser;

	/**
	 * Environment detected locale
	 *
	 * @var string Locales
	 */
	protected static $_environment;

	/**
	 * Default locale
	 *
	 * @var string Locales
	 */
	protected static $_default = array('en' => true);

	/**
	 * Generates a locale object
	 * If no locale is given a automatic search is done
	 * Then the most probable locale will be automatically set
	 * Search order is
	 *  1. Given Locale
	 *  2. HTTP Client
	 *  3. Server Environment
	 *  4. Framework Standard
	 *
	 * @param  string|Zend_Locale $locale (Optional) Locale for parsing input
	 * @throws Zend_Locale_Exception When autodetection has been failed
	 */
	public function __construct($locale = null) {
		$this->setLocale($locale);
	}

	/**
	 * Serialization Interface
	 *
	 * @return string
	 */
	public function serialize() {
		return serialize($this);
	}

	/**
	 * Returns a string representation of the object
	 *
	 * @return string
	 */
	public function toString() {
		return (string) $this->_locale;
	}

	/**
	 * Returns a string representation of the object
	 * Alias for toString
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->toString();
	}

	/**
	 * Return the default locale
	 *
	 * @return array Returns an array of all locale string
	 */
	public static function getDefault() {
		if ((self::$compatibilityMode === true) or (func_num_args() > 0)) {
			if (!self::$_breakChain) {
				self::$_breakChain = true;
				trigger_error('You are running Zend_Locale in compatibility mode... please migrate your scripts', E_USER_NOTICE);
				$params = func_get_args();
				$param = null;
				if (isset($params[0])) {
					$param = $params[0];
				}
				return self::getOrder($param);
			}

			self::$_breakChain = false;
		}

		return self::$_default;
	}

	/**
	 * Sets a new default locale which will be used when no locale can be detected
	 * If provided you can set a quality between 0 and 1 (or 2 and 100)
	 * which represents the percent of quality the browser
	 * requested within HTTP
	 *
	 * @param  string|Zend_Locale $locale  Locale to set
	 * @param  float              $quality The quality to set from 0 to 1
	 * @throws Zend_Locale_Exception When a autolocale was given
	 * @throws Zend_Locale_Exception When a unknown locale was given
	 * @return void
	 */
	public static function setDefault($locale, $quality = 1) {
		if (($locale === 'auto') or ($locale === 'root') or ($locale === 'default') or
			($locale === 'environment') or ($locale === 'browser')) {
			require_once 'Zend/Locale/Exception.php';
			throw new Zend_Locale_Exception('Only full qualified locales can be used as default!');
		}

		if (($quality < 0.1) or ($quality > 100)) {
			require_once 'Zend/Locale/Exception.php';
			throw new Zend_Locale_Exception("Quality must be between 0.1 and 100");
		}

		if ($quality > 1) {
			$quality /= 100;
		}

		$locale = self::_prepareLocale($locale);
		if (isset(self::$_localeData[(string) $locale]) === true) {
			self::$_default = array((string) $locale => $quality);
		} else {
			$elocale = explode('_', (string) $locale);
			if (isset(self::$_localeData[$elocale[0]]) === true) {
				self::$_default = array($elocale[0] => $quality);
			} else {
				require_once 'Zend/Locale/Exception.php';
				throw new Zend_Locale_Exception("Unknown locale '" . (string) $locale . "' can not be set as default!");
			}
		}

		self::$_auto = self::getBrowser() + self::getEnvironment() + self::getDefault();
	}

	/**
	 * Expects the Systems standard locale
	 *
	 * For Windows:
	 * f.e.: LC_COLLATE=C;LC_CTYPE=German_Austria.1252;LC_MONETARY=C
	 * would be recognised as de_AT
	 *
	 * @return array
	 */
	public static function getEnvironment() {
		if (self::$_environment !== null) {
			return self::$_environment;
		}

		require_once 'Zend/Locale/Data/Translation.php';

		$language = setlocale(LC_ALL, 0);
		$languages = explode(';', $language);
		$languagearray = array();

		foreach ($languages as $locale) {
			if (strpos($locale, '=') !== false) {
				$language = substr($locale, strpos($locale, '='));
				$language = substr($language, 1);
			}

			if ($language !== 'C') {
				if (strpos($language, '.') !== false) {
					$language = substr($language, 0, strpos($language, '.'));
				} else if (strpos($language, '@') !== false) {
					$language = substr($language, 0, strpos($language, '@'));
				}

				$language = str_ireplace(
					array_keys(Zend_Locale_Data_Translation::$languageTranslation), array_values(Zend_Locale_Data_Translation::$languageTranslation), (string) $language
				);

				$language = str_ireplace(
					array_keys(Zend_Locale_Data_Translation::$regionTranslation), array_values(Zend_Locale_Data_Translation::$regionTranslation), $language
				);

				if (isset(self::$_localeData[$language]) === true) {
					$languagearray[$language] = 1;
					if (strpos($language, '_') !== false) {
						$languagearray[substr($language, 0, strpos($language, '_'))] = 1;
					}
				}
			}
		}

		self::$_environment = $languagearray;
		return $languagearray;
	}

	/**
	 * Return an array of all accepted languages of the client
	 * Expects RFC compilant Header !!
	 *
	 * The notation can be :
	 * de,en-UK-US;q=0.5,fr-FR;q=0.2
	 *
	 * @return array - list of accepted languages including quality
	 */
	public static function getBrowser() {
		if (self::$_browser !== null) {
			return self::$_browser;
		}

		$httplanguages = getenv('HTTP_ACCEPT_LANGUAGE');
		if (empty($httplanguages) && array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER)) {
			$httplanguages = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		}

		$languages = array();
		if (empty($httplanguages)) {
			return $languages;
		}

		$accepted = preg_split('/,\s*/', $httplanguages);

		foreach ($accepted as $accept) {
			$match = null;
			$result = preg_match('/^([a-z]{1,8}(?:[-_][a-z]{1,8})*)(?:;\s*q=(0(?:\.[0-9]{1,3})?|1(?:\.0{1,3})?))?$/i', $accept, $match);

			if ($result < 1) {
				continue;
			}

			if (isset($match[2]) === true) {
				$quality = (float) $match[2];
			} else {
				$quality = 1.0;
			}

			$countrys = explode('-', $match[1]);
			$region = array_shift($countrys);

			$country2 = explode('_', $region);
			$region = array_shift($country2);

			foreach ($countrys as $country) {
				$languages[$region . '_' . strtoupper($country)] = $quality;
			}

			foreach ($country2 as $country) {
				$languages[$region . '_' . strtoupper($country)] = $quality;
			}

			if ((isset($languages[$region]) === false) || ($languages[$region] < $quality)) {
				$languages[$region] = $quality;
			}
		}

		self::$_browser = $languages;
		return $languages;
	}

	/**
	 * Sets a new locale
	 *
	 * @param  string|Zend_Locale $locale (Optional) New locale to set
	 * @return void
	 */
	public function setLocale($locale = null) {
		$locale = self::_prepareLocale($locale);

		if (isset(self::$_localeData[(string) $locale]) === false) {
			// Is it an alias? If so, we can use this locale
			if (isset(self::$_localeAliases[$locale]) === true) {
				$this->_locale = $locale;
				return;
			}

			$region = substr((string) $locale, 0, 3);
			if (isset($region[2]) === true) {
				if (($region[2] === '_') or ($region[2] === '-')) {
					$region = substr($region, 0, 2);
				}
			}

			if (isset(self::$_localeData[(string) $region]) === true) {
				$this->_locale = $region;
			} else {
				$this->_locale = 'root';
			}
		} else {
			$this->_locale = $locale;
		}
	}

	/**
	 * Returns the language part of the locale
	 *
	 * @return string
	 */
	public function getLanguage() {
		$locale = explode('_', $this->_locale);
		return $locale[0];
	}

	/**
	 * Returns the region part of the locale if available
	 *
	 * @return string|false - Regionstring
	 */
	public function getRegion() {
		$locale = explode('_', $this->_locale);
		if (isset($locale[1]) === true) {
			return $locale[1];
		}

		return false;
	}

	/**
	 * Return the accepted charset of the client
	 *
	 * @return string
	 */
	public static function getHttpCharset() {
		$httpcharsets = getenv('HTTP_ACCEPT_CHARSET');

		$charsets = array();
		if ($httpcharsets === false) {
			return $charsets;
		}

		$accepted = preg_split('/,\s*/', $httpcharsets);
		foreach ($accepted as $accept) {
			if (empty($accept) === true) {
				continue;
			}

			if (strpos($accept, ';') !== false) {
				$quality = (float) substr($accept, (strpos($accept, '=') + 1));
				$pos = substr($accept, 0, strpos($accept, ';'));
				$charsets[$pos] = $quality;
			} else {
				$quality = 1.0;
				$charsets[$accept] = $quality;
			}
		}

		return $charsets;
	}

	/**
	 * Returns true if both locales are equal
	 *
	 * @param  Zend_Locale $object Locale to check for equality
	 * @return boolean
	 */
	public function equals(Zend_Locale $object) {
		if ($object->toString() === $this->toString()) {
			return true;
		}

		return false;
	}

	/**
	 * Returns localized informations as array, supported are several
	 * types of informations.
	 * For detailed information about the types look into the documentation
	 *
	 * @param  string             $path   (Optional) Type of information to return
	 * @param  string|Zend_Locale $locale (Optional) Locale|Language for which this informations should be returned
	 * @param  string             $value  (Optional) Value for detail list
	 * @return array Array with the wished information in the given language
	 */
	public static function getTranslationList($path = null, $locale = null, $value = null) {
		require_once 'Zend/Locale/Data.php';
		$locale = self::findLocale($locale);
		$result = Zend_Locale_Data::getList($locale, $path, $value);
		if (empty($result) === true) {
			return false;
		}

		return $result;
	}

	/**
	 * Returns an array with the name of all languages translated to the given language
	 *
	 * @param  string|Zend_Locale $locale (Optional) Locale for language translation
	 * @return array
	 * @deprecated
	 */
	public static function getLanguageTranslationList($locale = null) {
		trigger_error("The method getLanguageTranslationList is deprecated. Use getTranslationList('language', $locale) instead", E_USER_NOTICE);
		return self::getTranslationList('language', $locale);
	}

	/**
	 * Returns an array with the name of all scripts translated to the given language
	 *
	 * @param  string|Zend_Locale $locale (Optional) Locale for script translation
	 * @return array
	 * @deprecated
	 */
	public static function getScriptTranslationList($locale = null) {
		trigger_error("The method getScriptTranslationList is deprecated. Use getTranslationList('script', $locale) instead", E_USER_NOTICE);
		return self::getTranslationList('script', $locale);
	}

	/**
	 * Returns an array with the name of all countries translated to the given language
	 *
	 * @param  string|Zend_Locale $locale (Optional) Locale for country translation
	 * @return array
	 * @deprecated
	 */
	public static function getCountryTranslationList($locale = null) {
		trigger_error("The method getCountryTranslationList is deprecated. Use getTranslationList('territory', $locale, 2) instead", E_USER_NOTICE);
		return self::getTranslationList('territory', $locale, 2);
	}

	/**
	 * Returns an array with the name of all territories translated to the given language
	 * All territories contains other countries.
	 *
	 * @param  string|Zend_Locale $locale (Optional) Locale for territory translation
	 * @return array
	 * @deprecated
	 */
	public static function getTerritoryTranslationList($locale = null) {
		trigger_error("The method getTerritoryTranslationList is deprecated. Use getTranslationList('territory', $locale, 1) instead", E_USER_NOTICE);
		return self::getTranslationList('territory', $locale, 1);
	}

	/**
	 * Returns a localized information string, supported are several types of informations.
	 * For detailed information about the types look into the documentation
	 *
	 * @param  string             $value  Name to get detailed information about
	 * @param  string             $path   (Optional) Type of information to return
	 * @param  string|Zend_Locale $locale (Optional) Locale|Language for which this informations should be returned
	 * @return string|false The wished information in the given language
	 */
	public static function getTranslation($value = null, $path = null, $locale = null) {
		require_once 'Zend/Locale/Data.php';
		$locale = self::findLocale($locale);
		$result = Zend_Locale_Data::getContent($locale, $path, $value);
		if (empty($result) === true && '0' !== $result) {
			return false;
		}

		return $result;
	}

	/**
	 * Returns the localized language name
	 *
	 * @param  string $value  Name to get detailed information about
	 * @param  string $locale (Optional) Locale for language translation
	 * @return array
	 * @deprecated
	 */
	public static function getLanguageTranslation($value, $locale = null) {
		trigger_error("The method getLanguageTranslation is deprecated. Use getTranslation($value, 'language', $locale) instead", E_USER_NOTICE);
		return self::getTranslation($value, 'language', $locale);
	}

	/**
	 * Returns the localized script name
	 *
	 * @param  string $value  Name to get detailed information about
	 * @param  string $locale (Optional) locale for script translation
	 * @return array
	 * @deprecated
	 */
	public static function getScriptTranslation($value, $locale = null) {
		trigger_error("The method getScriptTranslation is deprecated. Use getTranslation($value, 'script', $locale) instead", E_USER_NOTICE);
		return self::getTranslation($value, 'script', $locale);
	}

	/**
	 * Returns the localized country name
	 *
	 * @param  string             $value  Name to get detailed information about
	 * @param  string|Zend_Locale $locale (Optional) Locale for country translation
	 * @return array
	 * @deprecated
	 */
	public static function getCountryTranslation($value, $locale = null) {
		trigger_error("The method getCountryTranslation is deprecated. Use getTranslation($value, 'country', $locale) instead", E_USER_NOTICE);
		return self::getTranslation($value, 'country', $locale);
	}

	/**
	 * Returns the localized territory name
	 * All territories contains other countries.
	 *
	 * @param  string             $value  Name to get detailed information about
	 * @param  string|Zend_Locale $locale (Optional) Locale for territory translation
	 * @return array
	 * @deprecated
	 */
	public static function getTerritoryTranslation($value, $locale = null) {
		trigger_error("The method getTerritoryTranslation is deprecated. Use getTranslation($value, 'territory', $locale) instead", E_USER_NOTICE);
		return self::getTranslation($value, 'territory', $locale);
	}

	/**
	 * Returns an array with translated yes strings
	 *
	 * @param  string|Zend_Locale $locale (Optional) Locale for language translation (defaults to $this locale)
	 * @return array
	 */
	public static function getQuestion($locale = null) {
		require_once 'Zend/Locale/Data.php';
		$locale = self::findLocale($locale);
		$quest = Zend_Locale_Data::getList($locale, 'question');
		$yes = explode(':', $quest['yes']);
		$no = explode(':', $quest['no']);
		$quest['yes'] = $yes[0];
		$quest['yesarray'] = $yes;
		$quest['no'] = $no[0];
		$quest['noarray'] = $no;
		$quest['yesexpr'] = self::_prepareQuestionString($yes);
		$quest['noexpr'] = self::_prepareQuestionString($no);

		return $quest;
	}

	/**
	 * Internal function for preparing the returned question regex string
	 *
	 * @param  string $input Regex to parse
	 * @return string
	 */
	private static function _prepareQuestionString($input) {
		$regex = '';
		if (is_array($input) === true) {
			$regex = '^';
			$start = true;
			foreach ($input as $row) {
				if ($start === false) {
					$regex .= '|';
				}

				$start = false;
				$regex .= '(';
				$one = null;
				if (strlen($row) > 2) {
					$one = true;
				}

				foreach (str_split($row, 1) as $char) {
					$regex .= '[' . $char;
					$regex .= strtoupper($char) . ']';
					if ($one === true) {
						$one = false;
						$regex .= '(';
					}
				}

				if ($one === false) {
					$regex .= ')';
				}

				$regex .= '?)';
			}
		}

		return $regex;
	}

	/**
	 * Checks if a locale identifier is a real locale or not
	 * Examples:
	 * "en_XX" refers to "en", which returns true
	 * "XX_yy" refers to "root", which returns false
	 *
	 * @param  string|Zend_Locale $locale     Locale to check for
	 * @param  boolean            $strict     (Optional) If true, no rerouting will be done when checking
	 * @param  boolean            $compatible (DEPRECATED) Only for internal usage, brakes compatibility mode
	 * @return boolean If the locale is known dependend on the settings
	 */
	public static function isLocale($locale, $strict = false, $compatible = true) {
		if (($locale instanceof Zend_Locale) || (is_string($locale) && array_key_exists($locale, self::$_localeData))
		) {
			return true;
		}

		if (($locale === null) || (!is_string($locale) and !is_array($locale))) {
			return false;
		}

		try {
			$locale = self::_prepareLocale($locale, $strict);
		} catch (Zend_Locale_Exception $e) {
			return false;
		}

		if (($compatible === true) and (self::$compatibilityMode === true)) {
			trigger_error('You are running Zend_Locale in compatibility mode... please migrate your scripts', E_USER_NOTICE);
			if (isset(self::$_localeData[$locale]) === true) {
				return $locale;
			} else if (!$strict) {
				$locale = explode('_', $locale);
				if (isset(self::$_localeData[$locale[0]]) === true) {
					return $locale[0];
				}
			}
		} else {
			if (isset(self::$_localeData[$locale]) === true) {
				return true;
			} else if (!$strict) {
				$locale = explode('_', $locale);
				if (isset(self::$_localeData[$locale[0]]) === true) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Finds the proper locale based on the input
	 * Checks if it exists, degrades it when necessary
	 * Detects registry locale and when all fails tries to detect a automatic locale
	 * Returns the found locale as string
	 *
	 * @param string $locale
	 * @throws Zend_Locale_Exception When the given locale is no locale or the autodetection fails
	 * @return string
	 */
	public static function findLocale($locale = null) {
		if ($locale === null) {
			require_once 'Zend/Registry.php';
			if (Zend_Registry::isRegistered('Zend_Locale')) {
				$locale = Zend_Registry::get('Zend_Locale');
			}
		}

		if ($locale === null) {
			$locale = new Zend_Locale();
		}

		if (!Zend_Locale::isLocale($locale, true, false)) {
			if (!Zend_Locale::isLocale($locale, false, false)) {
				$locale = Zend_Locale::getLocaleToTerritory($locale);

				if (empty($locale)) {
					require_once 'Zend/Locale/Exception.php';
					throw new Zend_Locale_Exception("The locale '$locale' is no known locale");
				}
			} else {
				$locale = new Zend_Locale($locale);
			}
		}

		$locale = self::_prepareLocale($locale);
		return $locale;
	}

	/**
	 * Returns the expected locale for a given territory
	 *
	 * @param string $territory Territory for which the locale is being searched
	 * @return string|null Locale string or null when no locale has been found
	 */
	public static function getLocaleToTerritory($territory) {
		$territory = strtoupper($territory);
		if (array_key_exists($territory, self::$_territoryData)) {
			return self::$_territoryData[$territory];
		}

		return null;
	}

	/**
	 * Returns a list of all known locales where the locale is the key
	 * Only real locales are returned, the internal locales 'root', 'auto', 'browser'
	 * and 'environment' are suppressed
	 *
	 * @return array List of all Locales
	 */
	public static function getLocaleList() {
		$list = self::$_localeData;
		unset($list['root']);
		unset($list['auto']);
		unset($list['browser']);
		unset($list['environment']);
		return $list;
	}

	/**
	 * Returns the set cache
	 *
	 * @return Zend_Cache_Core The set cache
	 */
	public static function getCache() {
		require_once 'Zend/Locale/Data.php';
		return Zend_Locale_Data::getCache();
	}

	/**
	 * Sets a cache
	 *
	 * @param  Zend_Cache_Core $cache Cache to set
	 * @return void
	 */
	public static function setCache(Zend_Cache_Core $cache) {
		require_once 'Zend/Locale/Data.php';
		Zend_Locale_Data::setCache($cache);
	}

	/**
	 * Returns true when a cache is set
	 *
	 * @return boolean
	 */
	public static function hasCache() {
		require_once 'Zend/Locale/Data.php';
		return Zend_Locale_Data::hasCache();
	}

	/**
	 * Removes any set cache
	 *
	 * @return void
	 */
	public static function removeCache() {
		require_once 'Zend/Locale/Data.php';
		Zend_Locale_Data::removeCache();
	}

	/**
	 * Clears all set cache data
	 *
	 * @param string $tag Tag to clear when the default tag name is not used
	 * @return void
	 */
	public static function clearCache($tag = null) {
		require_once 'Zend/Locale/Data.php';
		Zend_Locale_Data::clearCache($tag);
	}

	/**
	 * Disables the set cache
	 *
	 * @param  boolean $flag True disables any set cache, default is false
	 * @return void
	 */
	public static function disableCache($flag) {
		require_once 'Zend/Locale/Data.php';
		Zend_Locale_Data::disableCache($flag);
	}

	/**
	 * Internal function, returns a single locale on detection
	 *
	 * @param  string|Zend_Locale $locale (Optional) Locale to work on
	 * @param  boolean            $strict (Optional) Strict preparation
	 * @throws Zend_Locale_Exception When no locale is set which is only possible when the class was wrong extended
	 * @return string
	 */
	private static function _prepareLocale($locale, $strict = false) {
		if ($locale instanceof Zend_Locale) {
			$locale = $locale->toString();
		}

		if (is_array($locale)) {
			return '';
		}

		if (empty(self::$_auto) === true) {
			self::$_browser = self::getBrowser();
			self::$_environment = self::getEnvironment();
			self::$_breakChain = true;
			self::$_auto = self::getBrowser() + self::getEnvironment() + self::getDefault();
		}

		if (!$strict) {
			if ($locale === 'browser') {
				$locale = self::$_browser;
			}

			if ($locale === 'environment') {
				$locale = self::$_environment;
			}

			if ($locale === 'default') {
				$locale = self::$_default;
			}

			if (($locale === 'auto') or ($locale === null)) {
				$locale = self::$_auto;
			}

			if (is_array($locale) === true) {
				$locale = key($locale);
			}
		}

		// This can only happen when someone extends Zend_Locale and erases the default
		if ($locale === null) {
			require_once 'Zend/Locale/Exception.php';
			throw new Zend_Locale_Exception('Autodetection of Locale has been failed!');
		}

		if (strpos($locale, '-') !== false) {
			$locale = strtr($locale, '-', '_');
		}

		$parts = explode('_', $locale);
		if (!isset(self::$_localeData[$parts[0]])) {
			if ((count($parts) == 1) && array_key_exists($parts[0], self::$_territoryData)) {
				return self::$_territoryData[$parts[0]];
			}

			return '';
		}

		foreach ($parts as $key => $value) {
			if ((strlen($value) < 2) || (strlen($value) > 3)) {
				unset($parts[$key]);
			}
		}

		$locale = implode('_', $parts);
		return (string) $locale;
	}

	/**
	 * Search the locale automatically and return all used locales
	 * ordered by quality
	 *
	 * Standard Searchorder is Browser, Environment, Default
	 *
	 * @param  string  $searchorder (Optional) Searchorder
	 * @return array Returns an array of all detected locales
	 */
	public static function getOrder($order = null) {
		switch ($order) {
			case self::ENVIRONMENT:
				self::$_breakChain = true;
				$languages = self::getEnvironment() + self::getBrowser() + self::getDefault();
				break;

			case self::ZFDEFAULT:
				self::$_breakChain = true;
				$languages = self::getDefault() + self::getEnvironment() + self::getBrowser();
				break;

			default:
				self::$_breakChain = true;
				$languages = self::getBrowser() + self::getEnvironment() + self::getDefault();
				break;
		}

		return $languages;
	}

	/**
	 * Is the given locale in the list of aliases?
	 *
	 * @param  string|Zend_Locale $locale Locale to work on
	 * @return boolean
	 */
	public static function isAlias($locale) {
		if ($locale instanceof Zend_Locale) {
			$locale = $locale->toString();
		}

		return isset(self::$_localeAliases[$locale]);
	}

	/**
	 * Return an alias' actual locale.
	 *
	 * @param  string|Zend_Locale $locale Locale to work on
	 * @return string
	 */
	public static function getAlias($locale) {
		if ($locale instanceof Zend_Locale) {
			$locale = $locale->toString();
		}

		if (isset(self::$_localeAliases[$locale]) === true) {
			return self::$_localeAliases[$locale];
		}

		return (string) $locale;
	}

}
