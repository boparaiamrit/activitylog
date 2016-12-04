<?php

namespace Boparaiamrit\ActivityLog;


use Boparaiamrit\ActivityLog\Exceptions\InvalidConfiguration;
use Boparaiamrit\ActivityLog\Models\Activity;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class ActivityLogServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap the application events.
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__ . '/../config/activitylog.php' => config_path('activitylog.php'),
		], 'config');
		
		$this->mergeConfigFrom(__DIR__ . '/../config/activitylog.php', 'activitylog');
	}
	
	/**
	 * Register the service provider.
	 */
	public function register()
	{
		require 'helpers.php';
		
		$this->app->singleton('activitylog:class', function ($app) {
			/** @var Application $app */
			/** @noinspection PhpUndefinedMethodInspection */
			$activityModelClass = $app['config']->get('activitylog.activity_model');
			
			if (!is_a($activityModelClass, Activity::class, true)) {
				throw InvalidConfiguration::modelIsNotValid($activityModelClass);
			}
			
			return $activityModelClass;
		});
		
		$this->app->singleton('activitylog:instance', function ($app) {
			return new $app['activitylog:class'];
		});
		
		$this->app->singleton('activitylog', function ($app) {
			return new ActivityLogger($app['auth'], $app['config']);
		});
		
		$this->app->bind('command.activitylog:clean', CleanActivityLogCommand::class);
		
		$this->commands([
			'command.activitylog:clean',
		]);
	}
}
