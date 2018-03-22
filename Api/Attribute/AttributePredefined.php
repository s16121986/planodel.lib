<?php
namespace Api\Attribute;

use Api\Attribute\Exception;

class AttributePredefined extends AbstractAttribute{

	protected $_qualifiers = array(
		'value' => null
	);

	public function prepareValue($value) {
		return $this->value;
	}

	public function getDefault() {
		return $this->value;
	}

	public function setValue($value) {
		throw new Exception(Exception::ATTRIBUTE_PREDEFINED, $this->name);
	}

}