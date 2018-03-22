<?php
require_once 'Library/Controller/Router/Abstract.php';

class Controller_Router_Rewrite extends Controller_Router_Abstract{
	
	protected $_useDefaultRoutes = true;

    /**
     * Array of routes to match against
     *
     * @var array
     */
    protected $_routes = array();

    /**
     * Currently matched route
     *
     * @var Zend_Controller_Router_Route_Interface
     */
    protected $_currentRoute = null;

    /**
     * Global parameters given to all routes
     *
     * @var array
     */
    protected $_globalParams = array();

    /**
     * Separator to use with chain names
     *
     * @var string
     */
    protected $_chainNameSeparator = '-';

    /**
     * Determines if request parameters should be used as global parameters
     * inside this router.
     *
     * @var boolean
     */
    protected $_useCurrentParamsAsGlobal = false;
	
    public function addDefaultRoutes() {
        if (!$this->hasRoute('default')) {
            $dispatcher = $this->getFrontController()->getDispatcher();
            $request = $this->getFrontController()->getRequest();

            require_once 'Library/Controller/Router/Route/Module.php';
            $compat = new Controller_Router_Route_Module(array(), $dispatcher, $request);

            $this->_routes = array('default' => $compat) + $this->_routes;
        }

        return $this;
    }
	
	public function route(Controller_Request_Abstract $request) {
		
		if (!$request instanceof Controller_Request_Http) {
            require_once 'Library/Controller/Router/Exception.php';
            throw new Controller_Router_Exception('Controller_Router_Rewrite requires a Zend_Controller_Request_Http-based request object');
        }

        if ($this->_useDefaultRoutes) {
            $this->addDefaultRoutes();
        }

        // Find the matching route
        $routeMatched = false;

        foreach (array_reverse($this->_routes, true) as $name => $route) {
            // TODO: Should be an interface method. Hack for 1.0 BC
            if (method_exists($route, 'isAbstract') && $route->isAbstract()) {
                continue;
            }

            // TODO: Should be an interface method. Hack for 1.0 BC
            if (!method_exists($route, 'getVersion') || $route->getVersion() == 1) {
                $match = $request->getPathInfo();
            } else {
                $match = $request;
            }

            if ($params = $route->match($match)) {
                $this->_setRequestParams($request, $params);
                $this->_currentRoute = $name;
                $routeMatched        = true;
                break;
            }
        }

         if (!$routeMatched) {
             require_once 'Library/Controller/Router/Exception.php';
             throw new Controller_Router_Exception('No route matched the request', 404);
         }

        if($this->_useCurrentParamsAsGlobal) {
            $params = $request->getParams();
            foreach($params as $param => $value) {
                $this->setGlobalParam($param, $value);
            }
        }

        return $request;
	}
	
    protected function _setRequestParams($request, $params) {
        foreach ($params as $param => $value) {

            $request->setParam($param, $value);

            if ($param === $request->getModuleKey()) {
                $request->setModuleName($value);
            }
            if ($param === $request->getControllerKey()) {
                $request->setControllerName($value);
            }
            if ($param === $request->getActionKey()) {
                $request->setActionName($value);
            }

        }
    }
	
    public function hasRoute($name) {
        return isset($this->_routes[$name]);
    }
	
}
?>
