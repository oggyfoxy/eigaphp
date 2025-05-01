<?php
// This file might eventually be loaded via the render method too,
// but for simplicity, the router includes it directly for now.
$pageTitle = "Page Not Found";
require_once __DIR__ . '/../layouts/main.php'; // Manually include layout FOR NOW
?>

<div style="text-align: center; padding: 50px;">
    <h1>404 - Page Not Found</h1>
    <p>Sorry, the page you were looking for could not be found.</p>
    <p><a href="<?= BASE_URL ?>/">Go to Homepage</a></p>
</div>

<?php
// If loaded directly, ensure the script stops if needed.
// If loaded via render(), the layout will handle the end.
// exit;
?>