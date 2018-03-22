<?php
namespace Api\Util;

use Db;
use Api\Util\Translation;

class Setup{
	
	public static function field($attribute, $field = null) {
		if (null === $field) {
			$field = $attribute->name;
		}
		$s = '`' . $field . '` ';
		switch ($attribute->name) {
			case 'id':return $s . 'int(11) unsigned NOT NULL AUTO_INCREMENT';
			case 'created':return $s . 'timestamp NOT NULL DEFAULT "0000-00-00 00:00:00"';
			case 'updated':return $s . 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
		}
		switch ($attribute->getType()) {
			case \AttributeType::Url:
			case \AttributeType::String:
			case \AttributeType::Password:
				if ($attribute->length) {
					$s .= 'VARCHAR(' . $attribute->length . ')';
				} else {
					$s .= 'TEXT';
				}
				break;
			case \AttributeType::Boolean:
				$s .= 'TINYINT(1) unsigned';
				break;
			case \AttributeType::Date:
				switch ($attribute->dateFractions) {
					case 'date':$s .= 'date';break;
					case 'time':$s .= 'time';break;
					default:$s .= 'timestamp';
				}
				break;
			case \AttributeType::Table:
			case \AttributeType::Model:
				$s .= 'INT(11) unsigned';
				break;
			case \AttributeType::Enum:
				$s .= 'TINYINT(2) unsigned';
				break;
			case \AttributeType::Year:
				$s .= 'SMALLINT(4) unsigned';
				break;
			case \AttributeType::Number:
				$s .= 'INT(11) unsigned';
				break;
				
		}
		if ($attribute->notnull) {
			$s .= ' NOT NULL';
		}
		if ($attribute->default) {
			$s .= ' DEFAULT "' . $attribute->default . '"';
		}
		return $s;
	}
	
	public static function create($api) {
		$s = 'CREATE TABLE IF NOT EXISTS `' . $api->table . '` (';
		foreach ($api->getAttributes() as $attribute) {
			$s .= self::field($attribute) . ',';
			if ($attribute->locale) {
				foreach (Translation::getLanguages() as $lang) {
					if ($lang->default) continue;
					$s .= self::field($attribute, $attribute->name . '_' . $lang->code) . ',';
				}
			}
		}
		$s .= 'PRIMARY KEY (`id`)'
			. ') ENGINE=InnoDB  DEFAULT CHARSET=utf8';
		Db::query($s);
		/*
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` tinyint(2) unsigned DEFAULT NULL,
  `parent_id` tinyint(3) unsigned DEFAULT NULL,
  `dir` varchar(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `text` text NOT NULL,
  `index` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `deletable` tinyint(1) NOT NULL DEFAULT '1',
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  KEY `parent_id` (`parent_id`),
  KEY `site_id` (`site_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;*/
		//ALTER TABLE  `ref_howitworks` CHANGE  `anonce_en`  `anonce_en` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  ''
		//ALTER TABLE  `page_options` ADD INDEX (  `type` )
	}
	
	public function db($api) {
		$tables = Db::query('SHOW TABLES')->fetchAll(Db::FETCH_COLUMN);
		if (!in_array($api->table, $tables)) {
			return self::create($api);
		}
		$fields = Db::query('SHOW FIELDS FROM `' . $api->table . '`')->fetchAll(Db::FETCH_NAMED, 'Field');
		foreach ($api->getAttributes() as $attribute) {
			if (!isset($fields[$attribute->name])) {
				Db::query('ALTER TABLE `' . $api->table . '` ADD ' . self::field($attribute));
			}
			if ($attribute->locale) {
				$after = $attribute->name;
				foreach (Translation::getLanguages() as $lang) {
					if ($lang->default) continue;
					$field = $attribute->name . '_' . $lang->code;
					if (!isset($fields[$field])) {
						Db::query('ALTER TABLE `' . $api->table . '` ADD ' . self::field($attribute, $field) . ' AFTER `' . $after . '`');
						if ($attribute->required && !$attribute->default) {
							Db::query('UPDATE `' . $api->table . '` SET `' . $field . '`=`' . Translation::getColumn($attribute->name) . '`');
						}
					}
					$after = $field;
				}
			}
		}
	}
	
}