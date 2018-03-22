<?php
namespace Api;

abstract class ServiceManager{
	
	private static $services = array();
	
	public static function set($name, $service) {
		self::$services[$name] = $service;
	}
	
	public static function get($name) {
		return self::$services[$name];
	}
	
	public static function has($name) {
		return isset(self::$services[$name]);
	}
	
}