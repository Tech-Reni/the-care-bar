<?php

// ================================
// BASE_URL Logic
// ================================
if (str_contains($_SERVER['HTTP_HOST'], 'localhost')) {
    $BASE_URL = '/TheCareBar/'; 

    // Database (LOCAL)
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "thecarebar";

} else {
    $BASE_URL = '../'; 

    // Database (LIVE)
    $servername = "mysql.the-care-bar.com";
    $username = "admin_thecarebar";
    $password = "programming123";
    $dbname = "thecarebar";
}

// ================================
// Database Connection
// ================================
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// ================================
// SETTINGS HELPER (key/value store)
// ================================

/**
 * Get a setting value by key, or null if not found.
 */
function getSetting($key) {
    global $conn;
    $sql = "SELECT v FROM settings WHERE k = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return null;

    $stmt->bind_param('s', $key);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    return $row ? $row['v'] : null;
}

/**
 * Insert or update setting key => value.
 */
function saveSetting($key, $value) {
    global $conn;
    $sql = "INSERT INTO settings (k, v, updated_at)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            v = VALUES(v), updated_at = NOW()";

    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;

    $stmt->bind_param('ss', $key, $value);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

// ================================
// ORDER DEBUG HELPERS
// ================================

$ORDER_CREATE_DEBUG = [];

/**
 * Add a debug record for order creation.
 */
function order_debug_append($msg, $data = null) {
    global $ORDER_CREATE_DEBUG;
    $ORDER_CREATE_DEBUG[] = [
        'time' => date('c'),
        'msg'  => $msg,
        'data' => $data
    ];
}

/**
 * Return the debug data collected during an order creation.
 */
function getOrderDebug() {
    global $ORDER_CREATE_DEBUG;
    return $ORDER_CREATE_DEBUG;
}

/**
 * Log order creation debug info into file.
 */
function writeOrderDebugLog($payload, $debug) {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $file = $logDir . '/orders_debug.log';
    $entry = [
        'timestamp' => date('c'),
        'payload'   => $payload,
        'debug'     => $debug
    ];

    @file_put_contents(
        $file,
        json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}


/**
 * Get random featured products
 * @param int $limit Number of products to fetch
 * @return array Array of products
 */
function getRandomProducts($limit = 5)
{
    global $conn;
    $limit = (int)$limit;

    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              ORDER BY RAND() 
              LIMIT ?";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    $stmt->close();
    return $products;
}

/**
 * Get all products with optional filtering
 * @param int $limit Items per page
 * @param int $offset Pagination offset
 * @param int $category_id Optional category filter
 * @return array Array of products
 */
function getAllProducts($limit = 12, $offset = 0, $category_id = null)
{
    global $conn;
    $limit = (int)$limit;
    $offset = (int)$offset;
    $category_id = $category_id ? (int)$category_id : null;

    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id";

    if ($category_id) {
        $query .= " WHERE p.category_id = ?";
    }

    $query .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }

    if ($category_id) {
        $stmt->bind_param("iii", $category_id, $limit, $offset);
    } else {
        $stmt->bind_param("ii", $limit, $offset);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    $stmt->close();
    return $products;
}

/**
 * Get product by ID
 * @param int $id Product ID
 * @return array|null Product data or null
 */
function getProductById($id)
{
    global $conn;
    $id = (int)$id;

    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE p.id = ?";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    $product = $result->num_rows > 0 ? $result->fetch_assoc() : null;

    $stmt->close();
    return $product;
}

/**
 * Get all categories
 * @param int $limit Optional limit
 * @return array Array of categories
 */
function getCategories($limit = null)
{
    global $conn;

    $query = "SELECT id, name, created_at FROM categories ORDER BY name ASC";

    if ($limit) {
        $limit = (int)$limit;
        $query .= " LIMIT ?";
    }

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }

    if ($limit) {
        $stmt->bind_param("i", $limit);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }

    $stmt->close();
    return $categories;
}

/**
 * Get total product count
 * @param int $category_id Optional category filter
 * @return int Total count
 */
function getProductCount($category_id = null)
{
    global $conn;

    $query = "SELECT COUNT(*) as total FROM products";

    if ($category_id) {
        $category_id = (int)$category_id;
        $query .= " WHERE category_id = ?";
    }

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return 0;
    }

    if ($category_id) {
        $stmt->bind_param("i", $category_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();
    return (int)$row['total'];
}

/**
 * Sanitize string input
 * @param string $input User input
 * @return string Sanitized string
 */
function sanitizeInput($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate integer
 * @param mixed $value Value to validate
 * @return int|null Integer or null if invalid
 */
function validateInt($value)
{
    $value = filter_var($value, FILTER_VALIDATE_INT);
    return $value !== false ? $value : null;
}

// ================================
// ADMIN FUNCTIONS
// ================================

/**
 * Create new product
 * @param array $data Product data
 * @return int|false Product ID or false
 */
function createProduct($data)
{
    global $conn;

    $name = sanitizeInput($data['name'] ?? '');
    $description = $data['description'] ?? '';
    $price = filter_var($data['price'] ?? 0, FILTER_VALIDATE_FLOAT);
    $category_id = validateInt($data['category_id'] ?? null);
    $image = $data['image'] ?? '';

    if (!$name || !$price) {
        return false;
    }

    $query = "INSERT INTO products (category_id, name, description, price, image, created_at) 
              VALUES (?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("issds", $category_id, $name, $description, $price, $image);
    $result = $stmt->execute();

    if ($result) {
        $id = $conn->insert_id;
        $stmt->close();
        return $id;
    }

    $stmt->close();
    return false;
}

/**
 * Update product
 * @param int $id Product ID
 * @param array $data Product data
 * @return bool Success
 */
function updateProduct($id, $data)
{
    global $conn;

    $id = (int)$id;
    $name = sanitizeInput($data['name'] ?? '');
    $description = $data['description'] ?? '';
    $price = filter_var($data['price'] ?? 0, FILTER_VALIDATE_FLOAT);
    $category_id = validateInt($data['category_id'] ?? null);
    $image = $data['image'] ?? '';

    if (!$name || !$price) {
        return false;
    }

    $query = "UPDATE products SET category_id=?, name=?, description=?, price=?, image=? WHERE id=?";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("issdsi", $category_id, $name, $description, $price, $image, $id);
    $result = $stmt->execute();

    $stmt->close();
    return $result;
}

/**
 * Increment product review count
 * @param int $id Product ID
 * @param int $inc Amount to increment (default 1)
 * @return bool Success
 */
function incrementProductReview($id, $inc = 1)
{
    global $conn;
    $id = (int)$id;
    $inc = (int)$inc;

    $query = "UPDATE products SET review_no = COALESCE(review_no, 0) + ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) return false;

    $stmt->bind_param('ii', $inc, $id);
    $res = $stmt->execute();
    $stmt->close();
    return $res;
}

/**
 * Delete product
 * @param int $id Product ID
 * @return bool Success
 */
function deleteProduct($id)
{
    global $conn;

    $id = (int)$id;

    $query = "DELETE FROM products WHERE id=?";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("i", $id);
    $result = $stmt->execute();

    $stmt->close();
    return $result;
}

/**
 * Create new category
 * @param string $name Category name
 * @return int|false Category ID or false
 */
function createCategory($name)
{
    global $conn;

    $name = sanitizeInput($name);

    if (!$name) {
        return false;
    }

    $query = "INSERT INTO categories (name, created_at) VALUES (?, NOW())";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("s", $name);
    $result = $stmt->execute();

    if ($result) {
        $id = $conn->insert_id;
        $stmt->close();
        return $id;
    }

    $stmt->close();
    return false;
}

/**
 * Update category
 * @param int $id Category ID
 * @param string $name New name
 * @return bool Success
 */
function updateCategory($id, $name)
{
    global $conn;

    $id = (int)$id;
    $name = sanitizeInput($name);

    if (!$name) {
        return false;
    }

    $query = "UPDATE categories SET name=? WHERE id=?";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("si", $name, $id);
    $result = $stmt->execute();

    $stmt->close();
    return $result;
}

/**
 * Delete category
 * @param int $id Category ID
 * @return bool Success
 */
function deleteCategory($id)
{
    global $conn;

    $id = (int)$id;

    // First, reset products with this category
    $query1 = "UPDATE products SET category_id=NULL WHERE category_id=?";
    $stmt1 = $conn->prepare($query1);
    if ($stmt1) {
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        $stmt1->close();
    }

    // Then delete the category
    $query2 = "DELETE FROM categories WHERE id=?";
    $stmt2 = $conn->prepare($query2);
    if (!$stmt2) {
        return false;
    }

    $stmt2->bind_param("i", $id);
    $result = $stmt2->execute();

    $stmt2->close();
    return $result;
}

/**
 * Get admin statistics
 * @return array Statistics data
 */
function getAdminStats()
{
    global $conn;

    $stats = [];

    // Product count
    $result = $conn->query("SELECT COUNT(*) as total FROM products");
    $stats['product_count'] = $result ? $result->fetch_assoc()['total'] : 0;

    // Category count
    $result = $conn->query("SELECT COUNT(*) as total FROM categories");
    $stats['category_count'] = $result ? $result->fetch_assoc()['total'] : 0;

    // Total product value
    $result = $conn->query("SELECT SUM(price) as total FROM products");
    $stats['total_inventory_value'] = $result ? ($result->fetch_assoc()['total'] ?? 0) : 0;

    // Orders stats
    $result = $conn->query("SELECT COUNT(*) as total FROM orders");
    $stats['total_orders'] = $result ? ($result->fetch_assoc()['total'] ?? 0) : 0;

    $result = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'Pending'");
    $stats['orders_pending'] = $result ? ($result->fetch_assoc()['total'] ?? 0) : 0;

    $result = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'Completed'");
    $stats['orders_completed'] = $result ? ($result->fetch_assoc()['total'] ?? 0) : 0;

    $result = $conn->query("SELECT SUM(total) as sales FROM orders WHERE status = 'Completed'");
    $stats['total_sales'] = $result ? ($result->fetch_assoc()['sales'] ?? 0) : 0;

    return $stats;
}

/**
 * Create new order
 * @param array $data
 * @return int|false Order ID or false
 */
function createOrder($data)
{
    global $conn;

    // Clear previous debug
    order_debug_append('start_create', ['input_keys' => array_keys($data)]);

    $order_number = sanitizeInput($data['order_number'] ?? uniqid('ORD_'));
    $first_name = sanitizeInput($data['first_name'] ?? '');
    $last_name = sanitizeInput($data['last_name'] ?? '');
    $email = sanitizeInput($data['email'] ?? '');
    $phone = sanitizeInput($data['phone'] ?? '');
    $address = sanitizeInput($data['address'] ?? '');
    $city = sanitizeInput($data['city'] ?? '');
    $state = sanitizeInput($data['state'] ?? '');
    $zip = sanitizeInput($data['zip'] ?? '');
    $payment_method = sanitizeInput($data['payment_method'] ?? '');
    $payment_details = $data['payment_details'] ?? '';
    $items = $data['items'] ?? [];
    $subtotal = floatval($data['subtotal'] ?? 0);
    $tax = floatval($data['tax'] ?? 0);
    $shipping = floatval($data['shipping'] ?? 0);
    $total = floatval($data['total'] ?? 0);

    // Insert order (no items column in normalized schema)
    $query = "INSERT INTO orders (order_number, first_name, last_name, email, phone, address, city, state, zip, payment_method, payment_details, subtotal, tax, shipping, total, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())";
    order_debug_append('prepare_insert_orders', ['query' => $query]);
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        order_debug_append('prepare_failed', ['error' => $conn->error, 'errno' => $conn->errno]);
        return false;
    }

    $bind_ok = $stmt->bind_param('sssssssssssdddd', $order_number, $first_name, $last_name, $email, $phone, $address, $city, $state, $zip, $payment_method, $payment_details, $subtotal, $tax, $shipping, $total);
    if ($bind_ok === false) {
        order_debug_append('bind_failed', ['stmt_error' => $stmt->error]);
    } else {
        order_debug_append('bind_ok', ['order_number' => $order_number, 'subtotal' => $subtotal, 'total' => $total]);
    }

    $res = $stmt->execute();
    if (!$res) {
        order_debug_append('execute_failed', ['stmt_error' => $stmt->error, 'conn_error' => $conn->error, 'errno' => $conn->errno]);
        $stmt->close();
        return false;
    }

    $order_id = $conn->insert_id;
    order_debug_append('insert_ok', ['order_id' => $order_id]);
    $stmt->close();

    // Insert each item into order_items table
    if (!empty($items) && is_array($items)) {
        $qi = $conn->prepare("INSERT INTO order_items (order_id, product_id, name, price, quantity, total) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$qi) {
            order_debug_append('prepare_order_items_failed', ['error' => $conn->error]);
        } else {
            foreach ($items as $idx => $it) {
                $prod_id = isset($it['id']) ? (int)$it['id'] : 0;
                $name = sanitizeInput($it['name'] ?? '');
                $price = floatval($it['price'] ?? 0);
                $qty = intval($it['quantity'] ?? 1);
                $it_total = $price * $qty;
                $bind = $qi->bind_param('iisdid', $order_id, $prod_id, $name, $price, $qty, $it_total);
                if ($bind === false) {
                    order_debug_append('order_items_bind_failed', ['index' => $idx, 'stmt_error' => $qi->error]);
                }
                $exec = $qi->execute();
                if ($exec === false) {
                    order_debug_append('order_items_execute_failed', ['index' => $idx, 'stmt_error' => $qi->error, 'conn_error' => $conn->error]);
                } else {
                    order_debug_append('order_items_inserted', ['index' => $idx, 'product_id' => $prod_id, 'quantity' => $qty, 'total' => $it_total]);
                }
            }
            $qi->close();
        }
    }

    // Write debug log (payload minimal)
    writeOrderDebugLog(['order_number' => $order_number, 'email' => $email, 'subtotal' => $subtotal, 'total' => $total], getOrderDebug());

    return $order_id;
}

/**
 * Get items for an order
 */
function getOrderItems($order_id)
{
    global $conn;
    $order_id = (int)$order_id;
    $rows = [];
    $stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC");
    if (!$stmt) return $rows;
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    $stmt->close();
    return $rows;
}

/**
 * Get a single order by id
 */
function getOrderById($id)
{
    global $conn;
    $id = (int)$id;
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row;
}

/**
 * Get all orders
 */
function getAllOrders()
{
    global $conn;
    $rows = [];
    $result = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
    if ($result) {
        while ($r = $result->fetch_assoc()) $rows[] = $r;
    }
    return $rows;
}

/**
 * Update order status
 */
function updateOrderStatus($id, $status)
{
    global $conn;
    $id = (int)$id;
    $status = sanitizeInput($status);
    $query = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) return false;
    $stmt->bind_param('si', $status, $id);
    $res = $stmt->execute();
    $stmt->close();
    return $res;
}
