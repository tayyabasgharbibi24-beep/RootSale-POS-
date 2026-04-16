<?php
// pages/customer-display/update-state.php
session_start();
require_once __DIR__ . '/../../includes/auth.php';

// Only logged in users (cashiers/admins) can update the state
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'No autorizado']));
}

$cashier_id = $_SESSION['user_id'];
$data = file_get_contents('php://input');

$stateDir = __DIR__ . '/states';
if (!is_dir($stateDir)) {
    mkdir($stateDir, 0777, true);
}

$file = $stateDir . '/state_' . $cashier_id . '.json';
file_put_contents($file, $data);

echo json_encode(['success' => true]);
