<?php
namespace Form\Element;

class File extends Xhtml{

	protected $_options = array(
		'inputType' => 'file',
		'deleteUrl' => '',
		'multiple' => false
	);

	protected $_attributes = array('multiple');

	public function isFileUpload() {
		return true;
	}

	private function setFileData($file, $data) {
		if (isset($data[$file->id])) {
			$file->setData($data[$file->id]);
		}
	}

	public function setData($data) {
		$value = $this->getValue();
		if ($this->multiple && is_array($data) && is_array($value)) {
			$index = 0;
			foreach ($data as $id => $v) {
				foreach ($value as $file) {
					if ($file->id == $id) {
						$file->index = ($index++);
					}
				}
			}
		} else {
			//$this->setFileData($value, $data);
		}
	}
	
	public function getInputName() {
		return parent::getInputName() . ($this->multiple ? '[]' : '');
	}

	public function getOriginalInputName() {
		return parent::getInputName();
	}

	protected static function checkFileData($data) {
		if ($data instanceof \File) {
			return true;
		} elseif (is_array($data)) {
			return (isset($data['tmp_name']) && $data['tmp_name']);
		}
		return false;
	}

	protected static function createFile($data) {
		if (is_array($data)) {
			return new \File($data);
		} elseif ($data instanceof \File) {
			return $data;
		}
		return null;
	}

	public function checkValue($value) {
		if ($this->multiple) {
			return is_array($value);
		} elseif (null !== $value) {
			return self::checkFileData($value);
		}
		return true;
	}

	protected function prepareValue($value) {
		if ($this->multiple) {
			$valueTemp = $value;
			$value = array();
			if (is_array($valueTemp)) {
				foreach ($valueTemp as $data) {
					if (($file = self::createFile($data))) {
						$value[] = $file;
					}
				}
			}
		} elseif (null !== $value) {
			$value = self::createFile($value);
		}
		return $value;
	}

	public function getHtml() {
		$html = '<div id="' . $this->id . '_files" class="box">';
		$value = $this->getValue();
		if ($value) {
			if (!is_array($value)) {
				$value = array($value);
			}
			foreach ($value as $file) {
				$html .= '<div class="file-item">';
				$html .='<a href="/file/' . $file . '/" target="_blank">Скачать</a>';
				if ($this->deleteUrl) {
					$html .= '<br /><a href="' . str_replace('%id%', $file->id, $this->deleteUrl) . '" class="button-delete">удалить</a>';
				}				
				$html .= '<input type="hidden" name="' . $this->getOriginalInputName() . '[' . $file->id . '][index][]" value="1" />';
				$html .= '</div>';
			}
			$html .= '<br />';
		}
		$html .= '<input type="file"' . $this->attrToString() . ' />';
		$html .= '</div>';
		return $html;
	}


}
