<?php

namespace Boparaiamrit\ActivityLog\Traits;


use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Jenssegers\Mongodb\Eloquent\Model;

trait LogsActivity
{
	use DetectsChanges;
	
	protected static function bootLogsActivity()
	{
		/** @var Collection $EventsToBeRecorded */
		$EventsToBeRecorded = static::eventsToBeRecorded();
		
		$EventsToBeRecorded->each(function ($eventName) {
			return static::$eventName(function (Model $Model) use ($eventName) {
				/** @noinspection PhpUndefinedMethodInspection */
				if (!$Model->shouldLogEvent($eventName)) {
					return;
				}
				
				/** @noinspection PhpUndefinedMethodInspection */
				$description = $Model->getDescriptionForEvent($eventName);
				
				/** @noinspection PhpUndefinedMethodInspection */
				$logName = $Model->getLogNameToUse($eventName);
				
				if ($description == '') {
					return;
				}
				
				/** @noinspection PhpUndefinedMethodInspection */
				app('activitylog')
					->useLog($logName)
					->performedOn($Model)
					->withProperties($Model->attributeValuesToBeLogged($eventName))
					->log($description);
			});
		});
	}
	
	public function activity(): MorphMany
	{
		return $this->morphMany(app('activitylog:class'), 'subject');
	}
	
	public function getDescriptionForEvent(string $eventName): string
	{
		return $eventName;
	}
	
	public function getLogNameToUse(): string
	{
		return config('activitylog.default_log_name');
	}
	
	/*
	 * Get the event names that should be recorded.
	 */
	protected static function eventsToBeRecorded(): Collection
	{
		/** @noinspection PhpUndefinedFieldInspection */
		if (isset(static::$recordEvents)) {
			/** @noinspection PhpUndefinedFieldInspection */
			return collect(static::$recordEvents);
		}
		
		$events = collect([
			'created',
			'updated',
			'deleted',
		]);
		
		if (collect(class_uses(__CLASS__))->contains(SoftDeletes::class)) {
			$events->push('restored');
		}
		
		return $events;
	}
	
	public function attributesToBeIgnored(): array
	{
		/** @noinspection PhpUndefinedFieldInspection */
		if (!isset(static::$ignoreChangedAttributes)) {
			return [];
		}
		
		/** @noinspection PhpUndefinedFieldInspection */
		return static::$ignoreChangedAttributes;
	}
	
	protected function shouldLogEvent(string $eventName): bool
	{
		if (!in_array($eventName, ['created', 'updated'])) {
			return true;
		}
		
		if (array_has($this->getDirty(), 'deleted_at')) {
			if ($this->getDirty()['deleted_at'] === null) {
				return false;
			}
		}
		
		//do not log update event if only ignored attributes are changed
		return (bool)count(array_except($this->getDirty(), $this->attributesToBeIgnored()));
	}
}
