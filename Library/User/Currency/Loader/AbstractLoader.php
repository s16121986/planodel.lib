<?php
namespace User\Currency\Loader;

abstract class AbstractLoader{

	protected $_rates = array();
	
	public function setRate($currency, $rate) {
		$this->_rates[$currency] = $rate;
		return $this;
	}

	public function getRate($currency) {
		$rates = $this->getRates();
		return (isset($rates[$currency]) ? $rates[$currency] : false);
	}
	
	public function setRates($rates){
		foreach ($rates as $currency => $rate) {
			$this->setRate($currency, $rate);
		}
		return $this;
	}

	public function getRates() {
		return $this->_rates;
	}
	
}