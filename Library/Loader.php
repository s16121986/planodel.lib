<?php
class Loader{

	public static function loadApi($class, $create = true) {
		//$incPath = get_include_path();
		//set_include_path('Api');
		
		if (preg_match('/[^a-z0-9_]/i', $class)) {
			require_once 'Api/Exception.php';
            throw new Api_Exception('Security check: Illegal character in filename');
        }
		$className = 'Api_' . $class;
		//echo $className;exit;
		if (!class_exists($className)) {
			$incPath = get_include_path();
			
			//echo $incPath . 'sdf';exit;
			$file = $incPath . '/Api/Classes/';
			$file .= str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
			
			if (!file_exists($file)) {
				require_once 'Api/Exception.php';
				throw new Api_Exception("Module \"$file\" does not exist");
			}
			if (!class_exists('Api')) {
				require 'Api/Api.php';
				//self::loadClass('Api', 'Api');
			}
			require_once $file;
		}
		//set_include_path($incPath);
		if ($create) {
			return new $className();
		}
	}

	public static function loadClass($class, $dirs = '') {
	    if (class_exists($class, false) || interface_exists($class, false)) {
            return;
        }
        if ((null !== $dirs) && !is_string($dirs) && !is_array($dirs)) {
            require_once 'Library/Exception.php';
            throw new AppException('Directory argument must be a string or an array');
        }

        $className = ltrim($class, '\\');
        $file      = '';
        $namespace = '';
        if ($lastNsPos = strripos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $file      = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $file .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
		$file = 'Library/' . $file;
        if (!empty($dirs)) {
            // use the autodiscovered path
            $dirPath = dirname($file);
            if (is_string($dirs)) {
                $dirs = explode(PATH_SEPARATOR, $dirs);
            }
            foreach ($dirs as $key => $dir) {
                if ($dir == '.') {
                    $dirs[$key] = $dirPath;
                } else {
                    $dir = rtrim($dir, '\\/');
                    $dirs[$key] = $dir . DIRECTORY_SEPARATOR . $dirPath;
                }
            }
            $file = basename($file);
			
            self::loadFile($file, $dirs, true);
        } else {
            self::loadFile($file, null, true);
        }
		return ;
        if (!class_exists($class, false) && !interface_exists($class, false)) {
            throw new Exception("File \"$file\" does not exist or class \"$class\" was not found in the file");
        }
    }

    public static function loadFile($filename, $dirs = null, $once = false) {
        self::_securityCheck($filename);
        $incPath = false;
        if (!empty($dirs) && (is_array($dirs) || is_string($dirs))) {
            if (is_array($dirs)) {
                $dirs = implode(PATH_SEPARATOR, $dirs);
            }
            $incPath = get_include_path();
			
            set_include_path($dirs . PATH_SEPARATOR . $incPath);
			//echo get_include_path() . '<br />';
        }
        if ($once) {
            include_once $filename;
        } else {
            include $filename;
        }

        if ($incPath) {
            set_include_path($incPath);
        }

        return true;
    }

    public static function isReadable($filename) {
        if (is_readable($filename)) {
            // Return early if the filename is readable without needing the
            // include_path
            return true;
        }

        foreach (self::explodeIncludePath() as $path) {
            if ($path == '.') {
                if (is_readable($filename)) {
                    return true;
                }
                continue;
            }
            $file = $path . '/' . $filename;
            if (is_readable($file)) {
                return true;
            }
        }
        return false;
    }

    public static function explodeIncludePath($path = null) {
        if (null === $path) {
            $path = get_include_path();
        }

        if (PATH_SEPARATOR == ':') {
            // On *nix systems, include_paths which include paths with a stream
            // schema cannot be safely explode'd, so we have to be a bit more
            // intelligent in the approach.
            $paths = preg_split('#:(?!//)#', $path);
        } else {
            $paths = explode(PATH_SEPARATOR, $path);
        }
        return $paths;
    }

    protected static function _securityCheck($filename) {
        if (preg_match('/[^a-z0-9\\/\\\\_.:-]/i', $filename)) {
            require_once 'Exception.php';
            throw new AppException('Security check: Illegal character in filename');
        }
    }

    protected static function _includeFile($filespec, $once = false) {
        if ($once) {
            return include_once $filespec;
        } else {
            return include $filespec ;
        }
    }

}
?>
