<?php

namespace Dater\Locale;
use Dater\Dater;

class En extends \Dater\Locale {

	protected static $months = array('January', 'February', 'March', 'April', 'May', 'June', 'Jule', 'August', 'September', 'October', 'November', 'December');
	protected static $weekDays = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
	protected static $weekDaysShort = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');

	protected static $formats = array(
		Dater::DATE_FORMAT => 'j F Y',
		Dater::TIME_FORMAT => 'g:i A',
		Dater::DATETIME_FORMAT => 'm/d/Y g:i A',
	);
}
