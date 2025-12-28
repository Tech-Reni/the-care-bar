<?php
require_once __DIR__ . "/includes/db.php";
include __DIR__ . "/includes/header.php";

$product_id = validateInt($_GET['id'] ?? null);
if (!$product_id) {
    header("Location: shop.php");
    exit;
}

$product = getProductById($product_id);
if (!$product) {
    header("Location: shop.php");
    exit;
}

// Related Products
$related_products = [];
if ($product['category_id']) {
    $related_products = getAllProducts(4, 0, $product['category_id']);
    $related_products = array_filter($related_products, fn($p) => $p['id'] != $product_id);
    $related_products = array_slice($related_products, 0, 3);
}

// 1. Fetch Extra Gallery Images
$stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$gallery = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 2. Fetch Variants
$stmtV = $conn->prepare("SELECT id, variant_name, price, stock_quantity FROM product_variants WHERE product_id = ?");
$stmtV->bind_param("i", $product_id);
$stmtV->execute();
$variants = $stmtV->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtV->close();

$stock = $product['stock_quantity'] ?? 0;
$is_out_of_stock = $stock <= 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - The Care Bar</title>
    <link rel="stylesheet" href="<?= $BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= $BASE_URL ?>assets/css/product.css">
    <style>
        /* Gallery Styles */
        .gallery-container {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 5px;
        }

        .gallery-thumb {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            cursor: pointer;
            object-fit: cover;
            border: 2px solid transparent;
            transition: 0.2s;
            background: #f9f9f9;
        }

        .gallery-thumb.active,
        .gallery-thumb:hover {
            border-color: #E91E63;
        }

        /* Variant Styles */
        .variant-section {
            margin: 20px 0;
        }

        .variant-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            color: #555;
        }

        .variant-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .variant-pill {
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 20px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            user-select: none;
            background: #fff;
        }

        .variant-pill:hover {
            border-color: #E91E63;
            color: #E91E63;
        }

        .variant-pill.selected {
            background: #E91E63;
            color: white;
            border-color: #E91E63;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(233, 30, 99, 0.3);
        }

        .variant-pill.out-of-stock {
            background: #f5f5f5;
            color: #999;
            cursor: not-allowed;
            opacity: 0.6;
        }
    </style>
</head>

