<?php
namespace Api\Service\Page;

use Db;
use Api;
use stdClass as Option;
use Api\Util\Translation;

class Head{
	
	private static $table = 'page_head';
	private static $default = null;
	private static $ini = null;
	
	private $page;
	private $pageType = null;
	private $data = array();
	
	public static function setDefault($data) {
		self::$default = array();
		foreach ($data as $k => $r) {
			if (($option = self::_option($k, $r))) {
				self::$default[$k] = $option;
			} else {
				unset(self::$default[$k]);
			}
		}
		self::_setData(null, null, self::$default);
	}
	
	public static function getDefault() {
		if (null === self::$default) {
			self::$default = array();
			foreach (self::_getData(null, null) as $r) {
				if (($option = self::_option($r['name'], $r['value'], $r['attributes']))) {
					self::$default[$r['name']] = $option;
				}
			}
		}
		return self::$default;
	}
	
	private static function _option($type, $name, $value = null, $attributes = null) {
		$option = new Option();
		$option->type = $type;
		$option->name = $name;
		$option->value = (empty($value) ? null : $value);
		$option->attributes = ($attributes ? $attributes : null);
		return $option;
	}
	
	private static function _getData($pageId, $pageType = null) {
		return Db::from(self::$table, array('name', Translation::getColumn('value'). ' as value', 'attributes'))
			->where('page_id' . ($pageId ? '=' . $pageId : ' IS NULL'))
			->where('page_type' . ($pageType ? '=' . $pageType : ' IS NULL'))
			->query()->fetchAll();
	}
	
	private static function _setData($pageId, $pageType, $data) {
		$names = Db::from(self::$table, 'name')
				->where('page_id=' . $pageId)
				->where('page_type' . ($pageType ? '=' . $pageType : ' IS NULL'))
				->query()->fetchAll(Db::FETCH_COLUMN);
		foreach ($data as $o) {
			if (false === ($i = array_search($o->name, $names))) {
				Db::insert(self::$table, array(
					'page_id' => $pageId,
					'page_type' => $pageType,
					'type' => $o->type,
					'name' => $o->name,
					Translation::getColumn('value') => ($o->value ? $o->value : ''),
					'attributes' => $o->attributes
				));
			} else {
				//array_splice($names, $i, 1);
				Db::update(self::$table, array(
					Translation::getColumn('value') => ($o->value ? $o->value : ''),
					'attributes' => $o->attributes
				), array(
					'page_id' => $pageId,
					'page_type' => $pageType,
					'type' => $o->type,
					'name' => $o->name
				));
			}
		}
		$dw = array(
			'page_id' => $pageId,
			'page_type' => $pageType,
		);
		foreach (Translation::getLanguages() as $language) {
			$dw[Translation::getColumn('value', $language->code)] = '';
		}
		Db::delete(self::$table, $dw);
	}
	
	public function __construct(Api $page, $pageType = null) {
		$this->page = $page;
		$this->pageType = $pageType;
	}
	
	public function __get($name) {
		return (isset($this->data[$name]) ? $this->data[$name] : null);
	}
	
	public function __set($name, $value) {
		$this->set($name, $value);
	}
	
	public function init() {
		if ($this->page->isEmpty()) {
			return $this;
		}
		foreach (self::_getData($this->page->id, $this->pageType) as $r) {
			$this->set($r['name'], $r['value'], $r['attributes']);
		}
		return $this;
	}
	
	public function set($name, $value, $attributes = null) {
		$option = self::_option($name, $value, $attributes);
		$this->data[$option->name] = $option;
		return $this;
	}
	
	public function setData($data) {
		$this->data = array();
		foreach ($data as $k => $v) {
			$this->set($k, $v);
		}
		return $this;
	}
	
	public function getData($default = true) {
		return ($default ? array_merge(self::getDefault(), $this->data) : $this->data);
	}
	
	public function write($data = null) {
		if ($this->page->isEmpty()) {
			return false;
		}
		if ($data) {
			$this->set($data);
		}
		self::_setData($this->page->id, $this->pageType, $this->data);
		return true;
	}
	
}