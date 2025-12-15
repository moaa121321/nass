<?php
session_start();
header('Content-Type: application/json');
require __DIR__ . '/config.php';

if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

try {
    $chats = $pdo->query("SELECT DISTINCT sender_username AS username,
           (SELECT message FROM chat WHERE (sender_username = c.sender_username AND receiver_username = 'admin') OR (sender_username = 'admin' AND receiver_username = c.sender_username) ORDER BY created_at DESC LIMIT 1) AS last_message,
           (SELECT created_at FROM chat WHERE (sender_username = c.sender_username AND receiver_username = 'admin') OR (sender_username = 'admin' AND receiver_username = c.sender_username) ORDER BY created_at DESC LIMIT 1) AS last_time,
           (SELECT COUNT(*) FROM chat WHERE sender_username = c.sender_username AND receiver_username = 'admin' AND is_read = FALSE) AS unread
    FROM chat c
    WHERE receiver_username = 'admin' OR sender_username = 'admin'
    GROUP BY sender_username
    ORDER BY last_time DESC")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['chats' => $chats]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
