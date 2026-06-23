<?php
declare(strict_types=1);

namespace Syncro\Security;

use RuntimeException;

class SecurityManager
{

    /**
     * Sanitizes output to prevent XSS attacks.
     */
    public static function sanitizeOutput(?string $data): string
    {
        return htmlspecialchars($data ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Hashes a password using the enterprise-standard Argon2id algorithm.
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost'   => 4,
            'threads'     => 2
        ]);
    }

    /**
     * Verifies incoming OTA webhooks using HMAC SHA-256.
     */
    public static function verifyWebhookSignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }
}