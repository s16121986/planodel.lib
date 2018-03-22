<?php

require_once 'Library/Controller/Dispatcher/Abstract.php';

require_once 'Library/Controller/Action.php';

class Controller_Dispatcher_Standard extends Controller_Dispatcher_Abstract {

	protected $_curDirectory;
	protected $_curModule;
	protected $_controllerDirectory = array(
		'default' => 'application/controllers'
	);
	protected $_invokeParams = array(
		'useDefaultControllerAlways' => true,
		'prefixDefaultModule' => false
	);

	public function formatModuleName($unformatted) {
		if (($this->_defaultModule == $unformatted) && !$this->getParam('prefixDefaultModule')) {
			return $unformatted;
		}

		return ucfirst($this->_formatName($unformatted));
	}

	public function addControllerDirectory($path, $module = null) {
		if (null === $module) {
			$module = $this->_defaultModule;
		}

		$module = (0 === $module ? '' : (string) $module);
		$path = rtrim((string) $path, '/\\');

		$this->_controllerDirectory[$module] = $path;
		return $this;
	}

	public function setControllerDirectory($directory, $module = null) {
		$this->_controllerDirectory = array();

		if (is_string($directory)) {
			$this->addControllerDirectory($directory, $module);
		} elseif (is_array($directory)) {
			foreach ((array) $directory as $module => $path) {
				$this->addControllerDirectory($path, $module);
			}
		} else {
			require_once 'Library/Controller/Exception.php';
			throw new Controller_Exception('Controller directory spec must be either a string or an array');
		}

		return $this;
	}

	public function getControllerDirectory($module = null) {
		if (null === $module) {
			return $this->_controllerDirectory;
		}

		$module = (string) $module;
		if (array_key_exists($module, $this->_controllerDirectory)) {
			return $this->_controllerDirectory[$module];
		}

		return null;
	}

	public function formatClassName($moduleName, $className) {
		return $this->formatModuleName($moduleName) . '_' . $className;
	}

	public function classToFilename($class) {
		return str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
	}

	public function isDispatchable(Controller_Request_Abstract $request) {
		$className = $this->getControllerClass($request);
		if (!$className) {
			return false;
		}

		$finalClass = $className;
		if (($this->_defaultModule != $this->_curModule) || $this->getParam('prefixDefaultModule')) {
			$finalClass = $this->formatClassName($this->_curModule, $className);
		}
		if (class_exists($finalClass, false)) {
			return true;
		}

		$fileSpec = $this->classToFilename($className);
		$dispatchDir = $this->getDispatchDirectory();
		$test = $dispatchDir . DIRECTORY_SEPARATOR . $fileSpec;
		return Loader::isReadable($test);
	}

	public function dispatch(Controller_Request_Abstract $request, Controller_Response_Abstract $response) {
		$this->setResponse($response);
		/**
		 * Get controller class
		 */
		if (!$this->isDispatchable($request)) {
			$controller = $request->getControllerName();
			if (!$this->getParam('useDefaultControllerAlways') && !empty($controller)) {
				require_once 'Library/Controller/Dispatcher/Exception.php';
				throw new Controller_Dispatcher_Exception('Invalid controller specified (' . $request->getControllerName() . ')');
			}
			$className = $this->getDefaultControllerClass($request);
		} else {
			$className = $this->getControllerClass($request);
			if (!$className) {
				$className = $this->getDefaultControllerClass($request);
			}
		}

		$this->includeInitController();
		/**
		 * Load the controller class file
		 */
		$className = $this->loadClass($className);
		/**
		 * Instantiate controller with request, response, and invocation
		 * arguments; throw exception if it's not an action controller
		 */
		$controller = new $className($request, $this->getResponse(), $this->getParams());
		if (!($controller instanceof Controller_Action_Interface) &&
			!($controller instanceof Controller_Action)) {
			require_once 'Library/Controller/Dispatcher/Exception.php';
			throw new Controller_Dispatcher_Exception(
			'Controller "' . $className . '" is not an instance of Controller_Action_Interface'
			);
		}
		/**
		 * Retrieve the action name
		 */
		$action = $this->getActionMethod($request);

		/**
		 * Dispatch the method call
		 */
		$request->setDispatched(true);

		// by default, buffer output
		$disableOb = true; //$this->getParam('disableOutputBuffering');
		$obLevel = ob_get_level();
		if (empty($disableOb)) {
			ob_start();
		}
		try {
			$return = $controller->dispatch($action);
			if (null !== $return) {
				$response->appendBody($return);
			}
		} catch (Exception $e) {
			// Clean output buffer on error
			$curObLevel = ob_get_level();
			if ($curObLevel > $obLevel) {
				do {
					ob_get_clean();
					$curObLevel = ob_get_level();
				} while ($curObLevel > $obLevel);
			}
			throw $e;
		}
		if (empty($disableOb)) {
			$content = ob_get_clean();
			$response->appendBody($content);
		}
		// Destroy the page controller instance and reflection objects
		$controller = null;
	}

