<?php
require_once 'Library/Controller/Router/Interface.php';

abstract class Controller_Router_Abstract implements Controller_Router_Interface{
	
	protected $_frontController;
	
    public function getFrontController() {
        // Used cache version if found
        if (null !== $this->_frontController) {
            return $this->_frontController;
        }

        require_once 'Library/Controller/Front.php';
        $this->_frontController = Controller_Front::getInstance();
        return $this->_frontController;
    }
	
    public function setFrontController(Controller_Front $controller) {
        $this->_frontController = $controller;
        return $this;
    }
	
}
?>
