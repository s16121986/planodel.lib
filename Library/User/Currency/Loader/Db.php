<?php
namespace User\Currency\Loader;

use Db;

class Db extends AbstractLoader{

	public function getRate($currency) {
		$rate = parent::getRate($currency);
		if (false === $rate) {
			$rate = Db::from('currency_rates', array('rate'))
					->where('code=?', $currency)
					->order('date desc')
					->limit(1)
					->query()->fetchColumn();
			if (false === $rate) {
				$rate = 0;
			}
			$this->setRate($currency, $rate);
		}
		return $rate;
	}
	
}
