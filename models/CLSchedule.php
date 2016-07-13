<?php

class CLSchedule extends JModelBase
{
	use ACFModelTrait;

	protected $post;

	public function __construct($id)
	{
		parent::__construct($id);
	}

	// is it time for this to execute
	public function shouldExecute()
	{
		// date range check
		if( strtotime($this->start_date) > utc_time() || strtotime($this->end_date) < utc_time() ) {
			return false;
		}

		// time range check, for the current day-of-week
		$dow_abbrevs = [null, 'mon', 'tues', 'wed', 'thurs', 'fri', 'sat', 'sun'];
		$dow_int = utc_date()->format('N');	// 1 = mon, 7 = sun
		$dow = $dow_abbrevs[$dow_int];
	}

	// return the hours of operation for the given day-of-week, or false if not active for that day
	// [{start, end}]
	// Time is in HHMM format
	public function hoursOfOperation($dow)
	{
		$dow_is_active = $dow . '_is_active';
		$dow_start_time = $dow . '_start_time';
		$dow_end_time = $dow . '_end_time';

		if( !$this->$dow_is_active ) {
			return false;
		}

		return array(
			'start_time'	=> $this->$dow_start_time,
			'start_time'	=> $this->$dow_end_time,
		);
	}
}