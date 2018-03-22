<?php

namespace Dater\Locale;
use Dater;

class Ru extends \Dater\Locale {

	protected static $months = array('январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь');
	protected static $weekDays = array('понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота', 'воскресенье');
	protected static $weekDaysShort = array('Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс');

	protected static $formats = array(
		Dater::DATE_FORMAT => 'd.m.Y',
		Dater::TIME_FORMAT => 'G:i',
		Dater::DATETIME_FORMAT => 'd.m.Y G:i',
	);
}
