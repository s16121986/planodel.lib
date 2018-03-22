<?php
namespace Api\Acl\Resource;

require_once 'Api/Acl/Assert.php';

class Registry{

	private $_registry = array();

	public function get(\Api $object) {
		$objectId = self::resourceId($object);
		if (!isset($this->_registry[$objectId])) {
			$assertTemp = self::objectName($object) . '\\Assert';
			$assertClass = 'Api\\' . $assertTemp;
			if (!class_exists($assertClass, false)) {
				$assertFile = 'models/' . str_replace('\\', DIRECTORY_SEPARATOR, $assertTemp) . '.php';
				if (\Loader::isReadable($assertFile)) {
					\Loader::loadFile($assertFile);
				} else {
					if (!class_exists('\Api\Acl\Assert', false)) {
						require_once 'Api/Acl/Assert.php';
					}
					$assertClass = '\\Api\\Acl\\Assert';
				}
				/*if (!class_exists($assertClass)) {
					require_once 'Api/Exception.php';
					throw new Api_Exception('Cant find class "' . $assertClass . '" ("' . $assertFile . '")');
					return;
				}*/
			}
			$this->_registry[$objectId] = new $assertClass($object);
		}
		return $this->_registry[$objectId];
	}

	private static function resourceId(\Api $object) {
		return self::objectName($object) . '_' . $object->getId();
	}

	private static function objectName(\Api $object) {
		return $object->getModelName();
	}

}
?>
