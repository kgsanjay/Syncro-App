<?php
declare(strict_types=1);

// Prevent output buffering issues and set time limit
set_time_limit(60);

require_once __DIR__ . '/../core/init.php';

use Syncro\Models\Database;

class QueueWorker
{
    private \PDO $db;
    private int $maxExecutionTime = 50; // stop after 50 seconds to respect 60s cron limit
    private int $startTime;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
        $this->startTime = time();
    }

    public function run(): void
    {
        echo "Starting worker...\n";

        // Try to fetch up to 10 jobs
        $jobs = $this->getAndReserveJobs(10);

        if (empty($jobs)) {
            echo "No jobs found. Exiting.\n";
            return;
        }

        foreach ($jobs as $job) {
            // Check if we are running out of time
            if ((time() - $this->startTime) >= $this->maxExecutionTime) {
                echo "Time limit reached, stopping worker early.\n";
                break;
            }

            $this->processJob($job);
        }

        echo "Worker finished.\n";
    }

    private function getAndReserveJobs(int $limit): array
    {
        try {
            $this->db->beginTransaction();

            // Fetch oldest unreserved jobs, or jobs that have been reserved for more than 10 minutes (stuck)
            $stmt = $this->db->prepare("
                SELECT id, payload, attempts
                FROM jobs_queue 
                WHERE reserved_at IS NULL 
                   OR reserved_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                ORDER BY created_at ASC 
                LIMIT :limit
                FOR UPDATE
            ");
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            $jobs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (!empty($jobs)) {
                $ids = array_column($jobs, 'id');
                $inQuery = implode(',', array_fill(0, count($ids), '?'));
                
                // Lock them
                $updateStmt = $this->db->prepare("UPDATE jobs_queue SET reserved_at = NOW() WHERE id IN ($inQuery)");
                $updateStmt->execute($ids);
            }

            $this->db->commit();
            return $jobs ?: [];

        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Worker DB Error: " . $e->getMessage());
            return [];
        }
    }

    private function processJob(array $job): void
    {
        $payload = json_decode($job['payload'], true);
        $jobClass = $payload['job'] ?? null;
        $data = $payload['data'] ?? [];

        echo "Processing job {$job['id']} ({$jobClass})...\n";

        try {
            if (!$jobClass || !class_exists($jobClass)) {
                throw new \Exception("Job class {$jobClass} not found.");
            }

            // Instantiate the job class. Expecting it to have a handle() method
            $instance = new $jobClass();
            if (method_exists($instance, 'handle')) {
                $instance->handle($data);
            } else {
                throw new \Exception("Job class {$jobClass} does not have a handle() method.");
            }

            // Success, delete job
            $stmt = $this->db->prepare("DELETE FROM jobs_queue WHERE id = :id");
            $stmt->execute(['id' => $job['id']]);
            
            echo "Job {$job['id']} completed successfully.\n";

        } catch (\Syncro\Exceptions\TemporarySyncException $e) {
            error_log("Job {$job['id']} temporarily failed: " . $e->getMessage());
            $attempts = (int)$job['attempts'] + 1;
            // Exponential backoff: 5 mins, 10 mins, 15 mins... or just 5 minutes flat? 
            // "delay the reserved_at time by 5 minutes to implement an exponential backoff" - maybe 5 * attempts
            $delayMinutes = 5 * $attempts;
            
            $stmt = $this->db->prepare("
                UPDATE jobs_queue 
                SET attempts = attempts + 1, 
                    reserved_at = DATE_ADD(NOW(), INTERVAL :delay MINUTE)
                WHERE id = :id
            ");
            $stmt->execute(['id' => $job['id'], 'delay' => $delayMinutes]);
            echo "Job {$job['id']} temporarily failed. Retrying in {$delayMinutes} minutes.\n";
            
        } catch (\Throwable $e) {
            error_log("Job {$job['id']} failed fatally: " . $e->getMessage());
            
            $stmt = $this->db->prepare("
                UPDATE jobs_queue 
                SET attempts = attempts + 1, reserved_at = NULL 
                WHERE id = :id
            ");
            $stmt->execute(['id' => $job['id']]);
            
            echo "Job {$job['id']} failed.\n";
        }
    }
}

// Execute worker
$worker = new QueueWorker();
$worker->run();
