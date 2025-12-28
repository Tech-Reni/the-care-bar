<?php
// index.php

// 1. ENABLE ERROR REPORTING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. REQUIRE FILES
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

// Get products
$featured_products = getRandomProducts(8);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>The Care Bar - Facials, Oral Care, Accessories & Gift Sets</title>
    <meta name="description" content="The Care Bar is your trusted shop for premium facials, oral care essentials...">

    <!-- FAVICON -->
    <link rel="icon" href="<?php echo $BASE_URL; ?>assets/img/logo.png" type="image/x-icon">

    <!-- CSS LINKS -->
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>assets/css/home.css">

    <!-- FONTS & ICONS -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    
    <!-- SCROLL REVEAL -->
    <script src="https://unpkg.com/scrollreveal"></script>

    <!-- UI STYLES (MATCHING SHOP PAGE) -->
    <style>
        :root {
            --primary-color: #ff3f8e;
            --primary-hover: #ef3884ff;
            --text-dark: #222;
            --text-light: #666;
            --card-hover-shadow: 0 12px 25px rgba(0,0,0,0.12);
        }

        /* HERO TWEAKS */
        .hero { position: relative; overflow: hidden; }

        /* PRODUCT GRID SYSTEM */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 25px;
            padding: 10px 0;
        }

        /* CARD STYLING */
        .product-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #f0f0f0;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--card-hover-shadow);
            border-color: transparent;
        }

        /* IMAGE WRAPPER */
        .card-image-wrapper {
            position: relative;
            height: 240px;
            background: #f8f8f8;
            overflow: hidden;
        }

        .card-image-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .product-card:hover .card-image-wrapper img {
            transform: scale(1.08);
        }

        /* FLOATING ADD BUTTON */
        .quick-add-btn {
            position: absolute;
            bottom: 15px;
            right: 15px;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #fff;
            border: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            color: var(--text-dark);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: 0.3s;
            opacity: 0;
            transform: translateY(20px);
            z-index: 2;
        }

        .product-card:hover .quick-add-btn {
            opacity: 1;
            transform: translateY(0);
        }

        .quick-add-btn:hover {
            background: var(--primary-color);
            color: #fff;
        }

        /* CARD DETAILS */
        .card-details {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .category-tag {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 8px;
        }

        .product-title {
            font-size: 1.05rem;
            margin: 0 0 15px 0;
            line-height: 1.4;
            font-weight: 600;
        }

        .product-title a {
            text-decoration: none;
            color: var(--text-dark);
            transition: color 0.2s;
        }

        .product-title a:hover { color: var(--primary-color); }

        .price-row {
            margin-top: auto; 
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #f5f5f5;
        }

        .price {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--primary-color);
        }

        .view-link {
            text-decoration: none;
            color: var(--text-light);
            font-size: 1.2rem;
            transition: 0.2s;
        }
        .view-link:hover { color: var(--primary-color); transform: translateX(3px); }

        /* MOBILE RESPONSIVENESS */
        @media (max-width: 600px) {
            .product-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                padding: 10px;
            }

            .card-image-wrapper { height: 160px; }
            .card-details { padding: 12px; }
            .product-title { font-size: 0.95rem; }
            .price { font-size: 1rem; }

            .quick-add-btn {
                opacity: 1;
                transform: translateY(0);
                width: 35px;
                height: 35px;
                font-size: 1rem;
                bottom: 10px;
                right: 10px;
                background: rgba(255,255,255,0.95);
                color: var(--primary-color);
            }
        }
    </style>
</head>

