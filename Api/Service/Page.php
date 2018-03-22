<?php
namespace Api\Service;

use Api;
use Api\Service\Page\Options;
use Page\Page as HtmlPage;
use Db;

class Page extends Api{
	
	protected $pages = null;
	protected $parent = null;
	protected $htmlPage = null;
	protected $options = null;

	public static function getPathPages($path) {
		if ($path === '/') {
			$path = '';
		}
		$path = trim($path, '/');
		$pages = array();
		if ($path === '') {
			$parts = array('');
		} else {
			$parts = explode('/', $path);
		}
		$parentId = null;
		foreach ($parts as $part) {
			$page = Db::from('pages', '*')
					->where('dir=?', $part)
					->where('parent_id' . ($parentId ? '=' . $parentId : ' IS NULL'))
					->where('hidden=0')
					->query()->fetchRow();
			if (!$page) {
				return false;
			}
			$pages[] = self::factory('Page', $page['id']);
			$parentId = $page['id'];
		}
		return $pages;
	}
	
	protected function init() {
		$this->_table = 'pages';
		$this
			->addAttribute('site_id', 'number', array('notnull' => false))
			->addAttribute('parent_id', 'number', array('notnull' => false))
			->addAttribute('dir', 'string', array('length' => 20))
			->addAttribute('name', 'string', array('length' => 50, 'locale' => true, 'required' => true))
			->addAttribute('title', 'string', array('length' => 255, 'locale' => true, 'required' => true))
			->addAttribute('text', 'string', array('locale' => true))
			//->addAttribute('page_title', 'string', array('length' => 255))
			//->addAttribute('page_keywords', 'string', array())
			//->addAttribute('page_description', 'string', array())
			->addAttribute('index', 'number', array('length' => 3, 'default' => 0))
			->addAttribute('enabled', 'boolean', array('default' => true))
			->addAttribute('editable', 'boolean', array('default' => true))
			->addAttribute('deletable', 'boolean', array('default' => true))
			->addAttribute('hidden', 'boolean', array('default' => false))
			->addAttribute('xml_priority', 'number', array('length' => 1, 'fractionDigits' => 1, 'default' => 0.5))
			
			->addAttribute('created')
			->addAttribute('updated');
		Site::initApi($this);
	}
	
	public function __call($name, $arguments) {
		if ($this->htmlPage && method_exists($this->htmlPage, $name)) {
			return call_user_func_array(array($this->htmlPage, $name), $arguments);
		}
		return parent::__call($name, $arguments);
	}
	
	public function getData() {
		$data = parent::getData();
		$data['options'] = $this->getOptions()->getData();
		return $data;
	}
	
	public function getPages() {
		if (null === $this->pages) {
			$this->pages = array();
			$ids = Db::from($this->table, 'id')
						->where('parent_id' . ($this->id == 1 ? ' IS NULL AND id<>1' : '=' . (int)$this->id))
						->where('hidden=0')
						->query()->fetchAll(Db::FETCH_COLUMN);
			foreach ($ids as $id) {
				$page = new self();
				$page->findById($id);
				$this->pages[] = $page;
			}
		}
		return $this->pages;
	}
	
	public function getPath() {
		if ($this->parent_id) {
			$page = new self();
			$page->findById($this->parent_id);
			$path = $page->path;
		} else {
			$path = '';
		}
		return $path . $this->dir . '/';
	}
	
	public function initPath($path) {
		return $this->findByPath($path);
	}
	
	public function findByPath($path) {
		$pages = self::getPathPages($path);
		if ($pages) {
			if (false) {// && ($menu = $htmlPage->getMenu('breadcrumbs'))
				$href = '/';
				foreach ($pages as $page) {
					if ($page->id == 1) {
						continue;
					}
					$href .= $page->dir . '/';
					$menu->add($page->title, $href);
				}
			}
			$page = array_pop($pages);
			return $this->findById($page->id);
			//$this->initHtmlPage();
		}
		return false;
	}
	
	public function initHtmlPage() {
		if (null === $this->htmlPage) {
			$this->htmlPage = new HtmlPage();
			$this->htmlPage->api = $this;
		}
		if (!$this->isEmpty()) {
			$this->htmlPage->getHead()
				->setTitle($this->page_title)
				->addMetaName('description', $this->page_description)
				->addMetaName('keywords', $this->page_keywords);
		}
		return $this->htmlPage;
	}
	
	public function getHtmlPage() {
		return $this->htmlPage;
	}
	
	public function getOptions($array = false) {
		if (null === $this->options) {
			$this->options = new Options($this);
			$this->options->init();
		}
		return ($array ? $this->options->getData() : $this->options);
	}
	
}
