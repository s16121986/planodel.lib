<?php
class Paginator{

	protected $_options = array(
		'step' => 10,
		'pagesStep' => 4,
		'prevText' => '{lang:Prev}',
		'nextText' => '{lang:Next}',
		'baseUrl' => null,
		'queryParam' => 'p'
	);

	protected $_current = 1;

	protected $_count = 0;

	protected $pages = array();

	protected $_controller;

	public function __get($name) {
		return (isset($this->_options[$name]) ? $this->_options[$name] : null);
	}

	public function __set($name, $value) {
		$this->_options[$name] = $value;
	}

	public function __construct($controller, $options = null) {
		$this->_controller = $controller;
		if (is_int($options)) {
			$options = array('step' => $options);
		}
		if ($options) {
			$this->setOptions($options);
		}
	}

	public function setOptions($options) {
		foreach ($options as $k => $v) {
			$this->_options[$k] = $v;
		}
		return $this;
	}

	public function getQuery($name, $default = null) {
		return (isset($_GET[$name]) ? $_GET[$name] : $default);
	}

	public function setCount($count) {
		$this->_count = $count;
		return $this;
	}

	public function getCount() {
		return $this->_count;
	}

	public function getStartIndex() {
		return ($this->getCurrentPage() - 1) * $this->step;
	}

	public function getCurrentPage() {
		$this->_current = (int)$this->getQuery($this->queryParam);
		if ($this->_current > $this->getPageCount()) {
			$this->_current = $this->getPageCount();
		} elseif ($this->_current < 1) {
			$this->_current = 1;
		}
		return $this->_current;
	}

	public function getPageCount() {
		return ceil($this->_count / $this->step);
	}

	protected function link($page, $text = null) {
		if (null === $text) {
			$text = $page;
		}
		$query = $_SERVER['QUERY_STRING'];
		if ($query) {
			$query = '?' . $query;
		}
		$url = $this->baseUrl;
		if (null === $url) {
			$url = $_SERVER['REQUEST_URI'];
			if (false !== ($pos = strpos($url, '?'))) {
				$url = substr($url, 0, $pos);
			}
		}
		$params = $_GET;
		if ($page == 1) {
			unset($params[$this->queryParam]);
		} else {
			$params[$this->queryParam] = $page;
		}
		if ($params) {
			$url .= '?' . http_build_query($params);
		}
		return '<a href="' . $url . '">' . $text . '</a>';
	}

	public function render() {

		$pageCount = $this->getPageCount();
		if ($pageCount > 1) {
			$pages = new \stdClass();
			$pages->count = $this->_count;
			$pages->step = $this->step;
			$pages->first = 1;
			$pages->current = $this->getCurrentPage();
			$pages->last = $pageCount;
			$pages->previous = null;
			$pages->next = null;

			if ($pages->current - 1 > 0) {
				$pages->previous = $pages->current - 1;
			}
			if ($pages->current + 1 <= $pageCount) {
				$pages->next = $pages->current + 1;
			}

			$firstPageInRange = $pages->current - $this->pagesStep;
			$lastPageInRange = $pages->current + $this->pagesStep;
			if ($firstPageInRange <= 2) {
				$firstPageInRange = 1;
			}
			if ($lastPageInRange > $pages->last - 2) {
				$lastPageInRange = $pages->last;
			}
			$pagesInRange = array();
			for ($i = $firstPageInRange; $i <= $lastPageInRange; $i++) {
				$pagesInRange[] = $i;
			}
			$pages->pagesInRange     = $pagesInRange;
			$pages->firstPageInRange = $firstPageInRange;
			$pages->lastPageInRange  = $lastPageInRange;

			$f = $this->_controller->getHelper('viewRenderer')->getModuleDirectory() . '/views/layouts/paginator.phtml';
			ob_start();
			include $f;
			return ob_get_clean();
		}
	}

}