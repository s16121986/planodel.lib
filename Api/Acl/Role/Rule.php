<?php
namespace Api\Acl\Role;

class Rule{
	
	protected $model = null;
	protected $action = null;
	protected $flag = false;
	
	public function __construct($model, $action, $flag) {
		$this->model = $model;
		$this->action = $action;
		$this->flag = (bool)$flag;
	}
	
	public function __get($name) {
		return (isset($this->$name) ? $this->$name : null);
	}
	
}