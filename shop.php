<?php
require_once __DIR__ . "/includes/db.php";

// =========================================================
// 1. SUPER AJAX SEARCH LOGIC
// =========================================================
if (isset($_GET['ajax_search'])) {
    $search = trim($_GET['ajax_search']);
    $results = [];

    // Define Base URL dynamically
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $ajax_base_url = (strpos($host, 'localhost') !== false) ? '/TheCareBar/' : "{$protocol}://{$host}/";

    if (!empty($search)) {
        $sql = "SELECT p.id, p.name, p.price, p.image, c.name as category_name,
                (SELECT GROUP_CONCAT(variant_name SEPARATOR ' ') FROM product_variants WHERE product_id = p.id) as variant_names
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.stock_quantity > 0"; 
        
        $all_products = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

        $matches = [];
        $searchLower = strtolower($search);
        $searchMeta = metaphone($search);

        foreach ($all_products as $p) {
            $score = 1000;
            $fullName = strtolower($p['name']);
            $fullText = $fullName . ' ' . strtolower($p['category_name'] ?? '') . ' ' . strtolower($p['variant_names'] ?? '');

            if (strpos($fullName, $searchLower) !== false) {
                $score = 0; 
            } elseif (strpos($fullText, $searchLower) !== false) {
                $score = 10; 
            } else {
                $words = explode(' ', preg_replace('/[^a-z0-9 ]/', ' ', $fullText));
                foreach ($words as $word) {
                    if (empty($word)) continue;
                    if (metaphone($word) == $searchMeta) $score = min($score, 20);
                    $lev = levenshtein($searchLower, $word);
                    if ($lev <= 2 && strlen($searchLower) > 2) $score = min($score, 30 + $lev);
                }
            }

            if ($score < 1000) {
                $p['relevance'] = $score;
                $matches[] = $p;
            }
        }

        usort($matches, function ($a, $b) { return $a['relevance'] <=> $b['relevance']; });
        $results = $matches;
    }

    // OUTPUT HTML (AJAX RESPONSES)
    if (count($results) > 0) {
        echo '<div class="shop-header-bar"><p class="product-count">Found '.count($results).' results</p></div>';
        echo '<div class="product-grid">';
        foreach ($results as $product) {
            $imgSrc = $ajax_base_url . 'uploads/' . htmlspecialchars($product['image']);
            $pName = htmlspecialchars($product['name']);
            $pCat = htmlspecialchars($product['category_name'] ?? 'General');
            $pPrice = number_format($product['price'], 2);
            $pId = $product['id'];
            $pLink = $ajax_base_url . "product.php?id=" . $pId;

            echo "
            <div class='product-card reveal-item'>
                <div class='card-image-wrapper'>
                    <a href='$pLink'><img src='$imgSrc' loading='lazy' alt='$pName'></a>
                    <!-- Cart Button triggers cart.js via class 'add-to-cart-btn' -->
                    <button class='quick-add-btn add-to-cart-btn' data-product-id='$pId'>
                        <i class='ri-shopping-cart-2-line'></i>
                    </button>
                </div>
                <div class='card-details'>
                    <span class='category-tag'>$pCat</span>
                    <h3 class='product-title'><a href='$pLink'>$pName</a></h3>
                    <div class='price-row'>
                        <span class='price'>₦$pPrice</span>
                        <a href='$pLink' class='view-link'><i class='ri-arrow-right-line'></i></a>
                    </div>
                </div>
            </div>";
        }
        echo '</div>'; 
    } else {
        echo '
        <div class="empty-state reveal-item">
            <div class="empty-icon"><i class="ri-search-eye-line"></i></div>
            <h2>No Matches Found</h2>
            <p>Try checking your spelling or use different keywords.</p>
            <button onclick="clearSearch()" class="btn-reset">Clear Filters</button>
        </div>';
    }
    exit;
}

include __DIR__ . "/includes/header.php";

// =========================================================
// 2. STANDARD PAGINATION & FETCH
// =========================================================
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
$per_page = 12;
$offset = ($page - 1) * $per_page;

