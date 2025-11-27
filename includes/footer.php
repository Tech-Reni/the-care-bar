
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>assets/css/footer.css">
</head>
<body>
    <!-- Footer -->
<footer class="footer">
    <div class="container footer-grid">

        <!-- About Section -->
        <div class="footer-col">
            <h4>About TheCareBar</h4>
            <p>
                I specialize in self-care and lifestyle products, including facial and oral care items, bottles, 
                gift packages, hair accessories, tattoo stickers, beaded bracelets, packaging materials for 
                business owners, fans, and lip glosses. I also run an importation business to bring in these 
                products and other items for sale.
            </p>
        </div>

        <!-- Quick Links -->
        <div class="footer-col">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="<?php echo $BASE_URL; ?>index.php"><i class="ri-home-4-line"></i> Home</a></li>
                <li><a href="<?php echo $BASE_URL; ?>shop.php"><i class="ri-store-2-line"></i> Shop</a></li>
                <!-- Categories (First 3) -->
                <?php if ($categories && !empty($categories)): ?>
                    <?php foreach ($categories as $cat): ?>
                        <li><a href="<?php echo $BASE_URL; ?>shop.php?category=<?php echo (int)$cat['id']; ?>"><i class="ri-folder-line"></i> <?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Contact -->
        <div class="footer-col">
            <h4>Contact Us</h4>
            <ul>
                <li>
                    <a href="tel:+2348141989682">
                        <i class="ri-phone-fill"></i> +234 814 198 9682
                    </a>
                </li>
                <li>
                    <a href="https://www.tiktok.com/@the_care_bar" target="_blank">
                        <i class="fab fa-tiktok"></i> @the_care_bar
                    </a>
                </li>
            </ul>
        </div>

    </div>

    <!-- Bottom Bar -->
    <div class="footer-bottom">
        <p>© <?php echo date('Y'); ?> TheCareBar — All Rights Reserved.</p>
    </div>
</footer>

</body>
</html>