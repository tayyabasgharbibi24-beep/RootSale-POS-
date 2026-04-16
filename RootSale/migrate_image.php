<?php
$db = new PDO('sqlite:database/rootsale.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $db->exec("ALTER TABLE products ADD COLUMN image TEXT");
    echo "Column 'image' added to 'products' successfully.\n";
} catch (PDOException $e) {
    echo "Notice: " . $e->getMessage() . "\n";
}
?>
