<?php
namespace Api\Util;

use Api;

abstract class Storage{
	
	private static $cache = array();
	
	public static function add(Api $object) {
		$k = self::id($object);
		self::$cache[$k] = $object;
	}
	
	public static function get(Api $object, $id) {
		$k = self::id($object, $id);
		return (isset(self::$cache[$k]) ? self::$cache[$k] : null);
	}

	private static function id($object, $id = null) {
		if (null === $id) {
			$id = $object->id;
		}
		return get_class($object) . '_' . $id;
	}
	
}