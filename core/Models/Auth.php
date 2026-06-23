<?php
declare(strict_types=1);

namespace Syncro\Models;

class Auth
{
    public static function user(): ?User
    {
        if (isset($_SESSION['user_id'])) {
            $user = new User();
            $user->id = (int)$_SESSION['user_id'];
            $user->role_id = isset($_SESSION['role_id']) ? (int)$_SESSION['role_id'] : null;
            return $user;
        }
        return null;
    }
}
