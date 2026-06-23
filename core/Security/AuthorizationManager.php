<?php
declare(strict_types=1);

namespace Syncro\Security;

use Syncro\Models\QueryBuilder;
use Syncro\Services\CacheManager;

class AuthorizationManager
{
    private static ?array $permissionsCache = null;

    /**
     * Checks if a role has a specific permission.
     * Uses caching to minimize database lookups.
     */
    public static function hasPermission(int $roleId, string $permission): bool
    {
        // For Super Admin bypass, assume roleId 1 is always Super Admin, or we handle that in User model.
        // Let's rely on the DB explicitly for now.
        
        $cacheKey = "role_permissions_{$roleId}";
        
        if (self::$permissionsCache === null || !isset(self::$permissionsCache[$roleId])) {
            $cached = CacheManager::getInstance()->get($cacheKey);
            
            if ($cached !== null) {
                self::$permissionsCache[$roleId] = $cached;
            } else {
                $qb = new QueryBuilder((new \Syncro\Models\Database())->getConnection());
                $permissions = $qb->table('permissions')
                                  ->select(['permissions.name'])
                                  ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                                  ->where('role_has_permissions.role_id', '=', $roleId)
                                  ->withoutTenantScope() // Global tables
                                  ->get();
                
                $permArray = array_column($permissions, 'name');
                CacheManager::getInstance()->set($cacheKey, $permArray, 3600); // Cache for 1 hour
                self::$permissionsCache[$roleId] = $permArray;
            }
        }

        return in_array($permission, self::$permissionsCache[$roleId]);
    }
}
