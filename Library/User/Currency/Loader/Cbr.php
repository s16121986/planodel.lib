<?php
namespace User\Currency\Loader;

use Db;
use Dater;
use SoapClient;
use SimpleXMLElement;

class Cbr extends AbstractLoader{
	
	private $_xml;
	
	private function initXml($date) {
		if (null === $this->_xml) {
			try {
				$client = new SoapClient("http://www.cbr.ru/DailyInfoWebServ/DailyInfo.asmx?WSDL");
				$curs = $client->GetCursOnDate(array("On_date" => $date));
				$this->_xml = $curs->GetCursOnDateResult->any;
				return new SimpleXMLElement($this->_xml);
			} catch (Exception $ex) {
				$this->_xml = false;
				return false;
			}
			return true;
		} elseif ($this->_xml) {
			return new SimpleXMLElement($this->_xml);
		}
		return false;
	}

	public function getRate($currency) {
		$rate = parent::getRate($currency);
		if (false === $rate) {
			$date = Dater::serverDate();
			$rate = Db::from('currency_rates', array('rate'))
					->where('date=?', $date)
					->where('code=?', $currency)
					->query()->fetchColumn();
			if (false === $rate) {
				if (false === ($xml = $this->initXml($date))) {
					return 0;
				}
				$code1 = (int)$currency;
				if ($code1 != 0) {
					$result = $xml->xpath('ValuteData/ValuteCursOnDate/Vcode[.=' . $currency . ']/parent::*');
				} else {
					$result = $xml->xpath('ValuteData/ValuteCursOnDate/VchCode[.="' . $currency . '"]/parent::*');
				}
				if (!$result) {
					$rate = 0;
				} else {
					$vc = (float) $result[0]->Vcurs;
					$vn = (int) $result[0]->Vnom;
					$rate = ($vc / $vn);
					Db::query('INSERT INTO currency_rates (`date`,code,rate) VALUES ("' . $date . '","' . $currency . '",' . str_replace(',', '.', $rate) . ')');
				}
			}
			$this->setRate($currency, $rate);
		}
		return $rate;
	}
	
}
