<?php
namespace Api\Util;

class Join{

	const ALL = 0;
	const SELECT = 1;
	const DATA = 2;

	protected $_columns = array();

	protected $_params = array(
		'name' => null,
		'alias' => null,
		'condition' => null,
		'action' => 0
	);

	protected $_options = array();

	public function __construct($name, $condition, $columns = null, $options = null) {
		$this->_columns = new Settings\Columns();
		$this->setName($name)
				->setCondition($condition)
				->columns($columns);
		if (!is_array($options)) {
			$options = array('action' => $options);
		}
		if (isset($options['action'])) {
			$this->action = $options['action'];
		}
		$this->_options = $options;
	}

	public function __set($name, $value) {
		if (method_exists($this, 'set' . $name)) {
			return $this->{'set' . $name}($value);
		}
		if (array_key_exists($name, $this->_params)) {
			$this->_set($name, $value);
		}
	}

	protected function _set($name, $value) {
		$this->_params[$name] = $value;
		return $this;
	}

	public function __get($name) {
		switch ($name) {
			case 'columns':return $this->_columns;
		}
		if (isset($this->_params[$name])) {
			return $this->_params[$name];
		}
		if (isset($this->_options[$name])) {
			return $this->_options[$name];
		}
		return null;
	}

	public function setName($name) {
		if (is_array($name)) {
			$k = array_keys($name);
			$this->setAlias(array_shift($k));
		} elseif (preg_match('/^(.+)\s+as\s+(.+)$/i', $name, $m)) {
			$this->setAlias($m[2]);
		} else {
			$this->setAlias($name);
		}
		$this->_set('name', $name);
		return $this;
	}

	public function setAlias($alias) {
		$this->_set('alias', $alias);
		return $this;
	}

	public function setCondition($condition) {
		$this->_set('condition', $condition);
		return $this;
	}

	public function columns($columns) {
		if (!is_array($columns)) {
			$columns = array($columns);
		}
		foreach ($columns as $col) {
			$this->addColumn($col);
		}
		return $this;
	}

	public function addColumn($column) {
		$column = $this->_columns->add($column);
		$column->table = $this->alias;
		return $this;
	}

}