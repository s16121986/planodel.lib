<?php
require_once 'Library/Controller/Action/HelperBroker.php';

use Api\ServiceManager;
use Api\Service\Page;
use Api\Service\Page\Options as PageOptions;
use Page\Page as HtmlPage;
use Controller\Url;

class Controller_Action{

	protected $_classMethods;

    /**
     * Word delimiters (used for normalizing view script paths)
     * @var array
     */
    protected $_delimiters;

    /**
     * Array of arguments provided to the constructor, minus the
     * {@link $_request Request object}.
     * @var array
     */
    protected $_invokeArgs = array();

    /**
     * Front controller instance
     * @var Zend_Controller_Front
     */
    protected $_frontController;

    /**
     * Zend_Controller_Request_Abstract object wrapping the request environment
     * @var Zend_Controller_Request_Abstract
     */
    protected $_request = null;

    /**
     * Zend_Controller_Response_Abstract object wrapping the response
     * @var Zend_Controller_Response_Abstract
     */
    protected $_response = null;

    /**
     * View script suffix; defaults to 'phtml'
     * @see {render()}
     * @var string
     */
    public $viewSuffix = 'phtml';

    /**
     * View object
     * @var Zend_View_Interface
     */
    public $view;

    /**
     * Helper Broker to assist in routing help requests to the proper object
     *
     * @var Zend_Controller_Action_HelperBroker
     */
    protected $_helper = null;
	
	public $page;
	public $url;

    /**
     * Class constructor
     *
     * The request and response objects should be registered with the
     * controller, as should be any additional optional arguments; these will be
     * available via {@link getRequest()}, {@link getResponse()}, and
     * {@link getInvokeArgs()}, respectively.
     *
     * When overriding the constructor, please consider this usage as a best
     * practice and ensure that each is registered appropriately; the easiest
     * way to do so is to simply call parent::__construct($request, $response,
     * $invokeArgs).
     *
     * After the request, response, and invokeArgs are set, the
     * {@link $_helper helper broker} is initialized.
     *
     * Finally, {@link init()} is called as the final action of
     * instantiation, and may be safely overridden to perform initialization
     * tasks; as a general rule, override {@link init()} instead of the
     * constructor to customize an action controller's instantiation.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param Zend_Controller_Response_Abstract $response
     * @param array $invokeArgs Any additional invocation arguments
     * @return void
     */
    public function __construct(Controller_Request_Abstract $request, Controller_Response_Abstract $response, array $invokeArgs = array()) {
        $this->setRequest($request)
             ->setResponse($response)
             ->_setInvokeArgs($invokeArgs);
		
        $this->_helper = new Controller_Action_HelperBroker($this);
		$this->page = new HtmlPage();
		ServiceManager::set('page', new Page());
		if (false === $this->initUrl()) {
			if ('error' != $request->getControllerName()) {
				$this->redirect(404);
			}
		}
		
        $this->init();
    }

	public function __get($name) {
		return null;
	}

	public function getHelper($helperName) {
        return $this->_helper->{$helperName};
    }

	public function getRequest() {
        return $this->_request;
    }

    public function setRequest(Controller_Request_Abstract $request) {
        $this->_request = $request;
        return $this;
    }

    public function getResponse() {
        return $this->_response;
    }

    public function setResponse(Controller_Response_Abstract $response) {
        $this->_response = $response;
        return $this;
    }

	protected function _setInvokeArgs(array $args = array()) {
        $this->_invokeArgs = $args;
        return $this;
    }

    public function getInvokeArgs() {
        return $this->_invokeArgs;
    }

    public function getInvokeArg($key) {
        if (isset($this->_invokeArgs[$key])) {
            return $this->_invokeArgs[$key];
        }

        return null;
    }

    public function init() {}

    public function postDispatch() {
    }

