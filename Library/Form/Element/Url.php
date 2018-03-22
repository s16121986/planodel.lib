<?php
namespace Form\Element;
require_once 'Library/Form/Element/Text.php';

class Url extends Text{

	protected $_options = array(
		'inputType' => 'url'
	);

	public function checkValue($value) {
		return '' === $value || (bool)filter_var($value, FILTER_VALIDATE_URL);
	}


}
