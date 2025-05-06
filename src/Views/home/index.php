<!-- home/index.php view -->
<!-- File path: src/Views/home/index.php -->
<div class="hero animate-fadeIn">
    <h1>Welcome, <?= $isLoggedIn ? htmlspecialchars($currentUser['username']) : 'Guest' ?></h1>
    <p class="hero-subtitle">Discover, annotate, and collaborate on movies with fellow cinephiles.</p>
    
    <form action="<?= BASE_URL ?>/search" method="GET" class="search-form">
        <div class="search-container">
            <input type="text" name="q" placeholder="Search movies..." class="form-control">
            <button type="submit" class="btn btn-primary">Search</button>
        </div>
    </form>
</div>

<?php if (!empty($popularMovies)): ?>
    <section class="section">
        <h2>Recently Annotated</h2>
        <div class="grid">
            <?php foreach ($popularMovies as $movie): ?>
                <div class="card">
                    <img src="<?= !empty($movie['poster_path']) ? $movie['poster_path'] : BASE_URL . '/img/no-poster.jpg' ?>" 
                         alt="<?= htmlspecialchars($movie['title']) ?>" class="card-img">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($movie['title']) ?></h5>
                        <p class="card-text"><?= $movie['release_year'] ?></p>
                        <a href="<?= BASE_URL ?>/movie?id=<?= $movie['id'] ?>" class="btn btn-primary btn-sm">View</a>
                    </div>
                    <div class="card-footer">
                        <?= $movie['annotation_count'] ?? 0 ?> annotations
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if (!empty($popularCollections)): ?>
    <section class="section">
        <h2>Popular Collections</h2>
        <div class="grid">
            <?php foreach ($popularCollections as $collection): ?>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($collection['title']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars(substr($collection['description'], 0, 100)) ?>...</p>
                        <div class="collection-meta mb-2">
                            <div class="collection-user">
                                <img src="<?= !empty($collection['avatar']) ? $collection['avatar'] : BASE_URL . '/img/default-avatar.jpg' ?>" 
                                     alt="<?= htmlspecialchars($collection['username']) ?>" class="avatar avatar-sm">
                                <span><?= htmlspecialchars($collection['username']) ?></span>
                            </div>
                        </div>
                        <a href="<?= BASE_URL ?>/collection?id=<?= $collection['id'] ?>" class="btn btn-primary btn-sm">View</a>
                    </div>
                    <div class="card-footer">
                        <?= $collection['movie_count'] ?? 0 ?> movies
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>