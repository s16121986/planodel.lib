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
 * @subpackage Plugins
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Abstract.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @category   Zend
 * @package    Controller
 * @subpackage Plugins
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Controller_Plugin_Abstract{
    /**
     * @var Controller_Request_Abstract
     */
    protected $_request;

    /**
     * @var Controller_Response_Abstract
     */
    protected $_response;

    /**
     * Set request object
     *
     * @param Controller_Request_Abstract $request
     * @return Controller_Plugin_Abstract
     */
    public function setRequest(Controller_Request_Abstract $request)
    {
        $this->_request = $request;
        return $this;
    }

    /**
     * Get request object
     *
     * @return Controller_Request_Abstract $request
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * Set response object
     *
     * @param Controller_Response_Abstract $response
     * @return Controller_Plugin_Abstract
     */
    public function setResponse(Controller_Response_Abstract $response)
    {
        $this->_response = $response;
        return $this;
    }

    /**
     * Get response object
     *
     * @return Controller_Response_Abstract $response
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * Called before Controller_Front begins evaluating the
     * request against its routes.
     *
     * @param Controller_Request_Abstract $request
     * @return void
     */
    public function routeStartup(Controller_Request_Abstract $request)
    {}

    /**
     * Called after Controller_Router exits.
     *
     * Called after Controller_Front exits from the router.
     *
     * @param  Controller_Request_Abstract $request
     * @return void
     */
    public function routeShutdown(Controller_Request_Abstract $request)
    {}

    /**
     * Called before Controller_Front enters its dispatch loop.
     *
     * @param  Controller_Request_Abstract $request
     * @return void
     */
    public function dispatchLoopStartup(Controller_Request_Abstract $request)
    {}

    /**
     * Called before an action is dispatched by Controller_Dispatcher.
     *
     * This callback allows for proxy or filter behavior.  By altering the
     * request and resetting its dispatched flag (via
     * {@link Controller_Request_Abstract::setDispatched() setDispatched(false)}),
     * the current action may be skipped.
     *
     * @param  Controller_Request_Abstract $request
     * @return void
     */
    public function preDispatch(Controller_Request_Abstract $request)
    {}

    /**
     * Called after an action is dispatched by Controller_Dispatcher.
     *
     * This callback allows for proxy or filter behavior. By altering the
     * request and resetting its dispatched flag (via
     * {@link Controller_Request_Abstract::setDispatched() setDispatched(false)}),
     * a new action may be specified for dispatching.
     *
     * @param  Controller_Request_Abstract $request
     * @return void
     */
    public function postDispatch(Controller_Request_Abstract $request)
    {}

    /**
     * Called before Controller_Front exits its dispatch loop.
     *
     * @return void
     */
    public function dispatchLoopShutdown()
    {}
}
