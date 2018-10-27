<?php
namespace Api\Model;

use Api;
use Api\EventManager;

class Site extends Api{
	
	private static $siteId = null;
	
	public static function getSiteId() {
		return self::$siteId;
	}
	
	public static function setSiteId($id) {
		self::$siteId = $id;
	}
	
	public static function initApi($api) {
		if (!self::$siteId) {
			return;
		}
		EventManager::bind('beforeWrite', function($event) {
			$api = $event->api;
			if ($api->isNew()) {
				$api->site_id = Site::getSiteId();
			}
		}, $api);
		EventManager::bind('initSettings', function($event, $settings) {
			$table = $event->api->table;
			$settings->filter($table . '.site_id IS NULL OR ' . $table . '.site_id=' . Site::getSiteId());
			$settings->removeParam('site_id');
		}, $api);
	}
	
	public static function getDomain($siteId, $httpUrl = false) {
		$site = new self();
		if ($site->findById($siteId)) {
			$url = '';
			if ($httpUrl) {
				$url = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://';
			}
			$url .= $site->domain;
			return $url;
		}
		return '';
	}
	
	public static function getByDomain($name = null) {
		if (null === $name && isset($_SERVER['HTTP_HOST'])) {
			$name = $_SERVER['HTTP_HOST'];
		}
		$api = new self();
		if ($api->findByAttribute('domain', $name)) {
			return $api;
		}
	}
	
	protected function init() {
		$this->_table = 'sites';
		$this
			->addAttribute('name', 'string', array('required' => true, 'length' => 255))
			->addAttribute('domain', 'string', array('required' => true))
		;
	}

}