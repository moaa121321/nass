<?php
session_start();
header('Content-Type: application/json');

// Check if admin is online (has active session)
$online = false;
if (isset($_SESSION['user']) && $_SESSION['user'] === 'admin') {
    $online = true;
}
echo json_encode(['online' => $online]);
?>