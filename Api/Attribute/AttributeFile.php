<?php
namespace Api\Attribute;

use File\AbstractFile as BaseFile;
use Api\File as ApiFile;

class AttributeFile extends AbstractAttribute{

	protected $_plugins = array();

	protected $_params = array(
		'notnull' => false
	);

	protected $_qualifiers = array(
		'model' => null,
		'type' => null,
		'fieldName' => false,
		'fieldPreview' => true,
		'multiple' => false
	);

	public function __construct($name, $qualifiers = null) {
		if (isset($qualifiers['mimeType'])) {
			$this->addValidator('File\MimeType', array('mimeType' => $qualifiers['mimeType']));
			unset($qualifiers['mimeType']);
		}
		parent::__construct($name, $qualifiers);
	}

	protected function fileFactory($data) {
		$file = null;
		if (is_string($data)) {
			$file = ApiFile::getByGuid($data);
		} elseif ($data instanceof BaseFile) {
			$data = $data->getData();
			$data['type'] = $this->type;
			$file = new ApiFile($data);
		}
		if ($file && parent::checkValue($file)) {
			$file->setModel($this->model);
			return $file;
		}
		return null;
	}

	public function addPlugin($plugin, $options = null) {
		if (is_string($plugin)) {
			$cls = 'Api\\Attribute\\File\\Plugin\\' . $plugin;
			$plugin = new $cls($options);
		}
		$key = str_replace('Api\\Attribute\\File\\Plugin\\', '', get_class($plugin));
		$this->_plugins[$key] = $plugin;
		return $this;
	}

	protected function initPlugins($file) {
		foreach ($this->_plugins as $plugin) {
			$plugin->setFile($file);
			$plugin->init();
		}
	}

	public function checkValue($value) {
		if ($this->multiple) {
			return is_array($value);
		}
		if (($file = $this->fileFactory($value))) {
			return parent::checkValue($file);
		}
		return false;//(is_string($value) || $value instanceof \File);
	}

	public function prepareValue($value) {
		if ($this->multiple) {
			$valueTemp = $value;
			$value = array();
			if (is_string($valueTemp)) {
				$valueTemp = array($valueTemp);
			}
			if (is_array($valueTemp)) {
				foreach ($valueTemp as $data) {
					if (($file = $this->fileFactory($data))) {
						$value[] = $file;
					}
				}
			}
		} else {
			$value = $this->fileFactory($value);
		}
		return $value;
	}

	public function write() {
		$value = $this->getValue();
		if ($value) {
			if (!is_array($value)) {
				$value = array($value);
			}
			$files = array();
			foreach ($value as $file) {
				if ($file->isNew()) {
					$files[] = $file;
				} else {
					$file->write();
				}
			}
			if (empty($files)) {
				return true;
			}
			if (!$this->multiple) {
				$this->delete();
			}
			foreach ($files as $file) {
				$this->initPlugins($file);
				$file->write();
			}
			return true;
		}
		return false;
	}

	public function select() {
		$files = array();
		if (!$this->model->isEmpty()) {
			$items = \Db::from('files')
				->order('index')
				->where('parent_id=' . $this->model->id . ' AND type=' . $this->type)
			->query()->fetchAll();
			foreach ($items as $data) {
				$file = new \Api\File($data);
				$file->setModel($this->model);
				$files[] = $file;
			}
		}
		return $files;
	}

	public function delete() {
		foreach ($this->select() as $file) {
			$file->delete();
		}
		return true;
	}

}