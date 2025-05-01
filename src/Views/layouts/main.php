<?php
// We'll eventually load config here or earlier in the process
require_once __DIR__ . '/../../../config/config.php';

// Assume $pageTitle and $viewContent variables will be passed
// to this layout file from the controllers.
// Set defaults if they aren't passed.
$pageTitle = $pageTitle ?? SITE_NAME; // Use site name from config if no specific title
$viewContent = $viewContent ?? '<!-- No content provided -->';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= SITE_NAME ?></title>
    <!-- Link to your CSS file -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <!-- Add favicon links etc. here if you have them -->
    <!-- <link rel="icon" href="<?= BASE_URL ?>/favicon.ico" type="image/x-icon"> -->

    <!-- You might include core JS libraries here, or at the end of body -->
</head>
<body>
    <div class="app-container">

        <?php require_once __DIR__ . '/../partials/header.php'; ?>

        <main>
            <!-- This is where the specific page's content will be loaded -->
            <?php echo $viewContent; ?>
        </main>

        <?php require_once __DIR__ . '/../partials/footer.php'; ?>

    </div> <!-- /.app-container -->

    <!-- Link to your main JavaScript file (usually at the end) -->
    <script src="<?= BASE_URL ?>/js/main.js"></script>
    <!-- Add other JS files as needed -->

</body>
</html>