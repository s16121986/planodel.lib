<?php
namespace Api\Util\Reference;

class ForeignKey{

	const Value = 0;

	const Enum = 1;

	const Api = 2;

	public $name = '';

	public $value = null;

	public $type = null;

	public function __construct($name, $value, $type = self::Value) {
		$this->name = $name;
		$this->value = $value;
		$this->type = $type;
	}

	public function getValue($api = null) {
		switch ($this->type) {
			case self::Value:return $this->value;
			case self::Enum:return constant($this->value);
			case self::Api:return $api->{$this->value};
		}
	}

}