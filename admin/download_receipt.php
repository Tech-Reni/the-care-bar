<?php
require_once __DIR__ . '/../includes/db.php';
session_start();

// 1. Validate & Fetch Data
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) { die("Invalid Order ID"); }

// Fetch Order (Using your mysqli $conn)
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) { die("Order not found"); }

// Fetch Items
$stmtItems = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmtItems->bind_param("i", $id);
$stmtItems->execute();
$resultItems = $stmtItems->get_result();
$items = [];
while ($row = $resultItems->fetch_assoc()) {
    $items[] = $row;
}
$stmtItems->close();

// Settings
$companyName = "The Care Bar";
$companyEmail = "support@thecarebar.com";
$currency = "₦";
$logoPath = '../assets/img/logo.png'; 

// Convert Logo to Base64
$logoBase64 = '';
if (file_exists(__DIR__ . '/' . $logoPath)) {
    $type = pathinfo($logoPath, PATHINFO_EXTENSION);
    $data = file_get_contents(__DIR__ . '/' . $logoPath);
    $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?php echo $id; ?></title>
    
    <!-- html2pdf CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        body { 
            background: #525659; /* Dark background like a PDF viewer */
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 40px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* The Receipt Paper */
        #receipt-element {
            width: 800px; /* Fixed width A4-ish */
            min-height: 1000px;
            background: #fff;
            padding: 50px;
            box-sizing: border-box;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
            margin-bottom: 50px;
        }

        /* Overlay to tell user it's downloading */
        #loading-overlay {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 16px;
            z-index: 9999;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* --- Receipt Internal Styles --- */
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #f0f0f0; padding-bottom: 30px; margin-bottom: 40px; }
        .logo-area img { max-height: 70px; }
        .invoice-details { text-align: right; }
        .invoice-details h1 { margin: 0; color: #333; font-size: 28px; text-transform: uppercase; letter-spacing: 2px; }
        .invoice-details p { margin: 5px 0 0; color: #888; font-size: 14px; }
        
        .info-grid { display: flex; justify-content: space-between; margin-bottom: 50px; }
        .info-col { width: 45%; }
        .label { font-size: 11px; text-transform: uppercase; color: #999; letter-spacing: 1px; margin-bottom: 8px; font-weight: 700; }
        .value { font-size: 15px; color: #333; line-height: 1.6; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        th { text-align: left; background: #f8f9fa; padding: 15px; font-size: 12px; text-transform: uppercase; color: #666; font-weight: 600; border-bottom: 2px solid #eee; }
        td { padding: 15px; border-bottom: 1px solid #eee; font-size: 14px; color: #444; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .totals { width: 40%; margin-left: auto; }
        .totals-row { display: flex; justify-content: space-between; padding: 10px 0; font-size: 14px; color: #666; }
        .grand-total { border-top: 2px solid #333; padding-top: 15px; margin-top: 10px; font-weight: 700; font-size: 18px; color: #000; }

        .footer { margin-top: 80px; padding-top: 30px; border-top: 1px solid #f0f0f0; text-align: center; color: #999; font-size: 13px; }
    </style>
</head>
<body>

    <!-- Floating status message -->
    <div id="loading-overlay">
        <span>⚙️ Generating PDF...</span>
    </div>

    <!-- The actual receipt (Visible now!) -->
    <div id="receipt-element">
        
        <div class="header">
            <div class="logo-area">
                <?php if($logoBase64): ?>
                    <img src="<?php echo $logoBase64; ?>" alt="Logo">
                <?php else: ?>
                    <h2><?php echo $companyName; ?></h2>
                <?php endif; ?>
            </div>
            <div class="invoice-details">
                <h1>Receipt</h1>
                <p>#<?php echo $order['order_number'] ?? $order['id']; ?></p>
                <p><?php echo date('F j, Y', strtotime($order['created_at'] ?? 'now')); ?></p>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-col">
                <div class="label">Billed To</div>
                <div class="value">
                    <strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong><br>
                    <?php echo htmlspecialchars($order['email']); ?><br>
                    <?php echo htmlspecialchars($order['phone']); ?>
                </div>
            </div>
            <div class="info-col" style="text-align: right;">
                <div class="label">Delivery Address</div>
                <div class="value">
                    <?php echo htmlspecialchars($order['address']); ?><br>
                    <?php echo htmlspecialchars($order['city'] . ', ' . $order['state'] . ' ' . $order['zip']); ?>
                </div>
                <br>
                <div class="label">Payment Method</div>
                <div class="value" style="text-transform: capitalize;">
                    <?php echo htmlspecialchars($order['payment_method'] ?? 'Card'); ?>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="50%">Item</th>
                    <th width="15%" class="text-center">Qty</th>
                    <th width="15%" class="text-right">Price</th>
                    <th width="20%" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $item): 
                    $price = $item['price'];
                    $qty = $item['quantity'];
                    $total = $price * $qty; // Or use $item['total'] if in DB
                ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($item['product_name'] ?? $item['name'] ?? 'Item'); ?></strong>
                    </td>
                    <td class="text-center"><?php echo $qty; ?></td>
                    <td class="text-right"><?php echo $currency . number_format($price, 2); ?></td>
                    <td class="text-right"><?php echo $currency . number_format($total, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-row">
                <span>Subtotal</span>
                <span><?php echo $currency . number_format($order['subtotal'], 2); ?></span>
            </div>
            <div class="totals-row">
                <span>Tax</span>
                <span><?php echo $currency . number_format($order['tax'] ?? 0, 2); ?></span>
            </div>
            <div class="totals-row">
                <span>Shipping</span>
                <span><?php echo $currency . '0.00'; ?></span>
            </div>
            <div class="totals-row grand-total">
                <span>Total</span>
                <span><?php echo $currency . number_format($order['total'], 2); ?></span>
            </div>
        </div>

        <div class="footer">
            Thank you for shopping with <?php echo $companyName; ?>.<br>
            Need help? Contact us at <?php echo $companyEmail; ?>
        </div>

    </div>

    <script>
        window.onload = function() {
            // Wait 1 second to ensure styles/images are perfectly rendered
            setTimeout(function() {
                const element = document.getElementById('receipt-element');
                const overlay = document.getElementById('loading-overlay');
                
                const opt = {
                    margin:       0,
                    filename:     'Receipt-<?php echo $order['id']; ?>.pdf',
                    image:        { type: 'jpeg', quality: 0.98 },
                    html2canvas:  { scale: 2, useCORS: true, scrollY: 0 },
                    jsPDF:        { unit: 'pt', format: 'a4', orientation: 'portrait' }
                };

                html2pdf().set(opt).from(element).save().then(function() {
                    overlay.innerHTML = "✅ Downloaded!";
                    overlay.style.backgroundColor = "#28a745";
                    // Close tab after 2 seconds
                    setTimeout(function(){ window.close(); }, 2000);
                });
            }, 1000); // 1 second delay
        };
    </script>

</body>
</html>