<?php
// 1. START BUFFERING
ob_start();

$page_title = 'Products';

// ================================
// BASE_URL Logic
// ================================

// 1. ROBUST BASE_URL LOGIC (Works on all PHP versions)
$host = $_SERVER['HTTP_HOST'];
if ($host == 'localhost' || strpos($host, 'localhost') !== false) {
    // LOCALHOST SETTINGS
    $BASE_URL = '/TheCareBar/'; 
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "thecarebar"; 
} else {
    // LIVE SERVER SETTINGS
    // Use the full URL to ensure CSS/JS loads correctly from anywhere
    $BASE_URL = 'https://the-care-bar.com/'; 
    
    $servername = "mysql.the-care-bar.com";
    $username = "admin_thecarebar";
    $password = "programming123";
    $dbname = "thecarebar";
}

?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>The Care Bar Admin</title>

    <!-- Google Font: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">

    <!-- Main CSS -->
    <link rel="stylesheet" href="<?= $BASE_URL ?>assets/css/admin.css">

    <!-- Quill Editor (optional, loaded on demand) -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" href="<?= $BASE_URL ?>assets/img/logo.png" type="image/x-icon">
    <link rel="shortcut icon" href="<?= $BASE_URL ?>assets/img/logo.png" type="image/x-icon">
    <link rel="icon" href="<?= $BASE_URL ?>assets/img/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="<?= $BASE_URL ?>assets/img/logo.png">

    <link rel="manifest" href="/admin/manifest.json">

    <script>
        let deferredPrompt = null;

        // Catch the install event BEFORE browser hides it
        window.addEventListener("beforeinstallprompt", (e) => {
            e.preventDefault();
            deferredPrompt = e; // save the event for later

            const installBtn = document.getElementById("installAppBtn");
            if (installBtn) installBtn.style.display = "block";
        });

        // Your manual install button trigger
        async function installPWA() {
            if (!deferredPrompt) {
                showInfo('Info', 'Install option not available yet. Try again later.');
                return;
            }

            // Ask user via modal before triggering the native install prompt
            showConfirm('Install Admin App', 'Do you want to install the admin app?', async () => {
                try {
                    await deferredPrompt.prompt();
                    const choice = await deferredPrompt.userChoice;

                    if (choice.outcome === 'accepted') {
                        console.log('APP INSTALLED');
                    } else {
                        console.log('INSTALL DISMISSED');
                    }
                } catch (err) {
                    console.error('Install prompt failed:', err);
                } finally {
                    deferredPrompt = null; // clear
                }
            });
        }

        // Register service worker
        if ("serviceWorker" in navigator) {
            navigator.serviceWorker.register("/admin/service-worker.js")
                .catch(err => console.error("SW failed:", err));
        }
    </script>

</head>

<body>
    <!-- ================================
            SIDEBAR
    ================================ -->
    <div class="sidebar" id="sidebar">
        <div class="sb-header">
            <h2>The Care Bar</h2>
            <button class="sb-close" onclick="toggleSidebar()"><i class="ri-close-line"></i></button>
        </div>

        <div class="sb-menu">
            <a href="<?= $BASE_URL ?>admin/index.php" class="<?= (strpos($_SERVER['PHP_SELF'], 'admin/index') !== false) ? 'active' : ''; ?>">
                <i class="ri-dashboard-line"></i> Dashboard
            </a>
            <a href="<?= $BASE_URL ?>admin/products.php" class="<?= (strpos($_SERVER['PHP_SELF'], 'admin/products') !== false) ? 'active' : ''; ?>">
                <i class="ri-shopping-bag-3-line"></i> Products
            </a>
            <a href="<?= $BASE_URL ?>admin/categories.php" class="<?= (strpos($_SERVER['PHP_SELF'], 'admin/categories') !== false) ? 'active' : ''; ?>">
                <i class="ri-folders-line"></i> Categories
            </a>
            <a href="<?= $BASE_URL ?>admin/orders.php" class="<?= (strpos($_SERVER['PHP_SELF'], 'admin/orders') !== false) ? 'active' : ''; ?>">
                <i class="ri-file-list-3-line"></i> Orders
            </a>
            <!-- <a href="#" class="coming-soon">
                <i class="ri-settings-3-line"></i> Settings <span class="badge">Soon</span>
            </a> -->
        </div>

        <div class="sb-footer">
            <a href="<?= $BASE_URL ?>" class="sb-link-secondary">
                <i class="ri-home-4-line"></i> View Site
            </a>
        </div>
    </div>

    <!-- ================================
            MAIN CONTENT WRAPPER
    ================================ -->
    <div class="main">
        <!-- Top Navbar -->
        <div class="topbar">
            <button class="menu-btn" onclick="toggleSidebar()">
                <i class="ri-menu-line"></i>
            </button>

            <h3 id="page-title"><?php echo isset($page_title) ? $page_title : 'Admin Panel'; ?></h3>

            <!-- <div class="top-right">
                <button class="icon-btn" title="Notifications">
                    <i class="ri-notification-3-line"></i>
                    <span class="badge">3</span>
                </button>
                <button class="icon-btn" title="User Profile">
                    <i class="ri-user-3-line"></i>
                </button>
            </div> -->
        </div>

        <!-- Page Content -->
        <div class="page-content">
            <button id="installAppBtn"
                onclick="installPWA()"
                style="display:none; padding:8px 12px; background:#d1558f; color:white; border-radius:6px; border:none;">
                📲 Install Admin App
            </button>
