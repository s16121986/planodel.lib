<?php
namespace Api\Attribute;

class AttributeTable extends AbstractAttribute{

	private $_record;

	protected $_qualifiers = array(
		'table' => ''
	);

	private function setRecord($id) {
		$id = (int)$id;
		if ($id) {
			if (!$this->_record || $this->_record['id'] != $id) {
				$this->_record = \Db::from($this->table)
					->where('id=?', $id)
					->query()->fetchRow();
			}
			return (bool)$this->_record;
		}
		return false;
	}

	public function getRecord() {
		return $this->_record;
	}

	public function checkValue($value) {
		return (parent::checkValue($value) && (bool)$this->setRecord($value));
	}

	public function prepareValue($value) {
		return (int)$value;
	}

	public function getPresentation() {
		$record = $this->getRecord();
		if ($record) {
			if (isset($record['name'])) {
				return $record['name'];
			}
		}
		return '';
	}

}