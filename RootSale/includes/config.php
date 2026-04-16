<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_PATH', __DIR__ . '/../database/rootsale.db');
define('BASE_URL', '/rootsale');

try {
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Auto setup database if it is empty
    $stmt = $pdo->query("SELECT count(*) FROM sqlite_master WHERE type='table' AND name='users'");
    if ($stmt->fetchColumn() == 0) {
        $sql = file_get_contents(__DIR__ . '/../database/schema.sql');
        $pdo->exec($sql);
    }

    // Auto setup new hardware/display tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS hardware_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            printer_enabled INTEGER DEFAULT 0,
            drawer_command TEXT DEFAULT '\\x1B\\x70\\x00\\x19\\xFA',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $count = $pdo->query("SELECT count(*) FROM hardware_settings")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("INSERT INTO hardware_settings (printer_enabled, drawer_command) VALUES (0, '\\x1B\\x70\\x00\\x19\\xFA')");
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS customer_display_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            welcome_text TEXT DEFAULT 'Bienvenido a nuestra tienda',
            ad_image_path TEXT DEFAULT '',
            show_cart INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $countDisplay = $pdo->query("SELECT count(*) FROM customer_display_settings")->fetchColumn();
    if ($countDisplay == 0) {
        $pdo->exec("INSERT INTO customer_display_settings (welcome_text, show_cart) VALUES ('Bienvenido a RootSale', 1)");
    }

    $stmtDisp = $pdo->query("SELECT count(*) FROM users WHERE username = 'display'");
    if ($stmtDisp->fetchColumn() == 0) {
        $hash = password_hash('display123', PASSWORD_BCRYPT);
        $pdo->exec("INSERT INTO users (username, password, full_name, role) VALUES ('display', '$hash', 'Pantalla Cliente', 'customer_display')");
    }
    
    // Fetch global shop settings
    $stmt = $pdo->query("SELECT * FROM shop_settings LIMIT 1");
    $globalSettings = $stmt->fetch();
    
    // Set currency default globally
    define('CURRENCY_SYMBOL', $globalSettings ? $globalSettings['currency_symbol'] : '₹');
    
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
