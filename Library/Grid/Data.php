<?php
namespace Grid;

use Api;

class Data{
	
	protected $api;
	protected $data = null;
	protected $paginator;
	protected $params = array();
	protected $initialized = false;
	
	public function __construct($data = null) {
		$this->set($data);
	}
	
	public function __get($name) {
		$params = $this->getParams();
		return (isset($params[$name]) ? $params[$name] : null);
	}
	
	public function __set($name, $value) {
		$this->params[$name] = $value;
	}
	
	public function setPaginator($paginator) {
		$this->paginator = $paginator;
		return $this;
	}
	
	public function getPaginator() {
		return $this->paginator;
	}
	
	public function setParams($params) {
		foreach ($params as $k => $v) {
			$this->params[$k] = $v;
		}
		return $this;
	}
	
	public function getParams() {
		$params = $this->params;
		$q = $_GET;
		foreach (array('orderby', 'sortorder') as $k) {
			if (isset($q[$k]) && $q[$k]) {
				$params[$k] = $q[$k];
			}			
		}
		return $params;
		//return $this->params;
	}
	
	public function set($data) {
		if ($data instanceof Api) {
			$this->api = $data;
		} elseif (is_array($data)) {
			$this->data = $data;
		}
		return $this;
	}
	
	public function get() {
		if (null === $this->data) {
			$this->load();
		}
		return $this->data;
	}
	
	public function load($params = array()) {
		if ($this->paginator) {
			$this->paginator->setCount($this->count());
			$params['start-index'] = $this->paginator->getStartIndex();
			$params['max-results'] = $this->paginator->step;
		}
		$this->set($this->api->select(array_merge($this->getParams(), $params)));
		return $this->data;
	}
	
	public function count() {
		return $this->api->count($this->getParams());
	}
	
	public function order($name, $direction = 'asc') {
		return $this->setParams(array(
			'orderby' => $name,
			'sortorder' => $direction
		));
	}
	
	public function isEmpty() {
		$this->get();
		return empty($this->data);
	}
	
}