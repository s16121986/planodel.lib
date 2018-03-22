<?php
namespace Stdlib\Order;

class Item{
	
	protected $name;
	protected $dataIndex;
	protected $direction;
	
	public function __construct($name, $direction = \SortDirection::ASC) {
		$this->setName($name)
			->setDataIndex($this->name)
			->setDirection($direction);
	}

	public function __set($name, $value) {
		switch ($name) {
			case 'orderby':
			case 'name':
				$this->setName($value);
				break;
			case 'data':
			case 'dataIndex':
				$this->setDataIndex($value);
				break;
			case 'direction':
			case 'sortorder':
				$this->setDirection($value);
				break;
		}
	}
	
	public function __get($name) {
		if (isset($this->$name)) {
			return $this->name;
		}
		return null;
	}

	public function setName($name) {
		if (preg_match('/(.*\W)(asc|desc)\b/si', $name, $matches)) {
			$name = $matches[1];
			$this->setDirection($matches[2]);
		}
		$this->name = $name;
		return $this;
	}
	
	public function setDataIndex($dataIndex) {
		$this->dataIndex = $dataIndex;
		return $this;
	}

	public function setDirection($direction) {
		if (\SortDirection::valueExists($direction)) {
			$this->direction = $direction;
		} else {
			if (\SortDirection::keyExists($direction)) {
				$this->direction = \SortDirection::getValue($direction);
			}
		}
		return $this;
	}
	
}