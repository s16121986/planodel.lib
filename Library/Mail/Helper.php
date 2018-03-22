<?php

namespace Mail;

class Helper {

	protected $_sender;
	protected $_to = array();
	private $_options = array();
	private $_layout = null;
	private $_template = null;

	public function __construct($options = null) {
		$this->getSender();
		$this->initDefaultData();
		if (is_string($options)) {
			$options = array(
				'template' => $options
			);
		} elseif (!is_array($options)) {
			$options = array();
		}
		if (isset(\Cfg::$mail)) {
			$options = array_merge(\Cfg::$mail, $options);
		}
		$this->setOptions($options);
	}

	public function __get($name) {
		if (property_exists($this->getSender(), $name)) {
			return $this->getSender()->$name;
		} elseif (array_key_exists($name, $this->_options)) {
			return $this->_options[$name];
		}
		return null;
	}

	public function __set($name, $value) {
		switch ($name) {
			case 'template':return $this->setTemplate($value);
			case 'layout':return $this->setLayout($value);
		}
		if (property_exists($this->getSender(), $name)) {
			$this->getSender()->$name = $value;
		} else {
			$this->_options[$name] = $value;
		}
	}

	public function __call($name, $arguments) {
		return call_user_func_array(array($this->_sender, $name), $arguments);
	}
	
	public function initDefaultData() {
		$this->host = self::getHttpHost();
		$this->site_url = self::getScheme() . '://' . $this->host;
		//$this->setLayout('layout');
	}

	public function getSender() {
		if (!$this->_sender) {
			//include_once 'Library/Mailer/Sender.php';
			$this->_sender = new Sender();
		}
		return $this->_sender;
	}

	public function setOptions($config) {
		foreach ($config as $k => $v) {
			$this->$k = $v;
		}
		return $this;
	}

	public function setLayout($layout) {
		if (!$layout) {
			$this->_layout = null;
		} elseif ($layout instanceof Helper\Template) {
			$this->_layout = $layout;
		} else {
			$this->_layout = new Helper\Template($layout);
		}
		return $this;
	}
	
	public function getLayout() {
		return $this->_layout;
	}

	public function setTemplate($subject, $body = null) {
		if ($subject instanceof Helper\Template) {
			$this->_template = $subject;
		} else {
			$this->_template = new Helper\Template($subject, $body);
		}
		return $this;
	}
	
	public function getTemplate() {
		return $this->_template;
	}

	public function addAddress($address, $name = '', $data = null) {
		if (is_string($address)) {
			$address = str_replace(array("\n"), ';', $address);
			$address = explode(';', $address);
		}
		foreach ($address as $addr) {
			if (empty($addr)) continue;
			$this->_to[] = new Helper\Address(trim($addr), $name, $data);
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

	public static function getServer($key = null, $default = null) {
		if (null === $key) {
			return $_SERVER;
		}

		return (isset($_SERVER[$key])) ? $_SERVER[$key] : $default;
	}

	public static function getScheme() {
		return (self::getServer('HTTPS') == 'on') ? 'https' : 'http';
	}

	public static function getHttpHost() {
		$host = self::getServer('HTTP_HOST');
		if (!empty($host)) {
			return $host;
		}

		$scheme = self::getScheme();
		$name = self::getServer('SERVER_NAME');
		$port = self::getServer('SERVER_PORT');

		if (($scheme == 'http' && $port == 80) || ($scheme == 'https' && $port == 443)) {
			return $name;
		} else {
			return $name . ':' . $port;
		}
	}

}
