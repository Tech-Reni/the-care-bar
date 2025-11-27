<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($method !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

if ($action === 'create') {
    // Collect order data
    $data = [];
    $data['order_number'] = 'ORD_' . time() . '_' . rand(1000,9999);
    $data['first_name'] = $_POST['firstName'] ?? '';
    $data['last_name'] = $_POST['lastName'] ?? '';
    $data['email'] = $_POST['email'] ?? '';
    $data['phone'] = $_POST['phone'] ?? '';
    $data['address'] = $_POST['address'] ?? '';
    $data['city'] = $_POST['city'] ?? '';
    $data['state'] = $_POST['state'] ?? '';
    $data['zip'] = $_POST['zip'] ?? '';
    $data['payment_method'] = $_POST['paymentMethod'] ?? '';
    $data['payment_details'] = $_POST['paymentDetails'] ?? '';
    $data['items'] = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];
    $data['subtotal'] = floatval($_POST['subtotal'] ?? 0);
    $data['tax'] = floatval($_POST['tax'] ?? 0);
    $data['shipping'] = floatval($_POST['shipping'] ?? 0);
    $data['total'] = floatval($_POST['total'] ?? 0);

    $id = createOrder($data);
    if ($id) {
        echo json_encode(['success'=>true, 'message'=>'Order created', 'order_id'=>$id]);
    } else {
        // Gather debug info
        $debug = [];
        if (function_exists('getOrderDebug')) {
            $debug = getOrderDebug();
        }
        // include raw POST for debugging (may contain only last4 card info)
        $raw_post = $_POST;
        // Write server log as well
        if (function_exists('writeOrderDebugLog')) {
            writeOrderDebugLog($raw_post, $debug);
        }
        echo json_encode(['success'=>false, 'message'=>'Failed to create order', 'debug' => $debug, 'post' => $raw_post]);
    }
    exit;
}

if ($action === 'update_status') {
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $status = $_POST['status'] ?? '';
    if (!$order_id || !$status) {
        echo json_encode(['success'=>false, 'message'=>'Invalid parameters']);
        exit;
    }
    $ok = updateOrderStatus($order_id, $status);
    echo json_encode(['success'=>$ok]);
    exit;
}

echo json_encode(['success'=>false, 'message'=>'Unknown action']);
exit;
