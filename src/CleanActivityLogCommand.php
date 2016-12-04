<?php

namespace Boparaiamrit\ActivityLog;


use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanActivityLogCommand extends Command
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $signature = 'activitylog:clean';
	
	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Clean up old records from the activity log.';
	
	public function handle()
	{
		$this->comment('Cleaning activity log...');
		
		$maxAgeInDays = config('activitylog.expires');
		
		$cutOffDate = Carbon::now()->subDays($maxAgeInDays);
		
		$activity = app('activitylog')->getActivityModel();
		
		$amountDeleted = $activity::where('created_at', '<', $cutOffDate)->delete();
		
		$this->info("Deleted {$amountDeleted} record(s) from the activity log.");
		
		$this->comment('All done!');
	}
}
