<?php
use Stdlib\Date;
//use Translation;

/**
 * Datetime formats & timezones handler
 *
 * @see https://github.com/barbushin/dater
 * @author Sergey Barbushin http://linkedin.com/in/barbushin
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @copyright © Sergey Barbushin, 2013. Some rights reserved.
 *
 * All this methods works through Dater::__call method, mapped to format date with Dater::$formats[METHOD_NAME] format:
 * @method date($datetimeOrTimestamp = null) Get date in Dater::$formats['date'] format, in client timezone
 * @method time($datetimeOrTimestamp = null) Get date in Dater::$formats['time'] format, in client timezone
 * @method datetime($datetimeOrTimestamp = null) Get date in Dater::$formats['datetime'] format, in client timezone
 * @method isoDate($datetimeOrTimestamp = null) Get date in Dater::$formats['isoDate'] format, in client timezone
 * @method isoTime($datetimeOrTimestamp = null) Get date in Dater::$formats['isoTime'] format, in client timezone
 * @method isoDatetime($datetimeOrTimestamp = null) Get date in Dater::$formats['isoDatetime'] format, in client timezone
 */
class Dater {

	const DATE_FORMAT = 'date';
	const TIME_FORMAT = 'time';
	const DATETIME_FORMAT = 'datetime';
	const ISO_DATE_FORMAT = 'isoDate';
	const ISO_TIME_FORMAT = 'isoTime';
	const ISO_DATETIME_FORMAT = 'isoDatetime';

	protected static $formats = array(
		self::DATE_FORMAT => 'd.m.Y',
		self::TIME_FORMAT => 'H:i',
		self::DATETIME_FORMAT => 'd.m.Y H:i',
		self::ISO_DATE_FORMAT => 'Y-m-d',
		self::ISO_TIME_FORMAT => 'H:i:s',
		self::ISO_DATETIME_FORMAT => 'Y-m-d H:i:s',
	);

	/** @var Locale */
	protected static $locale;

	/** @var \DateTimezone[] */
	protected static $timezonesObjects = array();
	protected static $clientTimezone;
	protected static $serverTimezone;
	protected static $formatOptionsNames = array();
	protected static $formatOptionsPlaceholders = array();
	protected static $formatOptionsCallbacks = array();

	public static function init($serverTimezone = null, $clientTimezone = null) {
		//self::setLocale($locale);
		self::setServerTimezone($serverTimezone ? : date_default_timezone_get());
		self::setClientTimezone($clientTimezone ? : self::$serverTimezone);
		self::initCustomFormatOptions();
	}

	/**
	 * @return Locale
	 */
	public static function getLocale() {
		if (!self::$locale) {
			$locale = Translation::getCode();
			if ($locale) {
				self::setLocale($locale);
			}
		}
		return self::$locale;
	}

	/**
	 * Get locale by language & country code. See available locales in /Dater/Locale/*
	 * @param string $languageCode
	 * @param null $countryCode
	 * @throws \Exception
	 * @return
	 */
	public static function getLocaleByCode($languageCode, $countryCode = null) {
		$class = 'Dater\Locale\\' . ucfirst(strtolower($languageCode)) . ($countryCode ? ucfirst(strtolower($countryCode)) : '');
		if (!class_exists($class)) {
			throw new \Exception('Unknown locale code. Class "' . $class . '" not found.');
		}
		return new $class();
	}

	public static function setLocale($locale) {
		if (is_string($locale)) {
			$locale = self::getLocaleByCode($locale);
		}
		if (!($locale instanceof \Dater\Locale)) {
			throw new \Exception('Unknown locale code. Locale "' . $locale . '" not found.');
		}
		foreach ($locale::getFormats() as $alias => $format) {
			self::setFormat($alias, $format);
		}
		self::$locale = $locale;
	}

	protected static function initCustomFormatOptions() {
		self::addFormatOption('F', function (\DateTime $datetime) {
			return \Dater::getLocale()->getMonth($datetime->format('n') - 1);
		});
		self::addFormatOption('l', function (\DateTime $datetime) {
			return \Dater::getLocale()->getWeekDay($datetime->format('N') - 1);
		});
		/*self::addFormatOption('D', function (\DateTime $datetime) {
			return \Dater::getLocale()->getWeekDayShort($datetime->format('N') - 1);
		});*/
		$kFormat = function(\DateTime $datetime, $short) {
			$parts = array(
				'y' => 'год,года,лет',
				'm' => 'месяц,месяца,месяцев',
				'd' => 'день,дня,дней',
				'h' => 'час,часа,часов',
				'i' => 'минуту,минуты,минут',
				's' => 'секунду,секунды,секунд'
			);
			$name = array();
			$interval = $datetime->diff(Dater::now());
			foreach ($parts as $k => $variants) {
				if ($interval->$k) {
					$name[] = $interval->$k . ' ' . getWordDeclension($interval->$k, $variants);
					if ($short) {
						break;
					}
				}
			}
			$name[] = 'назад'; 
			return implode(' ', $name);
		};
		self::addFormatOption('K', function (\DateTime $datetime) use($kFormat) {
			return $kFormat($datetime, false);
		});
		self::addFormatOption('k', function (\DateTime $datetime) use($kFormat) {
			return $kFormat($datetime, true);
		});
	}

