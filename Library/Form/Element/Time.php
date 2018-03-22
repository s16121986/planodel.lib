<?php
namespace Form\Element;
require_once 'Library/Form/Element/Text.php';

class Time extends Text{

	protected $_options = array(
		'inputType' => 'text',
		'placeholder' => '00:00',
		'valueFormat' => 'string',
		'clockFormat' => true,
		'jsPlugin' => true
	);

	public function checkValue($value) {
		return ('' === $value || is_int($value) || preg_match('/^\d+:\d{2}$/', $value));
	}

	protected function prepareValue($value) {
		switch ($this->valueFormat) {
			case 'minutes':
				return self::timeToInt($value);
			default:
				$value = self::timeToString($value);
				if ($value && preg_match('/^\d+:\d{2}$/', $value)) {
					return $value;
				}
		}
		return null;
	}

	public function isEmpty() {
		return ('00:00' === $this->_value || empty($this->_value));
	}

	public function getHtml() {
		return '<input type="' . $this->inputType . '"' . $this->attrToString() . ' value="' . self::timeToString($this->getValue()) . '" />';
	}
	
	
	private static function timeToInt($time) {
		if (is_string($time)) {
			$p = explode(':', $time);
			if (isset($p[1])) {
				return $p[0] * 60 + (int)$p[1];
			} else {
				return (int)$p[0];
			}
		} else {
			return (int)$time;
		}
	}
	
	private static function timeToString($time) {
		if (is_int($time)) {
			$h = floor($time / 60);
			$i = $time % 60;
			return str_pad($h, 2, '0', STR_PAD_LEFT) . ':' . str_pad($i, 2, '0', STR_PAD_LEFT);
		} else {
			return (string)$time;
		}
	}

}
