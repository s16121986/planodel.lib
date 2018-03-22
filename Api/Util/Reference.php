<?php
namespace Api\Util;

use Api;

class Reference{

	const API = 'model';
	const MODEL = 'model';
	const TABLE = 'table';
	
	const RESTRICT = 'RESTRICT';
	const CASCADE = 'CASCADE';
	const SETNULL = 'SETNULL';
	const NOACTION = 'NOACTION';

	protected $_api = null;
	protected $_options = array(
		'name' => '',
		'referenceType' => self::API,
		'foreignKey' => '',
		'onDelete' => 'CASCADE',
		'onDeleteCascade' => true
	);

	public function __construct($api, $name, $options = null) {
		$this->_api = $api;
		$this->_options['name'] = $name;
		if (is_int($options)) {
			$options = array(
				'referenceType' => $options
			);
		}
		if (!is_array($options)) {
			$options = array();
		}
		if (!isset($options['foreignKey'])) {
			$options['foreignKey'] = $api->getForeignKey();
		}
		if (empty($options['foreignKey'])) {
			throw new \Exception('foreignKey undefined');
		}
		if (isset($options['onDelete'])) {
			$options['onDelete'] = strtoupper($options['onDelete']);
		} elseif (isset($options['onDeleteCascade'])) {
			$options['onDelete'] = ($options['onDeleteCascade'] ? self::CASCADE : self::RESTRICT);
		}
		foreach ($options as $k => $v) {
			if (isset($this->_options[$k])) {
				$this->_options[$k] = $v;
			}
		}
	}

	public function __get($name) {
		switch ($name) {
			case 'api':return $this->_api;
			case 'type':return $this->referenceType;
		}
		if (isset($this->_options[$name])) {
			return $this->_options[$name];
		}
		return null;
	}
	
	public function getModel() {
		if ($this->referenceType === self::MODEL) {
			return Api::factory($this->name);
		}
	}

}