    public function __call($methodName, $args) {
        require_once 'Library/Controller/Action/Exception.php';
        if ('Action' == substr($methodName, -6)) {
            $action = substr($methodName, 0, strlen($methodName) - 6);
            throw new Controller_Action_Exception(sprintf('Action "%s" does not exist and was not trapped in __call()', $action), 404);
        }

        throw new Controller_Action_Exception(sprintf('Method "%s" does not exist and was not trapped in __call()', $methodName), 500);
    }

    public function dispatch($action) {
        // Notify helpers of action preDispatch state
        $this->_helper->notifyPreDispatch();

        $this->preDispatch();
        if ($this->getRequest()->isDispatched()) {
            if (null === $this->_classMethods) {
                $this->_classMethods = get_class_methods($this);
            }
			//bad idea
			$action = $this->getRequest()->getActionName() . 'Action';
            // preDispatch() didn't change the action, so we can continue
            if ($this->getInvokeArg('useCaseSensitiveActions') || in_array($action, $this->_classMethods)) {
                if ($this->getInvokeArg('useCaseSensitiveActions')) {
                    trigger_error('Using case sensitive actions without word separators is deprecated; please do not rely on this "feature"');
                }
                $this->$action();
            } else {
                $this->__call($action, array());
            }
            $this->postDispatch();
        }

        // whats actually important here is that this action controller is
        // shutting down, regardless of dispatching; notify the helpers of this
        // state
        $this->_helper->notifyPostDispatch();

    }

	protected function _getParam($paramName, $default = null)
    {
        $value = $this->getRequest()->getParam($paramName);
         if ((null === $value || '' === $value) && (null !== $default)) {
            $value = $default;
        }

        return $value;
    }

    /**
     * Set a parameter in the {@link $_request Request object}.
     *
     * @param string $paramName
     * @param mixed $value
     * @return Zend_Controller_Action
     */
    protected function _setParam($paramName, $value)
    {
        $this->getRequest()->setParam($paramName, $value);

        return $this;
    }

    /**
     * Determine whether a given parameter exists in the
     * {@link $_request Request object}.
     *
     * @param string $paramName
     * @return boolean
     */
    protected function _hasParam($paramName)
    {
        return null !== $this->getRequest()->getParam($paramName);
    }

    /**
     * Return all parameters in the {@link $_request Request object}
     * as an associative array.
     *
     * @return array
     */
    protected function _getAllParams()
    {
        return $this->getRequest()->getParams();
    }





	public function url($uri = null) {
		if (null !== $uri) {
			switch ($uri) {
				case '':
				case 'home';
					return $this->url->getHome();
			}
		}
		$url = clone $this->url;
		$url->query = null;
		if ($uri) {
			$url->parse($uri);
		}
		
		return $url->toString();/**/
		if (null === $url) {
			return $_SERVER['REQUEST_URI'];
		}
		switch ($url) {
			case 'referer':
			case 'back':
				if (isset($_SERVER['HTTP_REFERER'])) {
					return $_SERVER['HTTP_REFERER'];
				}
				return '';
		}
		$request = $this->getRequest();
		if (is_array($url)) {
			$s = $url;
			$url = $this->url($s['url']);
			if (isset($s['query'])) {
				if (($p = strpos($url, '?'))) {
					$url = substr($url, 0, $p);
				}
				$q = (is_array($s['query']) ? $s['query'] : $request->getQuery());
				if ($q) {
					$url .= ($q ? '?' . http_build_query($q) : '');
				}
			}
			if (isset($s['hash'])) $url .= $s['hash'];
			if (isset($s['absolute'])) {
				$url = $request->getScheme() . '://' . $request->getHttpHost() . $url;
			}
			return $url;
		}
		if (0 !== strpos($url, '/') && 0 !== strpos($url, 'http')) {
			if (0 === strpos($url, './')) {
				$url = $request->getControllerName() . substr($url, 1);
			}
			$url = '/' . $url;
			if (($module = $request->getModuleName())) {
				$url = '/' . $module . $url;
			}
			$language = Translation::getLanguage();
			if (!$language->default) {
				$url = '/' . $language->code . $url;
			}
		}
		return $url;
	}

