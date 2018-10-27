<?php
namespace Api\Model;

use Api;
use Api\Util\Translation;
use Format;
use Db;

class Variables extends Api{
	
	private static $table = 'variables';
	private static $variables = array();

	public static function get($key, $tpl = null) {
		if (!isset(self::$variables[$key])) {
			$q = Db::from(self::$table);
			if (($siteId = Site::getSiteId())) {
				$q
					->where('site_id=' . $siteId . ' OR site_id IS NULL')
					->order('site_id DESC');
			}
			self::$variables[$key] = $q
				->where('`key`=?', $key)
				->limit(1)
				->query()->fetchRow();
		}
		if (self::$variables[$key]) {
			$name = Translation::getColumn('value');
			$value = self::$variables[$key][$name];
			if ($value && $tpl) {
				return Format::formatTemplate($value, $tpl);
			}
		return $value;
		}
		return null;
	}
	
	public static function format() {
		$args = func_get_args();
		$args[0] = self::get($args[0]);
		return call_user_func_array('sprintf', $args);
	}
	
	protected function init() {
		$this->_table = self::$table;
		$this
			->addAttribute('site_id', 'number', array('notnull' => false))
			->addAttribute('key', 'string', array('required' => true, 'length' => 50))
			->addAttribute('name', 'string', array('required' => true, 'length' => 255))
			->addAttribute('value', 'string', array('required' => true, 'locale' => true))
		;
		Site::initApi($this);
	}
	
	protected function initSettings($settings) {
		$settings->enableQuicksearch('key', 'name', 'value');
	}

}