<?php
// Called by the admin's browser to mark admin as active
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$file = __DIR__ . '/data/admin_status.json';
$data = ['last_active' => date('c')];

// atomic write
$tmp = $file . '.tmp';
if (file_put_contents($tmp, json_encode($data)) === false || !rename($tmp, $file)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to write status']);
    exit;
}

echo json_encode(['ok' => true, 'last_active' => $data['last_active']]);
