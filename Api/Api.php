<?php
require 'Api/enums.php';

use Api\Exception;
use Api\Util\TabularSection;
use Api\Attribute\Exception as AttributeException;
use Api\Util\Reference;
use Api\Util\DeleteManager;
use Api\EventManager;
use Api\Attribute\AttributeFile;
use Api\Attribute\AttributeModel;
use Api\Attribute\AttributeNumber;

abstract class Api extends Api\Util\BaseApi{

	const ACTION_PREFIX = 'action';
	const newId = 'new';

	protected static $_plugins = array();

	protected $_id = null;
	protected $tabularSections = array();
	protected $_data = array();
	protected $_record = array();
	protected $_changedAttributes = array();
	protected $_references = null;
	protected $foreignKey = null;
	protected $currentAction;

	public static function run($model, $action, $data = null) {
		try{
			$o = self::factory($model);
			if (isset($data['id']) && !in_array($action, array('select', 'count'))) {
				if ($o->setId($data['id']))
					unset($data['id']);
			}
			$e = $o->__call($action, array($data));
		} catch (Exception $e) {
		}
		return $e;
	}

	public static function factory($name, $id = null) {
		$cls = 'Api\\Model\\' . $name;
		$api = new $cls();
		if ($id) {
			$api->findById($id);
		}
		return $api;
	}

	public static function findId($api, $id) {
		$apiObj = self::factory($api);
		return $apiObj->findById($id);
	}

	public function getTitle() {
		return '';
	}

	public function notify($action, $options = null) {
		/*if (!class_exists('System', false)) {
			include 'Api/System.php';
		}
		System::notify($this, $action, $options);*/
		return $this;
	}

	public function isAllowed($action, $params = null) {
		return true;
		return Api\Acl::isAllowed(strtolower($this->getModelName()), $action);//Api\Acl::isAllowed($this, $action);
	}
	
	protected function addTabularSection($name, $table, $attribute = null, $attributeType = null, $attributeQualifiers = array()) {
		$this->tabularSections[$name] = new TabularSection($this, $table, $attribute, $attributeType, $attributeQualifiers);
		return $this;
	}
	
	public function getTabularSection($name) {
		return (isset($this->tabularSections[$name]) ? $this->tabularSections[$name] : null);
	}

	protected function init() {
		$fields = $this->getAdapter()->getTableColumns($this->_table);
		foreach ($fields as $name => $filed) {
			if (in_array($name, array('id')) || isset($this->_attributes[$name])) {
				continue;
			}
			switch ($name) {
				case 'created':
				case 'updated':
					$this->addAttribute($name);
					continue 2;
			}
			$params = $this->getAdapter()
					->fieldToAttributeParams($filed);
			if ($params) {
				$this->addAttribute($name, $params['type'], $params);
			}
		}
	}

	public function __construct() {
		//$this->initReferences();
		$this->_attributes['id'] = new AttributeNumber('id');
		$this->init();
	}

	public function __call($name, $arguments) {
		$action = self::ACTION_PREFIX . '_' . $name;
		if (method_exists($this, $action)) {
			$this->currentAction = $name;
			return call_user_func_array(array($this, $action), $arguments);
		} else {
			throw new Exception(Exception::ACTION_UNDEFINED, array('action' => $name));
		}
	}

	public function __set($name, $value) {

		$setMethod = 'set' . $name;
		if (method_exists($this, $setMethod)) {
			return $this->$setMethod($value);
		}
		if (isset($this->tabularSections[$name])) {
			$this->tabularSections[$name]->set($value);
			return true;
		}
		if (!array_key_exists($name, $this->_attributes)) {
			//throw new AttributeException(Exception::ATTRIBUTE_UNDEFINED, $name);
			return null;
		}
		/*if (!$this->isNew()) {
			if (!$this->_attributes[$name]->update) {
				throw new AttributeException(Exception::ATTRIBUTE_REWRITE, $name);
				return null;
			}
		}*/
		if (!$this->_attributes[$name]->setValue($value)) {
			throw new AttributeException(Exception::ATTRIBUTE_INVALID, $name);
		}
		if ($this->_attributes[$name] instanceof AttributeFile) {
			$this->_data[$name] = $this->_attributes[$name]->getValue();
			return true;
		}
		$this->_set($name, $this->_attributes[$name]->getValue());
		return true;
	}
	