<body>
    <main>
        <!-- HERO SECTION -->
        <section class="hero">
            <div class="hero-background">
                <div class="hero-shape hero-shape-1"></div>
                <div class="hero-shape hero-shape-2"></div>
                <div class="hero-shape hero-shape-3"></div>
            </div>
            <div class="hero-inner container">
                <div class="hero-content reveal-left">
                    <h1>Discover premium self-care & lifestyle products</h1>
                    <p>Facials • Oral care • Accessories • Gift sets • Packaging • Importation services</p>

                    <div class="hero-actions">
                        <a class="btn btn-outline" href="<?php echo $BASE_URL; ?>shop.php">Start Shopping</a>
                    </div>
                </div>
                
                <div class="hero-highlight reveal-right">
                    <div class="highlight-card">
                        <i class="ri-leaf-line"></i>
                        <div>
                            <h3>Premium Quality</h3>
                            <p>Curated essentials</p>
                        </div>
                    </div>
                    <div class="highlight-card">
                        <i class="ri-truck-line"></i>
                        <div>
                            <h3>Fast Delivery</h3>
                            <p>Quick & reliable</p>
                        </div>
                    </div>
                    <div class="highlight-card">
                        <i class="ri-shield-check-line"></i>
                        <div>
                            <h3>Secure Shopping</h3>
                            <p>Safe transactions</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <!-- FEATURED PRODUCTS -->
        <section class="container" style=" padding-left: 5px; padding-right: 5px; padding-top: 30px; padding-bottom: 30px;">
            <header class="section-header reveal-top" style="margin-bottom: 40px; display:flex; justify-content:space-between; align-items:center;">
                <h2 style="font-size: 2rem;">Featured Products</h2>
                <a href="<?php echo $BASE_URL; ?>shop.php" class="link-muted" style="text-decoration:none; color:#666; display:flex; align-items:center; gap:5px;">
                    View all <i class="ri-arrow-right-line"></i>
                </a>
            </header>

            <div class="product-grid">
                <?php if (!empty($featured_products)): ?>
                    <?php foreach ($featured_products as $product): ?>
                        <?php 
                            $imgSrc = $BASE_URL . 'uploads/' . htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8');
                            $pLink = $BASE_URL . "product.php?id=" . $product['id'];
                            // Fallback if category name isn't fetched by getRandomProducts
                            $pCat = isset($product['category_name']) ? htmlspecialchars($product['category_name']) : 'Featured';
                        ?>
                        <div class="product-card reveal-item">
                            <div class="card-image-wrapper">
                                <a href="<?php echo $pLink; ?>">
                                    <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                </a>
                                <!-- Quick Add Button -->
                                <button class="quick-add-btn add-to-cart-btn" data-product-id="<?php echo $product['id']; ?>">
                                    <i class="ri-shopping-cart-2-line"></i>
                                </button>
                            </div>
                            <div class="card-details">
                                <span class="category-tag"><?php echo $pCat; ?></span>
                                <h3 class="product-title">
                                    <a href="<?php echo $pLink; ?>">
                                        <?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </h3>
                                <div class="price-row">
                                    <span class="price">₦<?php echo number_format($product['price'], 2); ?></span>
                                    <a href="<?php echo $pLink; ?>" class="view-link">
                                        <i class="ri-arrow-right-line"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center">No products available yet.</p>
                <?php endif; ?>
            </div>
        </section>
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

    <!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

    <script>
        // --- 1. CONFETTI LOGIC ---
        function showConfetti() {
            var duration = 2 * 1000; 
            var animationEnd = Date.now() + duration;
            var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 0 };

            function randomInRange(min, max) { return Math.random() * (max - min) + min; }

            var interval = setInterval(function() {
                var timeLeft = animationEnd - Date.now();
                if (timeLeft <= 0) return clearInterval(interval);
                var particleCount = 50 * (timeLeft / duration);

                confetti(Object.assign({}, defaults, {
                    particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 },
                    colors: ['#ff4081', '#ffd700', '#f50057']
                }));
                confetti(Object.assign({}, defaults, {
                    particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 },
                    colors: ['#ff4081', '#ffd700', '#f50057']
                }));
            }, 250);
        }
        showConfetti(); // Trigger once on load

        // --- 2. SCROLL REVEAL INITIALIZATION ---
        const sr = ScrollReveal({
            origin: 'bottom',
            distance: '40px',
            duration: 900,
            delay: 100,
            reset: false
        });

        sr.reveal('.reveal-left', { origin: 'left' });
        sr.reveal('.reveal-right', { origin: 'right', delay: 200 });
        sr.reveal('.reveal-top', { origin: 'top' });
        sr.reveal('.reveal-item', { interval: 100 }); // Staggered cards
    </script>

    <script src="<?php echo $BASE_URL; ?>assets/js/cart.js"></script>
</body>

</html>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
<?php require_once __DIR__ . "/includes/modal.php"; ?>