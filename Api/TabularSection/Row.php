<?php
namespace Api\TabularSection;

use Db;
use Api\TabularSection;
use Api\Attribute\AttributeFile;
use Api\Attribute\Exception as AttributeException;
use Exception;

class Row{
	
	protected $tabularSection;
	protected $data = array();
	
	public function __construct(TabularSection $tabularSection, $data = null) {
		$this->tabularSection = $tabularSection;
		if ($data) {
			if (!is_array($data)) {
				$attributes = $this->getAttributes();
				$attributes = array_values($attributes);
				$data = array($attributes[1]->name => $data);
			}
			foreach ($data as $k => $v) {
				$this->set($k, $v);
			}
		}
	}
	
	public function __get($name) {
		if (isset($this->data[$name])) {
			return $this->data[$name];
		}
		return $this->tabularSection->$name;
	}
	
	public function __set($name, $value) {
		return $this->set($name, $value);
	}
	
	public function set($name, $value) {
		$attribute = $this->getAttribute($name);
		if (!$attribute) return null;
		if (!$attribute->setValue($value)) {
			throw new AttributeException(Exception::ATTRIBUTE_INVALID, $name);
		}
		$this->data[$name] = $attribute->getValue();
		return true;
	}
	
	public function isEmpty() {
		return empty($this->data);
	}
	
	public function isNew() {
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
	
	public function getAttributes() {
		return $this->tabularSection->getAttributes();
	}
	
	public function getAttribute($name) {
		return $this->tabularSection->getAttribute($name);
	}
	
	public function getGuid() {
		if ($this->isEmpty()) {
			return null;
		}
		return implode('_', $this->getUniqueData());
	}
	
	public function getData() {
		return $this->data;
	}
	
	public function getUniqueData() {
		if ($this->isEmpty()) {
			return null;
		}
		$guid = array(
			$this->parentIndex => $this->parent->id
		);
		foreach ($this->uniqueIndex as $name) {
			$guid[$name] = $this->$name;
		}
		return $guid;
	}
	
	public function write() {
		if ($this->isEmpty()) return false;
		$data = array();
		foreach ($this->getAttributes() as $attribute) {
			
		}
		if ($this->isNew()) {
			return Db::insert($this->tabularSection->table, $data);
		} else {
			return Db::update($this->tabularSection->table, $data, $this->getUniqueData());
		}
	}
	
	public function delete() {
		if ($this->isEmpty() || $this->isNew()) return false;
		return Db::delete($this->tabularSection->table, $this->getUniqueData());
	}
	
	protected function prepare($data, $fillDefault = true) {
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