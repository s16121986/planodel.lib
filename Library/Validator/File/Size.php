<?php
namespace Validator\File;

use Validator\AbstractValidator;

class Size extends AbstractValidator{

	public function isValid($value) {
		if ($value instanceof \File) {
			if ($value->getSize() > self::toBytes($this->max)) {
				return false;
			}
			return true;
		}
		return false;
	}
	
	private static function toBytes($value) {
		if (is_numeric($value)) {
			return (int)$value;
		}
		$units = array('bytes', 'kb', 'mb', 'gb');
		if (preg_match('/^(\d+)\s*(' . implode('|', $units) . ')/', $value, $matches)) {
			$i = array_search(trim($matches[2]), $units);
			return (float)$matches[1] * pow(1024, $i);
		}
		return 0;
	}

}