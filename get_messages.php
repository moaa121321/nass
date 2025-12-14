<?php
session_start();
header('Content-Type: application/json');
require __DIR__ . '/config.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'You must log in.']);
    exit;
}

$user = $_SESSION['user'];
$other = $_GET['other'] ?? 'admin';

try {
    // Get messages between user and other
    $stmt = $pdo->prepare("SELECT * FROM chat WHERE (sender_username = ? AND receiver_username = ?) OR (sender_username = ? AND receiver_username = ?) ORDER BY created_at ASC");
    $stmt->execute([$user, $other, $other, $user]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark as read if receiver is current user
    if ($other !== $user) {
        $pdo->prepare("UPDATE chat SET is_read = TRUE WHERE sender_username = ? AND receiver_username = ? AND is_read = FALSE")->execute([$other, $user]);
    }

    echo json_encode(['messages' => $messages]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to get messages: ' . $e->getMessage()]);
}
?>