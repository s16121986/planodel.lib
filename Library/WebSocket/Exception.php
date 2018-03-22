<?php
namespace WebSocket;

abstract class Exception{
	
	protected static $socket;
	protected static $setupFlag = false;
	
	public static function init($socket) {
		self::$socket = $socket;

		if (!self::$setupFlag) {
			
			ini_set('error_reporting', 0); //E_ERROR
			ini_set('display_errors', 'Off');
			ini_set('display_startup_errors', 'Off');
			
			ini_set('html_errors', 'Off');
			ini_set('docref_root', '');
			ini_set('docref_ext', '');

			ini_set('log_errors', 'On');
			ini_set('log_errors_max_len', 0);
			ini_set('ignore_repeated_errors', 'Off');
			ini_set('ignore_repeated_source', 'Off');
			ini_set('report_memleaks', 'Off');
			ini_set('track_errors', 'On');
			ini_set('xmlrpc_errors', 'Off');
			ini_set('xmlrpc_error_number', 'Off');
			ini_set('error_prepend_string', '');
			ini_set('error_append_string', '');
			
			self::$setupFlag = true;
			$errorTypesHandle = E_ALL | E_STRICT;
			set_error_handler(__CLASS__ . '::errorHandler', $errorTypesHandle);
			set_exception_handler(__CLASS__ . '::exceptionHandler');
			register_shutdown_function(__CLASS__ . '::shutdownHandler');
			assert_options(ASSERT_ACTIVE, 1);
			assert_options(ASSERT_WARNING, 0);
			assert_options(ASSERT_BAIL, 0);
			assert_options(ASSERT_QUIET_EVAL, 0);
			assert_options(ASSERT_CALLBACK, __CLASS__ . '::assertionHandler');
		}
	}

	public static function shutdownHandler() {
		self::log('shutdown');
		$error = error_get_last();
		if (isset($error)) {
			if ($error['type'] == E_ERROR || $error['type'] == E_PARSE || $error['type'] == E_COMPILE_ERROR || $error['type'] == E_CORE_ERROR) {
				$exception = new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
				self::log($exception);
			}
		}
		self::$socket->destroy();
	}

	public static function exceptionHandler($exception) {
		self::log($exception);
	}
	
	public static function errorHandler($severity, $message, $file, $line) {
		if (error_reporting() === 0) {
			return;
		}
		if ($severity & E_ALL) {
			$exception = new \ErrorException($message, 0, $severity, $file, $line);
			self::log($exception);
		}
	}
	
	public static function assertionHandler($file, $line, $message) {
		$exception = new ErrorException($message, 0, self::$assertionErrorType, $file, $line);
		self::log($exception, \Exception\Log::assertion);
	}
	
	public static function log($message, $type = null) {
		if (empty(self::$socket->logfile)) {
			
			return;
		}
		if ($message instanceof Exception) {
			$message = $message->getMessage();
		}
		$message = '[' . date('d-M-Y H:i:s') . '] ' . ($type ? $type . ': ' : '') . $message . PHP_EOL;
		error_log($message, 3, self::$socket->logfile);
	}
	
}