	public function includeInitController() {
		if (!class_exists('InitController')) {
			$dispatchDir = $this->getDispatchDirectory();
			$loadFile = $dispatchDir . DIRECTORY_SEPARATOR . 'InitController.php';
			if (Loader::isReadable($loadFile)) {
				include_once $loadFile;
			}
		}
	}

	public function loadClass($className) {
		$finalClass = $className;
		if (($this->_defaultModule != $this->_curModule) && false !== $this->getParam('prefixDefaultModule')) {
			$finalClass = $this->formatClassName($this->_curModule, $className);
		}
		if (class_exists($finalClass, false)) {
			return $finalClass;
		}

		$dispatchDir = $this->getDispatchDirectory();
		$loadFile = $dispatchDir . DIRECTORY_SEPARATOR . $this->classToFilename($className);

		if (Loader::isReadable($loadFile)) {
			include_once $loadFile;
		} else {
			require_once 'Library/Controller/Dispatcher/Exception.php';
			throw new Controller_Dispatcher_Exception('Cannot load controller class "' . $className . '" from file "' . $loadFile . "'");
		}

		if (!class_exists($finalClass, false)) {
			require_once 'Library/Controller/Dispatcher/Exception.php';
			throw new Controller_Dispatcher_Exception('Invalid controller class ("' . $finalClass . '")');
		}

		return $finalClass;
	}

	public function getControllerClass(Controller_Request_Abstract $request) {
		$controllerName = $request->getControllerName();
		if (empty($controllerName)) {
			if (!$this->getParam('useDefaultControllerAlways')) {
				return false;
			}
			$controllerName = $this->getDefaultControllerName();
			$request->setControllerName($controllerName);
		}

		$className = $this->formatControllerName($controllerName);

		$controllerDirs = $this->getControllerDirectory();
		$module = $request->getModuleName();
		if ($this->isValidModule($module)) {
			$this->_curModule = $module;
			$this->_curDirectory = $controllerDirs[$module];
		} elseif ($this->isValidModule($this->_defaultModule)) {
			$request->setModuleName($this->_defaultModule);
			$this->_curModule = $this->_defaultModule;
			$this->_curDirectory = $controllerDirs[$this->_defaultModule];
		} else {
			require_once 'Library/Controller/Exception.php';
			throw new Controller_Exception('No default module defined for this application');
		}

		return $className;
	}

	public function isValidModule($module) {
		if (!is_string($module)) {
			return false;
		}

		//$module = strtolower($module);
		$controllerDir = $this->getControllerDirectory();
		foreach (array_keys($controllerDir) as $moduleName) {
			if ($module === ($moduleName)) {
				return true;
			}
		}

		return false;
	}

	public function getDefaultControllerClass(Controller_Request_Abstract $request) {
		$controller = $this->getDefaultControllerName();
		$default = $this->formatControllerName($controller);
		$request->setControllerName($controller)
			->setActionName(null);

		$module = $request->getModuleName();
		$controllerDirs = $this->getControllerDirectory();
		$this->_curModule = $this->_defaultModule;
		$this->_curDirectory = $controllerDirs[$this->_defaultModule];
		if ($this->isValidModule($module)) {
			$found = false;
			if (class_exists($default, false)) {
				$found = true;
			} else {
				$moduleDir = $controllerDirs[$module];
				$fileSpec = $moduleDir . DIRECTORY_SEPARATOR . $this->classToFilename($default);
				if (Loader::isReadable($fileSpec)) {
					$found = true;
					$this->_curDirectory = $moduleDir;
				}
			}
			if ($found) {
				$request->setModuleName($module);
				$this->_curModule = $this->formatModuleName($module);
			}
		} else {
			$request->setModuleName($this->_defaultModule);
		}

		return $default;
	}

	public function getDispatchDirectory() {
		return $this->_curDirectory;
	}

	public function getActionMethod(Controller_Request_Abstract $request) {
		$action = $request->getActionName();
		if (empty($action)) {
			$action = $this->getDefaultAction();
			$request->setActionName($action);
		}

		return $this->formatActionName($action);
	}

}
