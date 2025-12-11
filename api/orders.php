<?php
// api/orders.php

// 1. DISABLE DISPLAY ERRORS (To prevent HTML breaking JSON)
ini_set('display_errors', 0); 
// 2. REPORT ALL ERRORS (So we can catch them)
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    if ($method !== 'POST') {
        throw new Exception("Invalid HTTP method");
    }

    if ($action === 'create') {
        // Start Transaction
        $conn->begin_transaction();

        // ----------------------------------------------------
        // 1. PREPARE DATA
        // ----------------------------------------------------
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
        $orderData['payment_details'] = $_POST['paymentDetails'] ?? '{}';
        $orderData['subtotal']        = $_POST['subtotal'] ?? 0;
        $orderData['total']           = $_POST['total'] ?? 0;
        
        $items_json = $_POST['items'] ?? '[]';
        $items = json_decode($items_json, true);
        
        if (empty($items)) { throw new Exception("Cart is empty"); }

        // ----------------------------------------------------
        // 2. DEDUCT STOCK
        // ----------------------------------------------------
        $sqlDeduct = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?";
        $stmtDeduct = $conn->prepare($sqlDeduct);
        if (!$stmtDeduct) { throw new Exception("DB Error (Deduct): " . $conn->error); }

        foreach ($items as $item) {
            $pid = (int)$item['id'];
            $qty = (int)$item['quantity'];
            if ($qty <= 0) continue;

            $stmtDeduct->bind_param("iii", $qty, $pid, $qty);
            $stmtDeduct->execute();

            if ($stmtDeduct->affected_rows === 0) {
                // Determine item name for cleaner error
                $n = $item['name'] ?? "Item #$pid";
                throw new Exception("Insufficient stock for: $n");
            }
        }
        $stmtDeduct->close();

        // ----------------------------------------------------
        // 3. CREATE ORDER HEADER
        // ----------------------------------------------------
        // NOTE: I used 'shipping_cost'. If your DB column is 'shipping', change it below.
        // NOTE: If you deleted 'user_id' from DB, remove it from here.
        $sqlOrder = "INSERT INTO orders (order_number, first_name, last_name, email, phone, address, city, state, zip, payment_method, payment_details, subtotal, tax, shipping, total, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmtOrder = $conn->prepare($sqlOrder);
        
        // !!! DEBUGGER !!!
        // If this fails, it means a Column Name is wrong in the DB
        if (!$stmtOrder) {
            throw new Exception("DB Prepare Error (Orders Table): " . $conn->error);
        }
        
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; 
        $tax = 0; 
        $shipping = 0; 
        
        $stmtOrder->bind_param("sssssssssssdddd", 
            $orderData['order_number'], 
            $orderData['first_name'], 
            $orderData['last_name'], 
            $orderData['email'], 
            $orderData['phone'], 
            $orderData['address'], 
            $orderData['city'], 
            $orderData['state'], 
            $orderData['zip'],
            $orderData['payment_method'], 
            $orderData['payment_details'],
            $orderData['subtotal'], 
            $tax, 
            $shipping, 
            $orderData['total']
        );

        if (!$stmtOrder->execute()) {
            throw new Exception("Order Execute Failed: " . $stmtOrder->error);
        }
        
        $order_id = $conn->insert_id;
        $stmtOrder->close();

        // ----------------------------------------------------
        // 4. CREATE ORDER ITEMS
        // ----------------------------------------------------
        $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, name, price, quantity) VALUES (?, ?, ?, ?, ?)");
        if (!$stmtItem) { throw new Exception("DB Error (Order Items): " . $conn->error); }

        foreach ($items as $item) {
            $finalName = $item['name'];
            if (!empty($item['variant_name'])) {
                $finalName .= " (" . $item['variant_name'] . ")";
            }

            $stmtItem->bind_param("iisdi", $order_id, $item['id'], $finalName, $item['price'], $item['quantity']);
            $stmtItem->execute();
        }
        $stmtItem->close();

        // Commit
        $conn->commit();
        unset($_SESSION['cart']);

        echo json_encode(['success' => true, 'order_id' => $order_id]);
        // Trigger OneSignal Push
        @file_get_contents("https://the-care-bar.com/send_test_push.php");
        exit;
    }

    if ($action === 'clear') {
        unset($_SESSION['cart']);
        echo json_encode(['success' => true]);
        exit;
    }

} catch (Exception $e) {
    // Rollback if transaction started
    if (isset($conn) && $conn->connect_errno === 0) {
        @$conn->rollback();
    }
    
    // RETURN RAW ERROR AS JSON
    // This will make the error appear in your Checkout Alert Box
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage() 
    ]);
    exit;
}
