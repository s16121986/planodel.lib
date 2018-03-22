<?php
namespace Api\Attribute;

class AttributeBoolean extends AbstractAttribute{

	protected $_qualifiers = array();

	public function prepareValue($value) {
		return (bool)$value;
	}

	public function getPresentation() {
		return \Format::formatBoolean($this->_value, '');
	}

}