<?php
require_once __DIR__ . "/includes/db.php";
include __DIR__ . "/includes/header.php";

// Get pagination parameters
$page = validateInt($_GET['page'] ?? 1) ?? 1;
$category_id = validateInt($_GET['category'] ?? null);
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Get products
$products = getAllProducts($per_page, $offset, $category_id);
$total_count = getProductCount($category_id);
$total_pages = ceil($total_count / $per_page);

// Get categories for filter
$all_categories = getCategories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - The Care Bar</title>
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>assets/css/home.css">
</head>
<body>
    <main>
        <!-- SHOP HEADER -->
        <section class="shop-header">
            <div class="container">
                <h1>Our Products</h1>
                <p>Discover our full collection of premium self-care & lifestyle products</p>
            </div>
        </section>

        <!-- SHOP CONTENT -->
        <section class="shop-container">
            <div class="container">
                <div class="shop-layout">
                    <!-- SIDEBAR FILTERS -->
                    <aside class="shop-sidebar">
                        <div class="filter-group">
                            <h3>Categories</h3>
                            <ul>
                                <li><a href="<?php echo $BASE_URL; ?>shop.php" class="<?php echo !$category_id ? 'active' : ''; ?>">
                                    <i class="ri-check-line"></i> All Products
                                </a></li>
                                <?php foreach ($all_categories as $cat): ?>
                                    <li><a href="<?php echo $BASE_URL; ?>shop.php?category=<?php echo $cat['id']; ?>" class="<?php echo $category_id == $cat['id'] ? 'active' : ''; ?>">
                                        <i class="ri-folder-line"></i> <?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <div class="filter-group">
                            <h3>About This Section</h3>
                            <p>Browse our complete catalog of carefully curated self-care products. Each item is selected for quality and authenticity.</p>
                        </div>
                    </aside>

                    <!-- PRODUCTS GRID -->
                    <div class="shop-main">
                        <?php if (!empty($products)): ?>
                            <div class="shop-header-bar">
                                <p class="product-count">Showing <?php echo count($products); ?> of <?php echo $total_count; ?> products</p>
                            </div>

                            <div class="grid">
                                <?php foreach ($products as $product): ?>
                                    <div class="card">
                                        <img src="<?php echo $BASE_URL . 'uploads/' . htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <h3><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                        <p class="category"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized', ENT_QUOTES, 'UTF-8'); ?></p>
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
                            </div>

                            <!-- PAGINATION -->
                            <?php if ($total_pages > 1): ?>
                                <div class="pagination">
                                    <?php if ($page > 1): ?>
                                        <a href="<?php echo $BASE_URL; ?>shop.php?page=<?php echo $page - 1; ?><?php echo $category_id ? '&category=' . $category_id : ''; ?>" class="page-link prev">
                                            <i class="ri-arrow-left-line"></i> Previous
                                        </a>
                                    <?php endif; ?>

                                    <div class="page-numbers">
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <a href="<?php echo $BASE_URL; ?>shop.php?page=<?php echo $i; ?><?php echo $category_id ? '&category=' . $category_id : ''; ?>" 
                                               class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>
                                    </div>

                                    <?php if ($page < $total_pages): ?>
                                        <a href="<?php echo $BASE_URL; ?>shop.php?page=<?php echo $page + 1; ?><?php echo $category_id ? '&category=' . $category_id : ''; ?>" class="page-link next">
                                            Next <i class="ri-arrow-right-line"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="ri-inbox-line"></i>
                                <h2>No Products Found</h2>
                                <p>We couldn't find any products in this category. Try browsing all products or another category.</p>
                                <a href="<?php echo $BASE_URL; ?>shop.php" class="btn">
                                    <i class="ri-refresh-line"></i> View All Products
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
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

    <script src="<?php echo $BASE_URL; ?>assets/js/cart.js"></script>
</body>
</html>

<?php include __DIR__ . "/includes/footer.php"; ?>
<?php include __DIR__ . "/includes/modal.php"; ?>
