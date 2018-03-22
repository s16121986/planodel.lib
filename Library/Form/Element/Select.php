<?php
namespace Form\Element;

class Select extends Xhtml{

	const EMPTY_VALUE = '';

	private static $_autoTextKeys = array('text', 'name', 'presentation');
	private static $_autoValueKeys = array('id', 'key', 'value');

	protected $_groups = null;
	protected $_items = null;
	protected $_attributes = array('size', 'multiple');
	protected $_options = array(
		'valueInList' => true,
		'valueIndex' => 'id',
		'textIndex' => 'name',
		'groupIndex' => '',
		'emptyItem' => false,
		'emptyValue' => null,
		'emptyItemValue' => '',
		
		'allowNotExists' => false,
		'idKey' => 'id',
		'nameKey' => 'name'
	);
	
	private static function getValueId($value) {
		if (is_string($value) && preg_match('/^[1-9]\d*$/', $value)) {
			$value = (int)$value;
		}
		return $value;
	}

	private static function getAutoKey($autoKeys, $item) {
		foreach ($autoKeys as $k) {
			if (isset($item[$k])) {
				return $item[$k];
			}
		}
	}

	protected function initItem($value, $text = '') {
		if (is_array($text)) {
			$value = $text;
			$text = null;
		}
		$item = new \stdClass();
		if (is_array($value)) {
			$data = $value;
			$value = isset($data[$this->valueIndex]) ? $data[$this->valueIndex] : self::getAutoKey(self::$_autoValueKeys, $data);
			$text = isset($data[$this->textIndex]) ? $data[$this->textIndex] : self::getAutoKey(self::$_autoTextKeys, $data);
		} else {
			$data = array();
		}
		$data['value'] = $value;
		$data['text'] = $text;
		foreach ($data as $k => $v) {
			$item->$k = $v;
		}
		$this->_items[] = $item;
	}

	protected function initGroup($text = '') {
		$item = new \stdClass();
		if (is_array($text)) {
			$data = $text;
			$text = isset($data[$this->textIndex]) ? $data[$this->textIndex] : self::getAutoKey(self::$_autoTextKeys, $data);
		} else {
			$data = array();
		}
		$data['text'] = $text;
		foreach ($data as $k => $v) {
			$item->$k = $v;
		}
		if (!isset($item->id)) $item->id = null;
		$this->_groups[] = $item;
	}

	protected function getDBItems($data) {
		if (isset($data['table'])) {
			$data = array_merge(array(
				'value' => 'id',
				'text' => 'name',
				'where' => '1',
				'order' => 'name'
			), $data);
			$fields = array($data['value'], $data['text']);
			$order = $data['order'];
			return \Db::from($data['table'], $fields)
					->where($data['where'])
					->order($order)
					->query()->fetchAll();
		}
		return array();
	}
	
	public function getGroups() {
		if (null === $this->_groups) {
			$this->_groups = array();
			$itemsTemp = array();
			$itemsData = $this->groups;
			if (is_array($itemsData)) {
				$itemsTemp = $this->getDBItems($itemsData);
				unset($itemsData['where'], $itemsData['order'], $itemsData['text'], $itemsData['value'], $itemsData['table']);
			}
			if (is_array($itemsData)) {
				foreach ($itemsData as $v) {
					$this->initGroup($v);
				}
			}
			foreach ($itemsTemp as $v) {
				$this->initGroup($v);
			}
		}
		return $this->_groups;
	}

	public function getItems() {
			//if (isset($_GET['test']) && $this->name == 'param16') var_dump($this->_items);
		if (null === $this->_items) {
			$this->_items = array();
			$itemsTemp = array();
			$itemsData = $this->items;
			if (is_array($itemsData)) {
				$itemsTemp = $this->getDBItems($itemsData);
				unset($itemsData['where'], $itemsData['order'], $itemsData['text'], $itemsData['value'], $itemsData['table']);
			}
			if (is_array($itemsData)) {
				foreach ($itemsData as $k => $v) {
					$this->initItem($k, $v);
				}
			}
			foreach ($itemsTemp as $v) {
				$this->initItem($v);
			}
		}
		return $this->_items;
	}
	
