<?php

use Boparaiamrit\ActivityLog\Models\Activity;

return [
	
	/*
	 * If set to false, no activities will be saved to the database.
	 */
	'enabled'        => env('ACTIVITY_LOG_ENABLED', true),
	
	/*
	 * When the clean-command is executed, all recording activities older than
	 * the number of days specified here will be deleted.
	 */
	'expires'        => 365,
	
	/*
	 * If no log name is passed to the activity() helper
	 * we use this default log name.
	 */
	'log_name'       => 'default',
	
	/*
	 * You can specify an auth driver here that gets user models.
	 * If this is null we'll use the default Laravel auth driver.
	 */
	'auth_driver'    => null,
	
	/*
	 * This model will be used to log activity. The only requirement is that
	 * it should be or extend the Boparaiamrit\ActivityLog\Models\Activity model.
	 */
	'activity_model' => Activity::class,
];
