<?php
namespace Translation;

use Exception;
use Api\Exception as ApiException;
use Api\Attribute\Exception as AttributeException;

abstract class Data{

	private static $_ldml = array();

	public static function getItems($locale) {
		self::_initFile($locale);
		$itemsTemp = self::$_ldml[(string) $locale]->xpath('/ldml/items/item');
		$items = array();
		foreach ($itemsTemp as $item) {
			//var_dump($item->attributes());
			$items[(string) $item->attributes()->type] = (string) $item;
		}
		return $items;
	}
	
	public static function getContent($value, $path = null, $locale = null) {
		switch (true) {
			case $value instanceof AttributeException:
				$value = AttributeException::getErrorKey($value->getCode()) . '_' . $value->attribute;
				$path = 'error';
				break;
			case $value instanceof ApiException:
				$value = ApiException::getErrorKey($value->getCode());
				$path = 'error';
				break;
			case $value instanceof Exception:
				$value = 'error_unknown';
				$path = 'error';
				break;
		}
		switch ($path) {
			case 'currencyname':
			case 'currency':
				$temp = self::_getFile($locale, '/ldml/currencies/currency[@type=\'' . $value . '\']/displayName', '', $value);
				break;
			case 'currencysymbol':
				$temp = self::_getFile($locale, '/ldml/currencies/currency[@type=\'' . $value . '\']/symbol', '', $value);
				break;
			case 'message':
				$temp = self::_getFile($locale, '/ldml/messages/message[@type=\'' . $value . '\']');
				break;
			case 'error':
				$temp = self::_getFile($locale, '/ldml/errors/error[@type=\'' . $value . '\']');
				break;
			case 'months':
				$valueTemp = array("gregorian", "format", "wide");
				if (empty($value)) {
					$value = $valueTemp;
				} elseif (!is_array($value)) {
					$valueTemp[2] = $value;
					$value = $valueTemp;
				}
				return self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value[0] . '\']/months/monthContext[@type=\'' . $value[1] . '\']/monthWidth[@type=\'' . $value[2] . '\']/month', 'type');
			case 'month':
				$valueTemp = array("gregorian", "format", "wide");
				if (is_array($value)) {
					
				} else {
					array_unshift($valueTemp, $value);
					$value = $valueTemp;
				}
				$temp = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value[1] . '\']/months/monthContext[@type=\'' . $value[2] . '\']/monthWidth[@type=\'' . $value[3] . '\']/month[@type=\'' . $value[0] . '\']');
				break;

			case 'days':
				if (empty($value)) {
					$value = "gregorian";
				}
				$temp['context'] = "format";
				$temp['default'] = "wide";
				$temp['format']['abbreviated'] = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/days/dayContext[@type=\'format\']/dayWidth[@type=\'abbreviated\']/day', 'type');
				$temp['format']['narrow'] = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/days/dayContext[@type=\'format\']/dayWidth[@type=\'narrow\']/day', 'type');
				$temp['format']['wide'] = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/days/dayContext[@type=\'format\']/dayWidth[@type=\'wide\']/day', 'type');
				$temp['stand-alone']['abbreviated'] = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/days/dayContext[@type=\'stand-alone\']/dayWidth[@type=\'abbreviated\']/day', 'type');
				$temp['stand-alone']['narrow'] = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/days/dayContext[@type=\'stand-alone\']/dayWidth[@type=\'narrow\']/day', 'type');
				$temp['stand-alone']['wide'] = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/days/dayContext[@type=\'stand-alone\']/dayWidth[@type=\'wide\']/day', 'type');
				break;

