<?php

namespace Boparaiamrit\ActivityLog\Exceptions;


use Boparaiamrit\ActivityLog\Models\Activity;
use Exception;

class InvalidConfiguration extends Exception
{
	public static function modelIsNotValid(string $className)
	{
		return new static("The given model class $className does not extend " . Activity::class);
	}
}
