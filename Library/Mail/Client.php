<?php
class Client{
	
	private static $defaults = array();
	
	private $config;
	private $template;
	private $to = array();
	
	public static function factory() {
		return new self();
	}
	
	public static function setDefaults() {
		
	}
	
	public function __construct() {
		$this->config = self::$defaults;
	}
	
	public function __get($name) {
		return (isset($this->config[$name]) ? $this->config[$name] : null);
	}
	
	public function setTemplate($template) {
		$this->getTemplate()->setName($template);
		return $this;
	}
	
	public function getTemplate() {
		if (null === $this->template) {
			$this->template = new Template($this->config['template']);
		}
		return $this->template;
	}

	public function addAddress($address, $name = '', $data = null) {
		if (is_string($address)) {
			$address = explode(';', $address);
		}
		foreach ($address as $addr) {
			$address = new Address(trim($addr), $name, $data);
			if (!$address->isValid()) {
				continue;
			}
			$this->to[] = $address;
		}
		return $this;
	}

	public function send() {
		if (!$this->_template || $this->_template->isEmpty()) {
			return false;
		}
		$sender = $this->getSender();
		$sender->SingleTo = true;
		foreach ($this->_to as $address) {
			$mail = new Mail();
			$this->_send($sender, $address);
		}
		return true;
	}
	
	private function _send($sender, $address) {
		if (!$address->address) {
			return false;
		}
		$template = $this->getTemplate();
		$layout = $this->getLayout();
		if ($layout && !$layout->isEmpty()) {
			$layout->template = $template;
		} else {
			$layout = $template;
		}
		$data = array_merge($this->_options, $address->data);
		$sender->AddAddress($address->address, $address->name);
		$sender->Subject = $template->getSubject($data);
		$sender->MsgHTML($layout->getBody($data));
		if (!$sender->Send()) {
			return false;
		}
		$sender->ClearAddresses();
	}
	
	public function reset() {
		$this->to = array();
		return $this;
	}
	
}