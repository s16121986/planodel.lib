<?php
namespace Form\Element;
require_once 'Library/Form/Element/Text.php';

class Password extends Text{

	protected $_options = array(
		'inputType' => 'password'
	);

	public function checkValue($value) {
		return !empty($value);
	}

}
