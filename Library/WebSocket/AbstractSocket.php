<?php
namespace WebSocket;

abstract class AbstractSocket {
	
	protected $socket;
	protected $config = array(
		'host' => 'localhost',
		'port' => 8000,
		'ssl' => false,
		'checkOrigin' => false
	);

	public function __construct($config) {
		$this->config = array_merge($this->config, $config);
	}
	
	public function __get($name) {
		return (isset($this->config[$name]) ? $this->config[$name] : null);
	}
	
	public function __set($name, $value) {
		$this->config[$name] = $value;
	}
	
	protected function getSocketAddress() {
		return ($this->ssl ? 'tls' : 'tcp') . '://' . $this->host . ':' . $this->port;
	}
	
	protected function getSocketContext() {
		if (!$this->ssl) {
			return null;
		}
		$context = stream_context_create();
		foreach ($this->ssl as $k => $v) {
			stream_context_set_option($context, 'ssl', $k, $v);
		}
		//stream_context_set_option($context, 'ssl', 'cafile', '/etc/ssl/private/private.key');
		/*stream_context_set_option($context, 'ssl', 'local_cert', $pemfile);
		stream_context_set_option($context, 'ssl', 'passphrase', $pem_passphrase);
		stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
		stream_context_set_option($context, 'ssl', 'verify_peer', false);*/
		return $context;
	}

	protected function log($message, $type = null) {
		Exception::log($message, $type);
	}

}
