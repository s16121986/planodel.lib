<?php

function lang() {
	return call_user_func_array(array('Translation', 'translate'), func_get_args());
}

function variable($name) {
	return Api\Service\Variables::get($name);
}

function format($value, $format) {
	return Format::exec($value, $format);
}

function getWordDeclension($number, $variants) {
	$number = (int) abs($number);
	switch (true) {
		case ($number % 100 == 1 || ($number % 100 > 20) && ($number % 10 == 1)):$i = 0;break;
		case ($number % 100 == 2 || ($number % 100 > 20) && ($number % 10 == 2)):
		case ($number % 100 == 3 || ($number % 100 > 20) && ($number % 10 == 3)):
		case ($number % 100 == 4 || ($number % 100 > 20) && ($number % 10 == 4)):
			$i = 1;
			break;
		default:$i = 2;
	}
	if (is_string($variants)) {
		$variants = explode(',', $variants);
	}
	return (isset($variants[$i]) ? $variants[$i] : null);
}

function getNumberDeclension($number, $variants) {
	$number = (int) abs($number);
	switch (true) {
		case ($number % 100 == 1 || ($number % 100 > 20) && ($number % 10 == 1)):$i = 0;break;
		case ($number % 100 == 3 || ($number % 100 > 20) && ($number % 10 == 3)):$i = 2;break;
		case ($number % 100 == 2 || ($number % 100 > 20) && ($number % 10 == 2)):
		case ($number % 100 == 3 || ($number % 100 > 20) && ($number % 10 == 6)):
		case ($number % 100 == 3 || ($number % 100 > 20) && ($number % 10 == 7)):
		case ($number % 100 == 3 || ($number % 100 > 20) && ($number % 10 == 8)):
			$i = 1;
			break;
		default:$i = 3;
	}
	if (is_string($variants)) {
		$variants = explode(',', $variants);
	}
	return (isset($variants[$i]) ? $variants[$i] : null);
}

function NumberInWords($number, $params, $format = null) {
	
	$elements = array(
		'L' => null,//Код локализации
		'SN' => true,//Включать/не включать название предмета исчисления
		'FN' => true,//Включать/не включать название десятичных частей предмета исчисления
		'FS' => false//Дробную часть выводить прописью/числом
	);	
	if (is_string($format)) {
		$formatTemp = $format;
		$format = array();
		$ei = array_keys($elements);
		$parts = explode(';', $formatTemp);
		if ('' === $parts[count($parts) - 1]) {
			array_pop($parts);
		}
		foreach ($parts as $i => $part) {
			$pp = explode('=', $part);
			if (isset($pp[1])) {
				if (isset($elements[$pp[0]])) {
					$format[$pp[0]] = $pp[1];
				}
			} else {
				if (isset($ei[$i])) {
					$format[$ei[$i]] = $pp[0];
				}
			}
		}
	} elseif (!is_array($format)) {
		$format = array();
	}
	$format = array_merge($elements, $format);
	
	
	//return Format::formatNumber($number, $format);
	$str = (string)$number;
	
}

