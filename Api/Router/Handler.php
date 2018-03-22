<?php
namespace Api\Router;

class Handler{
	
	protected $request;
	protected $response;
	protected $paths = array();
	protected $params = array();
	protected $options = array(
		'basePath' => '/api',
		//'next'
	);
	private $query = false;
	private $current = null;
	
	public static function factory($options = null) {
		return new self($options);
	}
	
	public function __construct($options = null) {
		$this->request = new Request($this);
		$this->response = new Response();
		if (is_array($options)) {
			foreach ($options as $k => $v) {
				$this->$k = $v;
			}
		}
	}
	
	public function __get($name) {
		if (isset($this->$name)) {
			return $this->$name;
		}
		return (isset($this->options[$name]) ? $this->options[$name] : null);
	}
	
	public function __set($name, $value) {
		$this->options[$name] = $value;
	}
	
	public function execute() {
		$this->next();
		if (!$this->query) {
			$this->response
				->setException(new \Exception('Action not found', 404));
		}
		$this->response->send();
	}
	
	public function next() {
		if (null === $this->current) {
			$this->current = 0;
		} else {
			$this->current++;
		}
		if (isset($this->paths[$this->current])) {
			$path = $this->paths[$this->current];
			if (null === $path->query()) {
				$this->next();
			} else {
				$this->query = true;
			}
		}
		
	}
	
	public function param($name, $handler = null) {
		$this->params[$name] = new Param($name, $handler);
		return $this;
	}
	
	public function getParam($name) {
		return (isset($this->params[$name]) ? $this->params[$name] : null);
	}
    
    public function all() {
		return $this->_path('all', func_get_args());
	}
    
    public function get() {
		return $this->_path('get', func_get_args());
	}
    
    public function post() {
		return $this->_path('post', func_get_args());
	}
    
    public function put() {
		return $this->_path('put', func_get_args());
	}
    
    public function delete() {
		return $this->_path('delete', func_get_args());
	}
	
	private function _path($method, $args) {
		$request = new Path($this, $method, $args);
		$this->paths[] = $request;
		return $this;
	}

}