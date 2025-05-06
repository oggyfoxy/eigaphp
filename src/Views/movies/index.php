<h1>Library</h1>

<?php if (empty($movies)): ?>
    <p>You haven’t added any movies yet.</p>
<?php endif; ?>

<div class="movie-grid" style="display:grid; grid-template-columns:repeat(auto-fill,150px); gap:20px">
<?php foreach ($movies as $movie): ?>
    <?php
        $poster = $movie['poster_path'] ?? null;
        if ($poster && !str_starts_with($poster,'http'))
            $poster = (new \App\Services\TMDBApi())->getPosterUrl($poster,'w185');

        $rating = $avgRatings[$movie['tmdb_id'] ?? ($movie['id'] ?? 0)] ?? null;
    ?>
    <a href="<?= BASE_URL ?>/movie?id=<?= $movie['tmdb_id'] ?? $movie['id'] ?>"
       style="text-decoration:none; color:inherit">
        <img src="<?= $poster ?: BASE_URL . '/img/no-poster.jpg' ?>"
             alt="<?= htmlspecialchars($movie['title'] ?? 'Untitled') ?>"
             style="width:150px; height:auto; border-radius:4px">
        <div style="margin-top:5px; font-size:14px">
            <?= htmlspecialchars($movie['title'] ?? 'Untitled') ?>
            <?php if (!empty($movie['release_year'])): ?>
                (<?= $movie['release_year'] ?>)
            <?php endif; ?>
            <br>
            <small><?= $rating !== null ? number_format($rating,1) . '★' : '—' ?></small>
        </div>
    </a>
<?php endforeach; ?>
</div>