function __initDateTimeArgument($date) {
	return \Dater::initDatetimeObject($date);
}
function CurrentDate() {
	return __initDateTimeArgument(null);
}
function Year($date = null) {
	return __initDateTimeArgument($date)->getYear();
}
function Month($date = null) {
	return __initDateTimeArgument($date)->getMonth();
}
function Day($date = null) {
	return __initDateTimeArgument($date)->getDay();
}
function Hour($date = null) {
	return __initDateTimeArgument($date)->getHour();
}
function Minute($date = null) {
	return __initDateTimeArgument($date)->getMinute();
}
function Second($date = null) {
	return __initDateTimeArgument($date)->getSecond();
}
function DayOfYear($date = null) {
	return (int)__initDateTimeArgument($date)->format('z');
}
function WeekOfYear($date = null) {
	return (int)__initDateTimeArgument($date)->format('W');
}
function BegOfYear($date = null) {
	$datetime = __initDateTimeArgument($date);
	$datetime
		->setDate($datetime->getYear(), 1, 1)
		->setTime(0, 0, 0);
	return $datetime;
}
function EndOfYear($date = null) {
	$datetime = __initDateTimeArgument($date);
	$datetime
		->setDate($datetime->getYear(), 12, 31)
		->setTime(23, 59, 59);
	return $datetime;
}
function BegOfQuarter($date = null) {
	
}
function EndOfQuarter($date = null) {
	
}
function BegOfMonth($date = null) {
	$datetime = __initDateTimeArgument($date);
	$datetime->modify('first day of this month');
	$datetime->setTime(0, 0, 0);
	return $datetime;
}
function EndOfMonth($date = null) {
	$datetime = __initDateTimeArgument($date);
	$datetime->modify('last day of this month');
	$datetime->setTime(23, 59, 59);
	return $datetime;
}
function AddMonth($date = null) {
	$datetime = __initDateTimeArgument($date);
	$datetime->modify('+1 month');
	return $datetime;
}
function WeekDay($date = null) {
	return __initDateTimeArgument($date)->getWeekDay();
}
function BegOfWeek($date = null) {
	$datetime = __initDateTimeArgument($date);
	$d = $datetime->getWeekDay();
	if ($d > 1) {
		$datetime->modify('-' . ($d - 1) . ' day');
	}
	$datetime->setTime(0, 0, 0);
	return $datetime;
}
function EndOfWeek($date = null) {
	$datetime = __initDateTimeArgument($date);
	$d = $datetime->getWeekDay();
	if ($d < 7) {
		$datetime->modify('+' . (7 - $d) . ' day');
	}
	$datetime->setTime(23, 59, 59);
	return $datetime;
}
function BegOfHour($date = null) {
	
}
function EndOfHour($date = null) {
	
}
function BegOfMinute($date = null) {
	
}
function EndOfMinute($date = null) {
	
}
function BegOfDay($date = null) {
	$datetime = __initDateTimeArgument($date);
	$datetime->setTime(0, 0, 0);
	return $datetime;
}
function EndOfDay($date = null) {
	$datetime = __initDateTimeArgument($date);
	$datetime->setTime(23, 59, 59);
	return $datetime;
}




function getPost($key = null, $default = null){
	if (null === $key) {
		return $_POST;
	}
	return (isset($_POST[$key])) ? $_POST[$key] : $default;
}

function getQuery($key = null, $default = null){
	if (null === $key) {
		return $_GET;
	}
	return (isset($_GET[$key])) ? $_GET[$key] : $default;
}

function dateRange($val, $format = 'd.m.Y', $default = null) {
	$date = '';
	if (is_string($val)) {
		switch ($val) {
			case 'today':$date = date($format);break;
			case 'week':
				$weekday = date('w');
				if ($weekday == 0) $weekday = 7;
				$weekday--;
				$sunday  = date('j') - $weekday;
				$date = array(
					date($format, mktime(0, 0, 0, date('n'), $sunday)),
					date($format, mktime(0, 0, 0, date('n'), $sunday + 6))
				);
				break;
			case 'month':
				$date = array(
					date($format, mktime(0, 0, 0, date('n'), 1)),
					date($format)//, mktime(0, 0, 0, date('n') + 1, 0)
				);
				break;
			case 'all':break;
			default:
				$time = strtotime($val);
				if (!$time) break;
				$date = date($format, $time);
		}

	} elseif (is_array($val)) {
		$date = array('', '');
		for ($i = 0; $i <= 1; $i ++) {
			if (($d = array_shift($val))) {
				if (($d = strtotime($d))) {
					$date[$i] = date($format, $d);
				}
			}
		}
	}
	if (is_string($date)) {
		$date = array($date, $date);
	}
	if (null !== $default && (!$date[0] || !$date[1])) {
		return dateRange($default, $format);
	}
	return $date;
}

function getUserAgent() {
	if (!isset($_SERVER['HTTP_USER_AGENT'])) return '';
	return $_SERVER['HTTP_USER_AGENT'];
}

function getClientIp($checkProxy = true) {
	$ip = null;
	if ($checkProxy && isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP'] != null) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} else if ($checkProxy && isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != null) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} elseif (isset($_SERVER['REMOTE_ADDR'])) {
		$ip = $_SERVER['REMOTE_ADDR'];
	}
	return $ip;
}

function debug($var, $exit = true) {
	if (!(isset($_GET['test']) || isset($_GET['debug']))) return;
	var_dump($var);
	if ($exit) exit;
}