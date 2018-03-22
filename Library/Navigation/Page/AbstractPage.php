<?php

namespace Navigation\Page;

use Exception;

abstract class AbstractPage extends \Navigation\AbstractContainer {

	protected $_data = array(
		'label' => null,
		'fragment' => null,
		'id' => null,
		'class' => null,
		'title' => null,
		'target' => null,
		'rel' => null,
		'rev' => null,
		'order' => null,
		'resource' => null,
		'privilege' => null,
		'permission' => null,
		'active' => null,
		'visible' => null
	);
	protected $_properties = array();
	protected $parent = null;

	/**
	 * Factory for Zend_Navigation_Page classes
	 *
	 * A specific type to construct can be specified by specifying the key
	 * 'type' in $options. If type is 'uri' or 'mvc', the type will be resolved
	 * to Zend_Navigation_Page_Uri or Zend_Navigation_Page_Mvc. Any other value
	 * for 'type' will be considered the full name of the class to construct.
	 * A valid custom page class must extend Zend_Navigation_Page.
	 *
	 * If 'type' is not given, the type of page to construct will be determined
	 * by the following rules:
	 * - If $options contains either of the keys 'action', 'controller',
	 *   or 'route', a Zend_Navigation_Page_Mvc page will be created.
	 * - If $options contains the key 'uri', a Zend_Navigation_Page_Uri page
	 *   will be created.
	 *
	 * @param  array|Traversable $options  options used for creating page
	 * @return AbstractPage  a page instance
	 * @throws Exception\InvalidArgumentException if $options is not
	 *                                            array/Traversable
	 * @throws Exception\InvalidArgumentException if 'type' is specified
	 *                                            but class not found
	 * @throws Exception\InvalidArgumentException if something goes wrong
	 *                                            during instantiation of
	 *                                            the page
	 * @throws Exception\InvalidArgumentException if 'type' is given, and
	 *                                            the specified type does
	 *                                            not extend this class
	 * @throws Exception\InvalidArgumentException if unable to determine
	 *                                            which class to instantiate
	 */
	public static function factory($options) {
		if ($options instanceof Traversable) {
			//$options = ArrayUtils::iteratorToArray($options);
		}

		if (!is_array($options)) {
			throw new Exception\InvalidArgumentException(
			'Invalid argument: $options must be an array or Traversable'
			);
		}

		if (isset($options['type'])) {
			$type = $options['type'];
			if (is_string($type) && !empty($type)) {
				switch (strtolower($type)) {
					case 'mvc':
						//$type = 'Navigation\Page\Mvc';
						break;
					case 'uri':
						$type = 'Navigation\Page\Uri';
						break;
				}

				if (!class_exists($type, true)) {
					throw new Exception\InvalidArgumentException(
					'Cannot find class ' . $type
					);
				}

				$page = new $type($options);
				if (!$page instanceof self) {
					throw new Exception\InvalidArgumentException(
					sprintf(
						'Invalid argument: Detected type "%s", which ' .
						'is not an instance of Zend\Navigation\Page', $type
					)
					);
				}
				return $page;
			}
		}

		$hasUri = isset($options['uri']);
		$hasMvc = false; //isset($options['action']) || isset($options['controller']) || isset($options['route']);

		if ($hasMvc) {
			return new Mvc($options);
		} elseif ($hasUri) {
			return new Uri($options);
		} else {
			return new Page($options);
		}
	}

	/**
	 * Page constructor
	 *
	 * @param  array|Traversable $options [optional] page options. Default is
	 *                                    null, which should set defaults.
	 * @throws Exception\InvalidArgumentException if invalid options are given
	 */
	public function __construct($options = null) {
		if ($options instanceof Traversable) {
			//$options = ArrayUtils::iteratorToArray($options);
		}
		if (is_array($options)) {
			$this->setOptions($options);
		}

		// do custom initialization
		$this->init();
	}

	/**
	 * Initializes page (used by subclasses)
	 *
	 * @return void
	 */
	protected function init() {
		
	}

	/**
	 * Sets page properties using options from an associative array
	 *
	 * Each key in the array corresponds to the according set*() method, and
	 * each word is separated by underscores, e.g. the option 'target'
	 * corresponds to setTarget(), and the option 'reset_params' corresponds to
	 * the method setResetParams().
	 *
	 * @param  array $options associative array of options to set
	 * @return AbstractPage fluent interface, returns self
	 * @throws Exception\InvalidArgumentException  if invalid options are given
	 */
	public function setOptions(array $options) {
		foreach ($options as $key => $value) {
			$this->set($key, $value);
		}

		return $this;
	}

	public function get($name) {
		if (isset($this->_properties[$name])) {
			return $this->_properties[$name];
		} elseif (isset($this->_data[$name])) {
			return $this->_data[$name];
		}
		return null;
	}

	public function set($name, $value) {
		$method = 'set' . $name;
		if (method_exists($this, $method)) {
			$this->$method($value);
		} elseif (array_key_exists($name, $this->_data)) {
			$this->_data[$name] = $value;
		} else {
			$this->_properties[$name] = $value;
		}
		return $this;
	}

	public function __get($name) {
		return $this->get($name);
	}

	public function __set($name, $value) {
		$this->set($name, $value);
	}

	public function __call($method, $arguments) {
		if (0 === strpos($method, 'get')) {
			return $this->{strtolower(substr($method, 3))};
		}
	}

	/**
	 * Returns page label
	 *
	 * Magic overload for enabling <code>echo $page</code>.
	 *
	 * @return string  page label
	 */
	public function __toString() {
		return $this->label;
	}

	final public function hashCode() {
		return spl_object_hash($this);
	}

	public function setOrder($order = null) {
		if (is_string($order)) {
			$temp = (int) $order;
			if ($temp < 0 || $temp > 0 || $order == '0') {
				$order = $temp;
			}
		}

		if (null !== $order && !is_int($order)) {
			throw new Exception\InvalidArgumentException(
			'Invalid argument: $order must be an integer or null, ' .
			'or a string that casts to an integer'
			);
		}

		$this->_data['order'] = $order;

		// notify parent, if any
		if (isset($this->parent)) {
			$this->parent->notifyOrderUpdated();
		}

		return $this;
	}

	/**
	 * Sets parent container
	 *
	 * @param  AbstractContainer $parent [optional] new parent to set.
	 *                           Default is null which will set no parent.
	 * @throws Exception\InvalidArgumentException
	 * @return AbstractPage fluent interface, returns self
	 */
	public function setParent(\Navigation\AbstractContainer $parent = null) {
		if ($parent === $this) {
			throw new Exception\InvalidArgumentException(
			'A page cannot have itself as a parent'
			);
		}

		// return if the given parent already is parent
		if ($parent === $this->parent) {
			return $this;
		}

		// remove from old parent
		if (null !== $this->parent) {
			$this->parent->removePage($this);
		}

		// set new parent
		$this->parent = $parent;

		// add to parent if page and not already a child
		if (null !== $this->parent && !$this->parent->hasPage($this, false)) {
			$this->parent->addPage($this);
		}

		return $this;
	}

	/**
	 * Returns parent container
	 *
	 * @return AbstractContainer|null  parent container or null
	 */
	public function getParent() {
		return $this->parent;
	}

	public function getProperties() {
		return $this->_properties;
	}

	public function toArray() {
		return array_merge($this->getProperties(), $this->_data, array(
			'type' => get_class($this),
			'pages' => parent::toArray(),
		));
	}

}
