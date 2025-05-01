<?php
// We might need config here later too, e.g., for BASE_URL in links
// require_once __DIR__ . '/../../../config/config.php';
?>
<div class="barcode">
    <!-- Simple placeholder for barcode style -->
    <!-- You could generate spans with JS if you want a dynamic effect -->
</div>

<header>
    <a href="<?= BASE_URL ?>/" class="logo"><?= SITE_NAME ?></a>
    <nav>
        <a href="<?= BASE_URL ?>/">home</a>
        <a href="#">library</a> <!-- Placeholder link -->
        <a href="#">explore</a> <!-- Placeholder link -->
        <a href="#">profile</a> <!-- Placeholder link -->
        <!-- We'll add login/logout links dynamically later -->
    </nav>
</header>