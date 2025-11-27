<?php
require_once __DIR__ . "/includes/db.php";
include __DIR__ . "/includes/header.php";

// Get product ID from URL
$product_id = validateInt($_GET['id'] ?? null);

if (!$product_id) {
    header("Location: " . $BASE_URL . "shop.php");
    exit;
}

// Get product details
$product = getProductById($product_id);

if (!$product) {
    header("Location: " . $BASE_URL . "shop.php");
    exit;
}

// Get related products (same category)
$related_products = [];
if ($product['category_id']) {
    $related_products = getAllProducts(4, 0, $product['category_id']);
    // Remove current product from related
    $related_products = array_filter($related_products, function($p) use ($product_id) {
        return $p['id'] != $product_id;
    });
    $related_products = array_slice($related_products, 0, 3);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?> - The Care Bar</title>
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>assets/css/product.css">
</head>
<body>
    <main>
        <br>
        <!-- BREADCRUMB -->
        <div class="container breadcrumb-bar">
            <a href="<?php echo $BASE_URL; ?>index.php"><i class="ri-home-4-line"></i> Home</a>
            <span><i class="ri-arrow-right-s-line"></i></span>
            <a href="<?php echo $BASE_URL; ?>shop.php">Shop</a>
            <span><i class="ri-arrow-right-s-line"></i></span>
            <span><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <br>

        <!-- PRODUCT DETAILS -->
        <section class="container product-detail">
            <div class="product-grid">
                <!-- Product Image -->
                <div class="product-image-section">
                    <div class="product-image-main">
                        <img id="mainImage" src="<?php echo $BASE_URL . 'uploads/' . htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8'); ?>" 
                             alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>

                <!-- Product Info -->
                <div class="product-info-section">
                    <div class="product-header">
                        <p class="category-badge"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized', ENT_QUOTES, 'UTF-8'); ?></p>
                        <h1><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
                        
                        <!-- Rating (Optional) -->
                        <div class="rating">
                            <div class="stars">
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-half-line"></i>
                            </div>
                            <span class="rating-text">(<?php echo (int)($product['review_no'] ?? 0); ?> reviews)</span>
                            <button id="openRating" class="btn btn-sm btn-pink" style="border-radius:12px;">
                                <i class="ri-heart-3-line" style="font-size:14px;color:#fff;"></i>
                                Rate
                            </button>
                        </div>
                    </div>

                    <!-- Price -->
                    <div class="product-price">
                        <h2 class="price">₦<?php echo number_format($product['price'], 2); ?></h2>
                        <p class="availability"><i class="ri-check-double-fill" style="color: var(--success);"></i> In Stock</p>
                    </div>

                    <!-- Description -->
                    <div class="product-description">
                        <h3>Description</h3>
                        <div><?php echo $product['description'] ?? ''; ?></div>
                    </div>

                    <!-- Add to Cart -->
                    <div class="product-actions">
                        <div class="quantity-selector">
                            <button id="decreaseQty" class="qty-btn">
                                <i class="ri-subtract-line"></i>
                            </button>
                            <input type="number" id="quantity" value="1" min="1" max="999">
                            <button id="increaseQty" class="qty-btn">
                                <i class="ri-add-line"></i>
                            </button>
                        </div>

                        <button id="addToCartBtn" class="btn btn-large add-to-cart-btn" data-product-id="<?php echo $product['id']; ?>">
                            <i class="ri-shopping-cart-line"></i> Add to Cart
                        </button>
                    </div>

                    <!-- Product Meta -->
                    <div class="product-meta">
                        <div class="meta-item">
                            <i class="ri-truck-line"></i>
                            <div>
                                <strong>Free Shipping</strong>
                                <p>On orders over ₦10,000</p>
                            </div>
                        </div>
                        <div class="meta-item">
                            <i class="ri-shield-check-line"></i>
                            <div>
                                <strong>Authentic</strong>
                                <p>100% Genuine Products</p>
                            </div>
                        </div>
                        <div class="meta-item">
                            <i class="ri-refresh-line"></i>
                            <div>
                                <strong>Easy Returns</strong>
                                <p>30-day return policy</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- RELATED PRODUCTS -->
        <?php if (!empty($related_products)): ?>
            <section class="container">
                <header class="section-header">
                    <h2>Related Products</h2>
                </header>

                <div class="grid">
                    <?php foreach ($related_products as $related): ?>
                        <div class="card">
                            <img src="<?php echo htmlspecialchars($related['image'], ENT_QUOTES, 'UTF-8'); ?>" 
                                 alt="<?php echo htmlspecialchars($related['name'], ENT_QUOTES, 'UTF-8'); ?>">
                            <h3><?php echo htmlspecialchars($related['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="category"><?php echo htmlspecialchars($related['category_name'] ?? 'Uncategorized', ENT_QUOTES, 'UTF-8'); ?></p>
                            <p class="price">₦<?php echo number_format($related['price'], 2); ?></p>

                            <div class="card-actions">
                                <button class="btn btn-block add-to-cart-btn" data-product-id="<?php echo $related['id']; ?>">
                                    <i class="ri-shopping-cart-line"></i> Add to Cart
                                </button>
                                <a href="<?php echo $BASE_URL; ?>product.php?id=<?php echo $related['id']; ?>" class="btn btn-outline btn-block">
                                    <i class="ri-eye-line"></i> View
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <!-- CART PANEL -->
    <div id="cart-panel" class="cart-panel hidden">
        <div class="cart-header">
            <h3>Your Cart</h3>
            <button id="close-cart-3" class="close-cart">
                <i class="ri-close-line"></i>
            </button>
        </div>

        <div id="cart-items" class="cart-items">
            <p class="empty-cart">Your cart is empty</p>
        </div>

        <div class="cart-footer">
            <div class="cart-total">
                <small>Subtotal</small>
                <strong id="cart-total">₦0</strong>
            </div>

            <div class="cart-actions">
                <a href="<?php echo $BASE_URL; ?>cart.php" class="btn">
                    <i class="ri-shopping-cart-line"></i> View Cart
                </a>
                <a href="<?php echo $BASE_URL; ?>checkout.php" id="checkout-btn" class="btn">
                    <i class="ri-bank-card-line"></i> Checkout
                </a>
                <button id="clear-cart" class="btn btn-ghost">
                    <i class="ri-delete-bin-line"></i> Clear
                </button>
            </div>
        </div>
    </div>

    <script>
        // Quantity controls
        document.getElementById('decreaseQty').addEventListener('click', () => {
            const qty = document.getElementById('quantity');
            if (qty.value > 1) qty.value--;
        });

        document.getElementById('increaseQty').addEventListener('click', () => {
            document.getElementById('quantity').value++;
        });

        // Add to cart with quantity
        document.getElementById('addToCartBtn').addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const quantity = parseInt(document.getElementById('quantity').value);
            cart.add(parseInt(productId), quantity);
        });

        // Rating modal logic
        (function(){
            // Create modal HTML
            const ratingModalHtml = `
                <div id="ratingModal" class="rating-modal" style="display:none; position:fixed; inset:0; z-index:9999; align-items:center; justify-content:center;">
                    <div class="rating-modal-backdrop"></div>
                    <div class="rating-modal-panel">
                        <h3 style="margin-top:0;">Rate this product</h3>
                        <div id="ratingStars" style="display:flex; gap:8px; justify-content:center; margin:18px 0;">
                            <button data-value="1" class="star-btn">☆</button>
                            <button data-value="2" class="star-btn">☆</button>
                            <button data-value="3" class="star-btn">☆</button>
                            <button data-value="4" class="star-btn">☆</button>
                            <button data-value="5" class="star-btn">☆</button>
                        </div>
                        <div style="display:flex; gap:8px; justify-content:flex-end;">
                            <button id="cancelRating" class="btn btn-outline">Cancel</button>
                            <button id="submitRating" class="btn btn-primary">Submit</button>
                        </div>
                    </div>
                </div>`;

            document.body.insertAdjacentHTML('beforeend', ratingModalHtml);
            const ratingModal = document.getElementById('ratingModal');
            const starButtons = ratingModal.querySelectorAll('.star-btn');
            let selectedRating = 5;

            function highlightStars(value) {
                starButtons.forEach(btn => {
                    const v = parseInt(btn.getAttribute('data-value'));
                    if (v <= value) {
                        btn.textContent = '★';
                        btn.classList.add('filled');
                    } else {
                        btn.textContent = '☆';
                        btn.classList.remove('filled');
                    }
                });
            }

            // Open modal
            const openBtn = document.getElementById('openRating');
            if (openBtn) openBtn.addEventListener('click', () => {
                ratingModal.style.display = 'flex';
                highlightStars(selectedRating);
            });

            // Star selection
            starButtons.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    selectedRating = parseInt(btn.getAttribute('data-value'));
                    highlightStars(selectedRating);
                });
            });

            // Cancel
            ratingModal.querySelector('#cancelRating').addEventListener('click', () => {
                ratingModal.style.display = 'none';
            });

            // Submit
            ratingModal.querySelector('#submitRating').addEventListener('click', async () => {
                const productId = <?php echo (int)$product['id']; ?>;
                try {
                    const formData = new FormData();
                    formData.append('action', 'add');
                    formData.append('product_id', productId);
                    formData.append('rating', selectedRating);

                    const res = await fetch('<?php echo $BASE_URL; ?>api/review.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    if (data.success) {
                        // update UI count
                        const txt = document.querySelector('.rating .rating-text');
                        if (txt) txt.textContent = '(' + (data.review_no || 0) + ' reviews)';
                        showSuccess('Thanks!', data.message || 'Rating submitted');
                    } else {
                        showError('Error', data.message || 'Failed to submit rating');
                    }
                } catch (err) {
                    console.error(err);
                    showError('Error', 'Failed to submit rating');
                } finally {
                    ratingModal.style.display = 'none';
                }
            });
        })();
    </script>

    <script src="<?php echo $BASE_URL; ?>assets/js/cart.js"></script>
</body>
</html>

<?php include __DIR__ . "/includes/footer.php"; ?>
<?php include __DIR__ . "/includes/modal.php"; ?>
