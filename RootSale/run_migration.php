<?php
require_once __DIR__ . '/includes/config.php';

try {
    // 1. Hardware Settings Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS hardware_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            printer_enabled INTEGER DEFAULT 0,
            drawer_command TEXT DEFAULT '\\x1B\\x70\\x00\\x19\\xFA',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Check if empty
    $count = $pdo->query("SELECT count(*) FROM hardware_settings")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("INSERT INTO hardware_settings (printer_enabled, drawer_command) VALUES (0, '\\x1B\\x70\\x00\\x19\\xFA')");
    }

    // 2. Customer Display Settings Table
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

    // 3. Insert customer_display user explicitly
    $stmt = $pdo->prepare("SELECT count(*) FROM users WHERE username = 'display'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('display123', PASSWORD_BCRYPT);
        $pdo->exec("INSERT INTO users (username, password, full_name, role) VALUES ('display', '$hash', 'Pantalla Cliente', 'customer_display')");
        echo "Usuario 'display' creado (pass: display123).\n";
    }

    echo "Migración completada exitosamente.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
