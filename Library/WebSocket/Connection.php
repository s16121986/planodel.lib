<?php

namespace WebSocket;

class Connection {

	protected $server;
	protected $socket;
	protected $ip;
	protected $port;
	protected $method = null;
	protected $uri;
	protected $headers = array();
	protected $meta = array();
	protected $data = array();
	protected $channels = array();
	private $waitingForData = false;
	private $_dataBuffer = '';

	public function __construct($server, $socket) {
		$this->server = $server;
		$this->socket = $socket;
		$address = explode(':', stream_socket_get_name($socket, true)); //получаем адрес клиента
		$this->ip = $address[0];
		$this->port = $address[1];
		$name = 'request';
		$data = $this->readBuffer();
		$lines = preg_split("/\r\n/", $data);
		foreach ($lines as $line) {
			switch ($name) {
				case 'request':
					$p = explode(' ', rtrim($line));
					if (!isset($p[1])) {
						break 2;
					}
					$this->method = $p[0];
					$this->uri = $p[1];
					$name = 'header';
					break;
				case 'header':
					$line = rtrim($line);
					if ($line && preg_match('/^(.*?): (.*)$/', $line, $matches)) {
						$this->headers[$matches[1]] = $matches[2];
					} else {
						break 2;
					}
					break;
				/* case 'data':
				  if ($line) {
				  $data = null;
				  parse_str($line, $data);
				  if ($data) {
				  $this->data = $data;
				  }
				  }
				  $name = false;
				  break 2; */
			}
		}
		//$this->meta = stream_get_meta_data($socket);
	}

	public function __get($name) {
		if (isset($this->$name))
			return $this->$name;
		if (isset($this->data[$name]))
			return $this->data[$name];
		return null;
	}

	public function hasChannel($channel) {
		return in_array($channel, $this->channels);
	}

	public function subscribe($channel) {
		if (!$this->hasChannel($channel)) {
			$this->channels[] = $channel;
		}
		return $this;
	}

	public function getHeader($name) {
		return (isset($this->headers[$name]) ? $this->headers[$name] : null);
	}

	public function isValid() {
		return ($this->method !== null);
	}

