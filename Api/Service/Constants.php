<?php
namespace Api\Service;

use Api;
use Db;

class Constants extends Api{
	
	private static $table = 'constants';
	private static $constants = null;

	public static function get($key) {
		self::_init();
		return (isset(self::$constants[$key]) ? self::$constants[$key] : null);
	}
	
	protected function init() {
		$this->_table = self::$table;
		$this
			->addAttribute('key', 'string', array('required' => true, 'length' => 50))
			->addAttribute('name', 'string', array('required' => true, 'length' => 255))
			->addAttribute('value', 'string', array('required' => true))
		;
	}
	
	private static function _init() {
		if (null === self::$constants) {
			self::$constants = array();
			$q = Db::from(self::$table)->query();
			while ($var = $q->fetch()) {
				self::$constants[$var['key']] = $var['value'];
			}
			$q->getResource()->free();
		}
	}

}