<body>
    <main>
        <br>
        <div class="container breadcrumb-bar">
            <a href="<?php echo $BASE_URL; ?>index.php"><i class="ri-home-4-line"></i> Home</a>
            <span><i class="ri-arrow-right-s-line"></i></span>
            <a href="<?php echo $BASE_URL; ?>shop.php">Shop</a>
            <span><i class="ri-arrow-right-s-line"></i></span>
            <span><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <br>

        <section class="container product-detail">
            <div class="product-grid">
                <!-- LEFT: IMAGES -->
                <div class="product-image-section">
                    <div class="product-image-main">
                        <img id="mainImage" src="<?= $BASE_URL ?>uploads/<?= htmlspecialchars($product['image']) ?>" alt="Main Product">
                    </div>

                    <!-- Gallery Thumbs -->
                    <div class="gallery-container">
                        <!-- Original -->
                        <img src="<?= $BASE_URL ?>uploads/<?= htmlspecialchars($product['image']) ?>" class="gallery-thumb active" onclick="swapImage(this)">
                        <!-- Extras -->
                        <?php foreach ($gallery as $img): ?>
                            <img src="<?= $BASE_URL ?>uploads/<?= htmlspecialchars($img['image_path']) ?>" class="gallery-thumb" onclick="swapImage(this)">
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- RIGHT: INFO -->
                <div class="product-info-section">
                    <p class="category-badge"><?= htmlspecialchars($product['category_name'] ?? 'General') ?></p>
                    <h1><?= htmlspecialchars($product['name']) ?></h1>

                    <!-- Rating (Optional) -->
                    <div class="rating">
                        <div class="stars">
                            <i class="ri-star-fill"></i>
                            <i class="ri-star-fill"></i>
                            <i class="ri-star-fill"></i>
                            <i class="ri-star-fill"></i>
                            <i class="ri-star-fill"></i>
                        </div>
                        <span class="rating-text">(<?php echo (int)($product['review_no'] ?? 0); ?> reviews)</span>
                        <button id="openRating" class="btn btn-sm btn-pink" style="border-radius:12px;">
                            <i class="ri-heart-3-line" style="font-size:14px;color:#fff;"></i>
                            Rate
                        </button>
                    </div>

                    <!-- Dynamic Price -->
                    <div class="product-price">
                        <h2 class="price" id="displayPrice">₦<?= number_format($product['price'], 2) ?></h2>
                        <?php 
                        $stock = $product['stock_quantity'] ?? 0;
                        $is_out_of_stock = $stock <= 0;
                        ?>

                        <p class="availability" style="color: <?php echo $is_out_of_stock ? 'var(--error, red)' : 'var(--success)'; ?>;">
                            <?php if ($is_out_of_stock): ?>
                                <i class="ri-close-circle-fill"></i> Out of Stock
                            <?php else: ?>
                                <i class="ri-check-double-fill"></i> In Stock (<?= $stock ?> left)
                            <?php endif; ?>
                        </p>
                    </div>

                    <!-- Variants Selector -->
                   <?php if (!empty($variants)): ?>
                        <div class="variant-section">
                            <span class="variant-title">Choose Option:</span>
                            <div class="variant-list">
                                
                                <!-- 1. MANUALLY ADD THE "STANDARD" (BASE) OPTION -->
                                <!-- This uses the Product's original Base Price and has an empty ID -->
                                <div class="variant-pill selected" 
                                     data-id="" 
                                     data-price="<?= $product['price'] ?>"
                                     data-stock="<?= $product['stock_quantity'] ?>"
                                     onclick="selectVariant(this)">
                                    Standard
                                </div>

                                <!-- 2. LOOP THROUGH DATABASE VARIANTS -->
                                <?php foreach($variants as $var): ?>
                                    <div class="variant-pill <?= $var['stock_quantity'] <= 0 ? 'out-of-stock' : '' ?>" 
                                         data-id="<?= $var['id'] ?>" 
                                         data-price="<?= $var['price'] ?>"
                                         data-stock="<?= $var['stock_quantity'] ?>"
                                         onclick="selectVariant(this)">
                                        <?= htmlspecialchars($var['variant_name']) ?> <?= $var['stock_quantity'] <= 0 ? '(Out of Stock)' : '' ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Hidden input starts empty (Standard selected by default) -->
                            <input type="hidden" id="selectedVariantId" value="">
                        </div>
                    <?php else: ?>
                        <input type="hidden" id="selectedVariantId" value="">
                    <?php endif; ?>

                    <div class="product-description">
                        <h3>Description</h3>
                        <div><?= $product['description'] ?></div>
                    </div>

                    <div class="product-actions">
                        <div class="quantity-selector">
                            <button id="decreaseQty" class="qty-btn"><i class="ri-subtract-line"></i></button>
                            <input type="number" id="quantity" value="1" min="1" max="<?= $stock ?>">
                            <button id="increaseQty" class="qty-btn"><i class="ri-add-line"></i></button>
                        </div>

                        <button id="addToCartBtn" class="btn btn-large" <?= $is_out_of_stock ? 'disabled' : '' ?>>
                            <i class="ri-shopping-cart-line"></i> Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <br>

        <!-- RELATED PRODUCTS -->
        <?php if (!empty($related_products)): ?>
            <section class="container">
                <header class="section-header">
                    <h2>Related Products</h2>
                </header>

                <div class="grid">
                    <?php foreach ($related_products as $related): ?>
                        <div class="card">
                            <img src="<?php echo $BASE_URL . 'uploads/' .  htmlspecialchars($related['image'], ENT_QUOTES, 'UTF-8'); ?>"
                                alt="<?php echo htmlspecialchars($related['name'], ENT_QUOTES, 'UTF-8'); ?>">
                            <h3><?php echo htmlspecialchars($related['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <!-- <p class="category"><?php echo htmlspecialchars($related['category_name'] ?? 'Uncategorized', ENT_QUOTES, 'UTF-8'); ?></p> -->
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

    <?php include __DIR__ . "/includes/modal.php"; ?>

    <script>
        // 1. IMAGE SWAPPER
        function swapImage(img) {
            document.getElementById('mainImage').src = img.src;
            document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
            img.classList.add('active');
        }

        // 2. VARIANT SELECTOR
        function selectVariant(el) {
            if (el.classList.contains('out-of-stock')) return; // Prevent selecting out of stock

            // Visual Update
            document.querySelectorAll('.variant-pill').forEach(p => p.classList.remove('selected'));
            el.classList.add('selected');

            // Data Update
            const newPrice = parseFloat(el.getAttribute('data-price'));
            const varId = el.getAttribute('data-id');
            const varStock = parseInt(el.getAttribute('data-stock'));

            document.getElementById('displayPrice').innerText = '₦' + newPrice.toLocaleString('en-US', {
                minimumFractionDigits: 2
            });
            document.getElementById('selectedVariantId').value = varId;

            // Update availability display
            const availabilityEl = document.querySelector('.availability');
            const addToCartBtn = document.getElementById('addToCartBtn');
            if (varStock <= 0) {
                availabilityEl.innerHTML = '<i class="ri-close-circle-fill"></i> Out of Stock';
                availabilityEl.style.color = 'var(--error, red)';
                addToCartBtn.disabled = true;
            } else {
                availabilityEl.innerHTML = '<i class="ri-check-double-fill"></i> In Stock (' + varStock + ' left)';
                availabilityEl.style.color = 'var(--success)';
                addToCartBtn.disabled = false;
            }

            // Update max stock for quantity
            window.currentMaxStock = varStock;
            // Reset quantity if exceeds new max
            const qtyInput = document.getElementById('quantity');
            qtyInput.max = varStock;
            if (parseInt(qtyInput.value) > varStock) {
                qtyInput.value = varStock > 0 ? 1 : 0;
            }
        }

        // 3. QUANTITY LOGIC
        const qtyInput = document.getElementById('quantity');
        window.currentMaxStock = <?= $stock ?>; // Default to product stock
        document.getElementById('decreaseQty').addEventListener('click', () => {
            if (qtyInput.value > 1) qtyInput.value--;
        });
        document.getElementById('increaseQty').addEventListener('click', () => {
            const currentQty = parseInt(qtyInput.value);
            if (currentQty >= window.currentMaxStock) {
                showInfo('Stock Limit', 'Only ' + window.currentMaxStock + ' items available in stock.');
                return;
            }
            qtyInput.value = currentQty + 1;
        });

        // 4. ADD TO CART (Direct Fetch)
        document.getElementById('addToCartBtn').addEventListener('click', function() {
            const qty = qtyInput.value;
            const varId = document.getElementById('selectedVariantId').value;
            const pid = <?= $product['id'] ?>;

            // Prepare Data
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', pid);
            formData.append('quantity', qty);
            formData.append('variant_id', varId);

            // Send to Backend
            fetch('<?= $BASE_URL ?>api/cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showSuccess('Added', 'Item added to cart!');
                        // Update cart count without reload
                        const cartCountEl = document.getElementById('cartCount');
                        if (cartCountEl) {
                            cartCountEl.textContent = data.cart_count;
                        }
                    } else {
                        showError('Error', data.message || 'Failed to add item');
                    }
                })
                .catch(err => console.error(err));
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