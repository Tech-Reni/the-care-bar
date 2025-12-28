<?php
// 1. START BUFFERING & SETTINGS
ob_start();
set_time_limit(300);
ini_set('memory_limit', '256M');

$page_title = 'Products';

// ================================
// 2. DATABASE CONNECTION (MUST BE AT THE TOP)
// ================================
$host = $_SERVER['HTTP_HOST'];
if ($host == 'localhost' || strpos($host, 'localhost') !== false) {
    // LOCALHOST
    $BASE_URL = '/TheCareBar/';
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "thecarebar";
} else {
    // LIVE SERVER
    $BASE_URL = 'https://the-care-bar.com/';
    $servername = "mysql.the-care-bar.com";
    $username = "admin_thecarebar";
    $password = "programming123";
    $dbname = "thecarebar";
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// ================================
// 3. HELPER FUNCTIONS
// ================================
function sanitizeInput($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateInt($value)
{
    $value = filter_var($value, FILTER_VALIDATE_INT);
    return $value !== false ? $value : null;
}

function getAllProducts($limit = 12, $offset = 0)
{
    global $conn;
    $stmt = $conn->prepare("SELECT p.id, p.name, p.price, p.stock_quantity, p.image, c.name as category_name 
                          FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          ORDER BY p.created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function deleteProductFiles($pid)
{
    global $conn;
    // Main Image
    $res = $conn->query("SELECT image FROM products WHERE id = $pid");
    if ($row = $res->fetch_assoc()) {
        if (!empty($row['image']) && file_exists(__DIR__ . '/../uploads/' . $row['image'])) {
            unlink(__DIR__ . '/../uploads/' . $row['image']);
        }
    }
    // Gallery Images
    $res = $conn->query("SELECT image_path FROM product_images WHERE product_id = $pid");
    while ($row = $res->fetch_assoc()) {
        if (file_exists(__DIR__ . '/../uploads/' . $row['image_path'])) {
            unlink(__DIR__ . '/../uploads/' . $row['image_path']);
        }
    }
}

// ================================
// 4. AJAX SEARCH HANDLER
// ================================
if (isset($_GET['ajax_search'])) {
    $search = trim($_GET['ajax_search']);
    $results = [];

    if (empty($search)) {
        // Return normal list if search is cleared
        $results = getAllProducts(50, 0);
    } else {
        // 1. Exact ID Search
        if (is_numeric($search)) {
            $stmt = $conn->prepare("SELECT p.id, p.name, p.price, p.stock_quantity, p.image, c.name as category_name 
                                    FROM products p 
                                    LEFT JOIN categories c ON p.category_id = c.id 
                                    WHERE p.id = ?");
            $stmt->bind_param("i", $search);
            $stmt->execute();
            $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            // 2. Fuzzy Search
            $sql = "SELECT p.id, p.name, p.price, p.stock_quantity, p.image, c.name as category_name,
                    (SELECT GROUP_CONCAT(variant_name SEPARATOR ' ') FROM product_variants WHERE product_id = p.id) as variant_names
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id";
            $all_products = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

            $matches = [];
            $searchLower = strtolower($search);
            $searchMeta = metaphone($search);

            foreach ($all_products as $p) {
                $score = 1000;
                $fullName = strtolower($p['name']);
                $fullText = $fullName . ' ' . strtolower($p['category_name'] ?? '') . ' ' . strtolower($p['variant_names'] ?? '');

                // A. Direct Match
                if (strpos($fullName, $searchLower) !== false) {
                    $score = 0;
                } elseif (strpos($fullText, $searchLower) !== false) {
                    $score = 10;
                } else {
                    // B. Fuzzy Match
                    $words = explode(' ', preg_replace('/[^a-z0-9 ]/', ' ', $fullText));
                    foreach ($words as $word) {
                        if (empty($word)) continue;
                        if (metaphone($word) == $searchMeta) {
                            $score = min($score, 20);
                        }
                        $lev = levenshtein($searchLower, $word);
                        if ($lev <= 2 && strlen($searchLower) > 2) {
                            $score = min($score, 30 + $lev);
                        }
                    }
                }

                if ($score < 1000) {
                    $p['relevance'] = $score;
                    $matches[] = $p;
                }
            }

            // Sort
            usort($matches, function ($a, $b) {
                return $a['relevance'] <=> $b['relevance'];
            });
            $results = array_slice($matches, 0, 20);
        }
    }

    // Output HTML Rows
    if (count($results) > 0) {
        foreach ($results as $p) {
            $stockLabel = $p['stock_quantity'] > 0 ? $p['stock_quantity'] : '<span style="color:red">Out</span>';
            $imgSrc = !empty($p['image']) ? "../uploads/" . htmlspecialchars($p['image']) : "";
            $imgHtml = $imgSrc ? "<img src='$imgSrc' style='width:40px; height:40px; border-radius:4px; object-fit:cover;'>" : "";
            $catName = htmlspecialchars($p['category_name'] ?? '--');
            $pName = htmlspecialchars($p['name']);
            $price = number_format($p['price'], 2);

            $varHint = '';
            if (isset($p['variant_names']) && stripos($p['variant_names'], $search) !== false) {
                $varHint = '<br><small style="color:#d1558f; font-size:10px;">(Matched in variants)</small>';
            }

            echo "<tr>
                <td>#{$p['id']}</td>
                <td>
                    <div style='display:flex; align-items:center; gap:10px;'>
        
                        <div>
                            $pName $varHint<br>
                            <small style='color:#888'>$catName</small>
                        </div>
                    </div>
                </td>
                <td>₦$price</td>
                <td>$stockLabel</td>
                <td>
                    <a href='?action=edit&id={$p['id']}' class='btn btn-sm btn-outline'><i class='ri-edit-line'></i></a>
                    <form method='POST' style='display:inline;' onsubmit='return confirm(\"Delete this product?\");'>
                        <input type='hidden' name='action' value='delete'>
                        <input type='hidden' name='id' value='{$p['id']}'>
                        <button type='submit' class='btn btn-sm btn-danger'><i class='ri-delete-bin-line'></i></button>
                    </form>
                </td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='5' style='text-align:center; padding: 30px; color: #888;'>
            <div style='background: #f9f9f9; display:inline-block; padding: 20px; border-radius: 50%; margin-bottom: 10px;'>
                <i class='ri-search-2-line' style='font-size: 24px; color: #ccc;'></i>
            </div>
            <br>
            <span style='font-weight:500; color:#555;'>No results for \"<b>" . htmlspecialchars($search) . "</b>\"</span><br>
            <small>Check your spelling.</small>
        </td></tr>";
    }
    exit; // Stop execution here for AJAX
}

// ================================
// 5. STANDARD POST LOGIC (SAVE/DELETE)
// ================================
$action = sanitizeInput($_GET['action'] ?? '');
$product_id = validateInt($_GET['id'] ?? null);
$error = '';
$success = '';

if (isset($_GET['created'])) $success = 'Product created successfully!';
if (isset($_GET['updated'])) $success = 'Product updated successfully!';
if (isset($_GET['deleted'])) $success = 'Product deleted successfully!';

$product = [];
$existing_images = [];
$existing_variants = [];

if ($action === 'edit' && $product_id) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    if (!$product) {
        header("Location: products.php");
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $existing_images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt = $conn->prepare("SELECT * FROM product_variants WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $existing_variants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    $save_id = validateInt($_POST['id'] ?? null);

    if ($postAction === 'save') {
        $name = sanitizeInput($_POST['name']);
        $price = (float)$_POST['price'];
        $stock = (int)$_POST['stock_quantity'];
        $cat_id = validateInt($_POST['category_id']);
        $desc = $_POST['description'];

        if (!$name || !$price) {
            $error = "Name and Price are required.";
        } else {
            $conn->begin_transaction();
            try {
                $upload_dir = __DIR__ . '/../uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                $new_main_image = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $fname = 'prod_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                    move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $fname);
                    $new_main_image = $fname;
                }

                $current_pid = 0;
                if ($save_id) {
                    $query = "UPDATE products SET category_id=?, name=?, description=?, price=?, stock_quantity=?";
                    $types = "issdi";
                    $params = [$cat_id, $name, $desc, $price, $stock];
                    if ($new_main_image) {
                        $query .= ", image=?";
                        $types .= "s";
                        $params[] = $new_main_image;
                    }
                    $query .= " WHERE id=?";
                    $types .= "i";
                    $params[] = $save_id;
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $current_pid = $save_id;
                } else {
                    $img_val = $new_main_image ?? '';
                    $stmt = $conn->prepare("INSERT INTO products (category_id, name, description, price, stock_quantity, image, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("issdis", $cat_id, $name, $desc, $price, $stock, $img_val);
                    $stmt->execute();
                    $current_pid = $conn->insert_id;
                }

                if (isset($_FILES['extra_images'])) {
                    $img_stmt = $conn->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)");
                    foreach ($_FILES['extra_images']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['extra_images']['error'][$key] === UPLOAD_ERR_OK) {
                            $ext = pathinfo($_FILES['extra_images']['name'][$key], PATHINFO_EXTENSION);
                            $fname = 'extra_' . $current_pid . '_' . time() . '_' . $key . '.' . $ext;
                            if (move_uploaded_file($tmp_name, $upload_dir . $fname)) {
                                $img_stmt->bind_param("is", $current_pid, $fname);
                                $img_stmt->execute();
                            }
                        }
                    }
                }

                if (isset($_POST['delete_images'])) {
                    $del_stmt = $conn->prepare("DELETE FROM product_images WHERE id = ?");
                    foreach ($_POST['delete_images'] as $imgId) {
                        $del_stmt->bind_param("i", $imgId);
                        $del_stmt->execute();
                    }
                }

                $conn->query("DELETE FROM product_variants WHERE product_id = $current_pid");
                if (isset($_POST['variants']) && is_array($_POST['variants'])) {
                    $v_stmt = $conn->prepare("INSERT INTO product_variants (product_id, variant_name, price, stock_quantity) VALUES (?, ?, ?, ?)");
                    foreach ($_POST['variants'] as $var) {
                        if (!empty($var['name']) && !empty($var['price'])) {
                            $v_stmt->bind_param("isdi", $current_pid, sanitizeInput($var['name']), (float)$var['price'], (int)($var['stock'] ?? 0));
                            $v_stmt->execute();
                        }
                    }
                }

                $conn->commit();
                header("Location: products.php" . ($save_id ? "?updated=1" : "?created=1"));
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Failed to save: " . $e->getMessage();
            }
        }
    } elseif ($postAction === 'delete' && $save_id) {
        deleteProductFiles($save_id);
        $conn->query("DELETE FROM products WHERE id = $save_id");
        header("Location: products.php?deleted=1");
        exit;
    }
}

// Initial Data Load
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;
$products = getAllProducts($per_page, $offset);
$cats_res = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $cats_res->fetch_all(MYSQLI_ASSOC);
$total_pages = ceil($conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'] / $per_page);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - The Care Bar Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $BASE_URL ?>assets/css/admin.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <link rel="icon" href="<?= $BASE_URL ?>assets/img/logo.png" type="image/x-icon">
    <style>
        #loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            backdrop-filter: blur(2px);
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #d1558f;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }

        .loading-text {
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            color: #333;
            font-size: 1.1rem;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .variant-row input {
            width: 80px;
        }
    </style>
</head>

<body>
    <div class="sidebar" id="sidebar">
        <div class="sb-header">
            <h2>The Care Bar</h2>
            <button class="sb-close" onclick="toggleSidebar()"><i class="ri-close-line"></i></button>
        </div>
        <div class="sb-menu">
            <a href="<?= $BASE_URL ?>admin/index.php"><i class="ri-dashboard-line"></i> Dashboard</a>
            <a href="<?= $BASE_URL ?>admin/products.php" class="active"><i class="ri-shopping-bag-3-line"></i> Products</a>
            <a href="<?= $BASE_URL ?>admin/categories.php"><i class="ri-folders-line"></i> Categories</a>
            <a href="<?= $BASE_URL ?>admin/orders.php"><i class="ri-file-list-3-line"></i> Orders</a>
        </div>
        <div class="sb-footer">
            <a href="<?= $BASE_URL ?>" class="sb-link-secondary"><i class="ri-home-4-line"></i> View Site</a>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <button class="menu-btn" onclick="toggleSidebar()"><i class="ri-menu-line"></i></button>
            <h3 id="page-title"><?= $page_title ?></h3>
        </div>

        <div class="page-content">
            <div class="container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                    <h1>Products</h1>
                    <a href="?action=new" class="btn btn-primary"><i class="ri-add-line"></i> Add Product</a>
                </div>

                <?php if ($error): ?><div class="alert alert-danger" style="background:#ffe4ef; color:#e63946; padding:15px; border-radius:6px; margin-bottom:20px;"><?= $error ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success" style="background:#d6f5e5; color:#1fbf60; padding:15px; border-radius:6px; margin-bottom:20px;"><?= $success ?></div><?php endif; ?>

                <?php if ($action === 'new' || $action === 'edit'): ?>
                    <div class="form-section" style="margin-bottom: 30px; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                        <h3><?= $action === 'new' ? 'Create New Product' : 'Edit Product' ?></h3>
                        <form id="productForm" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="id" value="<?= $product['id'] ?? '' ?>">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Product Name *</label>
                                    <input type="text" name="name" value="<?= htmlspecialchars($product['name'] ?? '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Base Price (₦) *</label>
                                    <input type="number" name="price" value="<?= $product['price'] ?? '' ?>" step="0.01" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Stock Quantity *</label>
                                    <input type="number" name="stock_quantity" value="<?= $product['stock_quantity'] ?? '0' ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Category</label>
                                    <select name="category_id">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= (isset($product['category_id']) && $product['category_id'] == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; margin: 20px 0;">
                                <h4>Product Images</h4>
                                <div class="form-group">
                                    <label>Main Image</label>
                                    <input type="file" id="main-image-input" name="image" accept="image/*">
                                    <?php if (!empty($product['image'])): ?>
                                        <div id="existing-main-preview" style="margin-top:5px;"><img src="../uploads/<?= htmlspecialchars($product['image']) ?>" style="width:100px; height:100px; object-fit:cover;"></div>
                                    <?php endif; ?>
                                    <div id="main-image-preview" style="display:none; margin-top:5px;"><img id="main-preview-img" style="width:100px; height:100px; object-fit:cover;"></div>
                                </div>
                                <div class="form-group" style="margin-top:15px;">
                                    <label>Gallery Images (Hold Ctrl to select multiple)</label>
                                    <input type="file" id="gallery-input" name="extra_images[]" multiple accept="image/*">
                                    <div id="gallery-previews" style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px;"></div>
                                    <?php if (!empty($existing_images)): ?>
                                        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px;">
                                            <?php foreach ($existing_images as $img): ?>
                                                <div style="text-align:center; border:1px solid #ddd; padding:5px; background:white;">
                                                    <img src="../uploads/<?= $img['image_path'] ?>" style="width:60px; height:60px; object-fit:cover;">
                                                    <div><input type="checkbox" name="delete_images[]" value="<?= $img['id'] ?>"> <small style="color:red">Del</small></div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="background: #fff5f8; padding: 15px; border-radius: 8px; margin: 20px 0;">
                                <div style="display:flex; justify-content:space-between;">
                                    <h4 style="color: #d63384;">Product Variants</h4>
                                    <button type="button" id="add-variant-btn" class="btn btn-sm" style="background:white; border:1px solid #ddd;">+ Add</button>
                                </div>
                                <div id="variants-container" style="margin-top:10px;">
                                    <?php if (!empty($existing_variants)): ?>
                                        <?php foreach ($existing_variants as $idx => $var): ?>
                                            <div class="variant-row" style="display: flex; gap: 10px; margin-bottom: 10px;">
                                                <input type="text" name="variants[<?= $idx ?>][name]" value="<?= htmlspecialchars($var['variant_name']) ?>" placeholder="Name" style="flex:2; padding:8px;">
                                                <input type="number" name="variants[<?= $idx ?>][price]" value="<?= $var['price'] ?>" placeholder="Price" step="0.01" style="flex:1; padding:8px;">
                                                <input type="number" name="variants[<?= $idx ?>][stock]" value="<?= $var['stock_quantity'] ?>" placeholder="Stock" style="flex:1; padding:8px;">
                                                <button type="button" onclick="this.parentElement.remove()" style="border:none; padding: 5px; background:#eee;">&times;</button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <div id="description-editor" style="height: 250px; background: white;"></div>
                                <textarea id="description" name="description" style="display: none;"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                            </div>
                            <div style="margin-top:20px;">
                                <button type="submit" class="btn btn-primary"><i class="ri-save-line"></i> Save Product</button>
                                <a href="products.php" class="btn btn-outline">Cancel</a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="table-container">
                    <div class="table-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                        <h2>All Products</h2>
                        <div class="search-wrapper" style="position: relative; width: 100%; max-width: 400px;">
                            <i class="ri-search-line" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #888; pointer-events: none;"></i>
                            <input type="text" id="pro-search" placeholder="Search product name..." style="width: 100%; padding: 10px 35px 10px 40px; border: 1px solid #ddd; border-radius: 25px; outline: none; transition: all 0.3s;">
                            <i id="clear-search" class="ri-close-circle-fill" style="display: none; position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #ccc; cursor: pointer; font-size: 18px;"></i>
                            <div id="search-spinner" style="display: none; position: absolute; right: 15px; top: 50%; transform: translateY(-50%);"><i class="ri-loader-4-line" style="animation: spin 1s infinite linear; color: #d1558f;"></i></div>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="products-table-body">
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td>#<?= $p['id'] ?></td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <div>
                                                <?= htmlspecialchars($p['name']) ?><br>
                                                <small style="color:#888"><?= htmlspecialchars($p['category_name'] ?? '--') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>₦<?= number_format($p['price'], 2) ?></td>
                                    <td><?= $p['stock_quantity'] > 0 ? $p['stock_quantity'] : '<span style="color:red">Out</span>' ?></td>
                                    <td>
                                        <a href="?action=edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline"><i class="ri-edit-line"></i></a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this product?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($total_pages > 1): ?>
                        <div style="margin-top:20px; text-align:center;">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?= $i ?>" class="btn <?= $i == $page ? 'btn-primary' : 'btn-outline' ?>" style="padding:5px 10px;"><?= $i ?></a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="loading-overlay">
                <div class="spinner"></div>
                <div class="loading-text">Saving Product... Please wait.</div>
            </div>

            <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
            <script>
                if (document.getElementById('description-editor')) {
                    var quill = new Quill('#description-editor', {
                        theme: 'snow'
                    });
                    <?php if (isset($product['description'])): ?>
                        quill.root.innerHTML = <?php echo json_encode($product['description']); ?>;
                    <?php endif; ?>
                    document.getElementById('productForm').addEventListener('submit', function() {
                        document.getElementById('description').value = quill.root.innerHTML;
                        document.getElementById('loading-overlay').style.display = 'flex';
                    });
                }

                const mainInp = document.getElementById('main-image-input');
                if (mainInp) {
                    mainInp.addEventListener('change', function(e) {
                        if (e.target.files[0]) {
                            var reader = new FileReader();
                            reader.onload = function(ev) {
                                document.getElementById('main-preview-img').src = ev.target.result;
                                document.getElementById('main-image-preview').style.display = 'block';
                                if (document.getElementById('existing-main-preview'))
                                    document.getElementById('existing-main-preview').style.display = 'none';
                            }
                            reader.readAsDataURL(e.target.files[0]);
                        }
                    });
                }

                let vCount = 1000;
                const vBtn = document.getElementById('add-variant-btn');
                if (vBtn) {
                    vBtn.addEventListener('click', function() {
                        vCount++;
                        const html = `<div class="variant-row" style="display: flex; gap: 10px; margin-bottom: 10px;">
                            <input type="text" name="variants[${vCount}][name]" placeholder="Name" style="flex:2; padding:8px;" required>
                            <input type="number" name="variants[${vCount}][price]" placeholder="Price" step="0.01" style="flex:1; padding:8px;" required>
                            <input type="number" name="variants[${vCount}][stock]" placeholder="Stock" style="flex:1; padding:8px;" required>
                            <button type="button" onclick="this.parentElement.remove()" style="border:none; background:#eee;">&times;</button>
                        </div>`;
                        document.getElementById('variants-container').insertAdjacentHTML('beforeend', html);
                    });
                }

                function toggleSidebar() {
                    document.getElementById('sidebar').classList.toggle('active');
                    document.querySelector('.main').classList.toggle('active');
                }

                // AJAX SEARCH
                const searchInput = document.getElementById('pro-search');
                const tableBody = document.getElementById('products-table-body');
                const spinner = document.getElementById('search-spinner');
                const clearBtn = document.getElementById('clear-search');
                let timeout = null;

                function performSearch(term) {
                    if (term.length > 0) {
                        clearBtn.style.display = 'none';
                        spinner.style.display = 'block';
                    } else {
                        clearBtn.style.display = 'none';
                        spinner.style.display = 'none';
                    }
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        fetch('products.php?ajax_search=' + encodeURIComponent(term))
                            .then(response => response.text())
                            .then(html => {
                                tableBody.innerHTML = html;
                                spinner.style.display = 'none';
                                if (term.length > 0) clearBtn.style.display = 'block';
                            })
                            .catch(err => {
                                console.error(err);
                                spinner.style.display = 'none';
                            });
                    }, 300);
                }

                if (searchInput) {
                    searchInput.addEventListener('keyup', function() {
                        performSearch(this.value);
                    });
                    if (clearBtn) {
                        clearBtn.addEventListener('click', function() {
                            searchInput.value = '';
                            performSearch('');
                            this.style.display = 'none';
                            searchInput.focus();
                        });
                    }
                }
            </script>
        </div>
    </div>
</body>
<?php require_once 'footer.php' ?>
<?php ob_end_flush(); ?>