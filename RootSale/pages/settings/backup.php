<?php
// pages/settings/backup.php
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$db_file = DB_PATH;

if (!file_exists($db_file)) {
    die("Error: Base de datos no encontrada.");
}

$filename = 'rootsale_backup_' . date('Ymd_His') . '.db';

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($db_file));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($db_file);
exit;
