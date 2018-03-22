<?php
namespace Api\Service\Menu;

use Api;
use Db;

class Item extends Api{
	
	private static $table = 'menu_items';
	private $menu;
	
	protected function init() {
		$this->_table = self::$table;
		$this
			->addAttribute('menu_id', 'number', array('notnull' => false, 'required' => true))
			->addAttribute('page_id', 'number', array('notnull' => false))
			->addAttribute('text', 'string', array('required' => true, 'locale' => true, 'length' => 50))
			->addAttribute('href', 'string', array('notnull' => false))
			->addAttribute('title', 'string', array('locale' => true, 'length' => 255))
			->addAttribute('index', 'number', array('default' => 0))
		;
	}
	
	protected function initSettings($settings) {
		$settings->order->setDefault('index');
		if ($this->menu && !$this->menu-IsEmpty()) {
			$settings->menu_id = $this->menu->id;
		}
	}
	
	public function updateIndexes($menuId, $indexes = array()) {
		$items = array();
		foreach ($this->select(array('menu_id' => $menuId)) as $item) {
			$items[] = $item['id'];
		}
		$order = array();
		if ($indexes && is_array($indexes)) {
			foreach ($indexes as $id) {
				if (false !== ($p = array_search($id, $items))) {
					$order[] = $id;
					array_splice($items, $p, 1);
				}
			}
		}
		foreach ($items as $id) {
			$order[] = $id;
		}
		foreach ($order as $i => $id) {
			Db::query('UPDATE `' . $this->_table . '` SET `index`=' . $i . ' WHERE id=' . $id);
		}
	}

	protected function afterWrite($isNew = false) {
		if ($isNew) {
			$this->updateIndexes($this->menu_id);
		}
	}

	protected function afterDelete() {
		$this->updateIndexes($this->menu_id);
	}
	
	public function setMenu($menu) {
		$this->menu = $menu;
		return $this;
	}

}