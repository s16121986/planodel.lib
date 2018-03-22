<?php
namespace Form\Element;
require_once 'Library/Form/Element/Text.php';

class Number extends Text{

	protected $_options = array(
		'inputType' => 'text',
		'allowZero' => true,
		'fractionDigits' => 0,
		'nonnegative' => false,
		'jsPlugin' => true
	);

	public function checkValue($value) {
		$pv = $this->prepareValue($value);
		if ($pv === null) {
			return true;
		}
		if ($this->nonnegative && $pv < 0) {
			return false;
		}
		if (false === $this->allowZero && $pv == 0) {
			return false;
		}
		return parent::checkValue($pv);
	}

	protected function prepareValue($value) {
		if (self::isNullValue($value)) {
			return null;
		}
		if (is_string($value)) {
			$value = str_replace(' ', '', $value);
			$value = str_replace(',', '.', $value);
		}
		return ($this->fractionDigits ? (float)$value : (int)$value);
	}

	public function isEmpty() {
		return (0 !== $this->_value && empty($this->_value));
	}
	
	private static function isNullValue($value) {
		return ('' === $value || null === $value);
	}

}
