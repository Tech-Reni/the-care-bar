<?php
// 1. START BUFFERING: This prevents HTML from sending immediately
ob_start();

$page_title = 'Products';
require_once __DIR__ . '/header.php';

// Handle CRUD operations
$action = sanitizeInput($_GET['action'] ?? '');
$product_id = validateInt($_GET['id'] ?? null);

// 2. FIX: Initialize as empty array to prevent "null" errors
$product = []; 
$categories = getCategories();
$products = [];
$error = '';
$success = '';

// Get all products
$products = getAllProducts(100, 0);

// Get single product for editing
if ($action === 'edit' && $product_id) {
    $product = getProductById($product_id);
    if (!$product) {
        // Redirection works now because of ob_start()
        header("Location: " . $BASE_URL . "admin/products.php");
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitizeInput($_POST['action'] ?? '');
    $product_id = validateInt($_POST['id'] ?? null); 

    if ($action === 'save') {
        $image_filename = $product['image'] ?? '';

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $file_mime = mime_content_type($_FILES['image']['tmp_name']);
            
            if (!in_array($file_mime, $allowed_types)) {
                $error = 'Invalid file type. Only JPEG, JPG, PNG, GIF, and WebP images are allowed.';
            } else {
                $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image_filename = 'product_' . time() . '_' . rand(1000, 9999) . '.' . strtolower($file_ext);
                $upload_path = $upload_dir . $image_filename;

                if ($product_id && isset($product['image']) && !empty($product['image'])) {
                    $old_image_path = $upload_dir . $product['image'];
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }

                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $error = 'Failed to upload image. Please try again.';
                    $image_filename = $product['image'] ?? '';
                }
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
            if ($product_id) {
                if (updateProduct($product_id, $product_data)) {
                    $success = 'Product updated successfully!';
                    $product = getProductById($product_id);
                } else {
                    $error = 'Failed to update product.';
                }
            } else {
                $new_id = createProduct($product_data);
                if ($new_id) {
                    $success = 'Product created successfully!';
                    header("Location: " . $BASE_URL . "admin/products.php");
                    exit;
                } else {
                    $error = 'Failed to create product.';
                }
            }
        }
    } elseif ($action === 'delete' && $product_id) { 
        if (deleteProduct($product_id)) {
            header("Location: " . $BASE_URL . "admin/products.php");
            $success = 'Product deleted successfully!';
            $product = null;
            exit;
        } else {
            $error = 'Failed to delete product.';
        }
    }

    // Reload products
    $products = getAllProducts(100, 0);
}
?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1>Products Management</h1>
    </div>

    <?php if ($error): ?>
        <div style="background: #ffe4ef; color: #e63946; padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #e63946;">
            <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="background: #d6f5e5; color: #1fbf60; padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #1fbf60;">
            <strong>Success!</strong> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <!-- Product Form -->
    <?php if ($action === 'new' || $action === 'edit'): ?>
        <div class="form-section" style="margin-bottom: 30px;">
            <h3><?php echo $action === 'new' ? 'Create New Product' : 'Edit Product'; ?></h3>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save">

                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Product Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name'] ?? '', ENT_QUOTES); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="price">Price (₦) *</label>
                        <input type="number" id="price" name="price" value="<?php echo htmlspecialchars($product['price'] ?? '', ENT_QUOTES); ?>" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="stock_quantity">Stock Quantity *</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" 
                               value="<?php echo htmlspecialchars($product['stock_quantity'] ?? '0', ENT_QUOTES); ?>" 
                               step="1" min="0" required>
                    </div>
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

                <div class="form-group">
                    <label for="image">Product Image</label>
                    <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                    <small style="color: var(--gray-400); display: block; margin-top: 5px;">Accepted formats: JPEG, PNG, GIF, WebP</small>
                    <?php if (isset($product['image']) && $product['image']): ?>
                        <div style="margin-top: 10px;">
                            <p style="font-size: 14px; color: var(--gray-500);">Current image:</p>
                            <img src="<?php echo $BASE_URL . 'uploads/' . htmlspecialchars($product['image']); ?>" alt="Product" style="max-width: 200px; border-radius: 6px;">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <div id="description-editor" style="height: 300px; background: white; border: 1px solid var(--border-color); border-radius: 6px; overflow: hidden;"></div>
                    <textarea id="description" name="description" style="display: none;"><?php echo htmlspecialchars($product['description'] ?? '', ENT_QUOTES); ?></textarea>
                </div>

                <div style="display: flex; gap: 10px;">
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
                        <th>Created</th>
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
                            <td><?php echo date('M d, Y', strtotime($prod['created_at'])); ?></td>
                            <td style="text-align: center;">
                                <a href="<?php echo $BASE_URL; ?>admin/products.php?action=edit&id=<?php echo $prod['id']; ?>" class="btn btn-sm btn-outline">
                                    <i class="ri-edit-line"></i> Edit
                                </a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $prod['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" style="padding: 6px 12px;">
                                        <i class="ri-delete-bin-line"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="padding: 40px; text-align: center; color: var(--gray-400);">
                <i class="ri-inbox-line" style="font-size: 48px; display: block; margin-bottom: 10px;"></i>
                <p>No products yet. <a href="<?php echo $BASE_URL; ?>admin/products.php?action=new" style="color: var(--pink-400);">Create one now</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quill Editor Script -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<script>
    // Only initialize Quill if we're on the form page
    const isFormPage = <?php echo ($action === 'new' || $action === 'edit') ? 'true' : 'false'; ?>;
    
    if (isFormPage) {
        const quill = new Quill('#description-editor', {
            theme: 'snow',
            placeholder: 'Enter product description...',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote', 'code-block'],
                    [{ 'header': 1 }, { 'header': 2 }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link'],
                    ['clean']
                ]
            }
        });

        // Load existing description if editing
        <?php if ($action === 'edit' && isset($product['description'])): ?>
            quill.root.innerHTML = <?php echo json_encode($product['description']); ?>;
        <?php endif; ?>

        // Sync Quill content to hidden textarea before form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const descriptionTextarea = document.getElementById('description');
            descriptionTextarea.value = quill.root.innerHTML;
        });
    }
</script>

<?php 
require_once __DIR__ . '/footer.php';
include __DIR__ . '/../includes/modal.php'; 

// 4. FLUSH BUFFER: Send the HTML to the browser now that we are done
ob_end_flush();
?>