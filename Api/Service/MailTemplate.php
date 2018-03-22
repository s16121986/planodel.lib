<?php
namespace Api\Service;

use Api;

class MailTemplate extends Api{
	
	protected function init() {
		$this->_table = 'mail_templates';
		$this
			->addAttribute('site_id', 'number', array('notnull' => false))
			->addAttribute('key', 'string', array('required' => true, 'length' => 50))
			->addAttribute('name', 'string', array('required' => true, 'length' => 255))
			->addAttribute('subject', 'string', array('required' => true, 'locale' => true, 'length' => 255))
			->addAttribute('body', 'string', array('required' => true, 'locale' => true))
		;
		Site::initApi($this);
	}

}