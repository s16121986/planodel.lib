<?php

class Output {

	private static $outputTypes = array(
		'css' => 'text/css',
		'js' => 'application/x-javascript',
		'pdf' => 'application/pdf',
		'default' => 'plain/text'
	);
	private $_body = array();
	private $_includePath = null;

	public static function factory($includePath = null) {
		return new self($includePath);
	}

	public static function getRequestHeaders() {
		if (function_exists("apache_request_headers")) {
			if (($headers = apache_request_headers())) {
				return $headers;
			}
		}
		$headers = array();
		// Grab the IF_MODIFIED_SINCE header
		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
			$headers['If-Modified-Since'] = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
		}
		return $headers;
	}

	public static function outputHeaders($destination, $time = null) {
		if ($time === null) {
			$time = 2592000;//month
		}
		header('Cache-Control: private, max-age=' . $time . ', pre-check=' . $time);
		header("Pragma: private");
		header("Expires: " . date("r", time() + $time));

		$fileModTime = filemtime($destination);

		$headers = self::getRequestHeaders();

		if (isset($headers['If-Modified-Since']) && (strtotime($headers['If-Modified-Since']) == $fileModTime)) {
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $fileModTime) . ' GMT', true, 304);
			exit;
		}
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $fileModTime) . ' GMT', true, 200);
		return $fileModTime;
	}

	private function __construct($includePath = null) {
		if ($includePath) {
			$this->setIncludePath($includePath);
		}
	}

	private function _readDir($dir, $type, $deep = true) {
		$dh = opendir($dir);
		$dir = $dir . DIRECTORY_SEPARATOR;
		$dirs = array();
		while (false !== ($file = readdir($dh))) {
			if (in_array($file, array('.', '..')))
				continue;
			if (is_dir($dir . $file)) {
				if ($deep) {
					$dirs[] = $dir . $file;
				}
			} else {
				if (null === $type || substr($file, strrpos($file, '.')) == ('.' . $type)) {
					if (!file_exists($dir . $file)) {
						die($dir . $file);
					}
					$this->addFile($dir . $file, $type);
				}
			}
		}
		foreach ($dirs as $dir) {
			$this->_readDir($dir . $file, $type, $deep);
		}
	}

	//deprecated
	private function gzipContent($content) {
		$pack = (isset($_SERVER["HTTP_ACCEPT_ENCODING"]) && strpos($_SERVER["HTTP_ACCEPT_ENCODING"], 'gzip') !== false);
		if ($pack) {
			header("Content-Encoding: gzip");
			$content = gzencode($content, 9);
			header("Content-Length: " . strlen($content));
			echo $content;
		} else {
			header("Content-Length: " . strlen($content));
			echo $content;
		}
		exit;
	}

	public function setIncludePath($path) {
		$this->_includePath = $path;
		return $this;
	}

	public function addFiles($files, $type = null) {
		if (!is_array($files)) {
			$files = array($files);
		}
		foreach ($files as $file) {
			$this->addFile($file, $type);
		}
		return $this;
	}

	public function addFile($file, $type = null) {
		if (is_string($file)) {
			if ($this->_includePath) {
				$file = $this->_includePath . $file;
			}
			if (isset($this->_body[$file])) {
				return $this;
			}
			$deep = false;
			if (strpos($file, '*')) {
				$deep = true;
				$file = substr($file, 0, -2);
			}
			if (is_dir($file)) {
				$this->_readDir($file, $type, $deep);
			} else {
				$this->_body[$file] = file_get_contents($file);
			}
		} elseif ($file instanceof File\AbstractFile) {
			$this->_body[] = $file->getData();
		}
		return $this;
	}

	public function addBody($body) {
		$this->_body[] = $body;
		return $this;
	}

	public function getBody() {
		return implode("\n", $this->_body);
	}

	public function setBody($body) {
		$this->_body = array($body);
		return $this;
	}

	public function outputImage($image) {
		if (!$image->exists()) {
			return;
		}
		$fileModTime = self::outputHeaders($image->fullname);
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $fileModTime) . ' GMT', true, 200);
		header('Pragma: public');
		$maxAge = 29030400;
		header('Cache-Control: public, max-age=' . $maxAge);
		header('Expires: ' . gmdate('r', time() + $maxAge));
		header('Content-type: ' . $image->mime_type);
		header('Content-Disposition: inline; filename="' . $image->name . '"');
		header('Content-transfer-encoding: binary');
		header("Content-Length: " . $image->getSize());
		echo $image->getContents();
		exit;
	}

	public function outputPdf($pdf) {
		if (!$pdf->exists()) {
			return;
		}
		$fileModTime = self::outputHeaders($pdf->fullname);
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $fileModTime) . ' GMT', true, 200);
		header('Pragma: public');
		$maxAge = 29030400;
		header('Cache-Control: public, max-age=' . $maxAge);
		header('Expires: ' . gmdate('r', time() + $maxAge));
		header('Content-type: ' . $pdf->mime_type);
		header('Content-Disposition: inline; filename="' . $pdf->name . '"');
		header('Content-transfer-encoding: binary');
		header("Content-Length: " . $pdf->getSize());
		echo $pdf->getContents();
		exit;
	}

	public function outputFile($file) {
		if (!$file->exists()) {
			return;
		}
		switch (true) {
			case false !== strpos($file->mime_type, 'image'):
				return $this->outputImage($file);
			case false !== strpos($file->mime_type, 'pdf'):
				return $this->outputPdf($file);
		}
		header("Content-type: " . $file->mime_type);
		header('Content-Disposition: attachment; filename="' . $file->name . '"');
		header("Content-Length: " . $file->getSize());
		if (($h = fopen($file->fullname, 'r'))) {
			while (!feof($h)) {
				echo fread($h, 1024);
			}
			fclose($h);
		}
		exit;
	}

	public function output($outputType = 'default') {
		if ($outputType instanceof \File\AbstractFile) {
			return $this->outputFile($outputType);
		} elseif (null === $outputType) {
			return;
		}
		if (isset(self::$outputTypes[$outputType])) {
			header('Pragma: public');
			$maxAge = 29030400;
			header('Cache-Control: public, max-age=' . $maxAge);
			header('Vary: Accept-Encoding');
			header('Expires: ' . gmdate('r', time() + $maxAge));
		} else {
			$outputType = 'default';
		}
		header('Content-type: ' . self::$outputTypes[$outputType]);
		$content = $this->getBody();
		header("Content-Length: " . strlen($content));
		echo $content;
		exit;
	}

}
