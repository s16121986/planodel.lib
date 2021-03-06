<?php
namespace Grid\Column;

class Enum extends AbstractColumn{

	protected $_options = array(
		'icon' => false
	);

	public function formatValue($value, $row = null) {
		return call_user_func_array(array('\\' . $this->enum, 'getLabel'), array($value));
	}

	public function render($value, $row) {
		$class = $this->enum . ' ' . $this->enum . '_' . call_user_func_array(array('\\' . $this->enum, 'getKey'), array($value));
		if ($this->icon) {
			return '<i class="' . $class . '" title="' . parent::render($value, $row) . '"></i>';
		} else {
			return '<span class="' . $class . '">' . parent::render($value, $row) . '</span>';
		}
	}

}