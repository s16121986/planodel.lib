<?php
namespace WebSocket;

class Server extends AbstractSocket {

	protected $connections = array();
	protected $triggers = array();
	protected $enabled = true;
	
	public function __construct($config) {
		parent::__construct($config);
		Exception::init($this);
	}

	public function destroy() {
		$this->exit();
	}
	
	public function __call($name, $arguments) {
		array_unshift($arguments, $name);
		return call_user_func_array(array($this, 'trigger'), $arguments);
	}

	public function start() {
		$this->socket = stream_socket_server($this->getSocketAddress(), $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $this->getSocketContext());
		if (!$this->socket) {
			$this->log("$errstr ($errno)\n");
			return false;
		}
		$this->enabled = true;
		$this->trigger('start');
		$this->log('server started');
		//stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_SERVER);
		$connections = array();
		//$i = time();
		while ($this->enabled) {
			//if (time() - $i > 10) {break;}
			//формируем массив прослушиваемых сокетов:
			$read = $connections;
			$read[] = $this->socket;
			$write = null;
			$except = null;

			@stream_select($read, $write, $except, 0, 5000);
			
			foreach ($read as $socket) {
				if ($socket === $this->socket) {
					$resource = @stream_socket_accept($this->socket);
					if ($resource <= 0) {
						$this->log('Socket error: ' . socket_strerror(socket_last_error()));
						continue;
					}
					//stream_set_blocking($resource, 0);
					$request = new Connection($this, $resource);
					if (false !== $this->trigger('connect', $request)) {
						$this->connections[] = $request;
						$connections[] = $resource;
					} else {
						$this->log('Socket Request error');
					}
				} else {
					$connection = $this->getConnection($socket);
					if (!$connection) {
						unset($connections[array_search($socket, $connections)]);
						continue;
					}
					$connection->execute();
				}
			}
		}
		fclose($this->socket);
		$this->trigger('stop');
		$this->log('server stopped');
	}

	public function trigger() {
		$args = func_get_args();
		$action = array_shift($args);
		foreach ($this->triggers as $trigger) {
			if ($trigger[0] === $action) {
				$r = call_user_func_array($trigger[1], $args);
				if (null !== $r) {
					return $r;
				}
			}
		}
		switch ($action) {
			case 'exit':
				$this->enabled = false;
				break;
			case 'connect':
				return $args[0]->handshake();
			case 'send':
				if (!isset($args[0])) {
					return false;
				}
				$data = $args[0];
				$channel = (isset($args[1]) ? $args[1] : null);
				foreach ($this->connections as $client) {
					if (null === $channel || $client->hasChannel($channel)) {
						$client->send($data);
					}
				}
				break;
			case 'stop':
				//$this->start();
				break;
		}
		return true;
	}

	public function bind($action, $callback, $params = null) {
		$this->triggers[] = array($action, $callback, $params);
		return $this;
	}

	public function onSocketClose($socket) {
		foreach ($this->connections as $i => $client) {
			if ($client->socket === $socket) {
				array_splice($this->connections, $i, 1);
				return true;
			}
		}
		return false;
	}

	protected function getConnection($socket) {
		foreach ($this->connections as $client) {
			if ($client->socket === $socket) {
				return $client;
			}
		}
		return false;
	}

}
