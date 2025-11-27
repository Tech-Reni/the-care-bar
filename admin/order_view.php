<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = getOrderById($id);
if (!$order) {
    echo '<div class="container"><h2>Order not found</h2></div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

$items = getOrderItems($id);

// Status badge colors
$statusColors = [
    'Pending' => '#FFA500',
    'Processing' => '#4169E1',
    'Completed' => '#28A745',
    'Cancelled' => '#DC3545'
];
$statusBgColors = [
    'Pending' => '#FFF3CD',
    'Processing' => '#E7F3FF',
    'Completed' => '#D4EDDA',
    'Cancelled' => '#F8D7DA'
];
$statusColor = $statusColors[$order['status']] ?? '#666';
$statusBgColor = $statusBgColors[$order['status']] ?? '#f5f5f5';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - The Care Bar Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/3.5.0/remixicon.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding: 20px;
        }

        .order-view-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header Section */
        .order-header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            border-left: 5px solid #E91E63;
        }

        .order-header-left h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .order-header-left i {
            font-size: 32px;
            color: #E91E63;
        }

        .order-number {
            color: #999;
            font-size: 14px;
            letter-spacing: 1px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            color: <?php echo $statusColor; ?>;
            background-color: <?php echo $statusBgColor; ?>;
            border: 1px solid <?php echo $statusColor; ?>;
        }

        .status-badge i {
            font-size: 18px;
        }

        /* Back Button */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #f5f5f5;
            color: #666;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .back-btn:hover {
            background: #E91E63;
            color: white;
        }

        /* Grid Layout */
        .order-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }

        @media (max-width: 1024px) {
            .order-content {
                grid-template-columns: 1fr;
            }
        }

        /* Card Styling */
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border-top: 3px solid #E91E63;
        }

        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            font-size: 22px;
            color: #E91E63;
        }

        /* Customer Information */
        .customer-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .info-item {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 3px solid #E91E63;
        }

        .info-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 14px;
            color: #333;
            font-weight: 500;
            word-break: break-word;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table thead {
            background: linear-gradient(135deg, #E91E63 0%, #C2185B 100%);
        }

        .items-table th {
            padding: 15px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
            font-size: 14px;
        }

        .items-table tbody tr {
            transition: background 0.3s ease;
        }

        .items-table tbody tr:hover {
            background: #f9f9f9;
        }

        .items-table tbody tr:last-child td {
            border-bottom: none;
        }

        .item-qty {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 30px;
            height: 30px;
            background: #E91E63;
            color: white;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
        }

        /* Summary Section */
        .summary-section {
            background: linear-gradient(135deg, #f5f7fa 0%, #f0f1f5 100%);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            font-size: 14px;
            border-bottom: 1px solid #e0e0e0;
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .summary-label {
            color: #666;
            font-weight: 500;
        }

        .summary-value {
            color: #333;
            font-weight: 600;
        }

        .summary-total {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            border-left: 4px solid #E91E63;
        }

        .summary-total .summary-label {
            font-size: 16px;
            color: #333;
            font-weight: 700;
        }

        .summary-total .summary-value {
            font-size: 24px;
            color: #E91E63;
            font-weight: 700;
        }

        /* Status Update Form */
        .status-form {
            background: linear-gradient(135deg, #FFF5E1 0%, #FFE0B2 100%);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #E91E63;
            border-radius: 8px;
            font-size: 14px;
            color: #333;
            background: white;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .form-select:hover {
            border-color: #C2185B;
            box-shadow: 0 2px 8px rgba(233, 30, 99, 0.15);
        }

        .form-select:focus {
            outline: none;
            border-color: #E91E63;
            box-shadow: 0 0 0 3px rgba(233, 30, 99, 0.1);
        }

        /* Button */
        .btn-update {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #E91E63 0%, #C2185B 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(233, 30, 99, 0.3);
        }

        .btn-update:active {
            transform: translateY(0);
        }

        .btn-update i {
            font-size: 16px;
        }

        /* Payment Info */
        .payment-info {
            background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%);
            border-radius: 10px;
            padding: 20px;
        }

        .payment-method {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: white;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .payment-method:last-child {
            margin-bottom: 0;
        }

        .payment-method i {
            font-size: 24px;
            color: #4CAF50;
            min-width: 30px;
        }

        .payment-details-text {
            font-size: 13px;
            color: #666;
            word-break: break-word;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .customer-info {
                grid-template-columns: 1fr;
            }

            .items-table th,
            .items-table td {
                padding: 10px;
                font-size: 12px;
            }

            .order-header-left h1 {
                font-size: 22px;
            }

            .summary-total .summary-value {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="order-view-container">
        <!-- Header -->
        <div class="order-header">
            <div class="order-header-left">
                <h1>
                    <i class="ri-file-list-3-line"></i>
                    Order Details
                </h1>
                <p class="order-number"><?php echo htmlspecialchars($order['order_number']); ?></p>
            </div>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <div class="status-badge">
                    <i class="ri-<?php 
                        echo match($order['status']) {
                            'Pending' => 'time-line',
                            'Processing' => 'progress-4-line',
                            'Completed' => 'check-double-line',
                            'Cancelled' => 'close-circle-line',
                            default => 'question-mark'
                        };
                    ?>"></i>
                    <?php echo htmlspecialchars($order['status']); ?>
                </div>
                <a href="orders.php" class="back-btn">
                    <i class="ri-arrow-left-line"></i>
                    Back to Orders
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="order-content">
            <!-- Left Column: Customer & Items -->
            <div>
                <!-- Customer Information -->
                <div class="card">
                    <div class="card-title">
                        <i class="ri-user-3-line"></i>
                        Customer Information
                    </div>
                    <div class="customer-info">
                        <div class="info-item">
                            <div class="info-label"><i class="ri-user-line"></i> Full Name</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="ri-mail-line"></i> Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['email']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="ri-phone-line"></i> Phone</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['phone']); ?></div>
                        </div>
                        <div class="info-item full-width">
                            <div class="info-label"><i class="ri-map-pin-line"></i> Delivery Address</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['address'] . ', ' . $order['city'] . ', ' . $order['state'] . ' ' . $order['zip']); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="card" style="margin-top: 25px;">
                    <div class="card-title">
                        <i class="ri-shopping-bag-line"></i>
                        Order Items (<?php echo count($items); ?>)
                    </div>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th width="8%">#</th>
                                <th width="45%">Product Name</th>
                                <th width="20%">Price</th>
                                <th width="15%">Qty</th>
                                <th width="20%">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $i => $it): ?>
                            <tr>
                                <td><strong><?php echo $i+1; ?></strong></td>
                                <td><?php echo htmlspecialchars($it['name']); ?></td>
                                <td>₦<?php echo number_format($it['price'], 2); ?></td>
                                <td><div class="item-qty"><?php echo (int)$it['quantity']; ?></div></td>
                                <td><strong>₦<?php echo number_format($it['total'], 2); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right Column: Summary & Status -->
            <div>
                <!-- Summary Card -->
                <div class="card">
                    <div class="card-title">
                        <i class="ri-calculator-line"></i>
                        Order Summary
                    </div>
                    <div class="summary-section">
                        <div class="summary-row">
                            <span class="summary-label">Subtotal</span>
                            <span class="summary-value">₦<?php echo number_format($order['subtotal'], 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Tax (5%)</span>
                            <span class="summary-value">₦<?php echo number_format($order['tax'], 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Shipping</span>
                            <span class="summary-value" style="color: #4CAF50; font-weight: 700;">FREE</span>
                        </div>
                        <div class="summary-total">
                            <div class="summary-row">
                                <span class="summary-label">Total Amount</span>
                                <span class="summary-value">₦<?php echo number_format($order['total'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Update -->
                <!-- <div class="card" style="margin-top: 25px; background: white; border-top: 3px solid #FFA500;">
                    <div class="card-title">
                        <i class="ri-refresh-line"></i>
                        Update Status
                    </div>
                    <form method="post" action="orders.php" class="status-form">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                        <div class="form-group">
                            <label class="form-label">Order Status</label>
                            <select name="status" class="form-select">
                                <option value="Pending" <?php echo $order['status']==='Pending'? 'selected':''; ?>>
                                    ⏱️ Pending
                                </option>
                                <option value="Processing" <?php echo $order['status']==='Processing'? 'selected':''; ?>>
                                    ⚙️ Processing
                                </option>
                                <option value="Completed" <?php echo $order['status']==='Completed'? 'selected':''; ?>>
                                    ✓ Completed
                                </option>
                                <option value="Cancelled" <?php echo $order['status']==='Cancelled'? 'selected':''; ?>>
                                    ✕ Cancelled
                                </option>
                            </select>
                        </div>
                        <button type="submit" class="btn-update">
                            <i class="ri-save-line"></i>
                            Update Status
                        </button>
                    </form>
                </div> -->

                <!-- Payment Information -->
                <div class="card" style="margin-top: 25px; background: white; border-top: 3px solid #4CAF50;">
                    <div class="card-title">
                        <i class="ri-bank-card-line"></i>
                        Payment Information
                    </div>
                    <div class="payment-info">
                        <div class="payment-method">
                            <i class="ri-<?php 
                                echo match($order['payment_method']) {
                                    'card' => 'bank-card-line',
                                    'bank' => 'building-line',
                                    'ussd' => 'smartphone-line',
                                    default => 'money-dollar-circle-line'
                                };
                            ?>"></i>
                            <div>
                                <div class="info-label">Method</div>
                                <div class="info-value" style="text-transform: capitalize;">
                                    <?php echo htmlspecialchars($order['payment_method']); ?>
                                </div>
                            </div>
                        </div>
                        <div class="payment-method" style="margin-top: 15px;">
                            <i class="ri-file-info-line"></i>
                            <div class="payment-details-text">
                                <?php echo htmlspecialchars($order['payment_details']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php require_once __DIR__ . '/footer.php';
