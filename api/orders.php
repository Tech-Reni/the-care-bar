<?php
// Prevent HTML errors from breaking JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Check Request Method
if ($method !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

if ($action === 'create') {
    // 1. Start Transaction (MySQLi style)
    // This ensures stock is deducted AND order is created, or NOTHING happens.
    $conn->begin_transaction();

    try {
        // --- Prepare Data for the createOrder function ---
        // We map the POST data (from checkout.js) to the keys expected by createOrder() in db.php
        $orderData = [];
        $orderData['order_number']    = 'ORD_' . time() . '_' . rand(1000, 9999);
        $orderData['first_name']      = $_POST['firstName'] ?? '';
        $orderData['last_name']       = $_POST['lastName'] ?? '';
        $orderData['email']           = $_POST['email'] ?? '';
        $orderData['phone']           = $_POST['phone'] ?? '';
        $orderData['address']         = $_POST['address'] ?? '';
        $orderData['city']            = $_POST['city'] ?? '';
        $orderData['state']           = $_POST['state'] ?? '';
        $orderData['zip']             = $_POST['zip'] ?? '';
        $orderData['payment_method']  = $_POST['paymentMethod'] ?? 'card';
        $orderData['payment_details'] = $_POST['paymentDetails'] ?? '{}'; // JSON string
        $orderData['subtotal']        = $_POST['subtotal'] ?? 0;
        $orderData['total']           = $_POST['total'] ?? 0;
        
        // Decode items
        $items_json = $_POST['items'] ?? '[]';
        $items = json_decode($items_json, true);
        $orderData['items'] = $items;

        if (empty($items)) {
            throw new Exception("Your cart is empty.");
        }

        // ---------------------------------------------------------
        //  ✅ STEP A: DEDUCT STOCK
        //  (We do this here because createOrder() doesn't do it)
        // ---------------------------------------------------------
        
        // Prepare the deduction statement once
        $sqlDeduct = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?";
        $stmtDeduct = $conn->prepare($sqlDeduct);

        if (!$stmtDeduct) {
            throw new Exception("Database error preparing stock deduction: " . $conn->error);
        }

        foreach ($items as $item) {
            $product_id = isset($item['id']) ? (int)$item['id'] : 0;
            $quantity   = (int)$item['quantity'];

            if ($quantity <= 0) continue;

            // Bind parameters: integer, integer, integer
            $stmtDeduct->bind_param("iii", $quantity, $product_id, $quantity);
            $stmtDeduct->execute();

            // Check if any row was actually updated
            if ($stmtDeduct->affected_rows === 0) {
                // Failed to deduct. Either ID is wrong or Stock is too low.
                // Let's find out the product name to show a nice error.
                $stmtName = $conn->prepare("SELECT name, stock_quantity FROM products WHERE id = ?");
                $stmtName->bind_param("i", $product_id);
                $stmtName->execute();
                $resName = $stmtName->get_result();
                $prodData = $resName->fetch_assoc();
                $stmtName->close();

                $pName = $prodData ? $prodData['name'] : 'Item #' . $product_id;
                $pStock = $prodData ? $prodData['stock_quantity'] : 0;

                throw new Exception("Insufficient stock for '$pName'. Only $pStock available.");
            }
        }
        $stmtDeduct->close();

        // ---------------------------------------------------------
        //  ✅ STEP B: CREATE ORDER & ORDER ITEMS
        //  (Using the function already in your db.php)
        // ---------------------------------------------------------
        
        $order_id = createOrder($orderData);

        if (!$order_id) {
            // Check debug log function if available
            $debugInfo = function_exists('getOrderDebug') ? json_encode(getOrderDebug()) : 'No debug info';
            throw new Exception("Failed to save order. " . $debugInfo);
        }

        // ---------------------------------------------------------
        //  ✅ STEP C: COMMIT TRANSACTION
        // ---------------------------------------------------------
        $conn->commit();

        // Success Response
        echo json_encode([
            'success' => true,
            'message' => 'Order created successfully',
            'order_id' => $order_id
        ]);

         // Trigger OneSignal Push
        @file_get_contents("https://the-care-bar.com/send_test_push.php");

        exit;

    } catch (Exception $e) {
        // 🛑 ROLLBACK
        $conn->rollback();
        
        // Log the actual error for the admin
        error_log("Order Process Failed: " . $e->getMessage());

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// Clear Cart Action (Optional, matches your JS)
if ($action === 'clear') {
    unset($_SESSION['cart']);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
exit;
