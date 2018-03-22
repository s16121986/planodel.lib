<?php
class Controller_Action_Page{

	public $styles = array();

	public $scripts = array();

	protected $_data = array();

	protected $_menu;

	public function __get($name) {
		return (isset($this->_data[$name]) ? $this->_data[$name] : null);
	}

	public function init($dir) {
		$data = Db::from('pages', '*')
						->where('dir=?', $dir)
						->query()->fetchRow();
		if (empty($data)) {
			return false;
		}
		$this->_data = $data;
		return true;
	}

	public function setTitle($page) {
		if (!is_array($page)) {
			$page = array(
				'title' => $page,
				'page_title' => $page
			);
		}
		$this->_data = array_merge(array(
			'title' => '',
			'page_description' => '',
			'page_keywords' => ''
		), $this->_data);
		foreach (array('title', 'page_title', 'page_description', 'page_keywords') as $k) {
			if (isset($page[$k])) {
				$this->_data[$k] = strip_tags($page[$k]);
			}
		}
		if (!isset($this->_data['page_title']) || !$this->_data['page_title']) {
			$this->_data['page_title'] = $this->_data['title'];
		}
		return $this;
	}

	public function renderScripts() {
		$scripts = '';
		foreach ($this->scripts as $script) {
			if (false === strpos($script, 'http') && false === strpos($script, '.')) {
				$script = '/resources/js/' . $script . '.js';
			}
			$scripts .= '<script type="text/javascript" src="' . $script . '" ></script>';
		}
		return $scripts;
	}

	public function renderStyles() {
		$styles = '';
		foreach ($this->styles as $style) {
			$styles .= '<link rel="stylesheet" type="text/css" href="/resources/css/' . $style . '.css" />';
		}
		return $styles;
	}

	public function renderHeaders() {
		return '<title>' . $this->page_title . '</title>
			<meta name="description" content="' . $this->page_description . '" />
			<meta name="keywords" content="' . $this->page_keywords . '" />';
	}

	public function getMainMenu() {
		if (null === $this->_menu) {
			$this->_menu = Db::from('main_menu')
					->joinLeft('pages', 'pages.id=main_menu.page_id', array('dir as page_dir'))
					->order('index')
					->query()->fetchAll();
		}
		return $this->_menu;
	}

	public function getData() {
		return $this->_data;
	}
	
	public function setData($data) {
		$this->_data = $data;
	}

	public function h1() {
		return '<h1>' . $this->title . '</h1>';
	}

}