	public function handshake() {
		$key = $this->getHeader('Sec-WebSocket-Key');
		if (empty($key)) {
			return false;
		}
		$header = $this->getHeader('Sec-WebSocket-Version');
		if ($header < 6) {
			$this->log('Unsupported websocket version.');
			$this->close(501);
			return false;
		}

		// check origin:
		if ($this->server->checkOrigin === true) {
			$origin = $this->getHeader('Origin');
			if (!$origin) {
				$origin = $this->getHeader('Sec-WebSocket-Origin');
			}

			if ($origin === null) {
				$this->log('No origin provided.');
				$this->close(401);
				return false;
			}

			if (empty($origin)) {
				$this->log('Empty origin provided.');
				$this->close(401);
				return false;
			}

			if ($this->server->checkOrigin($origin) === false) {
				$this->log('Invalid origin provided.');
				$this->close(401);
				return false;
			}
		}
		//отправляем заголовок согласно протоколу вебсокета
		$SecWebSocketAccept = base64_encode(pack('H*', sha1($this->getHeader('Sec-WebSocket-Key') . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
		$upgrade = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
			"Upgrade: websocket\r\n" .
			"Connection: Upgrade\r\n" .
			"Sec-WebSocket-Accept: $SecWebSocketAccept\r\n\r\n";
		/* if (isset($headers['Sec-WebSocket-Protocol']) && !empty($headers['Sec-WebSocket-Protocol'])) {
		  $response.= "Sec-WebSocket-Protocol: " . substr($path, 1) . "\r\n";
		  } */
		return $this->writeBuffer($upgrade);
	}

	public function send($payload, $type = 'text', $masked = false) {
		if (!$this->writeBuffer(Util::encode($payload, $type, $masked))) {
			$this->close();
			return false;
		}
		return true;
	}

	public function close($statusCode = null) {
		$payload = '';
		switch ($statusCode) {
			case 1000:
				$payload .= 'normal closure';
				break;

			case 1001:
				$payload .= 'going away';
				break;

			case 1002:
				$payload .= 'protocol error';
				break;

			case 1003:
				$payload .= 'unknown data (opcode)';
				break;

			case 1004:
				$payload .= 'frame too large';
				break;

			case 1007:
				$payload .= 'utf8 expected';
				break;

			case 1008:
				$payload .= 'message violates server policy';
				break;
		}
		if ($payload) {
			$pd = str_split(sprintf('%016b', $statusCode), 8);
			$pd[0] = chr(bindec($pd[0]));
			$pd[1] = chr(bindec($pd[1]));
			$payload = implode('', $pd) . $payload;
			if ($this->send($payload, 'close', false) === false) {
				return false;
			}
		} else {
			$this->sendHttpResponse($statusCode);
		}
		$this->server->onSocketClose($this->socket);
		stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
		//fclose($this->socket);
	}

	public function execute() {
		//$status = $this->server->trigger('request', $this);
		$data = $this->readBuffer();
		$bytes = strlen($data);
		if ($bytes === 0) {
			$this->close(1000);
		} elseif ($data === false) {
			$this->close();
		} else {
			if ($this->waitingForData === true) {
				$data = $this->_dataBuffer . $data;
				$this->_dataBuffer = '';
				$this->waitingForData = false;
			}

			$decodedData = Util::decode($data);

			if ($decodedData === false) {
				$this->waitingForData = true;
				$this->_dataBuffer .= $data;
				return false;
			} else {
				$this->_dataBuffer = '';
				$this->waitingForData = false;
			}

			// trigger status application:
			/* if ($this->server->getApplication('status') !== false) {
			  $this->server->getApplication('status')->clientActivity($this->port);
			  } */

			if (!isset($decodedData['type'])) {
				$this->close(401);
				return false;
			}
			switch ($decodedData['type']) {
				case 'text':
					$this->server->trigger('message', $this, $decodedData['payload']);
					break;
				case 'binary':
					$this->close(1003);
					break;
				case 'ping':
					$this->send($decodedData['payload'], 'pong', false);
					$this->log('Ping? Pong!');
					break;
				case 'pong':
					// server currently not sending pings, so no pong should be received.
					break;
				case 'close':
					$this->close();
					$this->log('Disconnected');
					break;
			}

			return true;
		}
		return false;
	}

	public function sendHttpResponse($httpStatusCode = 400) {
		switch ($httpStatusCode) {
			case 400:
				$httpHeader = '400 Bad Request';
				break;
			case 401:
				$httpHeader = '401 Unauthorized';
				break;
			case 403:
				$httpHeader = '403 Forbidden';
				break;
			case 404:
				$httpHeader = '404 Not Found';
				break;
			case 501:
				$httpHeader = '501 Not Implemented';
				break;
			default:
				return;
		}
		$httpHeader .= "\r\n";
		$this->writeBuffer('HTTP/1.1 ' . $httpHeader);
	}

	public function writeBuffer($string) {
		$stringLength = strlen($string);
		for ($written = 0; $written < $stringLength; $written += $fwrite) {
			$fwrite = @fwrite($this->socket, substr($string, $written));
			if ($fwrite === false) {
				return false;
			} elseif ($fwrite === 0) {
				return false;
			}
		}
		return $written;
	}

	protected function readBuffer() {
		if ($this->server->ssl) {
			$buffer = fread($this->socket, 8192);
			// extremely strange chrome behavior: first frame with ssl only contains 1 byte?!
			if (strlen($buffer) === 1) {
				$buffer .= fread($this->socket, 8192);
			}
			return $buffer;
		} else {
			$buffer = '';
			$buffsize = 8192;
			$metadata['unread_bytes'] = 0;
			do {
				if (feof($this->socket)) {
					return false;
				}
				$result = fread($this->socket, $buffsize);
				if ($result === false || feof($this->socket)) {
					return false;
				}
				$buffer .= $result;
				$metadata = stream_get_meta_data($this->socket);
				$buffsize = ($metadata['unread_bytes'] > $buffsize) ? $buffsize : $metadata['unread_bytes'];
			} while ($metadata['unread_bytes'] > 0);

			return $buffer;
		}
	}

	protected function log($message, $type = null) {
		//Exception::log('[client ' . $this->ip . ':' . $this->port . '] ' . $message, $type);
	}

}
