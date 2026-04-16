<?php
require_once __DIR__ . '/includes/config.php';

try {
    // Adding user_profiles
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_profiles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER UNIQUE,
        email TEXT,
        phone TEXT,
        profile_image TEXT,
        last_password_change DATETIME,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    // Adding invoice_settings
    $pdo->exec("CREATE TABLE IF NOT EXISTS invoice_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        receipt_type TEXT DEFAULT 'thermal',
        a4_logo_path TEXT,
        a4_footer_text TEXT,
        a4_terms_conditions TEXT,
        tax_type TEXT DEFAULT 'gst',
        cgst_rate DECIMAL(5,2) DEFAULT 2.5,
        sgst_rate DECIMAL(5,2) DEFAULT 2.5,
        igst_rate DECIMAL(5,2) DEFAULT 5,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Check if invoice_settings exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM invoice_settings");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO invoice_settings (receipt_type, a4_footer_text, a4_terms_conditions) 
        VALUES ('thermal', 'Thank you for shopping!', 'Goods once sold cannot be returned unless defective.')");
    }

    // Adding return_receipts
    $pdo->exec("CREATE TABLE IF NOT EXISTS return_receipts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        return_id INTEGER UNIQUE,
        shop_copy_printed INTEGER DEFAULT 0,
        customer_copy_printed INTEGER DEFAULT 0,
        printed_by INTEGER,
        printed_at DATETIME,
        FOREIGN KEY (return_id) REFERENCES returns(id)
    )");

    echo "Migration completed successfully.";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage());
}
