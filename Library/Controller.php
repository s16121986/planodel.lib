<?php
class Controller{

	protected $_baseUrl = null;

    /**
     * Directory|ies where controllers are stored
     *
     * @var string|array
     */
    protected $_controllerDir = null;

    /**
     * Instance of Zend_Controller_Dispatcher_Interface
     * @var Zend_Controller_Dispatcher_Interface
     */
    protected $_dispatcher = null;

    /**
     * Singleton instance
     *
     * Marked only as protected to allow extension of the class. To extend,
     * simply override {@link getInstance()}.
     *
     * @var Zend_Controller_Front
     */
    protected static $_instance = null;

    /**
     * Array of invocation parameters to use when instantiating action
     * controllers
     * @var array
     */
    protected $_invokeParams = array();

    /**
     * Subdirectory within a module containing controllers; defaults to 'controllers'
     * @var string
     */
    protected $_moduleControllerDirectoryName = 'controllers';

    /**
     * Instance of Zend_Controller_Plugin_Broker
     * @var Zend_Controller_Plugin_Broker
     */
    protected $_plugins = null;

    /**
     * Instance of Zend_Controller_Request_Abstract
     * @var Zend_Controller_Request_Abstract
     */
    protected $_request = null;

    /**
     * Instance of Zend_Controller_Response_Abstract
     * @var Zend_Controller_Response_Abstract
     */
    protected $_response = null;

    /**
     * Whether or not to return the response prior to rendering output while in
     * {@link dispatch()}; default is to send headers and render output.
     * @var boolean
     */
    protected $_returnResponse = false;

    /**
     * Instance of Zend_Controller_Router_Interface
     * @var Zend_Controller_Router_Interface
     */
    protected $_router = null;

    /**
     * Whether or not exceptions encountered in {@link dispatch()} should be
     * thrown or trapped in the response object
     * @var boolean
     */
    protected $_throwExceptions = false;

	public function  __construct() {
		//$this->_request = $request;
	}

	public static function getInstance() {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function resetInstance() {
        $reflection = new ReflectionObject($this);
        foreach ($reflection->getProperties() as $property) {
            $name = $property->getName();
            switch ($name) {
                case '_instance':
                    break;
                case '_controllerDir':
                case '_invokeParams':
                    $this->{$name} = array();
                    break;
                case '_plugins':
                    $this->{$name} = new Zend_Controller_Plugin_Broker();
                    break;
                case '_throwExceptions':
                case '_returnResponse':
                    $this->{$name} = false;
                    break;
                case '_moduleControllerDirectoryName':
                    $this->{$name} = 'controllers';
                    break;
                default:
                    $this->{$name} = null;
                    break;
            }
        }
        Zend_Controller_Action_HelperBroker::resetHelpers();
    }

    public static function run($controllerDirectory) {
        self::getInstance()
            ->setControllerDirectory($controllerDirectory)
            ->dispatch();
    }

	public function setControllerDirectory($directory, $module = null) {
        $this->getDispatcher()->setControllerDirectory($directory, $module);
        return $this;
    }

	public function location($url, $code = 301, $js = false) {
		//if (is_int($url)) $code = $url;
		switch ($code) {
			case 301:$s = 'Moved Permanently';break;
			case 302:$s = 'Moved Temporarily';break;
			case 403:
				header('HTTP/1.1 ' . $code . ' Forbidden', true, 403);
				$this->loadHelper('403');
				exit;
			case 404:
				header('HTTP/1.1 ' . $code . ' Not Found', true, 404);
				$this->loadHelper('404');
				exit;
			default:
			$code = 200;
			$s = 'OK';break;
		}
		if ($js) {
			if ($url) $s = '<script type="text/javascript">(parent||window).locate(\'' . $url . '\');</script>';
			die($s);
		} else {
			//if (getQuery('admin'))print_r($_SERVER);else
			header('Location: ' . $url, true, $code);
			header('HTTP/1.1 ' . $code . ' ' . $s);
			if ($url) header('Location: ' . $url);
		}
		exit(0);
	}

	public function loadHelper($name) {
		$file = $this->_viewsDir . '/helpers/' . $name . '.phtml';
		if (file_exists($file)) {
			include $file;
		}
	}

	public function _run() {

		$x = explode('/', trim($this->_request->getRequestUri(), '/'));
		if (substr($x[count($x) - 1], 0, 1) == '?') {
			array_pop($x);
		}
		global $uri_arr;
		$uri_arr = $x;
		//print_r($x);
		if ($x[0]) $this->_module = array_shift($uri_arr);
		if (isset($x[1]) && $x[1]) $this->_controller = array_shift($uri_arr);
		$login = ($this->_module == 'auth' && $this->_controller == 'login');
		if (Auth::getInstance()->hasIdentity()) {
			if ($login) $this->location('/');
		} else {
			if (!$login) $this->location('/auth/login/');
		}
		//if (isset($x[2]) && $x[2]) $this->_action = $x[2];
		$cfile = $this->_path . DIRECTORY_SEPARATOR
			. $this->_module . DIRECTORY_SEPARATOR
			. $this->_controller . '.php';
		if (file_exists($cfile)) {
			include $cfile;
			$cfile = $this->_viewsDir . '/templates' . DIRECTORY_SEPARATOR
				. $this->_module . DIRECTORY_SEPARATOR
				. $this->_controller . '.phtml';
			if (file_exists($cfile)) {
				header('Content-type: text/html; charset=utf-8');
				include $cfile;
			}
		}
	}

	public function setRequest($request) {
        if (is_string($request)) {
            if (!class_exists($request)) {
                require_once 'Library/Loader.php';
                Loader::loadClass($request);
            }
            $request = new $request();
        }
        if (!$request instanceof Controller_Request_Abstract) {
            require_once 'Library/Controller/Exception.php';
            throw new Controller_Exception('Invalid request class');
        }

        $this->_request = $request;

        return $this;
    }

	public function setResponse($response) {
        if (is_string($response)) {
            if (!class_exists($response)) {
                require_once 'Library/Loader.php';
                Loader::loadClass($response);
            }
            $response = new $response();
        }
        if (!$response instanceof Controller_Response_Abstract) {
            require_once 'Library/Controller/Exception.php';
            throw new Controller_Exception('Invalid response class');
        }

        $this->_response = $response;

        return $this;
    }

	public function setDispatcher(Controller_Dispatcher_Interface $dispatcher) {
        $this->_dispatcher = $dispatcher;
        return $this;
    }

    public function getDispatcher() {
        if (!$this->_dispatcher instanceof Controller_Dispatcher_Interface) {
            require_once 'Library/Controller/Dispatcher/Standard.php';
            $this->_dispatcher = new Controller_Dispatcher_Standard();
        }
        return $this->_dispatcher;
    }

	public function returnResponse($flag = null) {
        if (true === $flag) {
            $this->_returnResponse = true;
            return $this;
        } elseif (false === $flag) {
            $this->_returnResponse = false;
            return $this;
        }

        return $this->_returnResponse;
    }

	public function dispatch() {
		require_once 'Library/Controller/Request/Http.php';
        $request = new Controller_Request_Http();
		$this->setRequest($request);
		require_once 'Library/Controller/Response/Http.php';
        $response = new Controller_Response_Http();
		$this->setResponse($response);
		$dispatcher = $this->getDispatcher();
        $dispatcher->setResponse($this->_response);//->setParams($this->getParams());
		$dispatcher->dispatch($this->_request, $this->_response);
		if ($this->returnResponse()) {
            return $this->_response;
        }

		$this->_response->sendResponse();
	}

}
?>