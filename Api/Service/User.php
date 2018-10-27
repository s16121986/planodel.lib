<?php
namespace Api\Service;

require_once 'User/Enums.php';

use Api;
use Dater;
use Mail\Helper as MailHelper;
use Db;

class User extends Api{
	
	private $params = array();

	protected function init() {
		$this->foreignKey = 'user_id';
		$this->_table = 'users';
		$this
			->addAttribute('name', 'string', array('length' => 50))
			->addAttribute('surname', 'string', array('length' => 50))
			->addAttribute('patronymic', 'string', array('length' => 50))
			->addAttribute('presentation', 'string', array('length' => 255))
			->addAttribute('login', 'string', array('length' => 50))
			->addAttribute('password', 'password', array('regexp' => '/^[a-z0-9_!@#$%^&+=]{6,}$/i'))
			->addAttribute('email', 'string', array('length' => 80, 'notnull' => false))
			->addAttribute('phone', 'string', array('length' => 20, 'notnull' => false))
			->addAttribute('gender', 'enum', array('enum' => 'USER_GENDER', 'notnull' => false))
			->addAttribute('role', 'enum', array('enum' => 'USER_ROLE', 'required' => true))
			->addAttribute('status', 'enum', array('enum' => 'USER_STATUS'))
			->addAttribute('created')
			->addAttribute('updated');
	}
	
	protected function initSettings($settings) {
		$settings->enableQuickSearch('presentation', 'name', 'surname', 'patronymic', 'login', 'email', 'phone');
		$settings->order->setDefault('presentation');
	}
	
	public function findByParam($param, $value, $options = array()) {
		$userId = Db::from('user_params', 'user_id')
				->where('name=?', $param)
				->where('value=?', $value)
				->query()->fetchColumn();
		return ($userId && $this->findById($userId, $options));
	}

	public function sendMail($template, $data = array()) {
		$address = (isset($data['email']) ? $data['email'] : $this->email);
		if (!$address) return false;
		$mail = new MailHelper();
		return $mail->setTemplate($template)
						->addAddress($address, $this->presentation, array_merge($this->getData(), $data))
						->send();
	}
	
	public function setHash($type, $hash = null, $lifetime = '+1 day') {
		if (null === $hash) {
			$hash = md5($this->login . uniqid());
		}
		$datetime = CurrentDate();
		$datetime->modify($lifetime);
		$this
			->setParam('hash_' . $type, $hash)
			->setParam('hash_' . $type . '_time', Dater::serverDatetime($datetime));
		return $hash;
	}
	
	public function checkEmail($email) {
		$user = self::factory('User');
		if ($user->findByAttribute('email', $email) || $this->findByAttribute('login', $email)) {
			return $this->id == $user->id;
		}
		return true;
	}

	public function checkHash($hash, $type, $action = null) {
		if ($this->isEmpty()) {
			if (!$this->findByParam('hash_' . $type, $hash)) {
				return false;
			}
			$user = $this;
		} else {
			$user = self::factory('User');
			if (!$user->findByParam('hash_' . $type, $hash) || $this->id != $user->id) {
				return false;
			}
		}
		$hashTime = $user->getParam('hash_' . $type . '_time');
		if ($hashTime > Dater::serverDatetime()) {
			return true;
		} else {
			switch ($action) {
				case 'send':
					$user->sendUserConfirmation();
					break;
				case 'delete':
					$user->delete();
					break;
			}
		}
		return false;
	}
	
	public function clearHash($type) {
		$this
			->setParam('hash_' . $type, null)
			->setParam('hash_' . $type . '_time', null);
		return true;
	}
	
	public function setParam($name, $value) {
		$this->params[$name] = $value;
		if (!$this->isEmpty()) {
			Db::delete('user_params', array(
				'user_id' => $this->id,
				'name' => $name
			));
			if (null !== $value) {
				Db::insert('user_params', array(
					'user_id' => $this->id,
					'name' => $name,
					'value' => $value
				));
			}
		}
		return $this;
	}
	
	public function getParam($name) {
		if (!isset($this->params[$name])) {
			if ($this->isEmpty()) {
				return null;
			}
			$this->params[$name] = Db::from('user_params', 'value')
				->where('user_id=' . $this->id)
				->where('name=?', $name)
				->query()->fetchColumn();
		}
		return $this->params[$name];
	}
	
	public function getParams() {
		return Db::from('user_params', array('name', 'value'))
				->where('user_id=' . $this->id)
				->query()->fetchAll();
	}

	public function reset() {
		$this->_roles = null;
		$this->_notifications = null;
		return parent::reset();
	}

	protected function checkPresentation() {
		if (!$this->presentation || !preg_match('/[a-zа-я \.]+/iu', $this->presentation)) {
			$x = array();
			foreach (array('surname', 'name', 'patronymic') as $k) {
				if ($this->$k) {
					$x[] = $this->$k;
				}
			}
			if (empty($x)) {
				$this->presentation = (false === ($p = strpos($this->login, '@')) ? $this->login : substr($this->login, 0, $p));
			} else {
				for ($i = 1; $i < count($x); $i++) {
					$x[$i] = mb_substr($x[$i], 0, 1, 'utf8') . '.';
				}
				$this->presentation = join(' ', $x);
			}
		}
		return $this;
	}

}