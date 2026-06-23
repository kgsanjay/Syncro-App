<?php
declare(strict_types=1);

namespace Syncro\Services;

use Syncro\Models\Database;
use PDO;

class DatabaseQueue
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Push a new job onto the queue.
     *
     * @param string $jobClass The FQCN of the job to execute
     * @param array $data The payload/data needed for the job
     * @return bool
     */
    public function push(string $jobClass, array $data = []): bool
    {
        $payload = json_encode([
            'job' => $jobClass,
            'data' => $data
        ]);

        $stmt = $this->db->prepare("INSERT INTO jobs_queue (payload, attempts, created_at) VALUES (:payload, 0, :created_at)");
        return $stmt->execute([
            'payload' => $payload,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
