<?php
$dbFile = __DIR__ . '/rootsale.db';
$sqlFile = __DIR__ . '/schema.sql';

try {
    if (file_exists($dbFile)) {
        echo "Database already exists.\n";
        exit;
    }
    
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = file_get_contents($sqlFile);
    $pdo->exec($sql);
    
    echo "Database created successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
