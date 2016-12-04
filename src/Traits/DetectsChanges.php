<?php

namespace Boparaiamrit\ActivityLog\Traits;


use Illuminate\Support\Collection;
use Jenssegers\Mongodb\Eloquent\Model;

trait DetectsChanges
{
	protected $oldAttributes = [];
	
	protected static function bootDetectsChanges()
	{
		/** @var Collection $EventsToBeRecorded */
		$EventsToBeRecorded = static::eventsToBeRecorded();
		if ($EventsToBeRecorded->contains('updated')) {
			/** @noinspection PhpUndefinedMethodInspection */
			static::updating(function (Model $Model) {
				
				//temporary hold the original attributes on the model
				//as we'll need these in the updating event
				/** @var Model $OldModel */
				$OldModel = $Model->replicate()->setRawAttributes($Model->getOriginal());
				
				/** @noinspection PhpUndefinedFieldInspection */
				$Model->oldAttributes = static::logChanges($OldModel);
			});
		}
	}
	
	public function attributesToBeLogged(): array
	{
		/** @noinspection PhpUndefinedFieldInspection */
		if (!isset(static::$logAttributes)) {
			return [];
		}
		
		/** @noinspection PhpUndefinedFieldInspection */
		return static::$logAttributes;
	}
	
	public function attributeValuesToBeLogged(string $processingEvent): array
	{
		/** @var Model $this */
		if (!count($this->attributesToBeLogged())) {
			return [];
		}
		
		$properties['attributes'] = static::logChanges($this);
		
		/** @var Collection $EventsToBeRecorded */
		$EventsToBeRecorded = static::eventsToBeRecorded();
		
		if ($EventsToBeRecorded->contains('updated') && $processingEvent == 'updated') {
			$nullProperties = array_fill_keys(array_keys($properties['attributes']), null);
			
			$properties['old'] = array_merge($nullProperties, $this->oldAttributes);
		}
		
		return $properties;
	}
	
	/**
	 * @param Model $Model
	 *
	 * @return array
	 */
	public static function logChanges(Model $Model): array
	{
		/** @noinspection PhpUndefinedMethodInspection */
		return collect($Model)->only($Model->attributesToBeLogged())->toArray();
	}
}
