<?php
require_once 'Library/Controller/Router/Route/Module.php';

class Controller_Router_Route_Page extends Controller_Router_Route_Module{
	
	protected function initPage() {
		$dir = array_shift($paths);
		$pf = array('id', 'page_title', 'page_description', 'page_keywords', 'title', 'text', 'module', 'dir');
		$this->page = Db::from($this->pagesDBTable, $pf)
						->where('dir=?', $dir)
						->query()->fetchRow();
		//print_r($this->page);exit;
		if (empty($this->page)) {
			return false;
		}
		$module = $this->page['module'];
		if ($module) {
			$action = (!empty($paths) ? $paths[0] : '');
			$controller = $this->_controllerDir . '/' . $module . '/' . $action . '.php';
			if (file_exists($controller)) {
				array_shift($paths);
			} else {
				$action = $this->defaultAction;
			}
			$this->module = $module;
		} else {
			$module = ($this->page['dir'] ? $this->page['dir'] : $this->defaultAction);
			$action = (!empty($paths) ? $paths[0] : 'index');
			$controller = $this->_controllerDir . '/' . $module . '/' . $action . '.php';
			if (file_exists($controller)) {
				array_shift($paths);
			} else {
				$module = $this->defaultModule;
				$action = $this->defaultAction;
			}
		}
		$this->module = $module;
		$this->action = $action;
		return true;
	}
	
	public function match($path, $partial = false) {
        $this->_setRequestKeys();

        $values = array();
        $params = array();

        if (!$partial) {
            $path = trim($path, self::URI_DELIMITER);
        } else {
            $matchedPath = $path;
        }
		
		
		$paths = explode(self::URI_DELIMITER, $path);
		
		$this->initPage($paths);
		
		return $this->_values + $this->_defaults;

        if ($path != '') {
            
			
			

            if ($this->_dispatcher && $this->_dispatcher->isValidModule($path[0])) {
                $values[$this->_moduleKey] = array_shift($path);
                $this->_moduleValid = true;
            }

            if (count($path) && !empty($path[0])) {
                $values[$this->_controllerKey] = array_shift($path);
            }

            if (count($path) && !empty($path[0])) {
                $values[$this->_actionKey] = array_shift($path);
            }

            if ($numSegs = count($path)) {
                for ($i = 0; $i < $numSegs; $i = $i + 2) {
                    $key = urldecode($path[$i]);
                    $val = isset($path[$i + 1]) ? urldecode($path[$i + 1]) : null;
                    $params[$key] = (isset($params[$key]) ? (array_merge((array) $params[$key], array($val))): $val);
                }
            }
        }

        if ($partial) {
            $this->setMatchedPath($matchedPath);
        }

        $this->_values = $values + $params;

        return $this->_values + $this->_defaults;
    }
	
}
?>
