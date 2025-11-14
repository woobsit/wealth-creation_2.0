<?php

class FileCacheAccount {
    // Cache lifetime: 1 hour (in seconds)
    const ACCOUNT_TTL = 3600;

    // Default cache directory (relative to this file)
    private $cache_dir;

    public function __construct($custom_dir = '') {
        // Default to ../cache/ relative to this file
        $this->cache_dir = __DIR__ . '/../cache/';

        // If custom directory provided
        if (!empty($custom_dir)) {
            $this->cache_dir = rtrim($custom_dir, '/') . '/';
        }

        // Ensure the cache directory exists
        if (!is_dir($this->cache_dir)) {
            if (!mkdir($this->cache_dir, 0777, true)) {
                error_log("Failed to create cache directory: " . $this->cache_dir);
            }
        }

        // Ensure the directory is writable
        if (!is_writable($this->cache_dir)) {
            // Try to make it writable (may need proper user permissions)
            chmod($this->cache_dir, 0777);
        }
    }

    /**
     * Generates a unique cache key for Account Dashboard Stats
     */
    public static function generateAccountStatsKey() {
        $period_key = date('Y-W'); // Year-Week format
        $raw_key = "account_stats_" . $period_key;
        return sha1($raw_key) . '.cache';
    }

    /**
     * Get cached data if it’s still valid
     */
    public function get($key) {
        $file_path = $this->cache_dir . $key;

        if (file_exists($file_path)) {
            // Check if cache is still valid
            if (time() - filemtime($file_path) < self::ACCOUNT_TTL) {
                $contents = file_get_contents($file_path);
                return unserialize($contents);
            } else {
                // Expired cache file — remove it
                @unlink($file_path);
            }
        }
        return false;
    }

    /**
     * Save data to cache
     */
    public function set($key, $data) {
        $file_path = $this->cache_dir . $key;
        $contents = serialize($data);

        // Attempt to write file
        $result = file_put_contents($file_path, $contents);

        // Ensure file is writable (important on Ubuntu)
        if ($result !== false) {
            chmod($file_path, 0666);
            return true;
        }

        return false;
    }
}
?>
