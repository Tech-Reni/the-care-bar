<?php
require_once __DIR__ . "/includes/db.php";
include __DIR__ . "/includes/header.php";

// Get 5 random featured products
$featured_products = getRandomProducts(5);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Care Bar - Home</title>
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>assets/css/home.css">


    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
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
            <div class="hero-inner">
                <div class="hero-content">
                    <h1>Discover premium self-care & lifestyle products</h1>
                    <p>Facials • Oral care • Accessories • Gift sets • Packaging • Importation services</p>

                    <div class="hero-actions">
                        <a class="btn btn-outline" href="<?php echo $BASE_URL; ?>shop.php">Start Shopping</a>
                        <!-- <button class="btn btn-outline" id="view-featured">Featured</button> -->
                    </div>
                </div>
                <div class="hero-highlight">
                    <div class="highlight-card">
                        <i class="ri-leaf-line"></i>
                        <h3>Premium Quality</h3>
                        <p>Curated self-care essentials</p>
                    </div>
                    <div class="highlight-card">
                        <i class="ri-truck-line"></i>
                        <h3>Fast Delivery</h3>
                        <p>Quick & reliable shipping</p>
                    </div>
                    <div class="highlight-card">
                        <i class="ri-shield-check-line"></i>
                        <h3>Secure Shopping</h3>
                        <p>Safe & trusted transactions</p>
                    </div>
                </div>
            </div>
        </section>


        <!-- FEATURED PRODUCTS -->
        <section class="container">
            <header class="section-header">
                <h2>Featured Products</h2>
                <a href="<?php echo $BASE_URL; ?>shop.php" class="link-muted">View all <i class="ri-arrow-right-line"></i></a>
            </header>

            <div class="grid">
                <?php if (!empty($featured_products)): ?>
                    <?php foreach ($featured_products as $product): ?>
                        <div class="card">
                            <img src="<?php echo $BASE_URL . 'uploads/' . htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>">
                            <h3><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="price">₦<?php echo number_format($product['price'], 2); ?></p>

                            <div class="card-actions">
                                <button class="btn btn-block add-to-cart-btn" data-product-id="<?php echo $product['id']; ?>">
                                    <i class="ri-shopping-cart-line"></i> Add to Cart
                                </button>
                                <a href="<?php echo $BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline btn-block">
                                    <i class="ri-eye-line"></i> View
                                </a>
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

    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

    <script>
        /**
         * Function to trigger the confetti
         * You can call showConfetti() from anywhere in your code now.
         */
        function showConfetti() {
            var duration = 3 * 1000; // 3 Seconds
            var animationEnd = Date.now() + duration;
            var defaults = {
                startVelocity: 30,
                spread: 360,
                ticks: 60,
                zIndex: 0
            };

            function randomInRange(min, max) {
                return Math.random() * (max - min) + min;
            }

            var interval = setInterval(function() {
                var timeLeft = animationEnd - Date.now();

                if (timeLeft <= 0) {
                    return clearInterval(interval);
                }

                var particleCount = 50 * (timeLeft / duration);

                // Random bursts from left and right
                // colors: Pink, Gold, and a Darker Pink to match your theme
                confetti(Object.assign({}, defaults, {
                    particleCount,
                    origin: {
                        x: randomInRange(0.1, 0.3),
                        y: Math.random() - 0.2
                    },
                    colors: ['#ff4081', '#ffd700', '#f50057']
                }));
                confetti(Object.assign({}, defaults, {
                    particleCount,
                    origin: {
                        x: randomInRange(0.7, 0.9),
                        y: Math.random() - 0.2
                    },
                    colors: ['#ff4081', '#ffd700', '#f50057']
                }));
            }, 250);
        }

        showConfetti();
        showConfetti();
        showConfetti();
    </script>

    <script src="<?php echo $BASE_URL; ?>assets/js/cart.js"></script>
</body>

</html>

<?php include __DIR__ . "/includes/footer.php"; ?>
<?php include __DIR__ . "/includes/modal.php"; ?>