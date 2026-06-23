<?php
declare(strict_types=1);

namespace Syncro\Jobs;

interface JobInterface
{
    /**
     * Determine if the job should run at the current time.
     * This allows the Scheduler to call run() every minute,
     * but the Job only executes its core logic if due.
     */
    public function isDue(): bool;

    /**
     * Execute the core job logic.
     */
    public function handle(): void;

    /**
     * Get the unique name of the job for locking.
     */
    public function getName(): string;
}
