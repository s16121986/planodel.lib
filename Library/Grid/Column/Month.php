<?php
namespace Grid\Column;

class Month extends AbstractColumn{

	protected $_options = array(
		'icon' => false
	);

	public function formatValue($value, $row = null) {
		return \Dater::getLocale()->getMonth($value - 1);
	}

}