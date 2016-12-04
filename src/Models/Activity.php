<?php

namespace Boparaiamrit\ActivityLog\Models;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Jenssegers\Mongodb\Eloquent\Model;

/**
 * @property mixed  properties
 * @property string description
 * @property string log_name
 */
class Activity extends Model
{
	public $guarded = [];
	
	protected $collection = 'activity_logs';
	
	public function subject(): MorphTo
	{
		$isTrashRequired = config('activitylog.subject_returns_soft_deleted_models');
		
		if ($isTrashRequired) {
			/** @noinspection PhpUndefinedMethodInspection */
			return $this->morphTo()->withTrashed();
		}
		
		return $this->morphTo();
	}
	
	public function causer(): MorphTo
	{
		return $this->morphTo();
	}
	
	/**
	 * Get the extra properties with the given name.
	 *
	 * @param string $propertyName
	 *
	 * @return mixed
	 */
	public function getExtraProperty(string $propertyName)
	{
		return array_get(collect($this->properties)->toArray(), $propertyName);
	}
	
	public function getChangesAttribute(): Collection
	{
		return collect(array_filter($this->properties->toArray(), function ($key) {
			return in_array($key, ['attributes', 'old']);
		}, ARRAY_FILTER_USE_KEY));
	}
	
	public function scopeInLog(Builder $Query, ...$logNames): Builder
	{
		if (is_array($logNames[0])) {
			$logNames = $logNames[0];
		}
		
		return $Query->whereIn('log_name', $logNames);
	}
	
	/**
	 * Scope a query to only include activities by a given causer.
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $Query
	 * @param \Jenssegers\Mongodb\Eloquent\Model    $Causer
	 *
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function scopeCausedBy(Builder $Query, Model $Causer): Builder
	{
		return $Query
			->where('causer_type', get_class($Causer))
			->where('causer_id', $Causer->getKey());
	}
	
	/**
	 * Scope a query to only include activities for a given subject.
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $Query
	 * @param \Jenssegers\Mongodb\Eloquent\Model    $Subject
	 *
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function scopeForSubject(Builder $Query, Model $Subject): Builder
	{
		return $Query
			->where('subject_type', get_class($Subject))
			->where('subject_id', $Subject->getKey());
	}
}
