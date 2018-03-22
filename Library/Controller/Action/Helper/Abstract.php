<?php

/**
 * @category   Zend
 * @package    Controller
 * @subpackage Controller_Action_Helper
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Controller_Action_Helper_Abstract {

	/**
	 * $_actionController
	 *
	 * @var Controller_Action $_actionController
	 */
	protected $_actionController = null;

	/**
	 * @var mixed $_frontController
	 */
	protected $_frontController = null;

	/**
	 * setActionController()
	 *
	 * @param  Controller_Action $actionController
	 * @return Controller_ActionHelper_Abstract Provides a fluent interface
	 */
	public function setActionController(Controller_Action $actionController = null) {
		$this->_actionController = $actionController;
		return $this;
	}

	/**
	 * Retrieve current action controller
	 *
	 * @return Controller_Action
	 */
	public function getActionController() {
		return $this->_actionController;
	}

	/**
	 * Retrieve front controller instance
	 *
	 * @return Controller_Front
	 */
	public function getFrontController() {
		return Controller_Front::getInstance();
	}

	/**
	 * Hook into action controller initialization
	 *
	 * @return void
	 */
	public function init() {

	}

	/**
	 * Hook into action controller preDispatch() workflow
	 *
	 * @return void
	 */
	public function preDispatch() {

	}

	/**
	 * Hook into action controller postDispatch() workflow
	 *
	 * @return void
	 */
	public function postDispatch() {

	}

	/**
	 * getRequest() -
	 *
	 * @return Controller_Request_Abstract $request
	 */
	public function getRequest() {
		$controller = $this->getActionController();
		if (null === $controller) {
			$controller = $this->getFrontController();
		}

		return $controller->getRequest();
	}

	/**
	 * getResponse() -
	 *
	 * @return Controller_Response_Abstract $response
	 */
	public function getResponse() {
		$controller = $this->getActionController();
		if (null === $controller) {
			$controller = $this->getFrontController();
		}

		return $controller->getResponse();
	}

	/**
	 * getName()
	 *
	 * @return string
	 */
	public function getName() {
		$fullClassName = get_class($this);
		if (strpos($fullClassName, '_') !== false) {
			$helperName = strrchr($fullClassName, '_');
			return ltrim($helperName, '_');
		} elseif (strpos($fullClassName, '\\') !== false) {
			$helperName = strrchr($fullClassName, '\\');
			return ltrim($helperName, '\\');
		} else {
			return $fullClassName;
		}
	}

}
