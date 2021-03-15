<?php

namespace App\Console;

use App\Models\Comment;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

        $redis_mysql_cron = env("REDIS_MYSQL_CRON", '* * * * *');
        // Sync from Redis to MySQL
        $schedule->call(function(){
            Log::info('Starting Redis dump');
            $processed = 0;
            Log::info('Updating Data');
            while($data = Redis::rpop('save:db')){
                try {
                    $row    = json_decode($data, true);
                    $class  = $row['model'];
                    $record = $row['object'];
                    $class::upsert($record, $record);
                    Log::info('Created: ' . $row['model']);
                    $processed++;
                } catch (\Exception $e) {
                    Log::error($e);
                }
            }
            Log::info('Finished Redis Dump, Records Updated: ' . $processed);

            Log::info('Deleting Data');
            $processed = 0;
            while($data = Redis::rpop('delete:db')){
                try {
                    $row    = json_decode($data, true);
                    $class  = $row['model'];
                    $record = $row['object'];
                    $class::destroy($record);
                    Log::info('Delete: ' . $row['model'] . ' - ' . $row['object']);
                    $processed++;
                } catch (\Exception $e) {
                    Log::error($e);
                }
            }
            Log::info('Finished Redis Dump, Records Deleted: ' . $processed);

        })->cron($redis_mysql_cron);

        // Clean up Redis entries not accessed for the last X mins
        $schedule->call(function(){
            Log::info('Starting Redis Cleanup');
            $processing_interval = env('REDIS_CACHE_CLEANUP', 5);
            $processed = 0;
            $score = time() - (60 * $processing_interval);
            try {
                $items = Redis::zRangeByScore('comments:accessed', 0, $score, ['withscores' => TRUE]);

                foreach($items as $id => $score){
                    Comment::UncacheRecord($id);
                }
            } catch (\Exception $e) {
                Log::error($e);
            }
            Log::info('Redis Cleanup Complete, Records cleared: ' . $processed);
        })->cron('* * * * *');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
