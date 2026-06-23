<?php
declare(strict_types=1);

namespace Syncro\Services;

use Syncro\Models\Database;
use Syncro\Jobs\BaseJob;
use PDO;
use Exception;

class Scheduler
{
    /** @var BaseJob[] */
    private array $jobs = [];

    public function registerJob(BaseJob $job): void
    {
        $this->jobs[] = $job;
    }

    public function run(): void
    {
        $db = Database::getConnection();

        foreach ($this->jobs as $job) {
            if (!$job->isDue()) {
                continue;
            }

            if ($this->acquireLock($db, $job->getName())) {
                try {
                    $job->execute();
                } finally {
                    $this->releaseLock($db, $job->getName());
                }
            } else {
                // Job is already running, skip execution this minute.
            }
        }
    }

    private function acquireLock(PDO $db, string $jobName): bool
    {
        try {
            // Attempt to insert a lock record. If it already exists (and hasn't expired), this will fail.
            // We'll also implement a simple timeout mechanism (e.g., 1 hour) for stale locks.
            
            // Clean up stale locks first (older than 1 hour)
            $cleanup = $db->prepare("DELETE FROM job_locks WHERE job_name = ? AND locked_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
            $cleanup->execute([$jobName]);

            // Try to acquire lock
            $stmt = $db->prepare("INSERT INTO job_locks (job_name, locked_at) VALUES (?, NOW())");
            return $stmt->execute([$jobName]);
        } catch (Exception $e) {
            // Unique constraint violation means it's already locked
            return false;
        }
    }

    private function releaseLock(PDO $db, string $jobName): void
    {
        try {
            $stmt = $db->prepare("DELETE FROM job_locks WHERE job_name = ?");
            $stmt->execute([$jobName]);
        } catch (Exception $e) {
            // Silently ignore release failures
        }
    }
}
