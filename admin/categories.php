<?php
$page_title = 'Categories';
require_once __DIR__ . '/header.php';

// Handle CRUD operations
$action = sanitizeInput($_GET['action'] ?? '');
$category_id = validateInt($_GET['id'] ?? null);

$category = null;
$categories = [];
$error = '';
$success = '';

// Get all categories
$categories = getCategories();

// Get single category for editing
if ($action === 'edit' && $category_id) {
    foreach ($categories as $cat) {
        if ($cat['id'] === $category_id) {
            $category = $cat;
            break;
        }
    }

    if (!$category) {
        header("Location: " . $BASE_URL . "admin/categories.php");
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitizeInput($_POST['action'] ?? '');
    $category_id = validateInt($_POST['id'] ?? null); 
    $name = sanitizeInput($_POST['name'] ?? '');

    if ($action === 'save') {
        if (empty($name)) {
            $error = 'Category name is required.';
        } else {
            if ($category_id) {
                // Update
                if (updateCategory($category_id, $name)) {
                    $success = 'Category updated successfully!';
                    $category = null;
                } else {
                    $error = 'Failed to update category.';
                }
            } else {
                // Create
                if (createCategory($name)) {
                    $success = 'Category created successfully!';
                } else {
                    $error = 'Failed to create category.';
                }
            }
        }

        // Reload categories
        $categories = getCategories();

    } elseif ($action === 'delete' && $category_id) {
        if (deleteCategory($category_id)) {
            $success = 'Category deleted successfully!';
            $category = null;
        } else {
            $error = 'Failed to delete category.';
        }

        // Reload categories
        $categories = getCategories();
    }
}
?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1>Categories Management</h1>
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

    <!-- Category Form -->
    <?php if ($action === 'new' || $action === 'edit'): ?>
        <div class="form-section" style="margin-bottom: 30px; max-width: 500px;">
            <h3><?php echo $action === 'new' ? 'Create New Category' : 'Edit Category'; ?></h3>

            <form method="POST">
                <input type="hidden" name="action" value="save">

                 <!-- ADD THIS LINE HERE: -->
                <input type="hidden" name="id" value="<?php echo $category['id'] ?? ''; ?>"> 

                <div class="form-group">
                    <label for="name">Category Name *</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($category['name'] ?? '', ENT_QUOTES); ?>" required autofocus>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line"></i> Save Category
                    </button>
                    <a href="<?php echo $BASE_URL; ?>admin/categories.php" class="btn btn-outline">
                        <i class="ri-close-line"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Categories Table -->
    <div class="table-container">
        <div class="table-header">
            <h2>All Categories</h2>
        </div>

        <?php if (!empty($categories)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category Name</th>
                        <th>Created</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td>#<?php echo $cat['id']; ?></td>
                            <td><?php echo htmlspecialchars($cat['name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($cat['created_at'])); ?></td>
                            <td style="text-align: center;">
                                <a href="<?php echo $BASE_URL; ?>admin/categories.php?action=edit&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline">
                                    <i class="ri-edit-line"></i> Edit
                                </a>
                                <form method="POST" style="display: inline;" class="confirmable" data-confirm-message="This will reassign products with this category. Continue?">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
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
                <p>No categories yet. <a href="<?php echo $BASE_URL; ?>admin/categories.php?action=new" style="color: var(--pink-400);">Create one now</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
<?php include __DIR__ . '/../includes/modal.php'; ?>

<script>
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