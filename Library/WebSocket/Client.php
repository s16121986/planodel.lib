<?php
namespace WebSocket;

class Client extends AbstractSocket {
	
	private $_connected = false;
	
	public function __construct($config) {
		parent::__construct($config);
		Exception::init($this);
	}
	
	public function __destruct() {
		$this->destroy();
	}

	public function destroy() {
		return $this->disconnect();
	}

	public function send($data, $type = 'text', $masked = true) {
		if ($this->_connected === false) {
			trigger_error("Not connected", E_USER_WARNING);
			return false;
		}
		if (!is_string($data)) {
			trigger_error("Not a string data was given.", E_USER_WARNING);
			return false;
		}
		if (strlen($data) == 0) {
			return false;
		}
		$res = @fwrite($this->socket, Util::encode($data, $type, $masked));
		if ($res === 0 || $res === false) {
			return false;
		}
		$buffer = ' ';
		//while ($buffer !== '') {
		//	$buffer = fread($this->socket, 512); // drop?
		//}
		//var_dump($buffer);

		return true;
	}

	public function connect($uri) {
		$this->socket = stream_socket_client($this->getSocketAddress(), $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $this->getSocketContext());
		//$this->socket = fsockopen(($this->ssl ? 'tls://' : '') . $this->host, $this->port, $errno, $errstr, 2);
		if (!$this->socket) {
			$this->log("$errstr ($errno)\n");
			return false;
		}
		//socket_set_timeout($this->socket, 0, 10000);
		$this->uri = $uri;
		$key = base64_encode(self::_generateRandomString(16, false, true));
		$header = "GET " . $this->uri . " HTTP/1.1\r\n";
		$header.= "Host: " . $this->host . ":" . $this->port . "\r\n";
		$header.= "Upgrade: websocket\r\n";
		$header.= "Connection: Upgrade\r\n";
		$header.= "Sec-WebSocket-Key: " . $key . "\r\n";
		if ($this->origin) {
			$header.= "Sec-WebSocket-Origin: " . $this->origin . "\r\n";
		}
		$header.= "Sec-WebSocket-Version: 13\r\n";
		@fwrite($this->socket, $header);
		$response = @fread($this->socket, 1500);
		preg_match('/Sec-WebSocket-Accept:\s(.*)$/mU', $response, $matches);
		if ($matches) {
			$keyAccept = trim($matches[1]);
			$expectedResonse = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
			$this->_connected = ($keyAccept === $expectedResonse) ? true : false;
		}

		return $this->_connected;
	}

	public function checkConnection() {
		$this->_connected = false;

		// send ping:
		$data = 'ping?';
		@fwrite($this->socket, Util::encode($data, 'ping', true));
		$response = @fread($this->socket, 300);
		if (empty($response)) {
			return false;
		}
		$response = Util::decode($response);
		if (!is_array($response)) {
			return false;
		}
		if (!isset($response['type']) || $response['type'] !== 'pong') {
			return false;
		}
		$this->_connected = true;
		return true;
	}

	public function disconnect() {
		$this->_connected = false;
		is_resource($this->socket) and fclose($this->socket);
	}

	public function reconnect() {
		sleep(10);
		$this->_connected = false;
		fclose($this->socket);
		$this->connect();
	}

	private static function _generateRandomString($length = 10, $addSpaces = true, $addNumbers = true) {
		$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"В§$%&/()=[]{}';
		$useChars = array();
		// select some random chars:    
		for ($i = 0; $i < $length; $i++) {
			$useChars[] = $characters[mt_rand(0, strlen($characters) - 1)];
		}
		// add spaces and numbers:
		if ($addSpaces === true) {
			array_push($useChars, ' ', ' ', ' ', ' ', ' ', ' ');
		}
		if ($addNumbers === true) {
			array_push($useChars, rand(0, 9), rand(0, 9), rand(0, 9));
		}
		shuffle($useChars);
		$randomString = trim(implode('', $useChars));
		$randomString = substr($randomString, 0, $length);
		return $randomString;
	}

}
