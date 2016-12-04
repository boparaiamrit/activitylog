<?php

namespace Boparaiamrit\ActivityLog;


use Boparaiamrit\ActivityLog\Exceptions\CouldNotLogActivity;
use Boparaiamrit\ActivityLog\Exceptions\InvalidConfiguration;
use Boparaiamrit\ActivityLog\Models\Activity;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Traits\Macroable;
use Jenssegers\Mongodb\Eloquent\Model;

class ActivityLogger
{
	use Macroable;
	
	/** @var AuthManager */
	protected $Auth;
	
	/**
	 * @var Repository
	 */
	protected $Config;
	
	protected $logName = '';
	
	/** @var bool */
	protected $logEnabled;
	
	/** @var Model */
	protected $performedOn;
	
	/** @var Model */
	protected $causedBy;
	
	/** @var \Illuminate\Support\Collection */
	protected $properties;
	
	public function __construct(AuthManager $auth, Repository $config)
	{
		$this->Auth   = $auth;
		$this->Config = $config;
		
		$this->properties = collect();
		
		$authDriver     = $config['activitylog']['auth_driver'] ?? $auth->getDefaultDriver();
		$this->causedBy = $auth->guard($authDriver)->user();
		
		$this->logName    = $config['activitylog']['log_name'];
		$this->logEnabled = $config['activitylog']['enabled'] ?? true;
	}
	
	public function performedOn(Model $model)
	{
		$this->performedOn = $model;
		
		return $this;
	}
	
	/**
	 * @param Model|int|string $modelOrId
	 *
	 * @return $this
	 */
	public function causedBy($modelOrId)
	{
		$model = $this->normalizeCauser($modelOrId);
		
		$this->causedBy = $model;
		
		return $this;
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
	
	public function useLogName(string $logName)
	{
		$this->logName = $logName;
		
		return $this;
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
		
		$Activity = $this->getActivityModel();
		
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
	 * @param Model|int|string $modelOrId
	 *
	 * @throws CouldNotLogActivity
	 *
	 * @return Model
	 */
	protected function normalizeCauser($modelOrId): Model
	{
		if ($modelOrId instanceof Model) {
			return $modelOrId;
		}
		
		/** @noinspection PhpUndefinedMethodInspection */
		if ($model = $this->Auth->getProvider()->retrieveById($modelOrId)) {
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
	
	/**
	 * @return Activity
	 *
	 * @throws InvalidConfiguration
	 */
	public function getActivityModel(): Activity
	{
		/** @noinspection PhpUndefinedMethodInspection */
		$activityModelClass = $this->Config->get('activitylog.activity_model');
		
		if (!is_a($activityModelClass, Activity::class, true)) {
			throw InvalidConfiguration::modelIsNotValid($activityModelClass);
		}
		
		return new $activityModelClass;
	}
}