	public function setTemplate($controller, $action = null) {
		if (null === $action) {
			$action = $controller;
			$controller = null;
		}
		$this->getHelper('ViewRenderer')->setScriptAction($action);
		if ($controller) $this->getHelper('ViewRenderer')->setScriptController($controller);
	}
	
	public function redirect($url = null, $code = 302) {
		if (is_int($url)) {
			$code = $url;
			$url = null;
		} else {
			$url = $this->url($url);
		}
		/*if ($url === 'referer') {
			$defaultUrl = $code;
			$code = $url;
		}*/
		switch ($code) {
			/*case 'referer':
				if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] !== $_SERVER['REQUEST_URI']) {
					$url = $_SERVER['HTTP_REFERER'];
				} else {
					$url = $defaultUrl;
				}
				$code = 302;
				break;*/
			case 403:
			case 404:
				require_once 'Library/Controller/Action/Exception.php';
				throw new Controller_Action_Exception('', $code);
		}
		$this->getResponse()->setRedirect($url, $code)->sendHeaders();
		exit;
	}
	
	protected function initQuicksearch() {
		return $this->initSearchOptions('quicksearch');
	}
	
	protected function initSearchOptions() {
		if (!$this->search) {
			$this->search = new Form\Search();
			//$this->page->scripts[] = '%childCombo';
		}
		$args = func_get_args();
		$this->search->addElements($args);
		return $this->search->submit();
	}
	
	public function h1() {
		$html = '';
		if ($this->search) {
			$html .= '<form method="get">';
		}
		$html .= '<div class="h1-wrapper' . ($this->search && $this->search->getElement('quicksearch') ? ' x-quicksearch' : '') . '">'
			. $this->page->h1();
		if (($menu = $this->page->getMenu('h1'))) {
			$html .= $menu->render();
		}
		if ($this->search && $this->search->getElement('quicksearch')) {
			$html .= '<div class="quicksearch">'
				. $this->search->getElement('quicksearch')->renderInput()
				. '<button type="submit"><i class="fa fa-search"></i></button>'
				. '</div>';
		}
		$html .= '</div>';
		if ($this->search) {
			$html .= $this->search->render();
		}
		if ($this->search) {
			$html .= '</form>';
		}
		return $html;
	}
	
	protected function _checkRoutePath($path, $routePath, $route) {
		$vars = explode(':', $routePath);
		$config = $route[2];
		$reg = array_shift($vars);
		if (count($vars) > 0) {
			foreach ($vars as $var) {
				if (isset($config[$var])) {
					$reg .= '(' . $config[$var] . ')';
				} else {
					$reg .= '(.+?)';
				}
			}
		}
		preg_match('/^' . $reg . '$/', $path, $m);
		if ($m) {
			$routeVars = array();
			foreach ($vars as $i => $var) {
				$routeVars[$var] = $m[$i + 1];
			}
			return $routeVars;
		}
		return false;
	}

	protected function _checkRoute($route, $paths) {
		$routePaths = explode('/', $route[0]);
		unset($routePaths[count($routePaths) - 1], $routePaths[0]);
		if (count($paths) != count($routePaths)) {
			return false;
		}
		$routeVars = array();
		foreach ($paths as $path) {
			if (false === ($vars = $this->_checkRoutePath($path, array_shift($routePaths), $route))) {
				return false;
			}
			$routeVars = array_merge($routeVars, $vars);
		}
		return $routeVars;
	}
	
