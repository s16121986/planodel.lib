<?php
namespace Api\Util;

use Api;
use Db;
use Api\Attribute\AttributeFile;

class TabularSection extends BaseApi{
	
	protected $parent;
	protected $parentIndex;
	protected $uniqueIndex = array();
	protected $qualifiers = array();
	public $data = array();
	public $dataDelete = array();
	
	private static function normalizeSetData($data) {
		if (is_string($data)) {
			$data = explode(',', $data);
		} elseif (!is_array($data)) {
			$data = array($data);
		}
		return $data;
	}
	
	public function __construct(Api $parent, $table, $attribute = null, $attributeType = null, $attributeQualifiers = array()) {
		$this->parent = $parent;
		$this->parentIndex = $this->parent->getForeignKey();
		$this->_table = $table;
		$this->addAttribute($this->parentIndex, 'model', array('model' => $this->parent->getModelName()));
		if ($attribute) {
			switch ($attribute) {
				case 'index':
					$this->addIndexAttribute();
					break;
				default:
					$this->addAttribute($attribute, $attributeType, array_merge(array('required' => true), $attributeQualifiers));
					$this->uniqueIndex[] = $attribute;
			}
		}
	}
	
	public function addIndexAttribute() {
		$this->uniqueIndex[] = 'index';
		return $this->addAttribute('index', 'number', array());
	}
	
	public function setUniqueIndex() {
		$this->uniqueIndex = func_get_args();
		return $this;
	}
	
	protected function initSettings($settings) {
		$settings->filter($this->_table . '.' . $this->parentIndex . '=' . $this->parent->id);
		if ($this->getAttribute('index')) {
			$settings->order->setDefault('index');
		}
	}
	
	public function findData($data) {
		if ($this->parent->isEmpty()) return false;
		$q = Db::from($this->_table)
			->where($this->parentIndex . '=' . $this->parent->id);
		unset($data[$this->parentIndex]);
		foreach ($this->uniqueIndex as $index) {
			if ($data[$index] === null) {
				$q->where('`' . $index . '` IS NULL');
			} else {
				$q->where('`' . $index . '`=?', $data[$index]);
			}
		}
		return (bool)$q->query()->fetchRow();
	}
	
	public function set($data) {
		$this->clear();
		foreach (self::normalizeSetData($data) as $row) {
			$this->add($row);
		}
		return $this;
	}
	
	public function add($data) {
		if (($rowData = $this->prepareData($data))) {
			for ($i = count($this->dataDelete) - 1; $i >= 0 ;$i--) {
				foreach ($rowData as $k => $v) {
					if ($v != $this->dataDelete[$i][$k]) {
						continue 2;
					}
				}
				array_splice($this->dataDelete, $i, 1);
			}
			if (!$this->findData($rowData)) {
				$this->data[] = $rowData;
			}
		}
		return $this;
	}
	
	public function get($index) {
		return (isset($this->data[$index]) ? $this->data[$index] : null);
	}
	
	public function delete($data) {
		if (($rowData = $this->prepareData($data, false))) {
			$this->dataDelete[] = $rowData;
		}
		return $this;
	}
	
	public function clear() {
		$this->data = array();
		$this->dataDelete = $this->select();
		return $this;
	}
	
	public function getData() {
		return $this->data;
	}
	
	public function select($data = null) {
		return ($this->parent->isEmpty() ? $this->data : $this->getAdapter()->select($this->getSettings($data)));
	}
	
	public function selectColumn($name, $options = null) {
		if (!is_array($options)) {
			$options = array();
		}
		$options['fields'] = array($name);
		$result = array();
		foreach ($this->select($options) as $r) {
			$result[] = $r[$name];
		}
		return $result;
	}
	
	public function count($data = null) {
		return ($this->parent->isEmpty() ? count($this->data) : $this->getAdapter()->count($this->getSettings($data)));
	}
	
	public function write() {
		if ($this->parent->isEmpty()) {
			return false;
		}
		
		if ($this->dataDelete) {
			$delete = $this->dataDelete;
			sort($delete);
			$count = count($delete);
			for ($i = $count - 1;$i >= 0;$i--) {
				$this->_delete($delete[$i]);
			}
			$this->dataDelete = array();
		}
		
		$insertData = array();
		foreach ($this->data as $row) {
			$row[$this->parentIndex] = $this->parent->id;
			$uid = array();
			$uq = Db::from($this->table)
				->where($this->parentIndex . '=?', $this->parent->id);
			foreach ($this->uniqueIndex as $index) {
				$uid[] = (string)$row[$index];
				if ($row[$index] === null) {
					$uq->where('`' . $index . '` IS NULL');
				} else {
					$uq->where('`' . $index . '`=?', $row[$index]);
				}
			}
			if ($uq->query()->fetchRow()) {
				continue;
			}
			$insertData[implode('_', $uid)] = $row;
		}
		if ($insertData) {
			Db::writeArray($this->table, array_values($insertData));
		}
		return true;
	}
	
	public function reset() {
		$this->data = array();
		$this->dataDelete = array();
		return $this;
	}
	
	private function _delete($data) {
		$data[$this->parentIndex] = $this->parent->id;
		return Db::delete($this->_table, $data);
	}
	
	private function prepareData($data, $fillDefault = true) {
		if (!is_array($data)) {
			$attributes = $this->getAttributes();
			$attributes = array_values($attributes);
			$data = array($attributes[1]->name => $data);
		}
		$rowData = array();
		foreach ($data as $name => $value) {
			if (in_array($name, array('index', $this->parentIndex)) || !($attribute = $this->getAttribute($name))) {
				continue;
			}
			if (!$attribute->setValue($value)) {
				return false;
			}
			$rowData[$name] = $attribute->getValue();
		}
		if ($fillDefault) {
			foreach ($this->getAttributes() as $attribute) {
				if (!array_key_exists($attribute->name, $rowData)) {
					if ($attribute->required) {
						return false;
					} elseif ($attribute instanceof AttributeFile) {
						continue;
					}
					$rowData[$attribute->name] = $attribute->getDefault();
				}
			}
		} else {
			foreach ($this->uniqueIndex as $name) {
				if (!array_key_exists($attribute->name, $rowData)) {
					return false;
				}
			}
		}
		$rowData[$this->parentIndex] = $this->parent->id;
		return $rowData;
	}
	
}