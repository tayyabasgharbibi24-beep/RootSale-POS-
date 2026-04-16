<?php
require_once __DIR__ . '/includes/config.php';
try {
    // Add iva_rate
    $pdo->exec("ALTER TABLE invoice_settings ADD COLUMN iva_rate DECIMAL(5,2) DEFAULT 21.00");
    echo "Added iva_rate column.\n";
} catch (Exception $e) {
    echo "iva_rate missing column might already exist: " . $e->getMessage() . "\n";
}
try {
    $pdo->exec("UPDATE invoice_settings SET iva_rate = 21.00 WHERE id = 1");
    echo "Set default IVA rate.\n";
} catch (Exception $e) {
    echo "Error updating IVA: " . $e->getMessage() . "\n";
}
echo "Migration complete.\n";
?>