<?php

// ================================
// Database Connection
// ================================
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Enable friendly error reporting

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    // This prints the exact database error if connection fails
    die("Database Connection Failed: " . $e->getMessage());
}

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

/**
 * Sanitize string input
 * @param string $input User input
 * @return string Sanitized string,
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
    $stock_quantity = isset($data['stock_quantity']) ? (int)$data['stock_quantity'] : 0;
    $category_id = validateInt($data['category_id'] ?? null);
    $image = $data['image'] ?? '';

    if (!$name || !$price) {
        return false;
    }

    $query = "INSERT INTO products (category_id, name, description, price, stock_quantity, image, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("issdis", $category_id, $name, $description, $price, $stock_quantity, $image);
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
function updateProduct($id, $data){
    global $conn;

    $id = (int)$id;
    $name = sanitizeInput($data['name'] ?? '');
    $description = $data['description'] ?? '';
    $price = filter_var($data['price'] ?? 0, FILTER_VALIDATE_FLOAT);
    // 1. Get stock quantity (default to 0 if missing)
    $stock_quantity = isset($data['stock_quantity']) ? (int)$data['stock_quantity'] : 0;
    $category_id = validateInt($data['category_id'] ?? null);
    $image = $data['image'] ?? '';

    if (!$name || !$price) {
        return false;
    }

    // 2. Added 'stock_quantity=?' to the SQL query
    $query = "UPDATE products SET category_id=?, name=?, description=?, price=?, stock_quantity=?, image=? WHERE id=?";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("issdisi", $category_id, $name, $description, $price, $stock_quantity, $image, $id);
    
    $result = $stmt->execute();

    $stmt->close();
    return $result;
}

/**
 * Delete product
 * @param int $id Product ID
 * @return bool Success
 */
