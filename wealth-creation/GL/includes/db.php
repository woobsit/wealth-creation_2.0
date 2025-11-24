<?php
$dbPath = '/home/runner/workspace/data/accounting.db';

if (!file_exists($dbPath)) {
    $dbDir = dirname($dbPath);
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
    }
}

$dsn = "sqlite:" . $dbPath;

try {
    $db = new PDO($dsn, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $db->exec("PRAGMA foreign_keys = ON;");
} catch (Exception $e) {
    die("DB Error: " . $e->getMessage());
}

?>