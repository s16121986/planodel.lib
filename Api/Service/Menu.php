<?php
namespace Api\Service;

use Api;
use Api\Service\Page;
use Api\Service\Menu\Item;
use Menu\Menu as HtmlMenu;

class Menu extends Api{
	
	private static $menus = array();
	private static $table = 'menu';

	public static function get($key) {
		if (!isset(self::$menus[$key])) {
			$api = new self();
			if ($api->findByAttribute('key', $key)) {
				self::$menus[$key] = $api->getMenu(array('cls' => $key));
			} else {
				self::$menus[$key] = false;
			}
		}
		return self::$menus[$key];
	}
	
	protected function init() {
		$this->_table = self::$table;
		$this
			->addAttribute('site_id', 'number', array('notnull' => false))
			->addAttribute('key', 'string', array('required' => true, 'length' => 50))
			->addAttribute('name', 'string', array('required' => true, 'length' => 255))
		;
		Site::initApi($this);
	}
	
	public function getMenu($options = array()) {
		$options = array_merge($options, $this->getData());
		$options['api'] = $this;
		unset($options['id']);
		$menu = new HtmlMenu($options);
		$itemApi = new Item();
		$items = $itemApi->select(array('menu_id' => $this->id));
		$pageApi = new Page();
		foreach ($items as $item) {
			if ($item['page_id']) {
				$pageApi->findById($item['page_id']);
				$item['action'] = $pageApi->dir;
				$item['href'] = $pageApi->getPath();
			}
			$menu->add($item);
		}
		return $menu;
	}

}