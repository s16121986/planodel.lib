<?php
namespace User\Currency\Storage;

class Session extends AbstractStorage{
	
	const SESSION_INDEX = 'currency';
	
	public function set($currency) {
		$_SESSION[self::SESSION_INDEX] = $currency;
		return parent::set($currency);
	}
	
	public function get() {
		return (isset($_SESSION[self::SESSION_INDEX]) ? $_SESSION[self::SESSION_INDEX] : parent::get());
	}
	
}
