<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'You must log in.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: [];
$title = trim($data['title'] ?? '');
$desc = trim($data['desc'] ?? '');
$price = trim($data['price'] ?? '');
$img = trim($data['img'] ?? '');

if ($title === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Title is required.']);
    exit;
}

$file = __DIR__ . '/products.json';
if (!file_exists($file)) file_put_contents($file, json_encode([]));
$products = json_decode(file_get_contents($file), true);
if (!is_array($products)) $products = [];

$new = [
    'id' => uniqid('p', true),
    'owner' => $_SESSION['user'],
    'title' => $title,
    'desc' => $desc,
    'price' => $price ?: 'by key',
    'img' => $img ?: 'https://via.placeholder.com/400x250?text=Product',
    'popularity' => 0,
    'created_at' => date(DATE_ATOM)
];

$products[] = $new;
file_put_contents($file, json_encode($products, JSON_PRETTY_PRINT));

echo json_encode($new);
