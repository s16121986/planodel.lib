<?php
require_once 'Library/Controller/Response/Http.php';


/**
 * Zend_Controller_Response_Http
 *
 * HTTP response for controllers
 *
 * @uses Zend_Controller_Response_Abstract
 * @package Zend_Controller
 * @subpackage Response
 */
class Controller_Response_Default extends Controller_Response_Http{

	protected $HTTP_ACCEPT_ENCODING = true;

	public function sendResponse() {
        $this->sendHeaders();
        if ($this->isException() && $this->renderExceptions()) {
            $exceptions = '';
            foreach ($this->getException() as $e) {
                $exceptions .= $e->__toString() . "\n";
            }
            echo $exceptions;
            return;
        }

        $this->outputBody();
    }

	protected static function prepareContent($content) {
		$content = preg_replace_callback(
			'/{lang:(.*)}/U',
			create_function(
			'$matches',
			'return lang($matches[1]);'
			),
			$content
		);
		$content = preg_replace_callback(
			'/{var:(.*)}/U',
			create_function(
				'$matches',
				'return Api\Service\Variables::get($matches[1]);'
			),
			$content
		);
		return $content;

		//preg_match_all('/{lang:(.*)}/U', $content, $m, PREG_SET_ORDER);
		//var_dump($m);exit;
	}

	public function outputBody() {
		$body = self::prepareContent(implode('', $this->_body));
		$this->setRawHeader("Content-Length: " . strlen($body));
		$this->sendHeaders();
        echo $body;
    }
}