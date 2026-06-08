<?php
declare(strict_types=1);

namespace Syncro\Security;

use RuntimeException;

class SecurityManager
{
    /**
     * Generates and stores a cryptographically secure CSRF token.
     */
    public static function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            try {
                // FIX: Removed the insecure mt_rand() fallback. 
                // A secure system must fail CLOSED if cryptography cannot be guaranteed.
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } catch (\Exception $e) {
                error_log("CRITICAL SECURITY ERROR: System cannot generate cryptographically secure randomness.");
                throw new RuntimeException("A critical system security error occurred. Operations halted.");
            }
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validates the provided CSRF token against the session.
     */
    public static function validateCsrfToken(?string $token): bool
    {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Escapes output to prevent Cross-Site Scripting (XSS).
     * Must be used on ALL user-supplied data rendered to the browser.
     */
    public static function sanitizeOutput(string $data): string
    {
        return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
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