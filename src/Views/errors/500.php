<!-- 500.php error view -->
<!-- File path: src/Views/errors/500.php -->
<div class="error-page">
    <div class="error-code">500</div>
    <h1 class="error-message">Server Error</h1>
    <p>Something went wrong on our end. Please try again later.</p>
    <?php if (isset($errorMessage)): ?>
        <div class="error-details">
            <pre><?= htmlspecialchars($errorMessage) ?></pre>
        </div>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/" class="btn btn-primary mt-4">
        <i class="fas fa-home"></i> Go Home
    </a>
</div>