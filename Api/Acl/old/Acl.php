<?php
namespace Api;

require_once 'Api/Acl/Resource/Registry.php';

abstract class Acl {
	const ADD = 'add';

	const EDIT = 'edit';

	const DELETE = 'delete';

	const VIEW = 'view';

	private static $_registry = null;

	private static $_registryRules = null;

	private static $_rules = array(
		'role' => array(
			//'god' => false,
			'developer' => false,
			'administrator' => false
			)
	);

	public static function hasRole($role) {
		if (is_int($role)) {
			$roleKey = strtolower(USER_ROLE::getKey($role));
			if (isset(self::$_rules['role'][$roleKey])) {
				return self::isAllowed('role', $roleKey);
			}
			return Auth::getUser()->hasRole($role);
		} else {
			return self::isAllowed('role', $role);
		}
	}

	public static function getRoles() {
		$roles = Auth::getUser()->getRoles();
		self::getRules();
		if (isset(self::$_registryRules['role'])) {
			foreach (self::$_registryRules['role'] as $role => $allowed) {
				if ($allowed) {
					$roles[] = USER_ROLE::getValue(strtoupper($role));
				}
			}
		}
		return $roles;
	}

	public static function getRules() {
		if (null === self::$_registryRules) {
			if (defined('UserId') && UserType == \USER_TYPE::USER) {
				self::$_registryRules = self::get(UserId);
			} else {
				self::$_registryRules = array();
			}
		}
		return self::$_registryRules;
	}

	public static function setRules($rules) {
		self::$_registryRules = $rules;
	}

	public static function isAllowed($object, $action = null) {
		if ($object instanceof \Api) {
			return self::_getRegistry()->get($object)->isAllowed($action);
		} elseif (is_string($object)) {
			$rules = self::getRules();
			return (isset($rules[$object]) && isset($rules[$object][$action]) && $rules[$object][$action]);
		}
		return false;
	}

	public static function get($userId = null) {
		$rules = self::$_rules;
		if ($userId) {
			$rows = Db::from('user_rules', array('role_id', 'rule_id', 'access'))
							->where('user_id=?', $userId)
							->query()->fetchAll();

			foreach ($rows as $row) {
				if (!isset($rules[$row['role_id']]) || !isset($rules[$row['role_id']][$row['rule_id']])) {
					continue;
				}
				$rules[$row['role_id']][$row['rule_id']] = ($row['access'] == 1);
			}
		}
		return $rules;
	}

	public static function write($ruleParts, $userId) {
		if (!($userId = (int)$userId)) {
			return;
		}

		$sql = array();
		foreach ($ruleParts as $k => $rules) {
			if (!isset(self::$_rules[$k]) || !is_array($rules)) {
				continue;
			}
			if ($k == 'role') {
				if (self::hasRole(USER_ROLE::ADMINISTRATOR)) {
					$allowed = false;
					foreach (self::$_rules[$k] as $action => $a) {
						if (isset($rules[$action]) && ($allowed || self::hasRole($action))) {
							$allowed = true;
							Db::delete('user_rules', 'user_id=' . $userId . ' AND role_id="' . $k . '" AND rule_id="' . $action . '"');
							$sql[] = '(' . $userId . ',"' . $k . '","' . $action . '",' . ($rules[$action] == 1 ? 1 : 0) . ')';
						}
					}
				}
			} else {
				foreach (self::$_rules[$k] as $action => $a) {
					if (isset($rules[$action])) {
						Db::delete('user_rules', 'user_id=' . $userId . ' AND role_id="' . $k . '" AND rule_id="' . $action . '"');
						$sql[] = '(' . $userId . ',"' . $k . '","' . $action . '",' . ($rules[$action] == 1 ? 1 : 0) . ')';
					}
				}
			}
			unset($rules);
		}
		if (!empty($sql)) {
			Db::query('INSERT INTO user_rules (user_id,role_id,rule_id,access) VALUES ' . implode(',', $sql));
		}
	}

	public static function setRole($userId, $roleId) {
		switch ((int) $roleId) {
			case USER_ROLE::ADMINISTRATOR:
				$rules = self::_setAllPrivileges(array('user', 'client'));
				self::write($rules, $userId);
				break;
			case USER_ROLE::USER:
				self::write(self::$_rules, $userId);
				break;
			default:
				return false;
		}
		return true;
	}

	private static function _getRegistry() {
		if (!self::$_registry) {
			self::$_registry = new Acl\Resource\Registry();
		}
		return self::$_registry;
	}

}