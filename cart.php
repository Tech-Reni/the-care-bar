<?php
require_once __DIR__ . "/includes/db.php";
include __DIR__ . "/includes/header.php";

$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="<?= $BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= $BASE_URL ?>assets/css/cart.css">
</head>
<body>
    <main>
        <div class="container page-header">
            <br><h1>Shopping Cart</h1><br>
        </div>

        <div class="container">
            <div class="cart-layout">
                <div class="cart-items-section">
                    <?php if (!empty($cart_items)): ?>
                        <div class="cart-table">
                            <div class="table-header">
                                <div class="col-product">Product</div>
                                <div class="col-price">Price</div>
                                <div class="col-quantity">Quantity</div>
                                <div class="col-total">Total</div>
                                <div class="col-action"></div>
                            </div>

                            <?php foreach ($cart_items as $key => $item): ?>
                                <div class="table-row">
                                    <!-- Product Info -->
                                    <div class="col-product">
                                        <div class="product-cell">
                                            <img src="<?= $BASE_URL ?>uploads/<?= htmlspecialchars($item['image']) ?>" alt="img">
                                            <div>
                                                <h4><?= htmlspecialchars($item['name']) ?></h4>
                                                
                                                <!-- VARIANT DISPLAY -->
                                                <?php if (!empty($item['variant_name'])): ?>
                                                    <span style="display:block; font-size:12px; color:#E91E63; font-weight:600;">
                                                        Option: <?= htmlspecialchars($item['variant_name']) ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <p class="sku">SKU: #<?= $item['id'] ?></p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Price -->
                                    <div class="col-price">
                                        <p class="price">₦<?= number_format($item['price'], 2) ?></p>
                                    </div>

                                    <!-- Quantity -->
                                    <div class="col-quantity">
                                        <div class="quantity-control">
                                            <button class="qty-btn" onclick="updateCartQty('<?= $key ?>', <?= $item['quantity'] - 1 ?>)">-</button>
                                            <span class="qty-value"><?= $item['quantity'] ?></span>
                                            <button class="qty-btn" onclick="updateCartQty('<?= $key ?>', <?= $item['quantity'] + 1 ?>)">+</button>
                                        </div>
                                    </div>

                                    <!-- Total -->
                                    <div class="col-total">
                                        <p class="item-total">₦<?= number_format($item['price'] * $item['quantity'], 2) ?></p>
                                    </div>

                                    <!-- Remove -->
                                    <div class="col-action">
                                        <button class="remove-btn" onclick="removeFromCart('<?= $key ?>')"><i class="ri-delete-bin-line"></i></button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-cart-message">
                            <h2>Your Cart is Empty</h2>
                            <a href="shop.php" class="btn">Go Shopping</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Summary -->
                <?php if (!empty($cart_items)): ?>
                    <aside class="cart-summary-section">
                        <div class="summary-card">
                            <h2>Order Summary</h2>
                            <div class="summary-row summary-total">
                                <span>Total</span>
                                <span>₦<?= number_format($subtotal, 2) ?></span>
                            </div>
                            <a href="checkout.php" class="btn btn-checkout">Proceed to Checkout</a>
                        </div>
                    </aside>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include __DIR__ . "/includes/modal.php"; ?>

    <script>
        // Updated JS to handle "Key" instead of ID
        function updateCartQty(key, qty) {
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('key', key);
            formData.append('quantity', qty);
            
            fetch('api/cart.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(d => { if(d.success) location.reload(); });
        }

        function removeFromCart(key) {
            showConfirm('Remove Item', 'Are you sure you want to remove this item from your cart?', () => {
                const formData = new FormData();
                formData.append('action', 'remove');
                formData.append('key', key);
                
                fetch('api/cart.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(d => { if(d.success) location.reload(); });
            });
        }
    </script>
</body>
</html>
<?php include __DIR__ . "/includes/footer.php"; ?>