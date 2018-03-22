<?php
namespace Stdlib;

class Date extends \DateTime{
	
	/*public function __construct($time = null, $object = null) {
		parent::__construct($time, $object);
	}*/
	
	public function getYear() {
		return (int)$this->format('Y');
	}
	
	public function getMonth() {
		return (int)$this->format('n');
	}
	
	public function getDay() {
		return (int)$this->format('j');
	}
	
	public function getWeekDay() {
		return (int)$this->format('N');
	}
	
	public function getHour() {
		return (int)$this->format('H');
	}
	
	public function getMinute() {
		return (int)$this->format('i');
	}
	
	public function getSecond() {
		return (int)$this->format('s');
	}
	
}