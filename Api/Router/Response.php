<?php
namespace Api\Router;

use Exception;

class Response extends Result{
	
	protected $options = array(
		'format' => 'json'
	);
	protected $httpCode = 200;
	protected $results = array();
	
	public function addResult(Result $result) {
		$this->results[] = $result;
		return $this;
	}
	
	public function getResults() {
		return $this->results;
	}
	
	public function setHttpCode($code) {
		$this->httpCode = $code;
		return $this;
	}
	
	public function send() {
		header('HTTP/1.1 ' . $this->httpCode);
		$format = new Response\Json($this);
		echo $format->getContent();
	}
	
}