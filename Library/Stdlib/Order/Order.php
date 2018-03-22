<?php
namespace Stdlib\Order;

use Exception;
use Stdlib\AbstractCollection;

class Order extends AbstractCollection{
	
	public function add($name, $direction = \SortDirection::ASC) {
		if (is_array($name)) {
			foreach ($name as $item) {
				$this->add($item);
			}
		} elseif (is_string($name)) {
			$this->items[] = new Item($name, $direction);
		} elseif ($name instanceof Item) {
			$this->items[] = $name;
		} else {
			throw new Exception('item type invalid');
		}
		return $this;
	}

	public function setDirection($direction) {
		foreach ($this->_items as $item) {
			$item->setDirection($direction);
		}
		return $this;
	}
	
}