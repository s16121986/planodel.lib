<?php
class Vars{
	
	private static $variables = array();

	public static function get($key, $tpl = null) {
		if (!isset(self::$variables[$key])) {
			$string = Db::from('system_variables', 'value')
					->where('`key`=?', $key)
					->query()->fetchColumn();
			if ($string && $tpl) {
				$string = Format::formatTemplate($string, $tpl);
			}
			self::$variables[$key] = $string;
		}
		return self::$variables[$key];
	}

	public static function set($key, $value) {
		return \Db::write('system_variables', array('value' => $value), '`key`="' . $key . '"');
	}
	
	public static function select() {
		$vars = array();
		$q = Db::from('system_variables')->query();
		while ($var = $q->fetch()) {
			$vars[$var['key']] = $var;
		}
		$q->getResource()->free();
		return $vars;
	}

}