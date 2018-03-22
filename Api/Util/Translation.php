<?php
namespace Api\Util;

use Translation as AbstractTranslation;

abstract class Translation extends AbstractTranslation{
	
	public static function getColumn($name, $code = null) {
		$language = self::getLanguage($code);
		if ($language && !$language->default) {
			return $name . '_' . $language->code;
		}
		return $name;
	}
	
}