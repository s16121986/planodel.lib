<?php
namespace User\Currency\Storage;

abstract class AbstractStorage{
	
	protected $_currency = null;
	
	public function set($currency) {
		$this->_currency = $currency;
		return $this;
	}
	
	public function get() {
		return $this->_currency;
	}
	
}