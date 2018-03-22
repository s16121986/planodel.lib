<?php
namespace Api\Util;

abstract class Plugin{

	protected $_options = array();

	public function __get($name) {
		return (isset($this->_options[$name]) ? $this->_options[$name] : null);
	}

	public function __construct($options) {
		if ($options) {
			$this->_options = $options;
		}
	}

	abstract function run($api, $action, $options);

}