<?php

namespace Dater\Locale;
use Dater\Dater;

class Ua extends \Dater\Locale {

	protected static $months = array('січня', 'лютого', 'березня', 'квітня', 'травня', 'червня', 'липня', 'серпня', 'вересня', 'жовтня', 'листопада', 'грудня');
	protected static $weekDays = array('понеділок', 'вівторок', 'середа', 'четвер', "п'ятниця", 'субота', 'неділя');
	protected static $weekDaysShort = array('Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Нд');

	protected static $formats = array(
		Dater::DATE_FORMAT => 'd.m.Y',
		Dater::TIME_FORMAT => 'G:i',
		Dater::DATETIME_FORMAT => 'd.m.Y G:i',
	);
}
