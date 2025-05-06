<!-- src/Views/home/search.php -->
<h1>Explore</h1>

<form action="<?= BASE_URL ?>/search" method="get" class="flex space-between mt-20">
    <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Search movies…" style="flex:1; padding:8px">
    <button type="submit" class="btn" style="margin-left:10px">Search</button>
</form>

<?php if ($query === ''): ?>
    <p class="mt-20">Start typing above to discover movies.</p>
<?php elseif (empty($results)): ?>
    <p class="mt-20">No results for “<?= htmlspecialchars($query) ?>”.</p>
<?php else: ?>
    <h2 class="mt-20">Results for “<?= htmlspecialchars($query) ?>”</h2>

    <div class="movie-grid mt-20" style="display:grid; grid-template-columns:repeat(auto-fill,150px); gap:20px">
        <?php foreach ($results as $m): ?>
            <a href="<?= BASE_URL ?>/movie?id=<?= $m['tmdb_id'] ?>" style="text-decoration:none; color:inherit">
                <img src="<?= (new \App\Services\TMDBApi())->getPosterUrl($m['poster_path'],'w185') ?>"
                     alt="<?= htmlspecialchars($m['title']) ?>"
                     style="width:150px; height:auto; border-radius:4px">
                <div style="margin-top:5px; font-size:14px">
                    <?= htmlspecialchars($m['title']) ?>
                    <?php if ($m['release_year']): ?>
                        (<?= $m['release_year'] ?>)
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
