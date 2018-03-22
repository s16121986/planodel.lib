<?php
namespace User\Currency;

use Format;

abstract class Enum extends \Enum{
	
	/*const RUB = 643;
	const EUR = 978;
	const USD = 840;
	const UAH = 980;
	const BYR = 974;
	const KZT = 398;
	const GBP = 826;*/
	
	private static $_loader;
	private static $_storage;
	private static $_mainCurrency = null;
	private static $_defaultCurrency = null;

	public static function init() {
		$current = self::getStorage()->get();
		if (null === $current) {
			$current = self::getDefault();
		}
		self::setCurrent($current);
	}

	public static function set($currency) {
		return self::setCurrent($currency);
	}

	public static function get() {
		self::init();
		return self::getCurrent();
	}
	
	public static function setMain($currency) {
		self::$_mainCurrency = $currency;
	}

	public static function getMain() {
		if (null === self::$_mainCurrency) {
			self::setMain(self::getDefault());
		}
		return self::$_mainCurrency;
	}
	
	public static function setDefault($currency) {
		self::$_defaultCurrency = $currency;
	}

	public static function getDefault() {
		if (null !== self::$_defaultCurrency) {
			return self::$_defaultCurrency;
		}
		return parent::getDefault();
	}
	
	public static function setCurrent($currency) {
		self::getStorage()->set($currency);
	}

	public static function getCurrent() {
		return self::getStorage()->get();
	}
	
	public static function setLoader($loader) {
		$cls = 'User\Currency\Loader\\' . ucfirst($loader);
		self::$_loader = new $cls();
	}
	
	public static function getLoader() {
		if (!self::$_loader) {
			self::setLoader('cbr');
		}
		return self::$_loader;
	}
	
	public static function setStorage($strage) {
		$cls = 'User\Currency\Storage\\' . ucfirst($strage);
		self::$_storage = new $cls();
	}
	
	public static function getStorage() {
		if (!self::$_storage) {
			self::setStorage('session');
		}
		return self::$_storage;
	}

	public static function getRate($code) {
		if ($code == self::getMain()) {
			return 1;
		}
		return self::getLoader()->getRate($code);
	}

	public static function getRates() {
		$rates = array();
		foreach (self::getValues() as $currency) {
			$rates[$currency] = self::getRate($currency);
		}
		return $rates;
	}
	
	public static function getLabel($currency) {
		return lang(self::getKey($currency), 'currencysymbol');
	}
	
	public static function getLabels() {
		$titles = array();
		foreach (self::asArray() as $key => $value) {
			$titles[$value] = lang($key, 'currencysymbol');
		}
		return $titles;
	}
	
	public static function getTitles() {
		$titles = array();
		foreach (self::asArray() as $key => $value) {
			$titles[$value] = lang($key, 'currencyname');
		}
		return $titles;
	}

	public static function convertPrice($price, $fromCurrency = null, $toCurrency = null) {
		if (null === $toCurrency) {
			$toCurrency = self::get();
		}
		if ($fromCurrency == $toCurrency) {
			return $price;
		} else {
			if ($fromCurrency == self::getMain()) {
				$toRate = self::getRate($toCurrency);
				$price = ($price / $toRate);
			} elseif ($toCurrency == self::getMain()) {
				$fromRate = self::getRate($fromCurrency);
				$price = ($price * $fromRate);
			} else {
				$toRate = self::getRate($toCurrency);
				$fromRate = self::getRate($fromCurrency);
				$price = ($price * $fromRate / $toRate);				
			}
		}
		return $price;
	}

	public static function renderPrice($price, $priceCurrency = null, $convert = false) {
		if ($convert) {
			$price = self::convertPrice($price, $priceCurrency, (true === $convert ? null : $convert));
		}
		return Format::formatNumber($price, Format::PRICE_FORMAT) . ' ' . self::getLabel($priceCurrency);
	}
	
}