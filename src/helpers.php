<?php

use Boparaiamrit\ActivityLog\ActivityLogger;

if (!function_exists('activity')) {
	/**
	 * @param string|null $logName
	 *
	 * @return ActivityLogger
	 */
	function activity(string $logName = null): ActivityLogger
	{
		$ActivityLogger = app('activitylog');
		
		$defaultLogName = config('activitylog.default_log_name');
		$ActivityLogger->useLog($logName ?? $defaultLogName);
		
		return $ActivityLogger;
	}
}
