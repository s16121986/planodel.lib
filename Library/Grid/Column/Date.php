<?php
namespace Grid\Column;

class Date extends AbstractColumn{

	protected $_options = array(
		'format' => 'd.m.Y'
	);

	public function formatValue($value, $row = null) {
		$t = strtotime($value);
		if ($t > 0) {
			$d = \Dater::format($t, $this->format);
		} else {
			$d = '';
		}
		return parent::formatValue($d, $row);
	}

}