<?php
declare(strict_types=1);

namespace Syncro\Jobs;

use Syncro\Models\Database;
use Exception;

class ProcessAuditLogsJob extends BaseJob
{
    private string $queueFile;
    private string $processingFile;

    public function __construct()
    {
        $logDir = __DIR__ . '/../../storage/logs';
        $this->queueFile = $logDir . '/audit_queue.jsonl';
        $this->processingFile = $logDir . '/audit_queue.processing.jsonl';
    }

    public function getName(): string
    {
        return 'ProcessAuditLogs';
    }

    /**
     * Executes every minute
     */
    public function isDue(): bool
    {
        return true; 
    }

    public function handle(): void
    {
        // If there's no queue file, nothing to do
        if (!file_exists($this->queueFile) || filesize($this->queueFile) === 0) {
            // Also check if a previous processing file was left over
            if (file_exists($this->processingFile)) {
                $this->processFile();
            }
            return;
        }

        // Atomically rename to processing to prevent race conditions with new log entries
        if (rename($this->queueFile, $this->processingFile)) {
            $this->processFile();
        }
    }

    private function processFile(): void
    {
        $lines = file($this->processingFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false || empty($lines)) {
            @unlink($this->processingFile);
            return;
        }

        $db = Database::getConnection();
        
        try {
            $db->beginTransaction();

            $stmt = $db->prepare("
                INSERT INTO audit_logs (hotel_id, user_id, action_type, description, ip_address, created_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($lines as $line) {
                $data = json_decode($line, true);
                if ($data) {
                    $stmt->execute([
                        $data['hotel_id'] ?? null,
                        $data['user_id'] ?? null,
                        $data['action_type'] ?? 'UNKNOWN',
                        $data['description'] ?? '',
                        $data['ip_address'] ?? null,
                        $data['created_at'] ?? date('Y-m-d H:i:s')
                    ]);
                }
            }

            $db->commit();

            // Successfully processed, delete the processing file
            @unlink($this->processingFile);

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            
            // Rename to failed so we don't lose logs, but don't block the next run
            $failedFile = $this->processingFile . '.' . time() . '.failed';
            @rename($this->processingFile, $failedFile);
            
            throw $e;
        }
    }
}
