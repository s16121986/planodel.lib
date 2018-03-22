<?php
namespace Mail\Helper;

use Db;
use Api\Util\Translation;

class Template{
	
	protected $subject = null;
	
	protected $body = null;
	
	protected $_data = array();
	
	public function __construct($subject, $body = null) {
		if (null === $body) {
			$this->init($subject);
		} else {
			$this->setSubject($subject);
			$this->setBody($body);
		}
	}
	
	public function __get($name) {
		if (property_exists($this, $name)) {
			return $this->$name;
		} elseif (array_key_exists($name, $this->_data)) {
			return $this->_data[$name];
		}
		return null;
	}
	
	public function __set($name, $value) {
		$this->_data[$name] = $value;
	}
	
	protected function init($key) {
		$table = 'mail_templates';
		if (class_exists('Cls')) {
			if (isset(\Cls::$mail['table'])) $table = \Cls::$mail['table'];
			elseif (isset(\Cfg::$MailTemplatesTable)) $table = \Cfg::$MailTemplatesTable;
		}
		$template = Db::from($table)
			->where('`key`=?', $key)
			->query()->fetchRow();
		if ($template) {
			$this->setSubject($template[Translation::getColumn('subject')])
				->setBody($template[Translation::getColumn('body')]);
		}
	}
	
	public function setSubject($subject) {
		$this->subject = $subject;
		return $this;
	}
	
	public function getSubject($data = null) {
		if ($data) {
			return $this->parseTemplate($this->subject, $data);
		} else {
			return $this->subject;
		}
	}
	
	public function setBody($body) {
		$this->body = $body;
		return $this;
	}
	
	public function getBody($data = null) {
		if ($data) {
			return $this->parseTemplate($this->body, $data);
		} else {
			return $this->body;
		}
	}
	
	public function isEmpty() {
		return empty($this->body);
	}

	private function parseTemplate($template, $data) {
		$dataValues = array();
		$data = array_merge($this->_data, $data, array('subject' => $this->subject));
		foreach ($data as $k => $v) {
			if ($v instanceof Template) {
				continue;
			}
			$dataValues[$k] = $v;
		} 
		foreach ($data as $k => $v) {
			if ($v instanceof Template) {
				$data[$k] = $v->getBody($dataValues);
			}
		}
		return \Format::formatTemplate($template, $data);
	}
	
}