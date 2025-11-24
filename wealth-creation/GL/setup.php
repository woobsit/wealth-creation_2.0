<?php
require_once 'includes/db.php';

echo "Setting up database...\n";

try {
    $schema = file_get_contents(__DIR__ . '/setup_database.sql');
    
    $lines = explode("\n", $schema);
    $statement = '';
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }
        
        $statement .= ' ' . $line;
        
        if (substr($statement, -1) === ';') {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $db->exec($statement);
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'UNIQUE constraint failed') === false &&
                        strpos($e->getMessage(), 'already exists') === false &&
                        strpos($e->getMessage(), 'table') === false) {
                        echo "Warning executing: " . substr($statement, 0, 50) . "...\n";
                        echo "Error: " . $e->getMessage() . "\n";
                    }
                }
            }
            $statement = '';
        }
    }
    
    echo "Database setup completed successfully!\n\n";
    
    try {
        $count = $db->query("SELECT COUNT(*) FROM accounts")->fetchColumn();
        echo "Accounts created: $count\n";
        
        $txCount = $db->query("SELECT COUNT(*) FROM account_general_transaction_new")->fetchColumn();
        echo "Sample transactions created: $txCount\n";
    } catch (Exception $e) {
        echo "Could not count records: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    die("Setup failed: " . $e->getMessage() . "\n");
}
?>
