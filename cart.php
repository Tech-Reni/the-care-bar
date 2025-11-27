<?php
require_once __DIR__ . "/includes/db.php";
include __DIR__ . "/includes/header.php";

// Get cart data
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$subtotal = 0;
$tax = 0;

// Calculate totals
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Calculate tax (5%)
$tax = $subtotal * 0.05;
$total = $subtotal + $tax;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - The Care Bar</title>
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>assets/css/cart.css">
</head>
<body>
    <main>
        <!-- PAGE HEADER -->
        <div class="container page-header">
            <br>
            <h1>Shopping Cart</h1>
            <br>
        </div>

        <div class="container">
            <div class="cart-layout">
                <!-- CART ITEMS -->
                <div class="cart-items-section">
                    <?php if (!empty($cart_items)): ?>
                        <div class="cart-table">
                            <div class="table-header">
                                <div class="col-product">Product</div>
                                <div class="col-price">Price</div>
                                <div class="col-quantity">Quantity</div>
                                <div class="col-total">Total</div>
                                <div class="col-action">Action</div>
                            </div>

                            <?php foreach ($cart_items as $product_id => $item): ?>
                                <div class="table-row" data-product-id="<?php echo $product_id; ?>">
                                    <!-- Product Info -->
                                    <div class="col-product">
                                        <div class="product-cell">
                                            <img src="<?php echo $BASE_URL . 'uploads/' . htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <div>
                                                <h4><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></h4>
                                                <p class="sku">ID: #<?php echo $product_id; ?></p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Price -->
                                    <div class="col-price">
                                        <p class="price">₦<?php echo number_format($item['price'], 2); ?></p>
                                    </div>

                                    <!-- Quantity Controls -->
                                    <div class="col-quantity">
                                        <div class="quantity-control">
                                            <button class="qty-btn" onclick="updateQuantity(<?php echo $product_id; ?>, <?php echo $item['quantity'] - 1; ?>)">
                                                <i class="ri-subtract-line"></i>
                                            </button>
                                            <span class="qty-value"><?php echo $item['quantity']; ?></span>
                                            <button class="qty-btn" onclick="updateQuantity(<?php echo $product_id; ?>, <?php echo $item['quantity'] + 1; ?>)">
                                                <i class="ri-add-line"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Item Total -->
                                    <div class="col-total">
                                        <p class="item-total">₦<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                    </div>

                                    <!-- Remove Button -->
                                    <div class="col-action">
                                        <button class="remove-btn" onclick="removeItem(<?php echo $product_id; ?>)" title="Remove item">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- CONTINUE SHOPPING -->
                        <div class="cart-actions-top">
                            <a href="<?php echo $BASE_URL; ?>shop.php" class="btn btn-outline">
                                <i class="ri-arrow-left-line"></i> Continue Shopping
                            </a>
                            <button onclick="cart.clear()" class="btn btn-ghost">
                                <i class="ri-delete-bin-line"></i> Clear Cart
                            </button>
                        </div>
                    <?php else: ?>
                        <!-- EMPTY CART -->
                        <div class="empty-cart-message">
                            <i class="ri-shopping-cart-line"></i>
                            <h2>Your Cart is Empty</h2>
                            <p>Start shopping to add products to your cart</p>
                            <a href="<?php echo $BASE_URL; ?>shop.php" class="btn">
                                <i class="ri-shopping-bag-line"></i> Continue Shopping
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- CART SUMMARY -->
                <?php if (!empty($cart_items)): ?>
                    <aside class="cart-summary-section">
                        <div class="summary-card">
                            <h2>Order Summary</h2>

                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span>₦<?php echo number_format($subtotal, 2); ?></span>
                            </div>

                            <div class="summary-row">
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

                            <!-- Promo Code -->
                            <div class="promo-section">
                                <input type="text" id="promoCode" placeholder="Enter promo code" class="promo-input">
                                <button class="btn btn-outline" onclick="applyPromo()">Apply</button>
                            </div>

                            <!-- Checkout Button -->
                            <a href="<?php echo $BASE_URL; ?>checkout.php" class="btn btn-checkout">
                                <i class="ri-bank-card-line"></i> Proceed to Checkout
                            </a>

                            <!-- Trust Badges -->
                            <div class="trust-badges">
                                <div class="badge">
                                    <i class="ri-shield-check-line"></i>
                                    <span>Secure Checkout</span>
                                </div>
                                <div class="badge">
                                    <i class="ri-truck-line"></i>
                                    <span>Fast Delivery</span>
                                </div>
                                <div class="badge">
                                    <i class="ri-refresh-line"></i>
                                    <span>Easy Returns</span>
                                </div>
                            </div>
                        </div>
                    </aside>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        /**
         * Update item quantity with real-time DOM updates
         */
        async function updateQuantity(productId, newQuantity) {
            if (newQuantity <= 0) {
                removeItem(productId);
            } else {
                // Update via cart API
                await cart.updateQuantity(productId, newQuantity);
                
                // Update DOM in real-time
                const row = document.querySelector(`[data-product-id="${productId}"]`);
                if (row) {
                    // Get item price from existing row
                    const priceText = row.querySelector('.col-price .price').textContent;
                    const price = parseFloat(priceText.replace('₦', '').replace(/,/g, ''));
                    
                    // Update quantity display
                    row.querySelector('.qty-value').textContent = newQuantity;
                    
                    // Update item total
                    const itemTotal = price * newQuantity;
                    row.querySelector('.col-total .item-total').textContent = '₦' + itemTotal.toLocaleString('en-US', { minimumFractionDigits: 2 });
                    
                    // Update +/- button quantities
                    row.querySelector('.qty-btn:first-child').onclick = () => updateQuantity(productId, newQuantity - 1);
                    row.querySelector('.qty-btn:last-child').onclick = () => updateQuantity(productId, newQuantity + 1);
                    
                    // Recalculate and update summary
                    updateSummary();
                }
            }
        }

        /**
         * Recalculate order summary totals
         */
        function updateSummary() {
            let subtotal = 0;
            document.querySelectorAll('.table-row').forEach(row => {
                const itemTotalText = row.querySelector('.col-total .item-total').textContent;
                const itemTotal = parseFloat(itemTotalText.replace('₦', '').replace(/,/g, ''));
                subtotal += itemTotal;
            });
            
            const tax = subtotal * 0.10;
            const total = subtotal + tax;
            
            // Update summary display
            document.querySelectorAll('.summary-row')[0].querySelector('span:last-child').textContent = 
                '₦' + subtotal.toLocaleString('en-US', { minimumFractionDigits: 2 });
            document.querySelectorAll('.summary-row')[1].querySelector('span:last-child').textContent = 
                '₦' + tax.toLocaleString('en-US', { minimumFractionDigits: 2 });
            document.querySelectorAll('.summary-row.summary-total')[0].querySelector('span:last-child').textContent = 
                '₦' + total.toLocaleString('en-US', { minimumFractionDigits: 2 });
        }

        /**
         * Remove item from cart
         */
        function removeItem(productId) {
            showConfirm('Remove Item?', 'Are you sure you want to remove this item?', async () => {
                await cart.remove(productId);
                
                // Remove row from DOM
                const row = document.querySelector(`[data-product-id="${productId}"]`);
                if (row) {
                    row.remove();
                    updateSummary();
                    
                    // Check if cart is now empty
                    if (document.querySelectorAll('.table-row').length === 0) {
                        location.reload();
                    }
                }
            });
        }

        /**
         * Apply promo code (placeholder)
         */
        function applyPromo() {
            const code = document.getElementById('promoCode').value.trim();
            if (!code) {
                showError('Error', 'Please enter a promo code');
                return;
            }
            
            // Placeholder - implement actual promo logic
            showInfo('Promo Code', 'Promo code validation will be implemented soon');
        }
    </script>

    <script src="<?php echo $BASE_URL; ?>assets/js/cart.js"></script>
</body>
</html>

<?php include __DIR__ . "/includes/footer.php"; ?>
<?php include __DIR__ . "/includes/modal.php"; ?>
