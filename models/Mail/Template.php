<?php
namespace Api\Model\Mail;

require_once 'Enums.php';

use Api;
use Mail\Sender as MailSender;
use Api\Model\Mail\Template\Address;
use Api\Model\Site;
use Api\Model\Reference\Country;
use Format;

class Template extends Api{
	
	private $sender;
	protected $layout = null;
	protected $template = null;
	protected $addresses = array();
	protected static $options = array();
	
	public static function setOptions(array $options) {
		self::$options = $options;
	}

	public function __call($name, $arguments) {
		if (method_exists($this->getSender(), $name)) {
			return call_user_func_array(array($this->_sender, $name), $arguments);
		}
		return parent::__call($name, $arguments);
	}
	
	protected function init() {
		$this->_table = 'mail_templates';
		$this
				->addAttribute('site_id', 'number', array('notnull' => false, 'nonnegative' => true))
				->addAttribute('country_id', 'number', array('notnull' => false, 'nonnegative' => true))
				->addAttribute('parent_id', 'number', array())
				->addAttribute('type', 'enum', array('enum' => 'MAIL_TYPE', 'notnull' => false))
				//->addAttribute('key', 'string', array())
				->addAttribute('name', 'string', array())
				->addAttribute('subject', 'string', array('locale' => true))
				->addAttribute('body', 'string', array('locale' => true));
		Site::initApi($this);
		Country::initApi($this);
	}
	
	protected function initSettings($settings) {
		$settings->joinLeft('ref_countries', 'ref_countries.id=mail_templates.country_id', 'name as country_name');
		parent::initSettings($settings);
	}
	
	public function find($type, array $options = array()) {
		if (\MAIL_TYPE::valueExists($type)) {
			
		} elseif (\MAIL_TYPE::keyExists(strtoupper($type))) {
			$type = \MAIL_TYPE::getValue(strtoupper($type));
		}
		$this->findByAttribute('type', $type, array_merge(array('auto' => true), $options));
		return $this;
	}

	public function addAddress($address, $name = '', $data = null) {
		foreach (self::getAddressArray($address, $name, $data) as $a) {
			$this->addresses[] = $a;
		}
		return $this;
	}

	public function getSender() {
		if (!$this->sender) {
			$this->sender = new MailSender();
			$this->sender->SingleTo = true;
			foreach (self::$options as $k => $v) {
				$this->sender->$k = $v;
			}
		}
		return $this->sender;
	}
	
	public function getLayout() {
		if (null === $this->layout) {
			$this->layout = false;
			if ($this->parent_id) {
				$layout = new self();
				if ($layout->findById($this->parent_id, array('auto' => true))) {
					$layout->template = $this;
					$this->layout = $layout;
				}
			}
		}
		return $this->layout;
	}
	
	public function getSubject($data = null) {
		if ($data) {
			return $this->parseTemplate($this->subject, $data);
		} else {
			return $this->_get('subject');
		}
	}
	
	public function getBody($data = null) {
		if (null === $data) {
			return $this->_get('body');
		} elseif ($this->getLayout()) {
			$data['template'] = $this->parseTemplate($this->_get('body'), $data);
			return $this->layout->getBody($data);
		} else {
			return $this->parseTemplate($this->_get('body'), $data);
		}
	}
	
	public function getTemplateData($data = array()) {
		$tdata = array();
		$tdata['host'] = self::getHttpHost();
		$tdata['site_url'] = self::getScheme() . '://' . $tdata['host'];
		return array_merge($tdata, self::$options, $this->getData(), $data);
	}

	private function parseTemplate($template, $data) {
		$dataValues = array();
		$data = array_merge($this->getData(), $data);
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
		return Format::formatTemplate($template, $data);
	}

	public function send($address = null, $name = null, $data = null) {
		if ($this->isEmpty()) {
			return false;
		}
		if (null === $address) {
			$array = $this->addresses;
		} else {
			$array = self::getAddressArray($address, $name, $data);
		}
		$f = true;
		foreach ($array as $address) {
			if (!$address->isValid()) {
				continue;
			}
			$sender = $this->getSender();
			$sender->AddAddress($address->address, $address->name);
			$data = $this->getTemplateData($address->data);
			$sender->Subject = $this->getSubject($data);
			$sender->MsgHTML($this->getBody($data));
			if (!$sender->Send()) {
				$f = false;
			}
			$sender->ClearAddresses();
		}
		return $f;
	}
	
	public function beforeSelect($q) {
		//echo $q;
	}

	public static function getServer($key = null, $default = null) {
		if (null === $key) {
			return $_SERVER;
		}

		return (isset($_SERVER[$key])) ? $_SERVER[$key] : $default;
	}

	public static function getScheme() {
		return (self::getServer('HTTPS') == 'on') ? 'https' : 'http';
	}

	public static function getHttpHost() {
		$host = self::getServer('HTTP_HOST');
		if (!empty($host)) {
			return $host;
		}

		$scheme = self::getScheme();
		$name = self::getServer('SERVER_NAME');
		$port = self::getServer('SERVER_PORT');

		if (($scheme == 'http' && $port == 80) || ($scheme == 'https' && $port == 443)) {
			return $name;
		} else {
			return $name . ':' . $port;
		}
	}
	
	private static function getAddressArray($address, $name = '', $data = null) {
		if (is_string($address)) {
			$address = str_replace(array("\n"), ';', $address);
			$address = explode(';', $address); 
		} elseif (!is_array($address)) {
			$address = array($address);
		}
		$array = array();
		foreach ($address as $addr) {
			if (empty($addr) || !is_string($addr)) continue;
			$array[] = new Address($addr, $name, $data);
		}
		return $array;
	}
	
}