			case 'day':
				if (empty($value)) {
					$value = array("gregorian", "format", "wide");
				}
				$temp = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value[0] . '\']/days/dayContext[@type=\'' . $value[1] . '\']/dayWidth[@type=\'' . $value[2] . '\']/day', 'type');
				break;
			case 'week':
				$minDays = self::_calendarDetail($locale, self::_getFile('supplementalData', '/supplementalData/weekData/minDays', 'territories'));
				$firstDay = self::_calendarDetail($locale, self::_getFile('supplementalData', '/supplementalData/weekData/firstDay', 'territories'));
				$weekStart = self::_calendarDetail($locale, self::_getFile('supplementalData', '/supplementalData/weekData/weekendStart', 'territories'));
				$weekEnd = self::_calendarDetail($locale, self::_getFile('supplementalData', '/supplementalData/weekData/weekendEnd', 'territories'));

				$temp = self::_getFile('supplementalData', "/supplementalData/weekData/minDays[@territories='" . $minDays . "']", 'count', 'minDays');
				$temp += self::_getFile('supplementalData', "/supplementalData/weekData/firstDay[@territories='" . $firstDay . "']", 'day', 'firstDay');
				$temp += self::_getFile('supplementalData', "/supplementalData/weekData/weekendStart[@territories='" . $weekStart . "']", 'day', 'weekendStart');
				$temp += self::_getFile('supplementalData', "/supplementalData/weekData/weekendEnd[@territories='" . $weekEnd . "']", 'day', 'weekendEnd');
				break;

			case 'quarters':
				if (empty($value)) {
					$value = "gregorian";
				}
				$temp['format']['abbreviated'] = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/quarters/quarterContext[@type=\'format\']/quarterWidth[@type=\'abbreviated\']/quarter', 'type');
				$temp['format']['narrow'] = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/quarters/quarterContext[@type=\'format\']/quarterWidth[@type=\'narrow\']/quarter', 'type');
				$temp['format']['wide'] = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/quarters/quarterContext[@type=\'format\']/quarterWidth[@type=\'wide\']/quarter', 'type');
				$temp['stand-alone']['abbreviated'] = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/quarters/quarterContext[@type=\'stand-alone\']/quarterWidth[@type=\'abbreviated\']/quarter', 'type');
				$temp['stand-alone']['narrow'] = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/quarters/quarterContext[@type=\'stand-alone\']/quarterWidth[@type=\'narrow\']/quarter', 'type');
				$temp['stand-alone']['wide'] = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/quarters/quarterContext[@type=\'stand-alone\']/quarterWidth[@type=\'wide\']/quarter', 'type');
				break;

			case 'quarter':
				if (empty($value)) {
					$value = array("gregorian", "format", "wide");
				}
				$temp = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value[0] . '\']/quarters/quarterContext[@type=\'' . $value[1] . '\']/quarterWidth[@type=\'' . $value[2] . '\']/quarter', 'type');
				break;

			case 'eras':
				if (empty($value)) {
					$value = "gregorian";
				}
				$temp['names'] = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/eras/eraNames/era', 'type');
				$temp['abbreviated'] = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/eras/eraAbbr/era', 'type');
				$temp['narrow'] = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/eras/eraNarrow/era', 'type');
				break;

			case 'era':
				if (empty($value)) {
					$value = array("gregorian", "Abbr");
				}
				$temp = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value[0] . '\']/eras/era' . $value[1] . '/era', 'type');
				break;

			case 'date':
				if (empty($value)) {
					$value = "gregorian";
				}
				$temp = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/dateFormats/dateFormatLength[@type=\'full\']/dateFormat/pattern', '', 'full');
				$temp += self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/dateFormats/dateFormatLength[@type=\'long\']/dateFormat/pattern', '', 'long');
				$temp += self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/dateFormats/dateFormatLength[@type=\'medium\']/dateFormat/pattern', '', 'medium');
				$temp += self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/dateFormats/dateFormatLength[@type=\'short\']/dateFormat/pattern', '', 'short');
				break;

			case 'time':
				if (empty($value)) {
					$value = "gregorian";
				}
				$temp = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/timeFormats/timeFormatLength[@type=\'full\']/timeFormat/pattern', '', 'full');
				$temp += self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/timeFormats/timeFormatLength[@type=\'long\']/timeFormat/pattern', '', 'long');
				$temp += self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/timeFormats/timeFormatLength[@type=\'medium\']/timeFormat/pattern', '', 'medium');
				$temp += self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/timeFormats/timeFormatLength[@type=\'short\']/timeFormat/pattern', '', 'short');
				break;

			case 'datetime':
				if (empty($value)) {
					$value = "gregorian";
				}

				$timefull = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/timeFormats/timeFormatLength[@type=\'full\']/timeFormat/pattern', '', 'full');
				$timelong = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/timeFormats/timeFormatLength[@type=\'long\']/timeFormat/pattern', '', 'long');
				$timemedi = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/timeFormats/timeFormatLength[@type=\'medium\']/timeFormat/pattern', '', 'medi');
				$timeshor = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/timeFormats/timeFormatLength[@type=\'short\']/timeFormat/pattern', '', 'shor');

				$datefull = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/dateFormats/dateFormatLength[@type=\'full\']/dateFormat/pattern', '', 'full');
				$datelong = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/dateFormats/dateFormatLength[@type=\'long\']/dateFormat/pattern', '', 'long');
				$datemedi = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/dateFormats/dateFormatLength[@type=\'medium\']/dateFormat/pattern', '', 'medi');
				$dateshor = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/dateFormats/dateFormatLength[@type=\'short\']/dateFormat/pattern', '', 'shor');

				$full = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/dateTimeFormats/dateTimeFormatLength[@type=\'full\']/dateTimeFormat/pattern', '', 'full');
				$long = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/dateTimeFormats/dateTimeFormatLength[@type=\'long\']/dateTimeFormat/pattern', '', 'long');
				$medi = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/dateTimeFormats/dateTimeFormatLength[@type=\'medium\']/dateTimeFormat/pattern', '', 'medi');
				$shor = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/dateTimeFormats/dateTimeFormatLength[@type=\'short\']/dateTimeFormat/pattern', '', 'shor');

				$temp['full'] = str_replace(array('{0}', '{1}'), array($timefull['full'], $datefull['full']), $full['full']);
				$temp['long'] = str_replace(array('{0}', '{1}'), array($timelong['long'], $datelong['long']), $long['long']);
				$temp['medium'] = str_replace(array('{0}', '{1}'), array($timemedi['medi'], $datemedi['medi']), $medi['medi']);
				$temp['short'] = str_replace(array('{0}', '{1}'), array($timeshor['shor'], $dateshor['shor']), $shor['shor']);
				break;

			case 'dateitem':
				if (empty($value)) {
					$value = "gregorian";
				}
				$_temp = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/dateTimeFormats/availableFormats/dateFormatItem', 'id');
				foreach ($_temp as $key => $found) {
					$temp += self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/dateTimeFormats/availableFormats/dateFormatItem[@id=\'' . $key . '\']', '', $key);
				}
				break;

			case 'dateinterval':
				if (empty($value)) {
					$value = "gregorian";
				}
				$_temp = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/dateTimeFormats/intervalFormats/intervalFormatItem', 'id');
				foreach ($_temp as $key => $found) {
					$temp[$key] = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value . '\']/dateTimeFormats/intervalFormats/intervalFormatItem[@id=\'' . $key . '\']/greatestDifference', 'id');
				}
				break;
            case 'field':
                if (!is_array($value)) {
                    $temp = $value;
                    $value = array("gregorian", $temp);
                }
                $temp = self::_getFile($locale, '/ldml/dates/fields/field[@type=\'' . $value[1] . '\']/displayName', '', $value[1]);
                break;
            case 'relative':
                if (!is_array($value)) {
                    $temp = $value;
                    $value = array("gregorian", $temp);
                }
                $temp = self::_getFile($locale, '/ldml/dates/fields/field[@type=\'day\']/relative[@type=\'' . $value[1] . '\']');
                // $temp = self::_getFile($locale, '/ldml/dates/calendars/calendar[@type=\'' . $value[0] . '\']/fields/field/relative[@type=\'' . $value[1] . '\']', '', $value[1]);
                break;
			default:
				$temp = self::_getFile($locale, '/ldml/items/item[@type=\'' . $value . '\']');
		}
		if (is_array($temp)) {
		    $temp = current($temp);
		}
		return (false === $temp ? $value : $temp);
	}

	private static function _initFile($locale) {
		if (!isset(self::$_ldml[(string) $locale])) {
			$filename = LIB_PATH . '/locale/' . $locale . '.xml';
			if (!file_exists($filename)) {
				#require_once 'Zend/Locale/Exception.php';
				throw new Exception("Missing locale file '$filename' for '$locale' locale.");
			}
			self::$_ldml[(string) $locale] = simplexml_load_file($filename);
		}
		return self::$_ldml[(string) $locale];
	}

	private static function _getFile($locale, $path, $attribute = false, $value = false, $temp = array()) {
		self::_initFile($locale);
		// without attribute - read all values
		// with attribute    - read only this value
		if (!empty(self::$_ldml[(string) $locale])) {
			$result = self::$_ldml[(string) $locale]->xpath($path);
			if (!empty($result)) {
				foreach ($result as &$found) {
					if (empty($value)) {
						if (empty($attribute)) {
							// Case 1
							$temp[] = (string) $found;
						} else if (empty($temp[(string) $found[$attribute]])) {
							// Case 2
							$temp[(string) $found[$attribute]] = (string) $found;
						}
					} else if (empty($temp[$value])) {

						if (empty($attribute)) {
							// Case 3
							$temp[$value] = (string) $found;
						} else {
							// Case 4
							$temp[$value] = (string) $found[$attribute];
						}
					}
				}
			}
		}
		return $temp;

		// parse required locales reversive
		// example: when given zh_Hans_CN
		// 1. -> zh_Hans_CN
		// 2. -> zh_Hans
		// 3. -> zh
		// 4. -> root
		if (($locale != 'root') && ($result)) {
			$locale = substr($locale, 0, -strlen(strrchr($locale, '_')));
			if (!empty($locale)) {
				$temp = self::_getFile($locale, $path, $attribute, $value, $temp);
			} else {
				$temp = self::_getFile('root', $path, $attribute, $value, $temp);
			}
		}
		return $temp;
	}
}