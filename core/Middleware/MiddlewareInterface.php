<?php
declare(strict_types=1);

namespace Syncro\Middleware;

interface MiddlewareInterface
{
    /**
     * Handle the incoming request.
     * Throws an exception if the request cannot proceed.
     */
    public function handle(): void;
}
