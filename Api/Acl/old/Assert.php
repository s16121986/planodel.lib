<?php
namespace Api\Acl;

class Assert{

	protected $_object = null;

	protected $_rules = array();

	public function __construct(\Api $object) {
		$this->_object = $object;
	}

	public function  __get($name) {
		return $this->_object->$name;
	}

	public function isAllowed($action = null) {
		//if ($action == 'select') return true;
		//if ($this->_object->id() === 0) return false;
		//if ($this->_object->isNew() && !in_array($action, array('write'))) return false;
		$fn = 'assert_' . $action;
		if (method_exists($this, $fn)) {
			return $this->$fn();
		}
		return $this->assert($action);
	}

	protected function assert($action = null) {
		return true;
	}


}
?>