$products = getAllProducts($per_page, $offset, $category_id);
$total_count = getProductCount($category_id);
$total_pages = ceil($total_count / $per_page);
$all_categories = getCategories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - The Care Bar</title>
    
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    
    <!-- ScrollReveal Script -->
    <script src="https://unpkg.com/scrollreveal"></script>

    <style>
        :root {
            --primary-color: #ff3f8e;
            --primary-hover: #ef3884ff;
            --text-dark: #222;
            --text-light: #666;
            --card-hover-shadow: 0 12px 25px rgba(0,0,0,0.12);
        }

        /* WRAPPER */
        .shop-container {
            padding: 40px 0 80px;
            background-color: #fff;
        }

        .shop-layout {
            display: flex;
            gap: 40px;
            align-items: flex-start;
        }

        /* SIDEBAR (Desktop & Mobile) */
        .shop-sidebar {
            width: 260px;
            flex-shrink: 0;
            background: #fff;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 12px;
            position: sticky;
            top: 100px;
        }

        .filter-group h3 {
            font-size: 1.1rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .category-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .category-list li { margin-bottom: 8px; }

        .category-list a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-decoration: none;
            color: var(--text-light);
            padding: 10px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .category-list a:hover {
            background-color: #fff0f6;
            color: var(--primary-color);
            padding-left: 20px;
        }

        .category-list a.active {
            background-color: var(--primary-color);
            color: #fff;
            font-weight: 500;
        }

        /* SEARCH BAR */
        .shop-main { flex-grow: 1; width: 100%; }

        .search-area {
            position: relative;
            max-width: 600px;
            margin: 0 auto 40px auto;
        }

        .search-input {
            width: 100%;
            padding: 16px 50px 16px 25px;
            border: 2px solid #eee;
            border-radius: 50px;
            font-size: 1rem;
            transition: 0.3s;
            background: #fdfdfd;
        }

        .search-input:focus {
            border-color: var(--primary-color);
            background: #fff;
            box-shadow: 0 4px 15px rgba(209, 85, 143, 0.15);
            outline: none;
        }

        .search-icons {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .loader-icon { color: var(--primary-color); display: none; animation: spin 0.8s linear infinite; }
        .clear-icon { cursor: pointer; color: #999; display: none; transition: 0.2s; }
        .clear-icon:hover { color: var(--primary-color); }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* PRODUCT GRID */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 25px;
        }

        .product-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #f0f0f0;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--card-hover-shadow);
            border-color: transparent;
        }

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
            color: #999;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .product-title {
            font-size: 1.05rem;
            margin: 0 0 15px 0;
            line-height: 1.4;
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

        /* PAGINATION STYLING (Restored) */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-top: 50px;
        }

        .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            background: #fff;
            border: 1px solid #ddd;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            transition: 0.3s;
            font-weight: 500;
        }

        .page-link:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .page-link.active {
            background: var(--primary-color);
            color: #fff;
            border-color: var(--primary-color);
        }

        .page-link i { margin: 0 5px; }

        .page-numbers {
            display: flex;
            gap: 5px;
        }
        
        .page-link.prev, .page-link.next {
            font-weight: 600;
        }

        /* MOBILE RESPONSIVENESS */
        @media (max-width: 991px) {
            .shop-layout { flex-direction: column; gap: 20px; }
            
            .shop-sidebar {
                width: 100%;
                padding: 15px;
                position: static;
                border: none;
                background: transparent;
            }
            
            .shop-sidebar h3, .shop-sidebar p { display: none; }
            
            .category-list {
                display: flex;
                overflow-x: auto;
                gap: 10px;
                padding-bottom: 5px;
                scrollbar-width: none;
            }
            .category-list::-webkit-scrollbar { display: none; }

            .category-list li { margin: 0; flex-shrink: 0; }
            
            .category-list a {
                background: #fff;
                border: 1px solid #eee;
                padding: 8px 18px;
                border-radius: 30px;
                font-size: 0.9rem;
            }

            .category-list a:hover { padding-left: 18px; }

            .search-area { margin-bottom: 25px; }
        }

        @media (max-width: 600px) {
            .product-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .card-image-wrapper { height: 180px; }
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
                background: rgba(255,255,255,0.9);
            }
            
            .pagination {
                gap: 8px;
                flex-wrap: wrap;
            }
            .page-link {
                padding: 8px 12px;
                font-size: 0.9rem;
            }
        }

        /* Empty State */
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-icon { font-size: 4rem; color: #ddd; margin-bottom: 15px; }
        .btn-reset { margin-top: 15px; padding: 10px 25px; background: var(--primary-color); color: #fff; border:none; border-radius: 50px; cursor: pointer; }

    </style>
</head>
<body>

    <main>
        <!-- Shop Hero -->
        <section class="shop-header" style="background: #222; color:#fff; padding: 80px 0; text-align:center;">
            <div class="container reveal-header">
                <h1 style="font-size: 3rem; margin-bottom: 10px;">Our Products</h1>
                <p style="opacity: 0.8; font-size: 1.1rem;">Curated essentials for your daily routines and Lifestyle</p>
            </div>
        </section>

        <!-- Shop Content -->
        <section class="shop-container">
            <div class="container">
                <div class="shop-layout">
                    
                    <!-- 1. Sidebar -->
                    <aside class="shop-sidebar reveal-left">
                        <div class="filter-group">
                            <h3><i class="ri-filter-3-line"></i> Categories</h3>
                            <ul class="category-list">
                                <li><a href="shop.php" class="<?= !$category_id ? 'active' : '' ?>">
                                    <span>All Products</span>
                                    <?php if(!$category_id): ?><i class="ri-check-line"></i><?php endif; ?>
                                </a></li>
                                
                                <?php foreach ($all_categories as $cat): ?>
                                    <li><a href="shop.php?category=<?= $cat['id'] ?>" class="<?= $category_id == $cat['id'] ? 'active' : '' ?>">
                                        <span><?= htmlspecialchars($cat['name']) ?></span>
                                        <?php if($category_id == $cat['id']): ?><i class="ri-check-line"></i><?php endif; ?>
                                    </a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="filter-group" style="margin-top: 30px;">
                            <p style="font-size: 0.9rem; color: #888; line-height: 1.6;">
                                We prioritize quality over quantity. Every item here is verified for authenticity.
                            </p>
                        </div>
                    </aside>

                    <!-- 2. Main Content -->
                    <div class="shop-main">
                        
                        <!-- Search Bar -->
                        <div class="search-area reveal-top">
                            <input type="text" id="shop-search" class="search-input" placeholder="Search for products">
                            <div class="search-icons">
                                <i class="ri-loader-4-line loader-icon" id="search-spinner"></i>
                                <i class="ri-close-circle-fill clear-icon" id="clear-search"></i>
                                <i class="ri-search-2-line" id="static-search-icon" style="color:#bbb;"></i>
                            </div>
                        </div>

                        <!-- Results Container -->
                        <div id="shop-results-container">
                            <?php if (!empty($products)): ?>
                                <div class="shop-header-bar reveal-item" style="margin-bottom: 20px; color: #888; font-size: 0.9rem;">
                                    Showing <?= count($products) ?> of <?= $total_count ?> products
                                </div>

                                <div class="product-grid">
                                    <?php foreach ($products as $product): ?>
                                        <?php 
                                            $imgSrc = $BASE_URL . 'uploads/' . htmlspecialchars($product['image']);
                                            $pLink = $BASE_URL . "product.php?id=" . $product['id'];
                                        ?>
                                        <div class="product-card reveal-item">
                                            <div class="card-image-wrapper">
                                                <a href="<?= $pLink ?>">
                                                    <img src="<?= $imgSrc ?>" loading="lazy" alt="<?= htmlspecialchars($product['name']) ?>">
                                                </a>
                                                <!-- Action Button - Triggers cart.js via class .add-to-cart-btn -->
                                                <button class="quick-add-btn add-to-cart-btn" data-product-id="<?= $product['id'] ?>">
                                                    <i class="ri-shopping-cart-2-line"></i>
                                                </button>
                                            </div>
                                            <div class="card-details">
                                                <span class="category-tag"><?= htmlspecialchars($product['category_name'] ?? 'General') ?></span>
                                                <h3 class="product-title"><a href="<?= $pLink ?>"><?= htmlspecialchars($product['name']) ?></a></h3>
                                                <div class="price-row">
                                                    <span class="price">₦<?= number_format($product['price'], 2) ?></span>
                                                    <a href="<?= $pLink ?>" class="view-link" title="View Details">
                                                        <i class="ri-arrow-right-line"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- PAGINATION (RESTORED PREV/NEXT LOGIC) -->
                                <?php if ($total_pages > 1): ?>
                                    <div class="pagination reveal-item">
                                        <!-- Previous Button -->
                                        <?php if ($page > 1): ?>
                                            <a href="?page=<?= $page - 1 ?><?= $category_id ? '&category=' . $category_id : '' ?>" class="page-link prev">
                                                <i class="ri-arrow-left-line"></i> Previous
                                            </a>
                                        <?php endif; ?>

                                        <!-- Numbered Links -->
                                        <div class="page-numbers">
                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                <a href="?page=<?= $i ?><?= $category_id ? '&category=' . $category_id : '' ?>" 
                                                   class="page-link <?= $i == $page ? 'active' : '' ?>">
                                                    <?= $i ?>
                                                </a>
                                            <?php endfor; ?>
                                        </div>

                                        <!-- Next Button -->
                                        <?php if ($page < $total_pages): ?>
                                            <a href="?page=<?= $page + 1 ?><?= $category_id ? '&category=' . $category_id : '' ?>" class="page-link next">
                                                Next <i class="ri-arrow-right-line"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                            <?php else: ?>
                                <div class="empty-state reveal-item">
                                    <div class="empty-icon"><i class="ri-inbox-line"></i></div>
                                    <h2>No Products Found</h2>
                                    <a href="shop.php" class="btn-reset">View All Products</a>
                                </div>
                            <?php endif; ?>
                        </div> 
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Include Cart JS -->
    <script src="<?php echo $BASE_URL; ?>assets/js/cart.js"></script>

    <!-- PAGE SCRIPTS -->
    <script>
        // 1. INITIALIZE SCROLL REVEAL
        const sr = ScrollReveal({
            origin: 'bottom',
            distance: '30px',
            duration: 800,
            delay: 100,
            reset: false 
        });

        sr.reveal('.reveal-header', { origin: 'top' });
        sr.reveal('.reveal-left', { origin: 'left', delay: 200 });
        sr.reveal('.reveal-top', { origin: 'top', delay: 300 });
        sr.reveal('.reveal-item', { interval: 100 });

        // 2. SEARCH LOGIC
        const searchInput = document.getElementById('shop-search');
        const resultsContainer = document.getElementById('shop-results-container');
        const spinner = document.getElementById('search-spinner');
        const clearBtn = document.getElementById('clear-search');
        const staticIcon = document.getElementById('static-search-icon');
        let searchTimeout;
        const originalContent = resultsContainer.innerHTML;

        if (searchInput) {
            searchInput.addEventListener('keyup', (e) => {
                const term = e.target.value;
                clearTimeout(searchTimeout);

                if (term.length > 0) {
                    spinner.style.display = 'block';
                    clearBtn.style.display = 'block';
                    staticIcon.style.display = 'none';

                    searchTimeout = setTimeout(() => {
                        fetch('shop.php?ajax_search=' + encodeURIComponent(term))
                            .then(res => res.text())
                            .then(html => {
                                resultsContainer.innerHTML = html;
                                spinner.style.display = 'none';
                                
                                // Re-trigger animations for new content
                                sr.clean('.reveal-item'); 
                                sr.reveal('.reveal-item', { interval: 50 });
                            })
                            .catch(err => console.error(err));
                    }, 400); // 400ms debounce
                } else {
                    resetSearch();
                }
            });

            clearBtn.addEventListener('click', () => {
                searchInput.value = '';
                resetSearch();
            });
        }

        function resetSearch() {
            resultsContainer.innerHTML = originalContent;
            spinner.style.display = 'none';
            clearBtn.style.display = 'none';
            staticIcon.style.display = 'block';
            sr.reveal('.reveal-item', { interval: 100 });
        }
    </script>

</body>
</html>

<!-- RESTORED MODAL INCLUSION -->
<?php include __DIR__ . "/includes/footer.php"; ?>
<?php include __DIR__ . "/includes/modal.php"; ?>