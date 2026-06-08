<?php
declare(strict_types=1);

namespace Syncro\Services;

class CacheManager
{
    private static function getCacheDir(): string
    {
        $dir = __DIR__ . '/../../cache/';
        if (!is_dir($dir)) {
            // Added concurrent safety to mkdir
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    public static function get(string $key)
    {
        $file = self::getCacheDir() . md5($key) . '.cache';
        
        if (file_exists($file)) {
            // Suppress warnings in case another process deletes it right as we read it
            $data = @include $file; 
            
            if (is_array($data) && isset($data['expiry']) && $data['expiry'] > time()) {
                return $data['content'];
            }
            
            // @ prevents errors if another request already deleted it
            @unlink($file); 
        }
        return false;
    }

    public static function set(string $key, $content, int $ttlSeconds = 900): void
    {
        $file = self::getCacheDir() . md5($key) . '.cache';
        $data = [
            'expiry'  => time() + $ttlSeconds,
            'content' => $content
        ];
        
        // Exporting as native PHP code allows OPcache to compile it instantly
        $export = '<?php return ' . var_export($data, true) . ';';
        
        // FIXED: Added LOCK_EX to prevent file corruption during simultaneous writes
        file_put_contents($file, $export, LOCK_EX);
    }
    
    public static function clear(string $key): void
    {
        $file = self::getCacheDir() . md5($key) . '.cache';
        if (file_exists($file)) {
            @unlink($file);
        }
    }
}