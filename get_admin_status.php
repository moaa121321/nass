<?php
session_start();
header('Content-Type: application/json');

$file = __DIR__ . '/data/admin_status.json';
$last = null;
if (is_readable($file)) {
    $data = json_decode(file_get_contents($file), true) ?: [];
    $last = isset($data['last_active']) ? intval($data['last_active']) : null;
}

// if admin himself is asking, consider them active now
if (isset($_SESSION['user']) && $_SESSION['user'] === 'admin') {
    $last = time();
}

if ($last === null) {
    echo json_encode(['online' => false, 'last_active' => null]);
    exit;
}

$now = time();
$online = false;
if ($last && ($now - $last) <= 90) { // consider online if active in last 90 seconds
    $online = true;
}

echo json_encode(['online' => $online, 'last_active' => $last]);

// if admin himself is asking, consider them active now
if (isset($_SESSION['user']) && $_SESSION['user'] === 'admin') {
    $last = time();
}

if ($last === null) {
    echo json_encode(['online' => false, 'last_active' => null, 'seconds_ago' => null]);
    exit;
}

$seconds = time() - $last;
$threshold = 60; // seconds to consider online
$online = ($seconds <= $threshold);

echo json_encode(['online' => $online, 'last_active' => date('c', $last), 'seconds_ago' => $seconds]);
?>