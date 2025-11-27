<?php
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($method !== 'POST' || $action !== 'add') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;

if ($product_id <= 0 || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// For this simple implementation we only increment the review count (review_no)
// Future: store per-user ratings in a reviews table and compute averages
$ok = incrementProductReview($product_id, 1);

if ($ok) {
    // Return updated count
    $product = getProductById($product_id);
    $count = $product['review_no'] ?? 0;
    echo json_encode(['success' => true, 'message' => 'Thank you for your rating', 'review_no' => (int)$count]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to record rating']);
}

exit;
