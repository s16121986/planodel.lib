<?php
namespace Api\Util;

use Api\Exception;
use Api\EventManager;
use Api\Attribute\AttributeDate;

abstract class BaseApi{
	
	protected $_table = '';
	protected $_attributes = array();
	protected $_adapter = null;
	
	public function __get($name) {
		switch ($name) {
			case 'table':return $this->_table;
		}
	}

	public function table() {
		return $this->_table;
	}

	public function getAttribute($name) {
		return (isset($this->_attributes[$name]) ? $this->_attributes[$name] : null);
	}

	public function getAttributes() {
		return $this->_attributes;
	}

	protected function addAttribute($name, $type = \AttributeType::String, $qualifiers = null) {
		if (!\AttributeType::valueExists($type)) {
			throw new Exception(Exception::UNKNOWN);
		}
		switch ($name) {
			case 'created':
			case 'updated':
				$type = \AttributeType::Date;
				$qualifiers = array(
					//'set' => Auth::getUser()->hasRole(USER_ROLE::SYNC),
					//'update' => Auth::getUser()->hasRole(USER_ROLE::SYNC),
					'changeable' => false,
					'dateFractions' => AttributeDate::DateTime
				);
				break;
		}
		$cls = '\\Api\\Attribute\\Attribute' . ucfirst($type);
		$this->_attributes[$name] = new $cls($name, $qualifiers);
		return $this;
	}

	protected function getAdapter() {
		if (!$this->_adapter) {
			$this->_adapter = new \Api\Adapter\Mysql($this);
		}
		return $this->_adapter;
	}

	protected function getSettings($options) {
		$settings = new Settings($options, $this);
		$this->initSettings($settings);
		EventManager::trigger('initSettings', $this, array($settings));
		$settings->init();
		return $settings;
	}

	protected function initSettings($settings) {}

}