	public function getItem($value) {
		foreach ($this->getItems() as $item) {
			if ($item->value == $value) {
				return $item;
			}
		}
		return null;
	}

	public function addItem($value, $text = null) {
		$this->initItem($value, $text);
		return $this;
	}
	
	public function valueExists($value) {
		return (bool)$this->getItem($value);
	}

	public function checkValue($value) {
		if ($value === self::EMPTY_VALUE) {
			return true;
		}
		if ($this->multiple) {
			return is_array($value) && !empty($value);
		} else {
			return ($this->allowNotExists || $this->valueExists($value));
		}		
	}

	public function getValue() {
		$value = parent::getValue();
		if (null === $value && false === $this->emptyItem && $this->_items) {
			return $this->_items[0]->value;
		}
		return $value;
	}
	
	public function addValue($value) {
		if ($this->checkValue($value)) {
			$this->_value[] = $this->prepareValue($value);
			return true;
		}
	}

	protected function prepareValue($value) {
		if ($value === self::EMPTY_VALUE) {
			return $this->emptyValue;
		}
		if ($this->multiple) {
			$valueTemp = $value;
			$value = array();
			if (is_array($valueTemp)) {
				foreach ($valueTemp as $val) {
					if ($this->allowNotExists || $this->valueExists($val)) {
						$value[] = self::getValueId($val);
					}
				}
			}
			if (empty($value)) {
				return null;
			}
		} else {
			$value = self::getValueId($value);
		}
		return $value;
	}
	
	protected function getOptionsHtml() {
		$html = '';
		if (false !== $this->emptyItem) {
			$html .= '<option value="' . self::EMPTY_VALUE . '">' . $this->emptyItem . '</option>';
		}
		if ($this->groups) {
			$items = $this->getItems();
			foreach ($items as $item) if (!isset($item->{$this->groupIndex}) || !$item->{$this->groupIndex}) {
				$isSel = $this->isSelected($item->value);
				$html .= '<option value="' . self::escape($item->value) . '"'
					. (isset($item->attr) ? ' ' . $item->attr : '')
					. ($isSel ? ' selected' : '') . '>' . $item->text . '</option>';
			}
			foreach ($this->getGroups() as $group) {
				$html .= '<optgroup label="' . $group->text . '">';
				foreach ($items as $item) if (isset($item->{$this->groupIndex}) && $item->{$this->groupIndex} == $group->id) {
					$isSel = $this->isSelected($item->value);
					$html .= '<option value="' . self::escape($item->value) . '"'
						. (isset($item->attr) ? ' ' . $item->attr : '')
						. ($isSel ? ' selected' : '') . '>' . $item->text . '</option>';
				}
				$html .= '</optgroup>';
			}
		} else {
			foreach ($this->getItems() as $item) {
				$isSel = $this->isSelected($item->value);
				$html .= '<option value="' . self::escape($item->value) . '"'
					. (isset($item->attr) ? ' ' . $item->attr : '')
					. ($isSel ? ' selected' : '') . '>' . $item->text . '</option>';
			}
		}
		//var_dump($this->default);
		
		return $html;
	}

	public function getValuePresentation() {
		foreach ($this->getItems() as $item) {
			if ($item->value == $this->_value) {
				return $item->text;
			}
		}
		return '';
	}
	
	public function isSelected($value) {
		$value = self::getValueId($value);
		if ($this->multiple) {
			if (is_array($this->value))
			foreach ($this->value as $val) {
				if ($val == $value) {
					return true;
				}
			}
		} else {
			return ($this->value === $value);
		}
		return false;
	}

	public function getInputName() {
		$name = parent::getInputName();
		if ($this->multiple) {
			$name .= '[]';
		}
		return $name;
	}

	public function getHtml() {
		$html = '<select' . $this->attrToString() . '>';
		$html .= $this->getOptionsHtml();
		$html .= '</select>';
		return $html;
	}
	
	public function isEmpty() {
		return (null === $this->value);
	}
}
