<?php
namespace Api\Attribute;

use Api\Exception;

abstract class AbstractAttribute{

	protected static $_defaultParams = array(
		'required' => false,
		'notnull' => true,
		'default' => null,
		'locale' => false,

		'dbfield' => true,

		'filterable' => true,
		'sortable' => true,
		'hidden' => false,

		'changeable' => true,

		'autoPrepare' => true
	);

	protected $_name = '';
	protected $_data = array();
	protected $_params = array();
	protected $_qualifiers = array();
	protected $_validators = array();
	protected $_value = null;

	public function __construct($name, $qualifiers = null) {
		$this->_name = $name;
		$this->_params = array_merge(self::$_defaultParams, $this->_params);
		if ($qualifiers) {
			$this
				->setParams($qualifiers)
				->setQualifiers($qualifiers);
		}
	}

	public function __get($name) {
		if ('name' === $name) {
			return $this->_name;
		}
		if (isset($this->_params[$name])) {
			return $this->_params[$name];
		}
		if (isset($this->_qualifiers[$name])) {
			return $this->_qualifiers[$name];
		}
		if (isset($this->_data[$name])) {
			return $this->_data[$name];
		}
		return null;
	}

	public function __set($name, $value) {
		$this->_data[$name] = $value;
	}

	protected function setParams($params) {
		foreach ($params as $k => $v) {
			if (array_key_exists($k, $this->_params)) {
				$this->_params[$k] = $v;
			}
		}
		return $this;
	}
	
	public function getParams() {
		return $this->_params;
	}

	public function addValidator($validator, $options = null) {
		if (is_string($validator)) {
			$cls = '\\Validator\\' . $validator;
			$validator = new $cls($options);
		}
		$this->_validators[] = $validator;
		return $this;
	}

	public function setQualifiers($qualifiers) {
		foreach ($qualifiers as $k => $v) {
			if (array_key_exists($k, $this->_qualifiers)) {
				$this->_qualifiers[$k] = $v;
			}
		}
		return $this;
	}
	
	public function getQualifiers() {
		return $this->_qualifiers;
	}

	public function getDefault() {
		return $this->default;
	}

	public function checkValue($value) {
		foreach ($this->_validators as $validator) {
			if (!$validator->isValid($value)) {
				return false;
			}
		}
		return true;
	}

	public function prepareValue($value) {
		return $value;
	}

	public function getValue() {
		return $this->_value;
	}

	public function setValue($value) {
		if (!$this->changeable) {
			throw new Exception(Exception::ATTRIBUTE_NOT_CHANGEABLE, $this->name);
		}

		if (null !== $value) {
			if (false === $this->checkValue($value)) {
				return false;
			}

			if ($this->autoPrepare) {
				$value = $this->prepareValue($value);
			}
		}
		if (null === $value && true === $this->notnull) {
			return false;
		}
		$this->_value = $value;
		return true;
	}

	public function setDefault() {
		return $this->setValue($this->getDefault());
	}
	
	public function isEmpty() {
		return (null === $this->_value);
	}

	public function getPresentation() {
		return (string)$this->_value;
	}

	public function getType() {
		return \AttributeType::getValue(str_replace(__NAMESPACE__ . '\Attribute', '', get_class($this)));
	}

	public function __toString() {
		return $this->getPresentation();
	}

}