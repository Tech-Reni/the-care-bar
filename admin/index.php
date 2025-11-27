<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/header.php';

// Get statistics
$stats = getAdminStats();
?>

<!-- Dashboard Content -->
<div class="container">
    <h1 style="margin-bottom: 30px;">Dashboard</h1>

    <!-- Statistics Grid -->
    <div class="dashboard-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="ri-shopping-bag-line"></i>
            </div>
            <div class="stat-content">
                <h3>Total Products</h3>
                <p class="value"><?php echo $stats['product_count']; ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="ri-folders-line"></i>
            </div>
            <div class="stat-content">
                <h3>Total Categories</h3>
                <p class="value"><?php echo $stats['category_count']; ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="ri-money-dollar-circle-line"></i>
            </div>
            <div class="stat-content">
                <h3>Inventory Value</h3>
                <p class="value">₦<?php echo number_format($stats['total_inventory_value'], 0); ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="ri-file-list-line"></i>
            </div>
            <div class="stat-content">
                <h3>Total Orders</h3>
                <p class="value"><?php echo $stats['total_orders'] ?? 0; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="ri-time-line"></i>
            </div>
            <div class="stat-content">
                <h3>Orders Pending</h3>
                <p class="value"><?php echo $stats['orders_pending'] ?? 0; ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="ri-check-line"></i>
            </div>
            <div class="stat-content">
                <h3>Orders Completed</h3>
                <p class="value"><?php echo $stats['orders_completed'] ?? 0; ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="ri-money-dollar-circle-line"></i>
            </div>
            <div class="stat-content">
                <h3>Sales</h3>
                <p class="value">₦<?php echo number_format($stats['total_sales'] ?? 0, 2); ?></p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div style="margin-bottom: 30px; display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="<?php echo $BASE_URL; ?>admin/products.php?action=new" class="btn btn-primary">
            <i class="ri-add-line"></i> Add New Product
        </a>
        <a href="<?php echo $BASE_URL; ?>admin/categories.php?action=new" class="btn btn-primary">
            <i class="ri-add-line"></i> Add New Category
        </a>
        <a href="<?php echo $BASE_URL; ?>admin/products.php" class="btn btn-outline">
            <i class="ri-list-check"></i> Manage Products
        </a>
        <a href="<?php echo $BASE_URL; ?>admin/categories.php" class="btn btn-outline">
            <i class="ri-folder-edit-line"></i> Manage Categories
        </a>
    </div>

    <!-- Welcome Message -->
    <!-- <div class="form-section">
        <h3>Welcome to The Care Bar Admin</h3>
        <p style="color: var(--gray-500); margin: 0;">
            Use the navigation menu on the left to manage products, categories, and more. 
            This admin panel is designed to help you efficiently manage your online store.
        </p>
    </div> -->
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
<?php include __DIR__ . '/../includes/modal.php'; ?>
