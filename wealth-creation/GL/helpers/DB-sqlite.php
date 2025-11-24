<?php
class DB {
    private static $instance = null;

    public static function conn() {
        if (self::$instance === null) {
            $dbPath = '/home/runner/workspace/data/accounting.db';
            
            if (!file_exists($dbPath)) {
                $dbDir = dirname($dbPath);
                if (!is_dir($dbDir)) {
                    mkdir($dbDir, 0755, true);
                }
            }

            $dsn = "sqlite:" . $dbPath;

            try {
                self::$instance = new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                
                self::$instance->exec("PRAGMA foreign_keys = ON;");
            } catch (PDOException $e) {
                die("DB Error: " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}
?>