	protected function initUrl() {
		$result = false;
		$request = $this->getRequest();
		$path = trim(urldecode($request->getPathInfo()), Url::URI_DELIMITER);
		if ($path) {
			$parts = explode(Url::URI_DELIMITER, $path);
		} else {
			$parts = array();
		}
		$url = new Url();
		$this->url = $url;
		$url->language = $request->getParam('language');
		$url->module = $request->getModuleName();
		$url->controller = $request->getControllerName();
		$url->action = $request->getActionName();
		if (($code = $url->language)) {
			array_shift($parts);
			Translation::setLanguage($code);
		} else {
			$code = Translation::getDefault()->code;
			Translation::setLanguage('auto');
		}
		if ($url->module) {
			array_shift($parts);
		}
		$url->path = implode(Url::URI_DELIMITER, $parts);
		if ($code !== Translation::getCode()) {
			$this->redirect(array(
				'language' => Translation::getCode(),
				'query' => true
			));
		} elseif ($url->language && Translation::getLanguage()->default) {
			$this->redirect(array(
				'query' => true
			));
		}
		if (isset($parts[0]) && $parts[0] === $url->controller) {
			array_shift($parts);
		}
		$partsTemp = $parts;
		switch (count($partsTemp)) {
			case 0:
				if ($url->controller != $request->controller) {
					$action = $request->controller;
					$request->setActionName($action);
				} else {
					$action = 'index';
				}
				$partsTemp[0] = $action;
				//break;
			case 1:
				if (method_exists($this, $partsTemp[0] . 'Action')) {
					$request->setActionName($partsTemp[0]);
					$result = true;
				}
				break;
		}
		if ($this->_routes) {
			foreach ($this->_routes as $route) {
				if (false !== ($vars = $this->_checkRoute($route, $parts))) {
					foreach ($vars as $k => $v) {
						$this->$k = $v;
					}
					if (isset($route[1]['action'])) {
						$request->setActionName($route[1]['action']);
					}
					$result = true;
					break;
				}
			}
		}
		$page = ServiceManager::get('page');
		if ($page->findByPath(!$url->path || $url->path === '/' ? 'home' : $url->path)) {
			$this->page->setData($page->getData());
		} else {
			$this->page->getHead()->setOptions(PageOptions::getDefault());
		}
		if (!$result && !$page->isEmpty()) {
			$request->setActionName('index');
			$result = true;
		}
		return $result;
	}
	
	protected function checkPath() {
		$result = false;
		$path = $request->getPathInfo();
		$parts = explode('/', $path);
		array_shift($parts);
		if ($request->getModuleName()) {
			array_shift($parts);
		}
		if (($code = $request->getParam('language'))) {
			array_shift($parts);
			Translation::setLanguage($code);
			$language = Translation::getLanguage();
			if ($language->default) {
				$this->redirect(array(
					'url'=> implode('/', $parts),
					'query' => true
				));
			}
		}
		array_pop($parts);
		$controller = $request->getControllerName();
		if (isset($parts[0]) && $parts[0] === $controller) {
			array_shift($parts);
		}
		$partsTemp = $parts;
		switch (count($partsTemp)) {
			case 0:
				if ($controller != $request->controller) {
					$action = $request->controller;
					$request->setActionName($action);
				} else {
					$action = 'index';
				}
				$partsTemp[0] = $action;
				//break;
			case 1:
				if (method_exists($this, $partsTemp[0] . 'Action')) {
					$request->setActionName($partsTemp[0]);
					$result = true;
				}
				break;
		}
		if ($this->_routes) {
			foreach ($this->_routes as $route) {
				if (false !== ($vars = $this->_checkRoute($route, $parts))) {
					foreach ($vars as $k => $v) {
						$this->$k = $v;
					}
					if (isset($route[1]['action'])) {
						$request->setActionName($route[1]['action']);
					}
					return true;
				}
			}
		}
		//if ($this->initPage($path)) {
		if (!$result && Api\ServiceManager::has('page') && !Api\ServiceManager::get('page')->isEmpty()) {
			$request->setActionName('index');
			$result = true;
		}
		return $result;
	}

    public function preDispatch() {
		
		
    }

}
