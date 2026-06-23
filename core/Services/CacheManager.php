<?php
declare(strict_types=1);

namespace Syncro\Services;

/**
 * @method static mixed get(string $key, mixed $default = null)
 * @method static bool set(string $key, mixed $value, int $ttl = 3600)
 * @method static bool delete(string $key)
 * @method static bool clear()
 */
class CacheManager
{
    private static ?CacheManager $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function getCacheDir(): string
    {
        $dir = __DIR__ . '/../../storage/framework/cache/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    private function getFilePath(string $key): string
    {
        return $this->getCacheDir() . md5($key) . '.cache';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            $content = @file_get_contents($file);
            if ($content !== false) {
                $data = @unserialize($content);
                if (is_array($data) && isset($data['expiry']) && $data['expiry'] > time()) {
                    return $data['content'];
                }
            }
            @unlink($file); // Expired or invalid
        }
        return $default;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $file = $this->getFilePath($key);
        $data = [
            'expiry'  => time() + $ttl,
            'content' => $value
        ];
        return file_put_contents($file, serialize($data), LOCK_EX) !== false;
    }

    public function delete(string $key): bool
    {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            return @unlink($file);
        }
        return true;
    }

    public function clear(): bool
    {
        $dir = $this->getCacheDir();
        $files = glob($dir . '*.cache');
        if ($files) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
        return true;
    }

    // Static proxies for direct static calls (e.g., CacheManager::get('key'))
    public static function __callStatic(string $name, array $arguments)
    {
        return self::getInstance()->$name(...$arguments);
    }
}