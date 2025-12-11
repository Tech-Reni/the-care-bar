<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => '', 'cart_count' => 0];

// Ensure cart exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

try {
    // --- ADD ITEM ---
    if ($action === 'add') {
        $pid = (int)$_POST['product_id'];
        $qty = (int)$_POST['quantity'];
        $vid = isset($_POST['variant_id']) && $_POST['variant_id'] !== '' ? (int)$_POST['variant_id'] : 0;

        if (!$pid || $qty < 1) throw new Exception("Invalid Item");

        // Fetch Product Base Info
        $product = getProductById($pid);
        if (!$product) throw new Exception("Product not found");

        $finalPrice = $product['price'];
        $variantName = "";

        // If Variant Selected, Fetch Specific Price & Name
        if ($vid > 0) {
            $stmt = $conn->prepare("SELECT * FROM product_variants WHERE id = ? AND product_id = ?");
            $stmt->bind_param("ii", $vid, $pid);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $finalPrice = (float)$row['price'];
                $variantName = $row['variant_name'];
            }
            $stmt->close();
        }

        // Create Unique Key: "Product_Variant" (e.g. 10_0 or 10_5)
        $cartKey = $pid . '_' . $vid;

        if (isset($_SESSION['cart'][$cartKey])) {
            $_SESSION['cart'][$cartKey]['quantity'] += $qty;
        } else {
            $_SESSION['cart'][$cartKey] = [
                'key' => $cartKey, // We store the key for easy identification later
                'id' => $pid,
                'variant_id' => $vid,
                'name' => $product['name'],
                'variant_name' => $variantName,
                'price' => $finalPrice,
                'image' => $product['image'],
                'quantity' => $qty,
                'stock_quantity' => $product['stock_quantity']
            ];
        }

        $response['success'] = true;
        $response['message'] = "Added to cart";

        // --- REMOVE ITEM (Using Key) ---
    } elseif ($action === 'remove') {
        $key = $_POST['key'] ?? ''; // Expecting string like "10_5"
        if (isset($_SESSION['cart'][$key])) {
            unset($_SESSION['cart'][$key]);
        }
        $response['success'] = true;

        // --- UPDATE QUANTITY (Using Key) ---
    } elseif ($action === 'update') {
        $key = $_POST['key'] ?? '';
        $qty = (int)$_POST['quantity'];

        if (isset($_SESSION['cart'][$key])) {
            if ($qty > 0) {
                $_SESSION['cart'][$key]['quantity'] = $qty;
            } else {
                unset($_SESSION['cart'][$key]);
            }
        }
        $response['success'] = true;

        // --- CLEAR CART ---
    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
        $response['success'] = true;

        // --- GET CART ---
    } elseif ($action === 'get') {
        $response['success'] = true;
        $response['cart'] = $_SESSION['cart'];
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

$response['cart_count'] = count($_SESSION['cart']);
echo json_encode($response);