	public static function setServerTimezone($timezone, $setSystemGlobal = true) {
		if ($setSystemGlobal) {
			date_default_timezone_set($timezone);
		}
		self::$serverTimezone = $timezone;
	}

	public static function getServerTimezone() {
		return self::$serverTimezone;
	}

	public static function setClientTimezone($timezone) {
		self::$clientTimezone = $timezone;
	}

	public static function getClientTimezone() {
		return self::$clientTimezone;
	}

	public static function addFormatOption($option, $callback) {
		if (!is_callable($callback)) {
			throw new \Exception('Argument $callback is not callable');
		}
		if (array_search($option, self::$formatOptionsPlaceholders) !== false) {
			throw new \Exception('Option "' . $option . '" already added');
		}
		self::$formatOptionsNames[] = $option;
		self::$formatOptionsPlaceholders[] = '~' . count(self::$formatOptionsPlaceholders) . '~';
		self::$formatOptionsCallbacks[] = $callback;
	}

	/**
	 * Stash custom format options from standard PHP \DateTime format parser
	 * @param $format
	 * @return bool Return true if there was any custom options in $format
	 */
	protected static function stashCustomFormatOptions(&$format) {
		$format = str_replace(self::$formatOptionsNames, self::$formatOptionsPlaceholders, $format, $count);
		return (bool) $count;
	}

	/**
	 * Stash custom format options from standard PHP \DateTime format parser
	 * @param $format
	 * @param \DateTime $datetime
	 * @return bool Return true if there was any custom options in $format
	 */
	protected static function applyCustomFormatOptions(&$format, \DateTime $datetime) {
		$formatOptionsCallbacks = self::$formatOptionsCallbacks;
		$format = preg_replace_callback('/~(\d+)~/', function ($matches) use ($datetime, $formatOptionsCallbacks) {
					return call_user_func($formatOptionsCallbacks[$matches[1]], $datetime);
				}, $format);
	}

	/**
	 * Format current datetime to specified format with timezone converting
	 * @param string|null $format http://php.net/date format or format name
	 * @param string|null $outputTimezone Default value is Dater::$clientTimezone
	 * @return string
	 */
	public static function now($format = null, $outputTimezone = null) {
		if (null === $format) {
			return self::initDatetimeObject(null, null, $outputTimezone);
		} else {
			return self::format(null, $format, $outputTimezone);
		}
	}

	/**
	 * Init standard \DateTime object configured to outputTimezone corresponding to inputTimezone
	 * @param null $datetimeOrTimestamp
	 * @param null $inputTimezone
	 * @param null $outputTimezone
	 * @return \DateTime
	 */
	public static function initDatetimeObject($datetimeOrTimestamp = null, $inputTimezone = null, $outputTimezone = null) {
		if (!$inputTimezone) {
			$inputTimezone = self::$serverTimezone;
		}
		if (!$outputTimezone) {
			$outputTimezone = self::$clientTimezone;
		}

		if (null === $datetimeOrTimestamp) {
			$datetime = new Date();
			$isDate = true;
		} elseif ($datetimeOrTimestamp instanceof DateTime) {
			$datetime = $datetimeOrTimestamp;
			$isDate = true;
		} else {
			if (is_numeric($datetimeOrTimestamp) || strlen($datetimeOrTimestamp) == 10) {
				$isTimeStamp = is_numeric($datetimeOrTimestamp);
				$isDate = !$isTimeStamp;
			} else {
				$isTimeStamp = false;
				$isDate = false;
			}

			if ($isTimeStamp) {
				$datetime = new Date();
				$datetime->setTimestamp($datetimeOrTimestamp);
			} else {
				try {
					$datetime = new Date($datetimeOrTimestamp, $inputTimezone ? self::getTimezoneObject($inputTimezone) : null);
				} catch (Exception $ex) {
					throw $ex;
				}
			}
		}

		if (!$isDate && $outputTimezone && $outputTimezone != $inputTimezone) {
			$datetime->setTimezone(self::getTimezoneObject($outputTimezone));
		}
		return $datetime;
	}

