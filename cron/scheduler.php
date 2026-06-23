<?php
declare(strict_types=1);

// This script should be run via CLI by a cron job every minute:
// * * * * * php /Applications/XAMPP/xamppfiles/htdocs/syncro/cron/scheduler.php >> /var/log/syncro_scheduler.log 2>&1

if (php_sapi_name() !== 'cli' && !isset($_GET['force'])) {
    http_response_code(403);
    die('Forbidden: Must be run via CLI.');
}

require_once dirname(__DIR__) . '/core/init.php';

use Syncro\Services\Scheduler;
use Syncro\Jobs\NightAuditJob;
use Syncro\Jobs\YieldEngineJob;
use Syncro\Jobs\SyncEngineJob;
use Syncro\Jobs\CancellationsJob;
use Syncro\Jobs\CommunicationsJob;
use Syncro\Jobs\ProcessAuditLogsJob;

try {
    $scheduler = new Scheduler();

    // Register all background jobs
    $scheduler->registerJob(new CommunicationsJob()); // Runs every minute
    $scheduler->registerJob(new SyncEngineJob());     // Runs every 5 mins
    $scheduler->registerJob(new YieldEngineJob());    // Runs every 15 mins
    $scheduler->registerJob(new CancellationsJob());  // Runs hourly
    $scheduler->registerJob(new NightAuditJob());     // Runs at night
    $scheduler->registerJob(new ProcessAuditLogsJob()); // Runs every minute

    // Execute any due jobs
    $scheduler->run();

} catch (\Throwable $e) {
    error_log("[SCHEDULER CRASH] " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    exit(1);
}
