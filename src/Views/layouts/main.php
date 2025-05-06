<!-- main.php layout -->
<!-- File path: src/Views/layouts/main.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/custom.css">
</head>
<body>
    <header>
        <div class="container">
            <nav class="navbar">
                <a href="<?= BASE_URL ?>/" class="logo"><?= SITE_NAME ?></a>
                
                <button class="mobile-menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <ul class="nav-links">
                    <li><a href="<?= BASE_URL ?>/" class="nav-link">Home</a></li>
                    <li><a href="<?= BASE_URL ?>/movies" class="nav-link">Library</a></li>
                    <li><a href="<?= BASE_URL ?>/search" class="nav-link">Explore</a></li>
                    
                    <?php if (isset($isLoggedIn) && $isLoggedIn): ?>
                        <li><a href="<?= BASE_URL ?>/profile" class="nav-link">Profile</a></li>
                        <li><a href="<?= BASE_URL ?>/logout" class="nav-link">Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?= BASE_URL ?>/login" class="nav-link">Login</a></li>
                        <li><a href="<?= BASE_URL ?>/register" class="nav-link">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    
    <main>
        <div class="container">
            <?php if (isset($flashMessages) && !empty($flashMessages)): ?>
                <div class="flash-messages">
                    <?php foreach ($flashMessages as $message): ?>
                        <div class="flash-message flash-<?= $message['type'] ?>">
                            <?= $message['message'] ?>
                            <button class="close-btn">&times;</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?= $viewContent ?>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4 class="footer-heading"><?= SITE_NAME ?></h4>
                    <p>A collaborative movie annotation platform</p>
                </div>
                
                <div class="footer-section">
                    <h4 class="footer-heading">Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="<?= BASE_URL ?>/" class="footer-link">Home</a></li>
                        <li><a href="<?= BASE_URL ?>/movies" class="footer-link">Library</a></li>
                        <li><a href="<?= BASE_URL ?>/search" class="footer-link">Explore</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4 class="footer-heading">Legal</h4>
                    <ul class="footer-links">
                        <li><a href="<?= BASE_URL ?>/about" class="footer-link">About</a></li>
                        <li><a href="<?= BASE_URL ?>/privacy" class="footer-link">Privacy Policy</a></li>
                        <li><a href="<?= BASE_URL ?>/terms" class="footer-link">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="copyright">
                &copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.
            </div>
        </div>
    </footer>
    
    <button class="theme-toggle">
        <i class="fas fa-moon"></i>
    </button>
    
    <script src="<?= BASE_URL ?>/js/main.js"></script>
</body>
</html>