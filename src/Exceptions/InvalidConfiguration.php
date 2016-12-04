<?php

namespace Boparaiamrit\ActivityLog\Exceptions;

use Exception;
use Boparaiamrit\ActivityLog\Models\Activity;

class InvalidConfiguration extends Exception
{
    public static function modelIsNotValid(string $className)
    {
        return new static("The given model class `$className` does not extend `".Activity::class.'`');
    }
}
