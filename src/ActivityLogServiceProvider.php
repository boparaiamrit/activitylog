<?php

namespace Boparaiamrit\ActivityLog;


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
	}
	
	/**
	 * Register the service provider.
	 */
	public function register()
	{
		require 'helpers.php';
		
		$this->mergeConfigFrom(__DIR__ . '/../config/activitylog.php', 'activitylog');
		
		$this->app->singleton('activitylog', function ($app) {
			return new ActivityLogger($app['auth'], $app['config']);
		});
		
		$this->app->bind('command.activitylog:clean', CleanActivityLogCommand::class);
		
		$this->commands([
			'command.activitylog:clean',
		]);
	}
}
