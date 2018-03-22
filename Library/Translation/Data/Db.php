<?php
namespace Translation\Data;

use Db as DbObj;
use Exception;
use Api\Exception as ApiException;
use Api\Attribute\Exception as AttributeException;

class Db extends AbstractData{
	
	private $items = null;
	
	public function getItems() {
		if (null === $this->items) {
			$this->items = array();
			$q = DbObj::from('translation_items', array('name', 'value_' . $this->language->code . ' as value'))
				->where('`path`' . ($this->path ? '="' . $this->path . '"' : ' IS NULL'))
				->query();
			while ($r = $q->fetch()) {
				$this->items[$r['name']] = $r['value'];
			}
		}
		return $this->items;
	}
	
	public function getContent($value, $path = 'item') {
		$this->getItems();
		switch (true) {
			case $value instanceof AttributeException:
				$value = AttributeException::getErrorKey($value->getCode()) . '_' . $value->attribute;
				$path = 'error';
				break;
			case $value instanceof ApiException:
				$value = ApiException::getErrorKey($value->getCode());
				$path = 'error';
				break;
			case $value instanceof Exception:
				$value = 'error_unknown';
				$path = 'error';
				break;
		}
		list($value, $path) = self::formatValue($value, $path);
		return (isset($this->items[$value]) ? $this->items[$value] : null);
	}
	
}
