<?php
declare(strict_types=1);

namespace Syncro\Models;

use Syncro\Security\AuthorizationManager;

class User
{
    public int $id;
    public ?int $role_id;

    public function can(string $permission): bool
    {
        if (!$this->role_id) {
            // Fallback for super admins or legacy roles if needed, 
            // but normally we check if role_id is present.
            return false;
        }
        
        return AuthorizationManager::hasPermission($this->role_id, $permission);
    }
}
