<?php
require_once __DIR__ . "/includes/db.php";
include __DIR__ . "/includes/header.php";

// Get cart data
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

if (empty($cart_items)) {
    header("Location: " . $BASE_URL . "cart.php");
    exit;
}

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$tax = $subtotal * 0.05;
$total = $subtotal + $tax;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - The Care Bar</title>
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>assets/css/checkout.css">
</head>
<body>
    <main>
        <div class="container page-header">
            <h1>Checkout</h1>
            <p>Complete your purchase</p>
        </div>

        <div class="container">
            <div class="checkout-layout">
                <!-- CHECKOUT FORM -->
                <div class="checkout-form-section">
                    <form id="checkoutForm" class="checkout-form">
                        <!-- Billing Information -->
                        <div class="form-section">
                            <h2>Billing Information</h2>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="firstName">First Name *</label>
                                    <input type="text" id="firstName" name="firstName" required>
                                </div>
                                <div class="form-group">
                                    <label for="lastName">Last Name *</label>
                                    <input type="text" id="lastName" name="lastName" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" required>
                            </div>

                            <div class="form-group">
                                <label for="phone">Phone Number *</label>
                                <input type="tel" id="phone" name="phone" required>
                            </div>

                            <div class="form-group">
                                <label for="address">Street Address *</label>
                                <input type="text" id="address" name="address" required>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="city">City *</label>
                                    <input type="text" id="city" name="city" required>
                                </div>
                                <div class="form-group">
                                    <label for="state">State *</label>
                                    <input type="text" id="state" name="state" required>
                                </div>
                                <div class="form-group">
                                    <label for="zip">ZIP Code *</label>
                                    <input type="text" id="zip" name="zip" required>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Information -->
                        <div class="form-section">
                            <h2>Payment Information</h2>
                            
                            <div class="payment-methods">
                                <label class="payment-method">
                                    <input type="radio" name="paymentMethod" value="card" checked>
                                    <span><i class="ri-bank-card-line"></i> Credit/Debit Card</span>
                                </label>
                                <label class="payment-method">
                                    <input type="radio" name="paymentMethod" value="bank">
                                    <span><i class="ri-building-line"></i> Bank Transfer</span>
                                </label>
                                <label class="payment-method">
                                    <input type="radio" name="paymentMethod" value="ussd">
                                    <span><i class="ri-smartphone-line"></i> USSD</span>
                                </label>
                            </div>

                            <div id="cardPayment" class="payment-details">
                                <div class="form-group">
                                    <label for="cardName">Cardholder Name *</label>
                                    <input type="text" id="cardName" name="cardName">
                                </div>

                                <div class="form-group">
                                    <label for="cardNumber">Card Number *</label>
                                    <input type="text" id="cardNumber" name="cardNumber" placeholder="0000 0000 0000 0000">
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="cardExp">Expiry Date *</label>
                                        <input type="text" id="cardExp" name="cardExp" placeholder="MM/YY">
                                    </div>
                                    <div class="form-group">
                                        <label for="cardCVC">CVC *</label>
                                        <input type="text" id="cardCVC" name="cardCVC" placeholder="000">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Terms -->
                        <div class="form-section">
                            <label class="checkbox-group">
                                <input type="checkbox" required>
                                <span>I agree to the <a href="#">Terms & Conditions</a> and <a href="#">Privacy Policy</a></span>
                            </label>
                        </div>
                    </form>
                </div>

                <!-- ORDER SUMMARY -->
                <aside class="checkout-summary-section">
                    <div class="summary-card">
                        <h2>Order Summary</h2>

                        <div class="order-items">
                            <?php foreach ($cart_items as $product_id => $item): ?>
                                <div class="order-item">
                                    <div class="item-info">
                                        <p class="item-name"><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p class="item-qty">Qty: <?php echo $item['quantity']; ?></p>
                                    </div>
                                    <p class="item-price">₦<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="summary-divider"></div>

                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>₦<?php echo number_format($subtotal, 2); ?></span>
                        </div>

                        <div class="summary-item">
                            <span>Tax (5%)</span>
                            <span>₦<?php echo number_format($tax, 2); ?></span>
                        </div>

                        <div class="summary-row">
                            <span>Shipping</span>
                            <span class="free">FREE</span>
                        </div>

                        <div class="summary-divider"></div>

                        <div class="summary-row summary-total">
                            <span>Total</span>
                            <span>₦<?php echo number_format($total, 2); ?></span>
                        </div>

                        <button form="checkoutForm" type="submit" class="btn btn-checkout">
                            <i class="ri-check-line"></i> Complete Purchase
                        </button>

                        <a href="<?php echo $BASE_URL; ?>cart.php" class="btn btn-outline">
                            <i class="ri-arrow-left-line"></i> Back to Cart
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    <script>
        // Embed cart items for client-side submission
        const CART_ITEMS = <?php echo json_encode($cart_items); ?>;

        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = e.target;

            // Gather form data
            const formData = new FormData(form);
            formData.append('action', 'create');
            formData.append('items', JSON.stringify(CART_ITEMS));
            formData.append('subtotal', '<?php echo $subtotal; ?>');
            formData.append('tax', '<?php echo $tax; ?>');
            formData.append('shipping', '0');
            formData.append('total', '<?php echo $total; ?>');
            // Add payment details summary (do NOT store sensitive full card data in production)
            const paymentDetails = {
                cardName: formData.get('cardName') || '',
                cardNumberLast4: (formData.get('cardNumber') || '').toString().slice(-4),
                cardExp: formData.get('cardExp') || ''
            };
            formData.append('paymentDetails', JSON.stringify(paymentDetails));

            showInfo('Processing', 'Placing your order...');

            // Send to orders API
            fetch('<?php echo $BASE_URL; ?>api/orders.php', {
                method: 'POST',
                body: formData
            }).then(r => r.json()).then(async data => {
                if (data.success) {
                    showSuccess('Order Placed!', 'Thank you. Your order has been received.');

                    // Clear cart on server
                    const clearForm = new FormData();
                    clearForm.append('action', 'clear');
                    await fetch('<?php echo $BASE_URL; ?>api/cart.php', { method: 'POST', body: clearForm });
                    sessionStorage.removeItem('cart');
                    setTimeout(() => { window.location.href = '<?php echo $BASE_URL; ?>index.php'; }, 1200);
                } else {
                    // Show detailed debug info for debugging
                    const apiMsg = data.message || 'Failed to create order';
                    const dbErr = data.db_error ? '\n\nDatabase error: ' + data.db_error : '';
                    const debug = data.debug ? '\n\nDebug: ' + JSON.stringify(data.debug, null, 2) : '';
                    const post = data.post ? '\n\nPost: ' + JSON.stringify(data.post, null, 2) : '';
                    console.error('Order create failed:', data);
                    showError('Error', apiMsg + dbErr + debug + post);
                }
            }).catch(err => {
                console.error('Fetch error while creating order:', err);
                showError('Error', 'Failed to place order: ' + (err && err.message ? err.message : err));
            });
        });
    </script>

    <script src="<?php echo $BASE_URL; ?>assets/js/cart.js"></script>
</body>
</html>

<?php include __DIR__ . "/includes/footer.php"; ?>
<?php include __DIR__ . "/includes/modal.php"; ?>
