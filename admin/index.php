<?php

require_once __DIR__ . '/auth_session.php'; 

$page_title = 'Dashboard';
require_once __DIR__ . '/header.php';

// Get statistics (Original stats)
$stats = getAdminStats();

// -------------------------------------------------------------------------
// CUSTOM MONTHLY SALES LOGIC (STARTS ON THE 23RD) - CRASH PROOF
// -------------------------------------------------------------------------

$monthly_sales_amount = 0;
$db_error = null;

// 1. Calculate the Date Range (Robust Math Method)
$start_day_limit = 23; 
$today_day = (int)date('d');
$today_month = (int)date('m');
$today_year = (int)date('Y');

if ($today_day >= $start_day_limit) {
    // CURRENT CYCLE: Today is 23rd or later (e.g. Dec 25). Start date is Dec 23.
    $period_start_date = date("Y-m-d", mktime(0, 0, 0, $today_month, $start_day_limit, $today_year));
} else {
    // PREVIOUS CYCLE: Today is before 23rd (e.g. Jan 5). Start date is Dec 23.
    $period_start_date = date("Y-m-d", mktime(0, 0, 0, $today_month - 1, $start_day_limit, $today_year));
}

$sales_label = "Sales (" . date('M j', strtotime($period_start_date)) . " - Now)";

// 2. Query the Database (Wrapped in Try-Catch to prevent Fatal Errors)
if (isset($conn) && $conn) {
    try {
        $sql_date = $period_start_date . " 00:00:00";
        
        // --- CONFIGURATION: EDIT COLUMN NAME HERE IF NEEDED ---
        // I changed 'total_amount' to 'total'. 
        // If this is still wrong, try changing 'total' to 'amount', 'price', or 'grand_total'.
        $sql = "SELECT SUM(total) as monthly_total 
                FROM orders 
                WHERE created_at >= '$sql_date' 
                AND status = 'completed'"; 
        
        $result = $conn->query($sql);
        
        if ($result) {
            $row = $result->fetch_assoc();
            $monthly_sales_amount = $row['monthly_total'] ?? 0;
            // Override the stats array
            $stats['total_sales'] = $monthly_sales_amount;
        }
    } catch (Exception $e) {
        // If the query fails (e.g., wrong column name), we catch the error here
        // so the page doesn't crash. We leave $monthly_sales_amount as 0.
        $db_error = "DB Error: Check column name";
    }
}
// -------------------------------------------------------------------------
?>

<script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
<script>
  window.OneSignalDeferred = window.OneSignalDeferred || [];
  OneSignalDeferred.push(async function(OneSignal) {
    await OneSignal.init({
      appId: "7e511f6c-c42e-4e6f-a989-b7eb636981bd",
      safari_web_id: "web.onesignal.auto.1b5e3a9a-fd8d-4cbc-b150-cc0a98b0f0fe",
      notifyButton: {
        enable: true,
      },
    });
  });
</script>


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

        <!-- UPDATED SALES CARD -->
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="ri-money-dollar-circle-line"></i>
            </div>
            <div class="stat-content">
                <!-- Title shows date range -->
                <h3><?php echo $sales_label; ?></h3>
                
                <!-- Value shows Sales or Error message if column is wrong -->
                <?php if ($db_error): ?>
                    <p class="value" style="font-size: 16px; color: red;"><?php echo $db_error; ?></p>
                <?php else: ?>
                    <p class="value">₦<?php echo number_format($stats['total_sales'] ?? 0, 2); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div style="margin-bottom: 30px; display: flex; gap: 10px; justify-content: space-evenly; flex-wrap: wrap;">
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

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
<?php include __DIR__ . '/../includes/modal.php'; ?>