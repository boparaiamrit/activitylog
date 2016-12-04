<?php

namespace Boparaiamrit\ActivityLog\Traits;


use Illuminate\Database\Eloquent\Relations\MorphMany;

trait CausesActivity
{
	public function activity(): MorphMany
	{
		/** @noinspection PhpUndefinedMethodInspection */
		return $this->morphMany(app('activitylog:class'), 'causer');
	}
	
	/** @deprecated Use activity() instead */
	public function loggedActivity(): MorphMany
	{
		return $this->activity();
	}
}
