<?php

class Db{
	
	protected $storage;
	protected $result = null;
	protected $exceptionHandler = null;

	public function authentication() {
		if (null !== $this->result) {
			return $this->result;
		}
		$token = $this->storage->getIdentity();
		$this->result = $this->action->setToken($token);
		if ($this->result->isValid()) {

			//$this->_action->updateSession();
		}
		//$this->_action->initUser();
		return $this->result;
	}
	
	public function login($data) {
		
	}
	
	public function logout() {
		
	}
	
	public function setExceptionHandler($handler) {
		
	}
	
}