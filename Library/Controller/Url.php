<?php
namespace Controller;

use Translation;
use Url as AbstractUrl;

class Url extends AbstractUrl{
	
	protected $request;
	
	protected function initDefaultUri($uri) {
		switch ($uri) {
			case 'referer':
				$this->module = null;
				$this->language = null;
				break;
			case 'home':
				$this->path = null;
				$this->pagePath = null;
				$this->controller = null;
				$this->action = null;
				return '';
		}
		return parent::initDefaultUri($uri);
	}
	
	/*public function __set($name, $value) {
		switch ($name) {
			case 'path':
				$isRelative = (null !== $value) && (0 !== strpos($value, self::URI_DELIMITER));
				if ($isRelative) {
					parent::__set('path', null);
					$name = 'relativePath';
				} else {
					parent::__set('relativePath', null);
				}
				$this->initPath();
				break;
			case 'controller':
			case 'action':
			case 'relativePath':
				$this->initPath();
				break;
		}
		return parent::__set($name, $value);
	}*/
	
	public function parse($uri) {
		if (is_string($uri) && 0 === strpos($uri, './')) {
			$uri = $this->controller . substr($uri, 1);
		}
		return parent::parse($uri);
	}
	
	public function getHome() {
		$uri = parent::getHome();
		if ($this->language && $this->language !== Translation::getDefault()->code) {
			$uri .=  $this->language . self::URI_DELIMITER;
		}
		if ($this->module) {
			$uri .= $this->module . self::URI_DELIMITER;
		}
		return $uri;
	}

}