function deleteProduct($id) {
    global $conn;
    $id = (int)$id;

    // First, delete associated images and variants
    $conn->query("DELETE FROM product_images WHERE product_id = $id");
    $conn->query("DELETE FROM product_variants WHERE product_id = $id");

    // Then delete the product
    $query = "DELETE FROM products WHERE id = ?";
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

// Handle CRUD operations
$action = sanitizeInput($_GET['action'] ?? '');
$product_id = validateInt($_GET['id'] ?? null);

// Initialize variables
$error = '';
$success = '';

// Check for success messages from redirects
if (isset($_GET['created'])) {
    $success = 'Product created successfully!';
} elseif (isset($_GET['updated'])) {
    $success = 'Product updated successfully!';
} elseif (isset($_GET['deleted'])) {
    $success = 'Product deleted successfully!';
}

// Variables for extra features
$existing_images = [];
$existing_variants = [];

// Initialize product for form
$product = [];

// Get single product for editing
if ($action === 'edit' && $product_id) {
    $product = getProductById($product_id);
    if (!$product) {
        header("Location: " . $BASE_URL . "admin/products.php");
        exit;
    }

    // FETCH GALLERY IMAGES
    $stmtImg = $conn->prepare("SELECT * FROM product_images WHERE product_id = ?");
    $stmtImg->bind_param("i", $product_id);
    $stmtImg->execute();
    $existing_images = $stmtImg->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtImg->close();

    // FETCH VARIANTS
    $stmtVar = $conn->prepare("SELECT * FROM product_variants WHERE product_id = ?");
    $stmtVar->bind_param("i", $product_id);
    $stmtVar->execute();
    $existing_variants = $stmtVar->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtVar->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitizeInput($_POST['action'] ?? '');
    $save_id = validateInt($_POST['id'] ?? null);

    if ($action === 'save') {
        // --- 1. HANDLE MAIN IMAGE ---
        $image_filename = $product['image'] ?? '';

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $file_mime = mime_content_type($_FILES['image']['tmp_name']);

            if (in_array($file_mime, $allowed_types)) {
                $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $new_name = 'prod_' . time() . '_' . rand(1000, 9999) . '.' . strtolower($file_ext);

                // Remove old image if replacing
                if ($save_id && !empty($product['image']) && file_exists($upload_dir . $product['image'])) {
                    unlink($upload_dir . $product['image']);
                }

                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_name)) {
                    // Optimize image: resize and compress
                    // optimizeImage($upload_dir . $new_name, 800, 600, 80);
                    $image_filename = $new_name;
                }
            } else {
                $error = "Invalid main image format.";
            }
        }

        $product_data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'price' => $_POST['price'] ?? 0,
            'stock_quantity' => isset($_POST['stock_quantity']) ? (int)$_POST['stock_quantity'] : 0,
            'category_id' => $_POST['category_id'] ?? null,
            'image' => $image_filename
        ];

        if (empty($product_data['name']) || empty($product_data['price'])) {
            $error = 'Product name and price are required.';
        } elseif (empty($error)) {

            $current_pid = 0;

            // SAVE OR UPDATE PRODUCT
            if ($save_id) {
                if (updateProduct($save_id, $product_data)) {
                    $current_pid = $save_id;
                    $success = 'Product updated successfully!';
                } else {
                    $error = 'Failed to update product.';
                }
            } else {
                $new_id = createProduct($product_data);
                if ($new_id) {
                    $current_pid = $new_id;
                    $success = 'Product created successfully!';
                } else {
                    $error = 'Failed to create product.';
                }
            }

            // --- 2. HANDLE EXTRA GALLERY IMAGES ---
            if ($current_pid && isset($_FILES['extra_images'])) {
                $upload_dir = __DIR__ . '/../uploads/';
                // Loop through all uploaded files
                foreach ($_FILES['extra_images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['extra_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($_FILES['extra_images']['name'][$key], PATHINFO_EXTENSION);
                        $fname = 'extra_' . $current_pid . '_' . time() . '_' . $key . '.' . $ext;

                        if (move_uploaded_file($tmp_name, $upload_dir . $fname)) {
                            // Optimize gallery image
                            // optimizeImage($upload_dir . $fname, 800, 600, 80);
                            $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)");
                            $stmt->bind_param("is", $current_pid, $fname);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
            }

            // --- 3. HANDLE GALLERY DELETIONS ---
            if ($current_pid && isset($_POST['delete_images'])) {
                foreach ($_POST['delete_images'] as $imgId) {
                    // Optional: Get filename to unlink file
                    $stmt = $conn->prepare("SELECT image_path FROM product_images WHERE id = ?");
                    $stmt->bind_param("i", $imgId);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($row = $res->fetch_assoc()) {
                        $fpath = __DIR__ . '/../uploads/' . $row['image_path'];
                        if (file_exists($fpath)) unlink($fpath);
                    }
                    $stmt->close();

                    // Delete DB Record
                    $stmtDel = $conn->prepare("DELETE FROM product_images WHERE id = ?");
                    $stmtDel->bind_param("i", $imgId);
                    $stmtDel->execute();
                    $stmtDel->close();
                }
            }

            // --- 4. HANDLE VARIANTS ---
            if ($current_pid) {
                // Simplest strategy: Delete old variants and re-insert active ones
                $conn->query("DELETE FROM product_variants WHERE product_id = $current_pid");

                if (isset($_POST['variants']) && is_array($_POST['variants'])) {
                    $stmtV = $conn->prepare("INSERT INTO product_variants (product_id, variant_name, price) VALUES (?, ?, ?)");
                    foreach ($_POST['variants'] as $var) {
                        if (!empty($var['name']) && !empty($var['price'])) {
                            $vName = sanitizeInput($var['name']);
                            $vPrice = (float)$var['price'];
                            $stmtV->bind_param("isd", $current_pid, $vName, $vPrice);
                            $stmtV->execute();
                        }
                    }
                    $stmtV->close();
                }
            }

            // Redirect to refresh data if it was a save
            if ($success && $save_id) {
                header("Location: " . $BASE_URL . "admin/products.php?updated=1");
                exit;
            } elseif ($success && !$save_id) {
                header("Location: " . $BASE_URL . "admin/products.php?created=1");
                exit;
            }
        }
    } elseif ($action === 'delete' && $save_id) {
        if (deleteProduct($save_id)) {
            header("Location: " . $BASE_URL . "admin/products.php?deleted=1");
            exit;
        } else {
            $error = 'Failed to delete product.';
        }
    }

    // Reload products list
    $products = getAllProducts(100, 0);
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 50; // Reduce from 100 to 50 for speed
$offset = ($page - 1) * $per_page;

// Initialize variables
$categories = getCategories();
$products = getAllProducts($per_page, $offset);
$total_products = getProductCount();
$total_pages = ceil($total_products / $per_page);

?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1>Products Management</h1>
        <a href="?action=new" class="btn btn-primary">
            <i class="ri-add-line"></i> Add New Product
        </a>
    </div>

    <?php if ($error): ?>
        <div style="background: #ffe4ef; color: #e63946; padding: 12px 15px; border-radius: 6px; margin-bottom: 20px;">
            <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="background: #d6f5e5; color: #1fbf60; padding: 12px 15px; border-radius: 6px; margin-bottom: 20px;">
            <strong>Success!</strong> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <!-- Product Form -->
    <?php if ($action === 'new' || $action === 'edit'): ?>
        <div class="form-section" style="margin-bottom: 30px; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <h3 style="margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                <?php echo $action === 'new' ? 'Create New Product' : 'Edit Product'; ?>
            </h3>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save">
                <!-- IMPORTANT: Hidden ID for editing -->
                <input type="hidden" name="id" value="<?php echo isset($product['id']) ? $product['id'] : ''; ?>">

                <!-- Basic Info -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Product Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name'] ?? '', ENT_QUOTES); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="price">Base Price (₦) *</label>
                        <input type="number" id="price" name="price" value="<?php echo htmlspecialchars($product['price'] ?? '', ENT_QUOTES); ?>" step="0.01" min="0" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="stock_quantity">Stock Quantity *</label>
                        <input type="number" id="stock_quantity" name="stock_quantity"
                            value="<?php echo htmlspecialchars($product['stock_quantity'] ?? '0', ENT_QUOTES); ?>"
                            step="1" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id">
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo (isset($product['category_id']) && $product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- 🖼️ IMAGES SECTION -->
                <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; margin: 20px 0;">
                    <h4 style="margin-top: 0; color: #555;">Product Images</h4>

                    <!-- Main Image -->
                    <div class="form-group">
                        <label>Main Image</label>
                        <input type="file" id="main-image-input" name="image" accept="image/*">
                        <?php if (isset($product['image']) && $product['image'] && $action === 'edit'): ?>
                            <div id="existing-main-preview" style="margin-top: 5px;">
                                <div style="position: relative; display: inline-block;">
                                    <img src="<?php echo $BASE_URL . 'uploads/' . htmlspecialchars($product['image']); ?>" style="width: 120px; height: 120px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                                    <small style="display: block; color: #888;">Current image (will be replaced if new selected)</small>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div id="main-image-preview" style="margin-top: 10px; display: none;">
                            <div style="position: relative; display: inline-block;">
                                <img id="main-preview-img" src="" style="width: 120px; height: 120px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                                <button type="button" id="remove-main-image" style="position: absolute; top: -5px; right: -5px; background: red; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 12px;">×</button>
                            </div>
                        </div>
                    </div>

                    <!-- Gallery Images -->
                    <div class="form-group" style="border-top: 1px solid #eee; padding-top: 15px; margin-top: 15px;">
                        <label>Gallery Images (Max 7)</label>
                        <input type="file" id="gallery-input" name="extra_images[]" multiple accept="image/*">
                        <small style="color: #888;">Hold Ctrl to select multiple images.</small>
                        <div id="gallery-previews" style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;"></div>

                        <?php if (!empty($existing_images)): ?>
                            <div style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;">
                                <?php foreach ($existing_images as $img): ?>
                                    <div style="text-align: center; background: #fff; padding: 5px; border: 1px solid #ddd; border-radius: 5px;">
                                        <img src="../uploads/<?= $img['image_path'] ?>" style="width: 60px; height: 60px; object-fit: cover;">
                                        <div style="margin-top: 5px;">
                                            <input type="checkbox" name="delete_images[]" value="<?= $img['id'] ?>"> <small style="color:red">Del</small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 🎨 VARIANTS SECTION -->
                <div style="background: #fff5f8; padding: 15px; border-radius: 8px; margin: 20px 0; border: 1px solid #ffeef2;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h4 style="margin: 0; color: #d63384;">Product Variants</h4>
                        <button type="button" id="add-variant-btn" class="btn btn-sm btn-outline" style="background: white;">+ Add Variant</button>
                    </div>
                    <p style="font-size: 12px; color: #666; margin-bottom: 15px;">Add options like Color, Size or Type. The price entered will override the Base Price.</p>

                    <div id="variants-container">
                        <!-- Existing Variants Loop -->
                        <?php if (!empty($existing_variants)): ?>
                            <?php foreach ($existing_variants as $index => $var): ?>
                                <div class="variant-row" style="display: flex; gap: 10px; margin-bottom: 10px;">
                                    <input type="text" name="variants[<?= $index ?>][name]" placeholder="Option Name (e.g. Red, 500ml)" value="<?= htmlspecialchars($var['variant_name']) ?>" style="flex: 2; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    <input type="number" name="variants[<?= $index ?>][price]" placeholder="Price" value="<?= $var['price'] ?>" step="0.01" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; max-width: 100px;">
                                    <button type="button" onclick="this.parentElement.remove()" style="background: #eee; border: none; padding: 0 12px; cursor: pointer; border-radius: 4px; color: #333;">&times;</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="description">Description</label>
                    <div id="description-editor" style="height: 250px; background: white;"></div>
                    <textarea id="description" name="description" style="display: none;"><?php echo htmlspecialchars($product['description'] ?? '', ENT_QUOTES); ?></textarea>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line"></i> Save Product
                    </button>
                    <a href="<?php echo $BASE_URL; ?>admin/products.php" class="btn btn-outline">
                        <i class="ri-close-line"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Products Table -->
    <div class="table-container">
        <div class="table-header">
            <h2>All Products</h2>
        </div>
        <?php if (!empty($products)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $prod): ?>
                        <tr>
                            <td>#<?php echo $prod['id']; ?></td>
                            <td><?php echo htmlspecialchars($prod['name']); ?></td>
                            <td><?php echo htmlspecialchars($prod['category_name'] ?? 'Uncategorized'); ?></td>
                            <td>₦<?php echo number_format($prod['price'], 2); ?></td>
                           <td>
                                <?php if ($prod['stock_quantity'] > 0): ?>
                                    <span style="color: var(--success); font-weight: bold;"><?php echo $prod['stock_quantity']; ?></span>
                                <?php else: ?>
                                    <span style="color: #e63946; font-weight: bold;">Out of Stock</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <a href="products.php?action=edit&id=<?php echo $prod['id']; ?>" class="btn btn-sm btn-outline"><i class="ri-edit-line"></i></a>
                                <form method="POST" style="display: inline;" class="confirmable" data-confirm-message="Are you sure?">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $prod['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="ri-delete-bin-line"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="padding: 20px; text-align: center; color: #888;">No products found.</p>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div style="margin-top: 20px; text-align: center;">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="btn btn-outline" style="margin-right: 10px;">&laquo; Previous</a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?page=<?= $i ?>" class="btn <?= $i == $page ? 'btn-primary' : 'btn-outline' ?>" style="margin: 0 2px;"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="btn btn-outline" style="margin-left: 10px;">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- JS for Editor & Variants -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    // 1. Quill Editor
    const isFormPage = <?php echo ($action === 'new' || $action === 'edit') ? 'true' : 'false'; ?>;
    if (isFormPage) {
        const quill = new Quill('#description-editor', {
            theme: 'snow'
        });

        <?php if ($action === 'edit' && isset($product['description'])): ?>
            quill.root.innerHTML = <?php echo json_encode($product['description']); ?>;
        <?php endif; ?>

        document.querySelector('form').addEventListener('submit', function() {
            document.getElementById('description').value = quill.root.innerHTML;
        });

        // 2. Variant Logic
        let vCounter = 1000;
        document.getElementById('add-variant-btn').addEventListener('click', function() {
            vCounter++;
            const html = `
            <div class="variant-row" style="display: flex; gap: 10px; margin-bottom: 10px;">
                <input type="text" name="variants[${vCounter}][name]" placeholder="Option Name (e.g. Red, 500ml)" style="flex: 2; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                <input type="number" name="variants[${vCounter}][price]" placeholder="Price" step="0.01" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; max-width: 100px;" required>
                <button type="button" onclick="this.parentElement.remove()" style="background: #eee; border: none; padding: 0 12px; cursor: pointer; border-radius: 4px; color: #333;">&times;</button>
            </div>`;
            document.getElementById('variants-container').insertAdjacentHTML('beforeend', html);
        });

        // 3. Image Preview Logic
        // Main Image Preview
        const mainImageInput = document.getElementById('main-image-input');
        const mainPreview = document.getElementById('main-image-preview');
        const mainPreviewImg = document.getElementById('main-preview-img');
        const removeMainBtn = document.getElementById('remove-main-image');
        const existingMainPreview = document.getElementById('existing-main-preview');

        mainImageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    mainPreviewImg.src = e.target.result;
                    mainPreview.style.display = 'block';
                    if (existingMainPreview) existingMainPreview.style.display = 'none';
                };
                reader.readAsDataURL(file);
            } else {
                mainPreview.style.display = 'none';
                if (existingMainPreview) existingMainPreview.style.display = 'block';
            }
        });

        removeMainBtn.addEventListener('click', function() {
            mainImageInput.value = '';
            mainPreview.style.display = 'none';
            if (existingMainPreview) existingMainPreview.style.display = 'block';
        });

        // Gallery Previews
        const galleryInput = document.getElementById('gallery-input');
        const galleryPreviews = document.getElementById('gallery-previews');
        let galleryFiles = [];

        galleryInput.addEventListener('change', function(e) {
            galleryFiles = Array.from(e.target.files);
            updateGalleryPreviews();
        });

        function updateGalleryPreviews() {
            galleryPreviews.innerHTML = '';
            galleryFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.style.position = 'relative';
                    div.style.display = 'inline-block';
                    div.innerHTML = `
                        <img src="${e.target.result}" style="width: 120px; height: 120px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                        <button type="button" data-index="${index}" style="position: absolute; top: -5px; right: -5px; background: red; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 12px;">×</button>
                    `;
                    galleryPreviews.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
            // Update input files
            const dt = new DataTransfer();
            galleryFiles.forEach(file => dt.items.add(file));
            galleryInput.files = dt.files;
        }

        galleryPreviews.addEventListener('click', function(e) {
            if (e.target.tagName === 'BUTTON') {
                const index = parseInt(e.target.dataset.index);
                galleryFiles.splice(index, 1);
                updateGalleryPreviews();
            }
        });
    }
</script>

<?php
require_once __DIR__ . '/footer.php';
?>

<?php include __DIR__ . '/../includes/modal.php'; ?>

<script>
    // Attach modal confirmations to forms with the `confirmable` class
    (function(){
        document.querySelectorAll('form.confirmable').forEach(function(form){
            form.addEventListener('submit', function(e){
                e.preventDefault();
                var msg = form.dataset.confirmMessage || 'Are you sure?';
                showConfirm('Please confirm', msg, function(){ form.submit(); });
            });
        });
    })();
</script>

<?php
ob_end_flush();
?>