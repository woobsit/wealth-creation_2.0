<?php

class FileCacheAccount {
    // Shorter Time To Live for real-time Account Dashboard data (25 minutes)
    const ACCOUNT_TTL = 3600; // 1 hour (Time To Live in seconds)
    
    // Default directory, assuming it's in the same structure as the other cache class
    private $cache_dir = __DIR__ . '/../cache/'; // NOTE: Using a different subdirectory is safer
    
    // We'll use the shorter TTL here for consistency, though the constructor doesn't directly use it.
    
    public function __construct($custom_dir = '') {
        if (!empty($custom_dir)) {
            $this->cache_dir = rtrim($custom_dir, '/') . '/';
        }
        
        // Ensure the cache directory exists and is writable
        if (!is_dir($this->cache_dir)) {
            // Attempt to create the directory recursively
            if (!mkdir($this->cache_dir, 0777, true)) {
                // You might want to throw an exception here in a production environment
                error_log("Failed to create cache directory: " . $this->cache_dir);
            }
        }
    }

    /**
     * Generates a unique, URL-safe key for Account Dashboard Stats.
     * This key is tied to the current period to ensure cache freshness.
     */
    public static function generateAccountStatsKey() {
        // Use Y-W (Year-Week) for the key, as it changes less frequently than day but more frequently than month.
        $period_key = date('Y-W'); 
        $raw_key = "account_stats_{$period_key}";
        return sha1($raw_key) . '.cache';
    }

    /**
     * Retrieves data from the file cache if it is not expired (using ACCOUNT_TTL).
     */
    public function get($key) {
        $file_path = $this->cache_dir . $key;

        if (file_exists($file_path)) {
            // Check file expiry against the shorter 5-minute TTL
            if (time() - filemtime($file_path) < self::ACCOUNT_TTL) {
                $contents = file_get_contents($file_path);
                // Unserialize the data for array reconstruction
                return unserialize($contents);
            } else {
                // Cache expired, delete the old file
                @unlink($file_path); // Use @ to suppress potential errors if file is locked
            }
        }
        return false;
    }

    /**
     * Saves data to the file cache.
     */
    public function set($key, $data) {
        $file_path = $this->cache_dir . $key;
        // Serialize the array before saving it to the file
        $contents = serialize($data);
        return (bool)file_put_contents($file_path, $contents);
    }
}
