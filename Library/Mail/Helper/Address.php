<?php
namespace Mail\Helper;

class Address{
	
	protected $address = null;
	
	protected $name = null;
	
	protected $data = array();
	
	public function __construct($address, $name = '', array $data = null) {
		$this->setAddress($address);
		$this->setName($name);
		if ($data) {
			$this->setData($data);
		}
	}
	
	public function __get($name) {
		if (property_exists($this, $name)) {
			return $this->$name;
		} elseif (array_key_exists($name, $this->data)) {
			return $this->data[$name];
		}
		return null;
	}
	
	public function setAddress($address) {
		$this->address = $address;
		return $this;
	}
	
	public function setName($name) {
		$this->name = $name;
		return $this;
	}
	
	public function setData($data) {
		$this->data = $data;
		return $this;
	}
	
}