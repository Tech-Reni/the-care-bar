<?php
session_start();
require_once __DIR__ . '/db.php';


// Get Cart Count from Session
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

// Get categories for sidebar (limit to 3)
$categories = getCategories(3);
?>
<!DOCTYPE html>
<html lang="en" data-base-url="<?php echo $BASE_URL; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">

    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Main CSS -->
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>assets/css/header.css">

    <!-- Favicon -->
    <link rel="icon" href="<?= $BASE_URL ?>assets/img/logo.png" type="image/x-icon">
    <link rel="shortcut icon" href="<?= $BASE_URL ?>assets/img/logo.png" type="image/x-icon">
    <link rel="icon" href="<?= $BASE_URL ?>assets/img/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="<?= $BASE_URL ?>assets/img/logo.png">

    <style>
        :root {
            --accent-color: #d63384; /* Dark Pink Accent */
        }
    </style>
</head>
<body>
    <!-- Dark Overlay -->
    <div id="overlay" class="overlay"></div>

    <!-- Sidebar Menu -->
    <aside id="sidebar" class="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo $BASE_URL; ?>assets/img/logo.png" alt="TheCareBar Logo" class="logo-sidebar">
            <i id="closeSidebar" class="ri-close-line close-icon"></i>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="<?php echo $BASE_URL; ?>index.php"><i class="ri-home-4-line"></i> Home</a></li>
                <li><a href="<?php echo $BASE_URL; ?>shop.php"><i class="ri-store-line"></i> Shop</a></li>
                
                <!-- Categories (First 3) -->
                <?php if ($categories && !empty($categories)): ?>
                    <?php foreach ($categories as $cat): ?>
                        <li><a href="<?php echo $BASE_URL; ?>shop.php?category=<?php echo (int)$cat['id']; ?>"><i class="ri-folder-line"></i> <?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Social Media Icons -->
        <div class="sidebar-social">
            <a href="tel:+2348141989682" title="Call us"><i class="ri-phone-fill"></i></a>
            <a href="https://www.tiktok.com/@the_care_bar" target="_blank" title="TikTok"><i class="fab fa-tiktok"></i></a>
            <a href="#" title="Instagram"><i class="ri-instagram-line"></i></a>
        </div>
    </aside>

    <!-- Header -->
    <header class="header"> 
        <div class="container flex-between">

            <!-- Logo -->
            <div class="logo">
                <a href="<?php echo $BASE_URL; ?>index.php">
                    <img src="<?php echo $BASE_URL; ?>assets/img/logo.png" alt="TheCareBar Logo">
                </a>
            </div>

            <!-- Desktop Navigation -->
            <nav class="nav-links desktop-nav">
                <ul>
                    <li><a href="<?php echo $BASE_URL; ?>index.php">Home</a></li>
                    <li><a href="<?php echo $BASE_URL; ?>shop.php">Shop</a></li>
                    <li>
                        <a href="<?php echo $BASE_URL; ?>cart.php">
                            <i class="ri-shopping-cart-line open-cart"></i> 
                            Cart 
                            <span id="cartCount"><?php echo $cart_count; ?></span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Mobile Right Controls -->
            <div class="mobile-controls">
                <!-- Mobile Cart -->
                <a href="<?php echo $BASE_URL; ?>cart.php" class="mobile-cart">
                    <i class="ri-shopping-cart-line open-cart"></i>
                    <span class="mobile-cart-count"><?php echo $cart_count; ?></span>
                </a>

                <!-- Hamburger -->
                <div class="hamburger" id="hamburger">
                    <i class="ri-menu-line"></i>
                </div>
            </div>

        </div>
    </header>


    <!-- JS for Sidebar + Overlay -->
    <script>
        const hamburger = document.getElementById('hamburger');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const closeSidebar = document.getElementById('closeSidebar');

        // Open Sidebar
        hamburger.addEventListener('click', () => {
            sidebar.classList.add('active');
            overlay.classList.add('active');
        });

        // Close Sidebar
        closeSidebar.addEventListener('click', () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });

        // Click Overlay to Close
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });

        // Close sidebar when clicking nav links
        document.querySelectorAll('.sidebar-nav a').forEach(link => {
            link.addEventListener('click', () => {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        });
    </script>
</body>
</html>
