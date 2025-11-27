<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

// Get action from request
$action = isset($_POST['action']) ? sanitizeInput($_POST['action']) : '';

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'cart_count' => isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0
];

try {
    switch ($action) {
        case 'add':
            $product_id = validateInt($_POST['product_id'] ?? null);
            $quantity = validateInt($_POST['quantity'] ?? 1);

            if (!$product_id || $quantity < 1) {
                throw new Exception('Invalid product or quantity');
            }

            // Verify product exists
            $product = getProductById($product_id);
            if (!$product) {
                throw new Exception('Product not found');
            }

            // Initialize cart if not exists
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            // Add or update product in cart
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'id' => $product_id,
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'image' => $product['image'],
                    'quantity' => $quantity
                ];
            }

            $response['success'] = true;
            $response['message'] = 'Product added to cart';
            $response['cart_count'] = count($_SESSION['cart']);
            break;

        case 'remove':
            $product_id = validateInt($_POST['product_id'] ?? null);

            if (!$product_id) {
                throw new Exception('Invalid product ID');
            }

            if (isset($_SESSION['cart'][$product_id])) {
                unset($_SESSION['cart'][$product_id]);
                $response['success'] = true;
                $response['message'] = 'Product removed from cart';
            } else {
                throw new Exception('Product not in cart');
            }

            $response['cart_count'] = count($_SESSION['cart']);
            break;

        case 'update':
            $product_id = validateInt($_POST['product_id'] ?? null);
            $quantity = validateInt($_POST['quantity'] ?? null);

            if (!$product_id || $quantity === null) {
                throw new Exception('Invalid product or quantity');
            }

            if (!isset($_SESSION['cart'][$product_id])) {
                throw new Exception('Product not in cart');
            }

            if ($quantity <= 0) {
                unset($_SESSION['cart'][$product_id]);
                $response['message'] = 'Product removed from cart';
            } else {
                $_SESSION['cart'][$product_id]['quantity'] = $quantity;
                $response['message'] = 'Cart updated';
            }

            $response['success'] = true;
            $response['cart_count'] = count($_SESSION['cart']);
            break;

        case 'clear':
            $_SESSION['cart'] = [];
            $response['success'] = true;
            $response['message'] = 'Cart cleared';
            $response['cart_count'] = 0;
            break;

        case 'get':
            $response['success'] = true;
            $response['cart'] = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
            $response['cart_count'] = count($response['cart']);

            // Calculate totals
            $subtotal = 0;
            foreach ($response['cart'] as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }

            $response['subtotal'] = $subtotal;
            $response['total'] = $subtotal; // Add taxes/shipping as needed
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
