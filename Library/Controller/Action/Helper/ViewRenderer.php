<?php

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Controller
 * @subpackage Controller_Action_Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: ViewRenderer.php 23775 2011-03-01 17:25:24Z ralph $
 */
/**
 * @see Controller_Action_Helper_Abstract
 */
require_once 'Library/Controller/Action/Helper/Abstract.php';

/**
 * @see View
 */
//require_once 'Library/View.php';

/**
 * View script integration
 *
 * Controller_Action_Helper_ViewRenderer provides transparent view
 * integration for action controllers. It allows you to create a view object
 * once, and populate it throughout all actions. Several global options may be
 * set:
 *
 * - noController: if set true, render() will not look for view scripts in
 *   subdirectories named after the controller
 * - viewSuffix: what view script filename suffix to use
 *
 * The helper autoinitializes the action controller view preDispatch(). It
 * determines the path to the class file, and then determines the view base
 * directory from there. It also uses the module name as a class prefix for
 * helpers and views such that if your module name is 'Search', it will set the
 * helper class prefix to 'Search_View_Helper' and the filter class prefix to ;
 * 'Search_View_Filter'.
 *
 * Usage:
 * <code>
 * // In your bootstrap:
 * Controller_Action_HelperBroker::addHelper(new Controller_Action_Helper_ViewRenderer());
 *
 * // In your action controller methods:
 * $viewHelper = $this->_helper->getHelper('view');
 *
 * // Don't use controller subdirectories
 * $viewHelper->setNoController(true);
 *
 * // Specify a different script to render:
 * $this->_helper->viewRenderer('form');
 *
 * </code>
 *
 * @uses       Controller_Action_Helper_Abstract
 * @package    Controller
 * @subpackage Controller_Action_Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Controller_Action_Helper_ViewRenderer extends Controller_Action_Helper_Abstract {

	/**
	 * @var View_Interface
	 */
	public $view;

	/**
	 * Word delimiters
	 * @var array
	 */
	protected $_delimiters;

	/**
	 * @var Filter_Inflector
	 */
	protected $_inflector;

	/**
	 * Inflector target
	 * @var string
	 */
	protected $_inflectorTarget = '';

	/**
	 * Current module directory
	 * @var string
	 */
	protected $_moduleDir = '';

	/**
	 * Whether or not to autorender using controller name as subdirectory;
	 * global setting (not reset at next invocation)
	 * @var boolean
	 */
	protected $_neverController = false;

	/**
	 * Whether or not to autorender postDispatch; global setting (not reset at
	 * next invocation)
	 * @var boolean
	 */
	protected $_neverRender = false;

	/**
	 * Whether or not to use a controller name as a subdirectory when rendering
	 * @var boolean
	 */
	protected $_noController = false;

	/**
	 * Whether or not to autorender postDispatch; per controller/action setting (reset
	 * at next invocation)
	 * @var boolean
	 */
	protected $_noRender = false;

	/**
	 * Characters representing path delimiters in the controller
	 * @var string|array
	 */
	protected $_pathDelimiters;

	/**
	 * Which named segment of the response to utilize
	 * @var string
	 */
	protected $_responseSegment = null;

	/**
	 * Which action view script to render
	 * @var string
	 */
	protected $_scriptController = null;
	protected $_scriptAction = null;

	/**
	 * View object basePath
	 * @var string
	 */
	protected $_viewBasePathSpec = ':moduleDir/views';

	/**
	 * View script path specification string
	 * @var string
	 */
	protected $_viewScriptPathSpec = ':controller/:action.:suffix';

	/**
	 * View script path specification string, minus controller segment
	 * @var string
	 */
	protected $_viewScriptPathNoControllerSpec = ':action.:suffix';

	/**
	 * View script suffix
	 * @var string
	 */
	protected $_viewSuffix = 'phtml';
	protected $_viewsDir;

	/**
	 * Constructor
	 *
	 * Optionally set view object and options.
	 *
	 * @param  View_Interface $view
	 * @param  array               $options
	 * @return void
	 */
	public function __construct(View_Interface $view = null, array $options = array()) {
		if (null !== $view) {
			$this->setView($view);
		}

		if (!empty($options)) {
			$this->_setOptions($options);
		}
	}

	/**
	 * init - initialize view
	 *
	 * @return void
	 */
	public function init() {
		if ($this->getFrontController()->getParam('noViewRenderer')) {
			return;
		}

		if ((null !== $this->_actionController) && (null === $this->_actionController->view)) {
			$this->_actionController->view = $this->view;
			//$this->_actionController->viewSuffix = $this->_viewSuffix;
		}

		//$this->initView();
	}

	/**
	 * Get current module name
	 *
	 * @return string
	 */
	public function getModule() {
		$request = $this->getRequest();
		$module = $request->getModuleName();
		if (null === $module) {
			$module = $this->getFrontController()->getDispatcher()->getDefaultModule();
		}

		return $module;
	}

	/**
	 * Get module directory
	 *
	 * @throws Controller_Action_Exception
	 * @return string
	 */
	public function getModuleDirectory() {
		$module = $this->getModule();
		$moduleDir = $this->getFrontController()->getControllerDirectory($module);
		if ((null === $moduleDir) || is_array($moduleDir)) {
			/**
			 * @see Controller_Action_Exception
			 */
			require_once 'Library/Controller/Action/Exception.php';
			throw new Controller_Action_Exception('ViewRenderer cannot locate module directory for module "' . $module . '"');
		}
		$this->_moduleDir = dirname($moduleDir);
		return $this->_moduleDir;
	}

    public function setNoRender($flag = true) {
        $this->_noRender = ($flag) ? true : false;
        return $this;
    }

	/**
	 * Set internal module directory representation
	 *
	 * @param  string $dir
	 * @return void
	 */
	protected function _setModuleDir($dir) {
		$this->_moduleDir = (string) $dir;
	}

	/**
	 * Get internal module directory representation
	 *
	 * @return string
	 */
	protected function _getModuleDir() {
		return $this->_moduleDir;
	}

	/**
	 * Generate a class prefix for helper and filter classes
	 *
	 * @return string
	 */
	protected function _generateDefaultPrefix() {
		$default = 'View';
		if (null === $this->_actionController) {
			return $default;
		}

		$class = get_class($this->_actionController);

		if (!strstr($class, '_')) {
			return $default;
		}

		$module = $this->getModule();
		if ('default' == $module) {
			return $default;
		}

		$prefix = substr($class, 0, strpos($class, '_')) . '_View';

		return $prefix;
	}

	/**
	 * Retrieve base path based on location of current action controller
	 *
	 * @return string
	 */
	protected function _getBasePath() {
		if (null === $this->_actionController) {
			return './views';
		}

		$inflector = $this->getInflector();
		$this->_setInflectorTarget($this->getViewBasePathSpec());

		$dispatcher = $this->getFrontController()->getDispatcher();
		$request = $this->getRequest();

		$parts = array(
			'module' => (($moduleName = $request->getModuleName()) != '') ? $dispatcher->formatModuleName($moduleName) : $moduleName,
			'controller' => $request->getControllerName(),
			'action' => $dispatcher->formatActionName($request->getActionName())
		);

		$path = $inflector->filter($parts);
		return $path;
	}

	/**
	 * Set options
	 *
	 * @param  array $options
	 * @return Controller_Action_Helper_ViewRenderer Provides a fluent interface
	 */
	protected function _setOptions(array $options) {
		foreach ($options as $key => $value) {
			switch ($key) {
				case 'neverRender':
				case 'neverController':
				case 'noController':
				case 'noRender':
					$property = '_' . $key;
					$this->{$property} = ($value) ? true : false;
					break;
				case 'responseSegment':
				case 'scriptAction':
				case 'viewBasePathSpec':
				case 'viewScriptPathSpec':
				case 'viewScriptPathNoControllerSpec':
				case 'viewSuffix':
					$property = '_' . $key;
					$this->{$property} = (string) $value;
					break;
				default:
					break;
			}
		}

		return $this;
	}

	protected function _getViewsDir() {
		if (null === $this->_viewsDir) {
			$dispatcher = $this->getFrontController()->getDispatcher();
			$this->_viewsDir = $this->getModuleDirectory()
					//. ((($moduleName = $this->getRequest()->getModuleName()) != '') ? $dispatcher->formatModuleName($moduleName) : $moduleName)
					. DIRECTORY_SEPARATOR . 'views';
		}
		return $this->_viewsDir;
	}

	public function setScriptController($name) {
		$this->_scriptController = (string) $name;
		return $this;
	}

	public function setScriptAction($name) {
		$this->_scriptAction = (string) $name;
		return $this;
	}

	/**
	 * Retrieve view script name
	 *
	 * @return string
	 */
	public function getScriptAction() {
		return $this->_scriptAction;
	}

	public function getViewScript($controller = null, $action = null) {
		if (null === $action) {
			$action = $controller;
			$controller = null;
		}
		if (null === $controller) {
			$controller = $this->_scriptController;
		}
		$request = $this->getRequest();
		$dispatcher = $this->getFrontController()->getDispatcher();
		$parts = array(
			//'module'     => (($moduleName = $request->getModuleName()) != '') ? $dispatcher->formatModuleName($moduleName) : $moduleName,
			'controller' => ($controller ? $controller : $request->getControllerName())
		);
		if (null === $action) {
			$action = $this->getScriptAction();
			if (null === $action) {
				$action = $dispatcher->formatActionName($request->getActionName());
				$action = str_replace('Action', '', $action);
			}
		}
		return $this->_getViewsDir() . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
				. strtolower($parts['controller']) . DIRECTORY_SEPARATOR
				. strtolower($action) . '.' . $this->_viewSuffix;
	}

	/**
	 * Render a view script (optionally to a named response segment)
	 *
	 * Sets the noRender flag to true when called.
	 *
	 * @param  string $script
	 * @param  string $name
	 * @return void
	 */
	public function renderScript($script, $__name = null) {
		if (null === $__name) {
			//$name = $this->getResponseSegment();
		}
		ob_start();
		include $script;
		$this->getResponse()->appendBody(ob_get_clean(), $__name);

		//$this->setNoRender();
	}

	public function layout($script, $append = false) {
		$path = $this->_getViewsDir() . DIRECTORY_SEPARATOR . 'layouts'
				. DIRECTORY_SEPARATOR . $script
				. '.' . $this->_viewSuffix;
		if ($append) {
			ob_start();
			include $path;
			$this->getResponse()->appendBody(ob_get_clean());
		} else {
			include $path;
		}
	}

	public function renderTemplate($controller = null, $action = null) {
		$path = $this->getViewScript($controller, $action);
		include $path;
	}

	/*public function globalTemplate($script) {
		$moduleDir = 'application/controllers';//$this->getFrontController()->getControllerDirectory('default');
		$path = dirname($moduleDir)
				. DIRECTORY_SEPARATOR . 'views'
				. DIRECTORY_SEPARATOR . 'global'
				. DIRECTORY_SEPARATOR . $script
				. '.' . $this->_viewSuffix;
		include $path;
	}

	public function layoutInclude($script) {
		$path = $this->_getViewsDir() . DIRECTORY_SEPARATOR . 'layouts'
				. DIRECTORY_SEPARATOR . $script
				. '.' . $this->_viewSuffix;
		include $path;
	}*/

	/**
	 * Render a view based on path specifications
	 *
	 * Renders a view based on the view script path specifications.
	 *
	 * @param  string  $action
	 * @param  string  $name
	 * @param  boolean $noController
	 * @return void
	 */
	public function render($action = null, $name = null, $noController = null) {
		//$this->setRender($action, $name, $noController);
		$path = $this->getViewScript($action);
		if ($this->layout) {
			ob_start();
			include $path;
			$content = ob_get_clean();
			ob_start();
			include $this->_getViewsDir()
				. DIRECTORY_SEPARATOR . 'layouts'
				. DIRECTORY_SEPARATOR . $this->layout
				. '.' . $this->_viewSuffix;
			$this->getResponse()->appendBody(ob_get_clean());
		} else {
			if (null === $action && isset($this->_actionController->renderHeader) && $this->_actionController->renderHeader) {
				$this->layout($this->_actionController->renderHeader, true);
			}
			$this->renderScript($path, $name);
			if (null === $action && isset($this->_actionController->renderFooter) && $this->_actionController->renderFooter) {
				$this->layout($this->_actionController->renderFooter, true);
			}
		}
	}

	/**
	 * postDispatch - auto render a view
	 *
	 * Only autorenders if:
	 * - _noRender is false
	 * - action controller is present
	 * - request has not been re-dispatched (i.e., _forward() has not been called)
	 * - response is not a redirect
	 *
	 * @return void
	 */
	public function postDispatch() {
		if ($this->_shouldRender()) {
			$this->render();
		}
	}

	/**
	 * Should the ViewRenderer render a view script?
	 *
	 * @return boolean
	 */
	protected function _shouldRender() {
		return (!$this->getFrontController()->getParam('noViewRenderer')
				&& !$this->_neverRender
				&& !$this->_noRender
				&& (null !== $this->_actionController)
				&& (true !== $this->_actionController->noRender)
				&& $this->getRequest()->isDispatched()
				&& !$this->getResponse()->isRedirect()
				);
	}

	public function __call($action, $arguments) {
		if (null !== $this->_actionController) {
			return call_user_func_array(array($this->_actionController, $action), $arguments);
		}
		return null;
	}

	public function __get($name) {
		if (null !== $this->_actionController) {
			return $this->_actionController->$name;
		}
		return null;
	}

}
