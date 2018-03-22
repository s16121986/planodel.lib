<?php
namespace Api\Util;

use Api\Util\BaseApi;
use Api\Util\Settings\Collection\Filter as FilterItem;

class Settings{

	protected $_api;
	protected $_columns;
	protected $_order;
	protected $_limit;
	protected $_group;
	protected $_filter;
	protected $_quicksearch;
	protected $_joins = array();
	protected $_params = array();

	public function __construct($params, BaseApi $api = null) {
		$this->_api = $api;
		$this->_filter = new Settings\Filter();
		$this->_columns = new Settings\Columns();
		$this->_order = new Settings\Order();
		$this->_limit = new Settings\Limit();
		$this->_group = new Settings\Collection('Group');
		$this->_quicksearch = new Settings\Quicksearch();
		if ($params && is_array($params)) {
			$this->_params = $params;
		}
		//$this->init($params);
	}

	public function init() {
		$params = $this->_params;
		foreach ($params as $k => $value) {
			if ($this->_setParam($k, $value)) {
				unset($params[$k]);
			}
		}
		if ($this->_columns->isEmpty()) {
			$this->columns('*');
		}
		if ($this->_api) {
			foreach ($this->_api->getAttributes() as $attribute) {
				if ($attribute->filterable) {
					if (array_key_exists($attribute->name, $params)) {
						$filter = new FilterItem($params[$attribute->name]);
						$filter->setAttribute($attribute);
						$filter->table = $this->_api->table();
						$this->_filter->add($filter);
						unset($params[$attribute->name]);
					}
				}
				if ($attribute->getType() == \AttributeType::Model && $attribute->joins) {
					$joinTable = $attribute->getModel()->table();
					foreach ($attribute->joins as $join) {
						$this->join($joinTable, 
							'`' . $this->_api->table() . '`.`' . $attribute->name . '`=`' . $joinTable . '`.`id`', 
							$join[0], 
							$join[1]);
					}
				}
			}
		}
		return $this;
	}

	public function __get($name) {
		if (isset($this->{'_' . $name})) {
			return $this->{'_' . $name};
		}
		if (isset($this->_params[$name])) {
			return $this->_params[$name];
		}
		return null;
	}
	
	public function __set($name, $value) {
		$this->setParam($name, $value);
	}
	
	public function enableQuickSearch() {
		$columns = func_get_args();
		$this->_quicksearch
			->enable()
			->setColumns($columns);
		return $this;
	}

	public function hasParam($name) {
		return array_key_exists($name, $this->_params);
	}

	public function setParam($name, $value) {
		if (!$this->_setParam($name, $value)) {
			$this->_params[$name] = $value;
		}
		return $this;
	}

	public function getParam($name) {
		return ($this->hasParam($name) ? $this->_params[$name] : null);
	}
	
	public function removeParam($name) {
		unset($this->_params[$name]);
		return $this;
	}

	public function getParams() {
		return $this->_params;
	}

	public function columns($columns) {
		$this->_columns->setNames($columns);
		return $this;
	}

	public function order($name, $direction = null) {
		if (!is_array($name)) {
			$name = array($name);
		}
		foreach ($name as $item) {
			$this->_order->add($item, $direction);
		}
		return $this;
	}

	public function group($group) {
		$this->_group->setNames($group);
		return $this;
	}

	public function join($name, $condition, $columns = null, $options = null) {
		if ($this->_api && is_array($condition)) {
			$condition = '`' . $name . '`.`' . $condition[0] . '`=`' . $this->_api->table() . '`.`' . $condition[1] . '`';
		}
		$join = new Settings\Join($name, $condition, $columns, $options);
		$this->_joins[$join->alias] = $join;
		return $this;
	}
	
	public function joinLeft($name, $condition, $columns = null, $options = null) {
		return $this->join($name, $condition, $columns, $options);
	}
	
	public function joinInner($name, $condition, $columns = null, $options = null) {
		if (!is_array($options)) {
			$options = array();
		}
		$options['type'] = 'inner';
		return $this->join($name, $condition, $columns, $options);
	}

	public function limit($step, $start = 0) {
		$this->_limit->set($step, $start);
		return $this;
	}

	public function filter($filter, $name = null, $table = null) {
		$this->_filter->add($filter, $name, $table);
		return $this;
	}

	public function filterIf($param, $filter, $name = null, $table = null) {
		if ($this->hasParam($param)) {
			$this->filter($filter, $name, $table);
		}
		return $this;
	}

	public function reset() {
		$this->_filter->reset();
		$this->_columns->reset();
		$this->_group->reset();
		$this->_limit->reset();
		$this->_order->reset();
		$this->_params = array();
		return $this;
	}
	
	private function _setParam($name, $value) {
		if (empty($value) || $this->_api && $this->_api->getAttribute($name)) {
			return false;
		}
		switch ($name) {
			case 'fields':
			case 'columns':
				$this->columns($value);
				break;
			case 'order':
			case 'orderby':
				$this->order($value);
				break;
			case 'sortorder':
				if (($item = $this->_order->last())) {
					$item->setDirection($value);
				}
				break;
			case 'limit':$this->limit($value);break;
			case 'step':
			case 'max-results':$this->_limit->setStep($value);break;
			case 'start':
			case 'offset':
			case 'start-index':$this->_limit->setStart($value);break;
			case 'group':
			case 'groupby':
				$this->group($value);
				break;
			case $this->_quicksearch->getParamName():
				$this->_quicksearch->setValue($value);
				break;
			default:
				return false;
		}
		return true;
	}

}