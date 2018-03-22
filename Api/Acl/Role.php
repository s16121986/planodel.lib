<?php
namespace Api\Acl;

use Api;
use Db;
use Api\Acl\Role\Rule;

class Role{
	
	protected $table = 'user_rules';
	protected $user;
	protected $default = true;
	protected $rules = array();
	protected $availableRules = array();
	protected static $namedRules = array(
		'view' => 1,//0x0001,
		'edit' => 3,//0x0011,
		'add' => 7,//0x0111,
		'delete' => 11,//0x1011
		//1111 = 15
	);
	
	private static function id($model, $action) {
		return $model . ($action ? '_' . $action : '');
	}
	
	public function __construct(Api $user = null) {
		$this->init();
		if ($user) {
			$this->setUser($user);
		}
	}
	
	public function setUser(Api $user) {
		$this->user = $user;
		$this->initData();
		return $this;
	}
	
	protected function init() {}
	
	protected function initData() {
		if (!$this->user || $this->user->isEmpty()) {
			return;
		}
		$q = Db::from($this->table)
			->where('user_id=?', $this->user->id)
			->query();
		while ($row = $q->fetch()) {
			$this->_allow($row['model'], $row['action'], (bool)$row['flag']);
		}
	}
	
	protected function _allowNamed($model, $action, $flag) {
		if (isset(self::$namedRules[$action])) {
			$this->_allow($model, self::$namedRules[$action], $flag);
		} else {
			$this->_allow($model, $action, $flag);
		}
		return $this;
	}
	
	protected function _allow($model, $action, $flag) {
		if (null === $model) {
			$this->default = $flag;
		} else {
			if (preg_match('/^[01]+$/', $action)) {
				$action = bindec($action);
			}
			if (is_int($action)) {
				foreach (self::$namedRules as $key => $b) {
					if (($action & $b) >= $b) {
						$this->rules[self::id($model, $key)] = new Rule($model, $key, $flag);
					}
				}
			} else {
				$this->rules[self::id($model, $action)] = new Rule($model, $action, $flag);
			}
		}
		return $this;
	}
	
	public function allow($model = null, $action = null) {
		return $this->_allowNamed($model, $action, true);
	}
	
	public function deny($model = null, $action = null) {
		return $this->_allowNamed($model, $action, false);
	}
	
	protected function getRuleFlag($model, $action) {
		$id = self::id($model, $action);			
		return (isset($this->rules[$id]) ? $this->rules[$id]->flag : null);
	}
	
	public function addAvailable($model, $actions, $flag = null) {
		if (!is_array($actions)) {
			switch ($actions) {
				case 'default':$actions = array_keys(self::$namedRules);break;
				default:
					$actions = array($actions);
			}
		}
		foreach ($actions as $action) {
			$this->availableRules[self::id($model, $action)] = new Rule($model, $action, $flag);
		}
		return $this;
	}
	
	public function getRules() {
		return $this->rules;
	}
	
	public function isAllowed($model, $action = null) {
		if ($model instanceof \Api) {
			$model = strtolower($model->getModelName());
		}
		if ($action) {
			$flag = $this->getRuleFlag($model, $action);
			if (null !== $flag) {
				return $flag;
			}
		}
		$flag = $this->getRuleFlag($model, null);
		if (null === $flag) {
			if ($action) {
				$id = self::id($model, $action);
				if (isset($this->availableRules[$id])) {
					$flag = $this->availableRules[$id]->flag;
				}
			} else {
				foreach ($this->availableRules as $rule) {
					if ($rule === $model) {
						$flag = true;
						break;
					}
				}
			}
			if (null === $flag) {
				$flag = $this->default;
			}
		}
		return (bool)$flag;
	}
	
	public function getAvailableRules($asArray = false) {
		if ($asArray) {
			$array = array();
			foreach ($this->availableRules as $rule) {
				if (!isset($array[$rule->model])) {
					$array[$rule->model] = array();
				}
				$array[$rule->model][$rule->action] = $rule->flag;
			}
			return $array;
		}
		return $this->availableRules;
	}
	
	public function write($data) {
		if (!$this->user) {
			return;
		}
		Db::delete($this->table, array('user_id' => $this->user->id));
		$array = array();
		foreach ($this->availableRules as $rule) {
			$array[] = array(
				'user_id' => $this->user->id,
				'model' => $rule->model,
				'action' => $rule->action,
				'flag' => (isset($data[$rule->model]) && (isset($data[$rule->model][$rule->action]) || in_array($rule->action, $data[$rule->model])))
			);
		}
		if ($array) {
			Db::writeArray($this->table, $array);
		}
		return $this;
	}
	
}