<?php

namespace Boparaiamrit\ActivityLog;


use Boparaiamrit\ActivityLog\Exceptions\CouldNotLogActivity;
use Boparaiamrit\ActivityLog\Models\Activity;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\Macroable;

class ActivityLogger
{
	use Macroable;
	
	/** @var \Illuminate\Auth\AuthManager */
	protected $auth;
	
	protected $logName = '';
	
	/** @var bool */
	protected $logEnabled;
	
	/** @var \Illuminate\Database\Eloquent\Model */
	protected $performedOn;
	
	/** @var \Illuminate\Database\Eloquent\Model */
	protected $causedBy;
	
	/** @var \Illuminate\Support\Collection */
	protected $properties;
	
	public function __construct(AuthManager $auth, Repository $config)
	{
		$this->auth = $auth;
		
		$this->properties = collect();
		
		$authDriver = $config['activitylog']['default_auth_driver'] ?? $auth->getDefaultDriver();
		
		$this->causedBy = $auth->guard($authDriver)->user();
		
		$this->logName = $config['activitylog']['default_log_name'];
		
		$this->logEnabled = $config['activitylog']['enabled'] ?? true;
	}
	
	public function performedOn(Model $model)
	{
		$this->performedOn = $model;
		
		return $this;
	}
	
	public function on(Model $model)
	{
		return $this->performedOn($model);
	}
	
	/**
	 * @param \Illuminate\Database\Eloquent\Model|int|string $modelOrId
	 *
	 * @return $this
	 */
	public function causedBy($modelOrId)
	{
		$model = $this->normalizeCauser($modelOrId);
		
		$this->causedBy = $model;
		
		return $this;
	}
	
	public function by($modelOrId)
	{
		return $this->causedBy($modelOrId);
	}
	
	/**
	 * @param array|\Illuminate\Support\Collection $properties
	 *
	 * @return $this
	 */
	public function withProperties($properties)
	{
		$this->properties = collect($properties);
		
		return $this;
	}
	
	/**
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return $this
	 */
	public function withProperty(string $key, $value)
	{
		$this->properties->put($key, $value);
		
		return $this;
	}
	
	public function useLog(string $logName)
	{
		$this->logName = $logName;
		
		return $this;
	}
	
	public function inLog(string $logName)
	{
		return $this->useLog($logName);
	}
	
	/**
	 * @param string $description
	 *
	 * @return null|mixed
	 */
	public function log(string $description)
	{
		if (!$this->logEnabled) {
			return null;
		}
		
		$Activity           = app('activitylog:instance');
		$Activity->log_name = $this->logName;
		
		if ($this->performedOn) {
			$Activity->subject()->associate($this->performedOn);
		}
		
		if ($this->causedBy) {
			$Activity->causer()->associate($this->causedBy);
		}
		
		$Activity->properties  = $this->properties->toArray();
		$Activity->description = $this->replacePlaceholders($description, $Activity);
		$Activity->save();
		
		return $Activity;
	}
	
	/**
	 * @param \Illuminate\Database\Eloquent\Model|int|string $modelOrId
	 *
	 * @throws \Boparaiamrit\ActivityLog\Exceptions\CouldNotLogActivity
	 *
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	protected function normalizeCauser($modelOrId): Model
	{
		if ($modelOrId instanceof Model) {
			return $modelOrId;
		}
		
		/** @noinspection PhpUndefinedMethodInspection */
		if ($model = $this->auth->getProvider()->retrieveById($modelOrId)) {
			return $model;
		}
		
		throw CouldNotLogActivity::couldNotDetermineUser($modelOrId);
	}
	
	protected function replacePlaceholders(string $description, Activity $Activity): string
	{
		return preg_replace_callback('/:[a-z0-9._-]+/i', function ($match) use ($Activity) {
			$match = $match[0];
			
			$attribute = (string)string($match)->between(':', '.');
			
			if (!in_array($attribute, ['subject', 'causer', 'properties'])) {
				return $match;
			}
			
			$propertyName = substr($match, strpos($match, '.') + 1);
			
			$attributeValue = $Activity->$attribute;
			
			$attributeValue = $attributeValue->toArray();
			
			return array_get($attributeValue, $propertyName, $match);
		}, $description);
	}
}
