<?php

require_once 'Library/Controller/Plugin/Broker.php';

require_once 'Library/Controller/Action/HelperBroker.php';

class Controller_Front {

	protected $_dispatcher = null;
	protected static $_instance = null;
	protected $_invokeParams = array();
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

	protected function __construct() {
		$this->_plugins = new Controller_Plugin_Broker();
	}

	public static function getInstance() {
		if (null === self::$_instance) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public static function run($controllerDirectory = 'application/controllers') {
		self::getInstance()
				->setControllerDirectory($controllerDirectory)
				->dispatch();
	}

	public function setControllerDirectory($directory, $module = null) {
		$this->getDispatcher()->setControllerDirectory($directory, $module);
		return $this;
	}

	/**
	 * Retrieve controller directory
	 *
	 * Retrieves:
	 * - Array of all controller directories if no $name passed
	 * - String path if $name passed and exists as a key in controller directory array
	 * - null if $name passed but does not exist in controller directory keys
	 *
	 * @param  string $name Default null
	 * @return array|string|null
	 */
	public function getControllerDirectory($name = null) {
		return $this->getDispatcher()->getControllerDirectory($name);
	}

	public function setDefaultAction($action) {
		$dispatcher = $this->getDispatcher();
		$dispatcher->setDefaultAction($action);
		return $this;
	}

	/**
	 * Retrieve the default action (unformatted string)
	 *
	 * @return string
	 */
	public function getDefaultAction() {
		return $this->getDispatcher()->getDefaultAction();
	}

	public function setRequest($request) {
		if (is_string($request)) {
			if (!class_exists($request)) {
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

	public function getRequest() {
		if (null == $this->_request) {
			require_once 'Library/Controller/Request/Http.php';
			$this->setRequest(new Controller_Request_Http());
		}
		return $this->_request;
	}

	/**
	 * Set router class/object
	 *
	 * Set the router object.  The router is responsible for mapping
	 * the request to a controller and action.
	 *
	 * If a class name is provided, instantiates router with any parameters
	 * registered via {@link setParam()} or {@link setParams()}.
	 *
	 * @param string|Zend_Controller_Router_Interface $router
	 * @throws Zend_Controller_Exception if invalid router class
	 * @return Zend_Controller_Front
	 */
	public function setRouter($router) {
		if (is_string($router)) {
			if (!class_exists($router)) {
				require_once 'Library/Loader.php';
				Loader::loadClass($router);
			}
			$router = new $router();
		}

		if (!$router instanceof Controller_Router_Interface) {
			require_once 'Library/Controller/Exception.php';
			throw new Controller_Exception('Invalid router class');
		}

		$router->setFrontController($this);
		$this->_router = $router;

		return $this;
	}

	/**
	 * Return the router object.
	 *
	 * Instantiates a Zend_Controller_Router_Rewrite object if no router currently set.
	 *
	 * @return Zend_Controller_Router_Interface
	 */
	public function getRouter() {
		if (null == $this->_router) {
			require_once 'Library/Controller/Router/Rewrite.php';
			$this->setRouter(new Controller_Router_Rewrite());
		}

		return $this->_router;
	}

	public function setDispatcher(Controller_Dispatcher_Interface $dispatcher) {
		$this->_dispatcher = $dispatcher;
		return $this;
	}

	/**
	 * Return the dispatcher object.
	 *
	 * @return Zend_Controller_Dispatcher_Interface
	 */
	public function getDispatcher() {
		/**
		 * Instantiate the default dispatcher if one was not set.
		 */
		if (!$this->_dispatcher instanceof Controller_Dispatcher_Interface) {
			require_once 'Library/Controller/Dispatcher/Standard.php';
			$this->_dispatcher = new Controller_Dispatcher_Standard();
		}
		return $this->_dispatcher;
	}

	/**
	 * Set response class/object
	 *
	 * Set the response object.  The response is a container for action
	 * responses and headers. Usage is optional.
	 *
	 * If a class name is provided, instantiates a response object.
	 *
	 * @param string|Zend_Controller_Response_Abstract $response
	 * @throws Zend_Controller_Exception if invalid response class
	 * @return Zend_Controller_Front
	 */
	public function setResponse($response) {
		if (is_string($response)) {
			if (!class_exists($response)) {
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

	/**
	 * Return the response object.
	 *
	 * @return null|Zend_Controller_Response_Abstract
	 */
	public function getResponse() {
		if (null == $this->_response) {
			require_once 'Library/Controller/Response/Default.php';
			$this->setResponse(new Controller_Response_Default());
		}
		return $this->_response;
	}

	/**
	 * Add or modify a parameter to use when instantiating an action controller
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return Zend_Controller_Front
	 */
	public function setParam($name, $value) {
		$name = (string) $name;
		$this->_invokeParams[$name] = $value;
		return $this;
	}

	/**
	 * Set parameters to pass to action controller constructors
	 *
	 * @param array $params
	 * @return Zend_Controller_Front
	 */
	public function setParams(array $params) {
		$this->_invokeParams = array_merge($this->_invokeParams, $params);
		return $this;
	}

	/**
	 * Retrieve a single parameter from the controller parameter stack
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function getParam($name) {
		if (isset($this->_invokeParams[$name])) {
			return $this->_invokeParams[$name];
		}

		return null;
	}

	/**
	 * Retrieve action controller instantiation parameters
	 *
	 * @return array
	 */
	public function getParams() {
		return $this->_invokeParams;
	}

	/**
	 * Clear the controller parameter stack
	 *
	 * By default, clears all parameters. If a parameter name is given, clears
	 * only that parameter; if an array of parameter names is provided, clears
	 * each.
	 *
	 * @param null|string|array single key or array of keys for params to clear
	 * @return Zend_Controller_Front
	 */
	public function clearParams($name = null) {
		if (null === $name) {
			$this->_invokeParams = array();
		} elseif (is_string($name) && isset($this->_invokeParams[$name])) {
			unset($this->_invokeParams[$name]);
		} elseif (is_array($name)) {
			foreach ($name as $key) {
				if (is_string($key) && isset($this->_invokeParams[$key])) {
					unset($this->_invokeParams[$key]);
				}
			}
		}

		return $this;
	}

	/**
	 * Register a plugin.
	 *
	 * @param  Zend_Controller_Plugin_Abstract $plugin
	 * @param  int $stackIndex Optional; stack index for plugin
	 * @return Zend_Controller_Front
	 */
	public function registerPlugin(Controller_Plugin_Abstract $plugin, $stackIndex = null) {
		$this->_plugins->registerPlugin($plugin, $stackIndex);
		return $this;
	}

	/**
	 * Unregister a plugin.
	 *
	 * @param  string|Zend_Controller_Plugin_Abstract $plugin Plugin class or object to unregister
	 * @return Zend_Controller_Front
	 */
	public function unregisterPlugin($plugin) {
		$this->_plugins->unregisterPlugin($plugin);
		return $this;
	}

	/**
	 * Is a particular plugin registered?
	 *
	 * @param  string $class
	 * @return bool
	 */
	public function hasPlugin($class) {
		return $this->_plugins->hasPlugin($class);
	}

	/**
	 * Retrieve a plugin or plugins by class
	 *
	 * @param  string $class
	 * @return false|Zend_Controller_Plugin_Abstract|array
	 */
	public function getPlugin($class) {
		return $this->_plugins->getPlugin($class);
	}

	/**
	 * Retrieve all plugins
	 *
	 * @return array
	 */
	public function getPlugins() {
		return $this->_plugins->getPlugins();
	}

	/**
	 * Set the throwExceptions flag and retrieve current status
	 *
	 * Set whether exceptions encounted in the dispatch loop should be thrown
	 * or caught and trapped in the response object.
	 *
	 * Default behaviour is to trap them in the response object; call this
	 * method to have them thrown.
	 *
	 * Passing no value will return the current value of the flag; passing a
	 * boolean true or false value will set the flag and return the current
	 * object instance.
	 *
	 * @param boolean $flag Defaults to null (return flag state)
	 * @return boolean|Zend_Controller_Front Used as a setter, returns object; as a getter, returns boolean
	 */
	public function throwExceptions($flag = null) {
		if ($flag !== null) {
			$this->_throwExceptions = (bool) $flag;
			return $this;
		}

		return $this->_throwExceptions;
	}

	/**
	 * Set whether {@link dispatch()} should return the response without first
	 * rendering output. By default, output is rendered and dispatch() returns
	 * nothing.
	 *
	 * @param boolean $flag
	 * @return boolean|Zend_Controller_Front Used as a setter, returns object; as a getter, returns boolean
	 */
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

	/**
	 * Dispatch an HTTP request to a controller/action.
	 *
	 * @param Zend_Controller_Request_Abstract|null $request
	 * @param Zend_Controller_Response_Abstract|null $response
	 * @return void|Zend_Controller_Response_Abstract Returns response object if returnResponse() is true
	 */
	public function dispatch(Controller_Request_Abstract $request = null, Controller_Response_Abstract $response = null) {

		if (!$this->getParam('noErrorHandler') && !$this->_plugins->hasPlugin('Controller_Plugin_ErrorHandler')) {
			// Register with stack index of 100
			require_once 'Library/Controller/Plugin/ErrorHandler.php';
			$this->_plugins->registerPlugin(new Controller_Plugin_ErrorHandler(), 100);
		}

		if (!$this->getParam('noViewRenderer') && !Controller_Action_HelperBroker::hasHelper('viewRenderer')) {
			require_once 'Library/Controller/Action/Helper/ViewRenderer.php';
			Controller_Action_HelperBroker::getStack()->offsetSet(-80, new Controller_Action_Helper_ViewRenderer());
		}

		if (null === $request) {
			$request = $this->getRequest();
		} else {
			$this->setRequest($request);
		}

		if (null === $response) {
			$response = $this->getResponse();
		} else {
			$this->setResponse($response);
		}

		$this->_plugins
				->setRequest($this->_request)
				->setResponse($this->_response);
		$router = $this->getRouter();

		$dispatcher = $this->getDispatcher();
		$dispatcher->setParams($this->getParams())
				->setResponse($this->_response);

		try {

			$this->_plugins->routeStartup($this->_request);

			try {

				$router->route($request);
			} catch (Ecxeption $e) {
				if ($this->throwExceptions()) {
					throw $e;
				}

				$this->_response->setException($e);
			}
			$this->_plugins->routeShutdown($this->_request);

			$this->_plugins->dispatchLoopStartup($this->_request);

			do {
				$this->_request->setDispatched(true);

				/**
				 * Notify plugins of dispatch startup
				 */
				$this->_plugins->preDispatch($this->_request);

				/**
				 * Skip requested action if preDispatch() has reset it
				 */
				if (!$this->_request->isDispatched()) {
					continue;
				}

				/**
				 * Dispatch request
				 */
				try {
					$dispatcher->dispatch($this->_request, $this->_response);
				} catch (Exception $e) {
					if ($this->throwExceptions()) {
						throw $e;
					}
					$this->_response->setException($e);
				}

				/**
				 * Notify plugins of dispatch completion
				 */
				$this->_plugins->postDispatch($this->_request);
			} while (!$this->_request->isDispatched());
		} catch (Exception $e) {
			if ($this->throwExceptions()) {
				throw $e;
			}
			$this->_response->setException($e);
		}

		try {
			$this->_plugins->dispatchLoopShutdown();
		} catch (Exception $e) {
			if ($this->throwExceptions()) {
				throw $e;
			}

			$this->_response->setException($e);
		}

		if ($this->returnResponse()) {
			return $this->_response;
		}

		$this->_response->sendResponse();
	}

}

?>
