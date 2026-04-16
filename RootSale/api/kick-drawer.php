<?php
require_once __DIR__ . '/../includes/header.php'; // Header handles session and DB

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$ip = '';
if (isset($_POST['ip'])) {
    $ip = trim($_POST['ip']);
} else {
    // Attempt to fetch from DB if not explicitly passed
    try {
        $hw = $pdo->query("SELECT * FROM hardware_settings LIMIT 1")->fetch();
        if ($hw && $hw['connection_type'] === 'network') {
            $ip = trim($hw['printer_ip']);
        }
    } catch(Exception $e) {}
}

if (empty($ip)) {
    echo json_encode(['success' => false, 'message' => 'IP de impresora no configurada']);
    exit;
}

// Timeout to prevent hanging if IP is totally wrong/down
$timeout = 2; 

// Port 9100 is standard for raw ESC/POS network printing
$fp = @fsockopen($ip, 9100, $errno, $errstr, $timeout);

if (!$fp) {
    echo json_encode(['success' => false, 'message' => "No se pudo conectar a $ip:9100. Error: $errstr ($errno)"]);
} else {
    // Send ESC/POS Cash Drawer open command
    // \x1B\x70\x00\x19\xFA
    $command = chr(27) . chr(112) . chr(0) . chr(25) . chr(250);
    fwrite($fp, $command);
    fclose($fp);
    
    echo json_encode(['success' => true, 'message' => 'Comando enviado con éxito']);
}
