<?php

use stdClass as Event;

abstract class EventManager{
	
	private static $triggers = array();
	
	public static function bind($object, $action, $callback, $params = array()) {
		self::$triggers[] = array($object, $action, $callback, $params);
	}
	
	public static function unbind($object, $action, $callback = null) {
		$triggers = array();
		foreach (self::$triggers as $item) {
			if (!($item[0] === $object && $item[1] === $action && self::isEqual($callback, $item[2], true))) {
				$triggers[] = $item;
			}
		}
		self::$triggers = $triggers;
	}
	
	public static function trigger($object, $action, $params = array()) {
		foreach (self::$triggers as $item) {
			if ($item[0] === $object && $item[1] === $action) {
				$event = new Event();
				$event->action = $action;
				$event->params = $item[3];
				if (false === call_user_func_array($item[2], array_merge(array($event), $params))) {
					return false;
				}
			}
		}
	}
	
	private static function getVarGuid($var) {
		switch (true) {
			case (null === $var):
				return $var;
			case is_scalar($var):
				return (string)$var;
			case ($var instanceof Api):
				$guid = $var->getModelName();
				if ($var->isNew()) {
					$guid .= '_new';
				} elseif (!$var->isEmpty()) {
					$guid .= '_' . $var->id;
				}
				return $guid;
			case is_object($var):
				return spl_object_hash($var);
			case is_callable($var):
				return $var;
		}
		return $var;
	}
	
	private static function isEqual($var1, $var2, $orNull = false) {
		if ($orNull && null === $var1) {
			return true;
		}
		return ($var1 === self::getVarGuid($var2));
	}
	
}