	/**
	 * Format \DateTime object to http://php.net/date format or format name
	 * @param \DateTime $datetime
	 * @param $format
	 * @return string
	 */
	public static function formatDatetimeObject(\DateTime $datetime, $format) {
		$format = self::getFormat($format) ? : $format;
		$isStashed = self::stashCustomFormatOptions($format);
		$result = $datetime->format($format);
		if ($isStashed) {
			self::applyCustomFormatOptions($result, $datetime);
		}
		return $result;
	}

	/**
	 * Format date/datetime/timestamp to specified format with timezone converting
	 * @param string|int|null $datetimeOrTimestamp Default value current timestamp
	 * @param string|null $format http://php.net/date format or format name. Default value is current
	 * @param string|null $outputTimezone Default value is Dater::$clientTimezone
	 * @param string|null $inputTimezone Default value is Dater::$serverTimezone
	 * @return string
	 */
	public static function format($datetimeOrTimestamp, $format, $outputTimezone = null, $inputTimezone = null) {
		$datetime = self::initDatetimeObject($datetimeOrTimestamp, $inputTimezone, $outputTimezone);
		$result = self::formatDatetimeObject($datetime, $format);
		return $result;
	}

	/**
	 * @param $datetimeOrTimestamp
	 * @param string $modify Modification string as in http://php.net/date_modify
	 * @param string|null $format http://php.net/date format or format name. Default value is Dater::ISO_DATETIME_FORMAT
	 * @param string|null $outputTimezone Default value is Dater::$serverTimezone
	 * @param string|null $inputTimezone Default value is Dater::$serverTimezone
	 * @return string
	 */
	public static function modify($datetimeOrTimestamp, $modify, $format = null, $outputTimezone = null, $inputTimezone = null) {
		$format = $format ? : self::ISO_DATETIME_FORMAT;
		$outputTimezone = $outputTimezone ? : self::$serverTimezone;
		$inputTimezone = $inputTimezone ? : self::$serverTimezone;
		$datetime = self::initDatetimeObject($datetimeOrTimestamp, $inputTimezone, $outputTimezone);
		$datetime->modify($modify);
		return self::formatDatetimeObject($datetime, $format);
	}

	/**
	 * Get date in YYYY-MM-DD format, in server timezone
	 * @param string|int|null $serverDatetimeOrTimestamp
	 * @return string
	 */
	public static function serverDate($serverDatetimeOrTimestamp = null) {
		return self::format($serverDatetimeOrTimestamp, self::ISO_DATE_FORMAT, self::$serverTimezone);
	}

	/**
	 * Get date in HH-II-SS format, in server timezone
	 * @param string|int|null $serverDatetimeOrTimestamp
	 * @return string
	 */
	public static function serverTime($serverDatetimeOrTimestamp = null) {
		return self::format($serverDatetimeOrTimestamp, self::ISO_TIME_FORMAT, self::$serverTimezone);
	}

	/**
	 * Get datetime in YYYY-MM-DD HH:II:SS format, in server timezone
	 * @param null $serverDatetimeOrTimestamp
	 * @return string
	 */
	public static function serverDatetime($serverDatetimeOrTimestamp = null) {
		return self::format($serverDatetimeOrTimestamp, self::ISO_DATETIME_FORMAT, self::$serverTimezone);
	}

	public static function setFormat($alias, $format) {
		self::$formats[$alias] = $format;
	}

	/**
	 * @param $alias
	 * @return string|null
	 */
	public static function getFormat($alias) {
		if (isset(self::$formats[$alias])) {
			return self::$formats[$alias];
		}
	}

	/**
	 * @return array
	 */
	public static function getFormats() {
		return self::$formats;
	}

	/**
	 * Get Datetimezone object by timezone name
	 * @param $timezone
	 * @return \DateTimezone
	 */
	protected static function getTimezoneObject($timezone) {
		if (!isset(self::$timezonesObjects[$timezone])) {
			self::$timezonesObjects[$timezone] = new \DateTimezone($timezone);
		}
		return self::$timezonesObjects[$timezone];
	}

	/**
	 * Magic call of $dater->format($datetimeOrTimestamp, $formatAlias).
	 *
	 * Example:
	 *   $dater->addFormat('shortDate', 'd/m')
	 *   echo $dater->shortDate(time());
	 * To annotate available formats-methods just add to Dater class annotations like:
	 *   @method shortDate($datetimeOrTimestamp = null)
	 *
	 * @param $formatAlias
	 * @param array $datetimeOrTimestampArg
	 * @return string
	 * @throws \Exception
	 */
	public function __call($formatAlias, array $datetimeOrTimestampArg) {
		$formatAlias = self::getFormat($formatAlias);
		if (!$formatAlias) {
			throw new \Exception('There is no method or format with name "' . $formatAlias . '"');
		}
		return self::format(reset($datetimeOrTimestampArg), $formatAlias);
	}

}
