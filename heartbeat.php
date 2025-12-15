<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$dataDir = __DIR__ . '/data';
$file = $dataDir . '/admin_status.json';
if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);
$status = ['last_active' => time()];
file_put_contents($file, json_encode($status));
echo json_encode(['ok' => true, 'last_active' => $status['last_active']]);
