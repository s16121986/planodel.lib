<?php
namespace Form\Element;
require_once 'Library/Form/Element/Text.php';

class Email extends Text{

	protected $_options = array(
		'inputType' => 'email'
	);

	public function checkValue($value) {
		return (bool)filter_var($value, FILTER_VALIDATE_EMAIL);
	}


}
