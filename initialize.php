<?php

if (!defined('LIB_PATH')) die('LIB_PATH undefined');
if (!defined('INCLUDE_PATH'))	define('INCLUDE_PATH', __DIR__);
if (!defined('MODELS_PATH'))	define('MODELS_PATH', LIB_PATH . '/models');
if (!defined('FILES_PATH'))		define('FILES_PATH', realpath(LIB_PATH . '/../files'));

set_include_path(INCLUDE_PATH);

spl_autoload_register(function($class) {
	$path = explode('\\', $class);
	if ($path[0] === 'Api') {
		switch ($path[1]) {
			case 'Model':
				unset($path[0], $path[1]);
				$filename = MODELS_PATH . '/' . implode('/', $path) . '.php';
				break;
			default:
				$filename = INCLUDE_PATH . '/' . implode('/', $path). '.php';
		}
		if (file_exists($filename)) {
			require $filename;
			return true;
		}
	}
	return false;
});
spl_autoload_register(function ($class) {
	$filename = INCLUDE_PATH . '/Library/' . str_replace('\\', '/', $class). '.php';
	if (file_exists($filename)) {
		require $filename;
		return true;
	}
	return false;
});
include 'functions.php';
include 'Api/Api.php';

if (!defined('EXCEPTION_HANDLER') || EXCEPTION_HANDLER) {
	Exception\Handler::$debug = defined('DEBUG_MODE') && DEBUG_MODE;
	//Exception\Handler::$scream = true;
	Exception\Handler::setupEnvironment();
	Exception\Handler::setupHandlers();
	Exception\Handler::$exceptionLog = new \Exception\Log\Email('gsv@keysoft.su');
}

include LIB_PATH . '/enums.php';
if (isset(Cfg::$db)) {
	Db::init(Cfg::$db);
}
Translation::addLanguage('ru', array('name' => 'Русский', 'locale' => 'ru_RU.utf8'));
Dater::init('Europe/Moscow');