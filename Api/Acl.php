<?php
namespace Api;

use Api\Acl\Role;

abstract class Acl{
	
	private static $roles = array();
	private static $role = null;
	
	public static function addRole($name, Role $role = null) {
		if (null === $role) {
			$role = new Role();
		}
		self::$roles[$name] = $role;
		if (null === self::$role) {
			self::$role = $name;
		}
		return $role;
	}
	
	public static function getRole($name = null) {
		if (null === $name) {
			$name = self::$role;
		}
		return (isset(self::$roles[$name]) ? self::$roles[$name] : null);
	}
	
	public static function setRole($name) {
		self::$role = $name;
	}
	
	public static function isAllowed($model, $action) {
		if(null === self::$role) {
			return true;
		}
		return self::getRole()->isAllowed($model, $action);
	}
	
}