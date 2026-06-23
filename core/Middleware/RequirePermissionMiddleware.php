<?php
declare(strict_types=1);

namespace Syncro\Middleware;

use Syncro\Models\Auth;

class RequirePermissionMiddleware implements MiddlewareInterface
{
    private string $permission;

    public function __construct(string $permission = '')
    {
        $this->permission = $permission;
    }

    public function handle(): void
    {
        if (empty($this->permission)) {
            // If misconfigured, deny by default
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['error' => 'Permission constraint misconfigured.']);
            exit;
        }

        $user = Auth::user();

        if (!$user) {
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(['error' => 'Unauthenticated.']);
            exit;
        }

        if (!$user->can($this->permission)) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['error' => "Missing required permission: {$this->permission}"]);
            exit;
        }
    }
}
