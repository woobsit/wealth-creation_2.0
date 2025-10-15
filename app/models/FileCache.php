<?php
class FileCache {
    private string $cache_dir = __DIR__ . '/../cache/';
    private const DEFAULT_TTL = 3600; // 1 hour (Time To Live in seconds)

    public function __construct(string $custom_dir = '') {
        if (!empty($custom_dir)) {
            $this->cache_dir = rtrim($custom_dir, '/') . '/';
        }
        
        // Ensure the cache directory exists and is writable
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0777, true);
        }
    }

    /**
     * Generates a unique, URL-safe key for the officer rating.
     */
    public static function generateKey(string $officer_id, string $month, string $year): string {
        // Use SHA1 to create a fixed-length, safe filename hash
        $raw_key = "officer_rating_{$officer_id}_{$year}_{$month}";
        return sha1($raw_key) . '.cache';
    }

    /**
     * Retrieves data from the file cache if it is not expired.
     */
    public function get(string $key): array|false {
        $file_path = $this->cache_dir . $key;

        if (file_exists($file_path)) {
            // Check file expiry (filemtime is file modification time)
            if (time() - filemtime($file_path) < self::DEFAULT_TTL) {
                $contents = file_get_contents($file_path);
                // Unserialize the data for array reconstruction
                return unserialize($contents);
            } else {
                // Cache expired, delete the old file
                unlink($file_path);
            }
        }
        return false;
    }

    /**
     * Saves data to the file cache.
     */
    public function set(string $key, array $data): bool {
        $file_path = $this->cache_dir . $key;
        // Serialize the array before saving it to the file
        $contents = serialize($data);
        return (bool)file_put_contents($file_path, $contents);
    }
}