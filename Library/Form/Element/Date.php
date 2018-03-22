<?php
namespace Form\Element;

class Date extends Text{

	protected $_options = array(
		'maxValue' => null,
		'minValue' => null,
		'format' => 'd.m.Y',
		'inputType' => 'text',
		'emptyValue' => false,
		'jsPlugin' => true
	);

	protected function prepareValue($value) {
		if ($value) {
			if (is_numeric($value)) {
				$t = $value;
			} else {
				$t = strtotime($value);
			}
			if ($t > 0) {
				$value = date('Y-m-d', $t);
				if ($this->maxValue && ($value > $this->maxValue)) {
					return null;
				}
				if ($this->minValue && ($value < $this->minValue)) {
					return null;
				}
				return $value;
			}
		} elseif (false !== $this->emptyValue && $this->emptyValue === $value) {
			return $value;
		}
		return null;
	}

	public function getHtml() {
		$d = '';
		if ($this->getValue()) {
			$t = strtotime($this->prepareValue($this->getValue()));
			if ($t > 0) {
				$d = date($this->format, $t);
			}
		}
		$s = '<input type="' . $this->inputType . '"' . $this->attrToString() . ' value="' . $d . '" />';
		return $s;
	}

}