	public function __get($name) {
		switch (true) {
			case $name === 'table':return $this->_table;
			case array_key_exists($name, $this->_data):return $this->_data[$name];
			case array_key_exists($name, $this->_record):return $this->_record[$name];
			case isset($this->tabularSections[$name]):return $this->tabularSections[$name];
			//case isset($this->_attributes[$name]):return $this->_attributes[$name];
		}
		$getMethod = 'get' . $name;
		if (method_exists($this, $getMethod)) {
			return $this->$getMethod();
		}
		if (($attr = $this->getAttribute($name . '_id') && ($attr instanceof AttributeModel))) {
			return $attr->getModel($attr->getValue());
		}
		return parent::__get($name);
	}

	public function getModelName() {
		return str_replace('Api\\Model\\', '', get_class($this));
	}

	public function isNew() {
		return $this->_id === self::newId;
	}

	public function isEmpty() {
		return (null === $this->_id || $this->isNew());
	}

	public function setId($id) {
		if ($id == self::newId) {
			$this->reset();
			$this->_id = $id;
			return true;
		} elseif ($this->_id === $id) {
			return $this->getData();
		} elseif ($id) {
			return $this->findById($id);
		}
		return false;
	}

	public function getId() {
		return $this->_id;
	}

	public function getData() {
		if (!$this->_data) {
			return null;
		}
		//return $this->_data;
		return array_merge($this->_record, $this->_data, array('id' => $this->_id));
	}

	public function getChangedData() {
		$data = array();
		foreach ($this->_changedAttributes as $k => $v) {
			$data[$k] = $this->_data[$k];
		}
		return $data;
	}

	public function setData($data) {
		if (is_array($data)) {
			if (isset($data['id'])) {
				if (!$this->setId($data['id'])) {
					return $this;
				}
				unset($data['id']);
			}
			foreach ($data as $k => $v) {
				$this->__set($k, $v);
			}
		}
		return $this;
	}

	public function findById($id, $options = null) {
		return $this->findByAttribute('id', (int)$id, $options);
	}

	public function findByAttribute($name, $value, $options = null) {
		if (!is_array($options)) {
			$options = array();
		}
		$options[$name] = $value;
		return $this->findByAttributes($options);
	}
	
	public function findByAttributes($options) {
		$settings = $this->getSettings($options);
		$record = $this->getAdapter()->get($settings);
		if (!$record) {
			//throw new \Api\Exception(Exception::);
			return false;
		}
		$this->reset();
		$this->_id = $record['id'];
		$this->_record = $record;
		$this->_setData($record);
		return true;
	}

	public function reset() {
		$this->_id = null;
		$this->_data = array();
		$this->_record = array();
		$this->_changedAttributes = array();
		foreach ($this->tabularSections as $tabularSection) {
			$tabularSection->reset();
		}
		//$this->getSettings()->reset();
		return $this;
	}

	protected function initReferences() {}

	protected function addReference($name, $options = null) {
		if (null === $this->_references) {
			$this->_references = array();
		}
		$this->_references[] = new Reference($this, $name, $options);
		return $this;
	}

	public function getReferences() {
		if (null === $this->_references) {
			$this->_references = array();
			$this->initReferences();
		}
		return $this->_references;
	}

	public function findLinks() {
		return array();
		$links = array();
		foreach ($this->getReferences() as $reference) {
			$records = $reference->findLinks();
			if (!empty($records)) {
				$links[] = array(
					'ref' => $reference,
					'records' => $records
				);
			}
			unset($records, $reference);
		}
		return $links;
	}

	public function getForeignKey() {
		return $this->foreignKey;
	}

	protected function _set($name, $value) {
		$this->_data[$name] = $value;
		$this->_changedAttributes[$name] = true;
		return $this;
	}
	
	protected function _get($name) {
		return (isset($this->_data[$name]) ? $this->_data[$name] : null);
	}

	protected function _setData($data) {
		foreach ($data as $k => $v) {
			if (isset($this->_attributes[$k])) {
				$attribute = $this->_attributes[$k];
				if (null === $v && false === $attribute->notnull) {
					$this->_data[$k] = null;
				} else {
					$this->_data[$k] = $attribute->prepareValue($v);
				}
			}
		}
		return $this;
	}

	protected function _update() {
		if (!$this->isEmpty() && isset($this->_attributes['updated'])) {
			$this->getAdapter()->query('UPDATE `' . $this->table() . '` SET updated=CURRENT_TIMESTAMP WHERE id=' . $this->id());
		}
		return $this;
	}

