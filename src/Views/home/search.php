<h1>Search Results</h1>

<?php if (!empty($query)): ?>
    <p>You searched for: <strong><?= htmlspecialchars($query) ?></strong></p>
    <!-- Insert logic to display results here -->
<?php else: ?>
    <p>No search query provided.</p>
<?php endif; ?>
