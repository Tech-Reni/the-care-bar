<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in (for future auth implementation)
// For now, just ensure admin access
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>The Care Bar Admin</title>

    <!-- Google Font: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">

    <!-- Main CSS -->
    <link rel="stylesheet" href="<?= $BASE_URL ?>assets/css/admin.css">

    <!-- Quill Editor (optional, loaded on demand) -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" href="<?= $BASE_URL ?>assets/img/logo.png" type="image/x-icon">
    <link rel="shortcut icon" href="<?= $BASE_URL ?>assets/img/logo.png" type="image/x-icon">
    <link rel="icon" href="<?= $BASE_URL ?>assets/img/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="<?= $BASE_URL ?>assets/img/logo.png">

    <link rel="manifest" href="/admin/manifest.json">

    <script>
        let deferredPrompt = null;

        // Catch the install event BEFORE browser hides it
        window.addEventListener("beforeinstallprompt", (e) => {
            e.preventDefault();
            deferredPrompt = e; // save the event for later

            const installBtn = document.getElementById("installAppBtn");
            if (installBtn) installBtn.style.display = "block";
        });

        // Your manual install button trigger
        async function installPWA() {
            if (!deferredPrompt) {
                showInfo('Info', 'Install option not available yet. Try again later.');
                return;
            }

            // Ask user via modal before triggering the native install prompt
            showConfirm('Install Admin App', 'Do you want to install the admin app?', async () => {
                try {
                    await deferredPrompt.prompt();
                    const choice = await deferredPrompt.userChoice;

                    if (choice.outcome === 'accepted') {
                        console.log('APP INSTALLED');
                    } else {
                        console.log('INSTALL DISMISSED');
                    }
                } catch (err) {
                    console.error('Install prompt failed:', err);
                } finally {
                    deferredPrompt = null; // clear
                }
            });
        }

        // Register service worker
        if ("serviceWorker" in navigator) {
            navigator.serviceWorker.register("/admin/service-worker.js")
                .catch(err => console.error("SW failed:", err));
        }
    </script>

</head>

<body>
    <!-- ================================
            SIDEBAR
    ================================ -->
    <div class="sidebar" id="sidebar">
        <div class="sb-header">
            <h2>The Care Bar</h2>
            <button class="sb-close" onclick="toggleSidebar()"><i class="ri-close-line"></i></button>
        </div>

        <div class="sb-menu">
            <a href="<?= $BASE_URL ?>admin/index.php" class="<?= (strpos($_SERVER['PHP_SELF'], 'admin/index') !== false) ? 'active' : ''; ?>">
                <i class="ri-dashboard-line"></i> Dashboard
            </a>
            <a href="<?= $BASE_URL ?>admin/products.php" class="<?= (strpos($_SERVER['PHP_SELF'], 'admin/products') !== false) ? 'active' : ''; ?>">
                <i class="ri-shopping-bag-3-line"></i> Products
            </a>
            <a href="<?= $BASE_URL ?>admin/categories.php" class="<?= (strpos($_SERVER['PHP_SELF'], 'admin/categories') !== false) ? 'active' : ''; ?>">
                <i class="ri-folders-line"></i> Categories
            </a>
            <a href="<?= $BASE_URL ?>admin/orders.php" class="<?= (strpos($_SERVER['PHP_SELF'], 'admin/orders') !== false) ? 'active' : ''; ?>">
                <i class="ri-file-list-3-line"></i> Orders
            </a>
            <!-- <a href="#" class="coming-soon">
                <i class="ri-settings-3-line"></i> Settings <span class="badge">Soon</span>
            </a> -->
        </div>

        <div class="sb-footer">
            <a href="<?= $BASE_URL ?>" class="sb-link-secondary">
                <i class="ri-home-4-line"></i> View Site
            </a>
        </div>
    </div>

    <!-- ================================
            MAIN CONTENT WRAPPER
    ================================ -->
    <div class="main">
        <!-- Top Navbar -->
        <div class="topbar">
            <button class="menu-btn" onclick="toggleSidebar()">
                <i class="ri-menu-line"></i>
            </button>

            <h3 id="page-title"><?php echo isset($page_title) ? $page_title : 'Admin Panel'; ?></h3>

            <div class="top-right">
                <!-- ADD THIS NEW LINK -->
                <a href="change_password.php" class="btn btn-icon" title="Change Password">
                    <i class="ri-shield-keyhole-line"></i>
                </a>

                <!-- Existing Logout Button -->
                <a href="logout.php" class="btn btn-outline" style="border-color: red; color: red;">
                    <i class="ri-logout-box-r-line"></i> Logout
                </a>
            </div>       
        </div>

        <!-- Page Content -->
        <div class="page-content">
            <button id="installAppBtn"
                onclick="installPWA()"
                style="display:none; padding:8px 12px; background:#d1558f; color:white; border-radius:6px; border:none;">
                📲 Install Admin App
            </button>