	protected function _write() {
		if (isset($this->_attributes['updated']) && !isset($this->_changedAttributes['updated'])) {
			$this->_set('updated', \Dater::serverDatetime());
		}
		$newId = $this->getAdapter()->write();
		if ($newId) {
			if ($this->isNew()) {
				$this->_id = $newId;
				$this->_data['id'] = $newId;
			}
			$this->_changedAttributes = array();
		}
		return $this->id;
	}

	protected function beforeWrite() {}

	protected function afterWrite($isNew = false) {}

	protected function beforeDelete() {}

	protected function afterDelete() {}

	protected function afterAction($action, $options = null) {
		//foreach (self::$_plugins as $plugin) {
		//	$plugin->run($this, $action, $options);
		//}
		return $this;
	}

	protected function action_select($data = null) {
		return $this->getAdapter()->select($this->getSettings($data));
	}

	protected function action_count($data = null) {
		return $this->getAdapter()->count($this->getSettings($data));
	}

	protected function action_write($data = null) {
		if ($data) {
			$this->setData($data);
		}
		if (false === EventManager::trigger('beforeWrite', $this) || false === $this->beforeWrite()) {
			return false;
		}
		if (null === $this->_id) {
			throw new Exception(Exception::ID_EMPTY);
		}
		$new = $this->isNew();
		if ($new && empty($this->_data)) {
			throw new Exception(Exception::DATA_EMPTY);
		}
		if ($new) {
			foreach ($this->_attributes as $k => $attribute) {
				//if ($attribute->type == \Api\Attribute::T_File) continue;
				if (in_array($k, array('created', 'updated', 'id'))) {

				} elseif (!isset($this->_data[$k]) && $attribute->isEmpty()) {
					if ($attribute->required) {
						throw new AttributeException(Exception::ATTRIBUTE_REQUIRED, $k);
					} elseif ($attribute instanceof AttributeFile) {
						continue;
					}
					$this->_set($k, $attribute->getDefault());
				}
			}
			if (isset($this->_attributes['created']) && !isset($this->_changedAttributes['created'])) {
				$this->_set('created', \Dater::serverDatetime());
			}
		}
		if (false !== $this->_write()) {
			//$this->reset();
			foreach ($this->getAttributes() as $attribute) {
				if  ($attribute instanceof AttributeFile) {
					if ($attribute->write()) {
						if ($attribute->fieldName) {
							$this->_set($attribute->name, $attribute->getValue()->id);
						}
					}
				}
			}
			if (!empty($this->_changedAttributes)) {
				$this->_write();
			}
			foreach ($this->tabularSections as $tabularSection) {
				$tabularSection->write();
			}
			$this->afterWrite($new);
			EventManager::trigger('write', $this);
			//$this->afterAction('write', array(self::newId => $new));
			return (isset($this->_attributes['id']) ? $this->id : true);
		}
		return false;
	}

	protected function action_data($data = null) {
		return $this->getData();
	}

	protected function action_delete($data = null) {
		if ($this->isEmpty()) {
			throw new Exception(Exception::ID_EMPTY);
		}
		if (DeleteManager::init($this)) {
			try {
				$e = null;
				$res = DeleteManager::delete();
				//$res = $this->delete();
			} catch (\Exception $e) {
				$res = false;
			}
			if (false === $res) {
				if (!$e) {
					$e = new Exception(Exception::DELETE_ABORTED);
				}
				DeleteManager::rollback();
				throw $e;
			} else {
				DeleteManager::commit();
			}
			return $res;
		}/**/

		if (false === EventManager::trigger('beforeDelete', $this)) {
			return false;
		}
		if (false === $this->beforeDelete()) {
			return false;
		}
		foreach ($this->tabularSections as $tabularSection) {
			$tabularSection->clear()->write();
		}
		if (!$this->getAdapter()->delete()) {
			throw new Exception(Exception::DELETE_ABORTED);
		}
		foreach ($this->getAttributes() as $attribute) {
			if  ($attribute instanceof AttributeFile) {
				DeleteManager::pushFileAttribute($attribute);
			}
		}
		$this->afterDelete();
		EventManager::trigger('delete', $this);
		//$this->afterAction('delete', array('presentation' => $this->getTitle()));
		//$this->reset();
		return true;
	}

}