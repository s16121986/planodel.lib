<?php
namespace Form\Element;

class Label extends Xhtml{

	protected $_options = array();

	public function getHtml() {
		return '<span' . $this->attrToString() . '>' . $this->getValue() . '</span>';
	}

}
