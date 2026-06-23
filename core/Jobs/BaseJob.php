<?php
declare(strict_types=1);

namespace Syncro\Jobs;

use Syncro\Models\Database;
use PDO;
use Exception;

abstract class BaseJob implements JobInterface
{
    /**
     * Executes the job logic safely within a try/catch, logging to audit_logs.
     */
    final public function execute(): void
    {
        $startTime = microtime(true);
        $db = Database::getConnection();
        
        try {
            $this->handle();
            
            $duration = microtime(true) - $startTime;
            $this->logExecution($db, 'success', "Job executed successfully in " . round($duration, 2) . " seconds.");
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            
            $duration = microtime(true) - $startTime;
            $this->logExecution($db, 'failed', "Job failed after " . round($duration, 2) . " seconds. Error: " . $e->getMessage());
        }
    }

    private function logExecution(PDO $db, string $status, string $message): void
    {
        try {
            $stmt = $db->prepare("
                INSERT INTO audit_logs (hotel_id, user_id, action_type, description, ip_address, created_at)
                VALUES (NULL, NULL, 'SYSTEM_JOB', ?, '127.0.0.1', NOW())
            ");
            
            $fullMessage = "[" . $this->getName() . "] [" . strtoupper($status) . "] " . $message;
            $stmt->execute([$fullMessage]);
        } catch (Exception $e) {
            // Failsafe: if audit logging fails, write to PHP error log so it isn't lost completely.
            error_log("Failed to write to audit_logs for job " . $this->getName() . ": " . $e->getMessage());
        }
    }

    /**
     * Default implementation. Subclasses should override this
     * if they need to run at specific intervals.
     */
    public function isDue(): bool
    {
        return true;
    }
}
