<?php
namespace Auth\Storage;

use Auth\Util;

class Headers extends AbstractStorage{
	
	protected $authParams;
	
	public function getAuthParams() {
		return $this->authParams;
	}
	
	public function getIdentity() {
		if ($this->hasIdentity()) {
			return parent::getIdentity();
		}
		$authHeader = Util::getHeader('Authorization');
		if (empty($authHeader)) {
			return null;
		}
		$this->authParams = Util::split_header($authHeader);
		unset($authHeader);
		if (empty($this->authParams) || !isset($this->authParams['authCode'])) {
			return null;
		}
		$authCode = $this->authParams['authCode'];
		return $authCode;
	}
	
}
