<?php
namespace Form\Element;

class Datetime extends Text{

	protected $_options = array(
		'inputType' => 'text',
		'format' => 'H:i d.m.Y',
		'inputType' => 'text',
		'type' => 'datetime',
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
				$value = date('Y-m-d H:i:s', $t);
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
