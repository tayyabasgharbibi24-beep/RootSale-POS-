<?php
// pages/customer-display/get-state.php
header('Content-Type: application/json');

$cashier_id = isset($_GET['cashier']) ? (int)$_GET['cashier'] : 0;
if ($cashier_id <= 0) {
    echo json_encode(['error' => 'No cashier specified']);
    exit;
}

$file = __DIR__ . '/states/state_' . $cashier_id . '.json';

if (file_exists($file)) {
    echo file_get_contents($file);
} else {
    // Return empty state if no active session
    echo json_encode(['items' => [], 'total' => 0]);
}
