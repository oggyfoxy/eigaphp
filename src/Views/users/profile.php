<!-- users/profile.php view -->
<!-- File path: src/Views/users/profile.php -->
<div class="profile-header">
    <div class="profile-avatar">
        <img src="<?= !empty($user['avatar']) ? $user['avatar'] : BASE_URL . '/img/default-avatar.jpg' ?>" 
             alt="<?= htmlspecialchars($user['username']) ?>">
    </div>
    
    <div class="profile-info">
        <h1 class="profile-username"><?= htmlspecialchars($user['username']) ?></h1>
        
        <div class="profile-stats">
            <div class="profile-stat">
                <span class="profile-stat-value"><?= count($annotations) ?></span>
                <span class="profile-stat-label">Annotations</span>
            </div>
            
            <div class="profile-stat">
                <span class="profile-stat-value"><?= count($favoriteMovies) ?></span>
                <span class="profile-stat-label">Favorites</span>
            </div>
            
            <div class="profile-stat">
                <span class="profile-stat-value"><?= count($collections) ?></span>
                <span class="profile-stat-label">Collections</span>
            </div>
        </div>
        
        <?php if (!empty($user['bio'])): ?>
            <div class="profile-bio">
                <p><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
            </div>
        <?php endif; ?>
        
        <div class="profile-actions">
            <?php if ($isOwner): ?>
                <a href="<?= BASE_URL ?>/profile/edit" class="btn btn-outline">
                    <i class="far fa-edit"></i> Edit Profile
                </a>
            <?php elseif ($isLoggedIn): ?>
                <button class="btn <?= $isFollowing ? 'btn-outline' : 'btn-primary' ?> follow-btn" 
                        onclick="toggleFollow(<?= $user['id'] ?>, '<?= BASE_URL ?>/user/toggle-follow')">
                    <i class="<?= $isFollowing ? 'fas' : 'far' ?> fa-user"></i>
                    <span><?= $isFollowing ? 'Unfollow' : 'Follow' ?></span>
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="profile-tabs">
    <a href="#annotations" class="profile-tab active" data-tab="annotations">Annotations</a>
    <a href="#favorites" class="profile-tab" data-tab="favorites">Favorites</a>
    <a href="#collections" class="profile-tab" data-tab="collections">Collections</a>
</div>

<div class="profile-content">
    <div class="profile-tab-content active" id="annotations-content">
        <?php if (empty($annotations)): ?>
            <div class="empty-state">
                <p>No annotations yet.</p>
            </div>
        <?php else: ?>
            <div class="annotations">
                <?php foreach ($annotations as $annotation): ?>
                    <div class="annotation" id="annotation-<?= $annotation['id'] ?>">
                        <div class="annotation-header">
                            <div class="annotation-meta">
                                <span class="movie-title">
                                    <a href="<?= BASE_URL ?>/movie?id=<?= $annotation['movie_id'] ?>">
                                        <?= htmlspecialchars($annotation['movie_title']) ?> (<?= $annotation['timestamp_seconds'] ? gmdate("H:i:s", $annotation['timestamp_seconds']) : 'General' ?>)
                                    </a>
                                </span>
                            </div>
                            
                            <div class="annotation-date">
                                <?= date('M j, Y', strtotime($annotation['created_at'])) ?>
                            </div>
                        </div>
                        
                        <div class="annotation-content">
                            <?= nl2br(htmlspecialchars($annotation['content'])) ?>
                        </div>
                        
                        <div class="annotation-footer">
                            <div class="annotation-actions">
                                <span class="annotation-action">
                                    <i class="far fa-heart"></i>
                                    <span><?= $annotation['likes_count'] ?></span>
                                </span>
                                
                                <span class="annotation-action">
                                    <i class="far fa-comment"></i>
                                    <span><?= $annotation['comments_count'] ?></span>
                                </span>
                                
                                <a href="<?= BASE_URL ?>/annotation?id=<?= $annotation['id'] ?>" class="annotation-action">
                                    <i class="far fa-eye"></i>
                                    <span>View</span>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="<?= BASE_URL ?>/profile/annotations?username=<?= urlencode($user['username']) ?>" 
                   class="btn btn-outline">
                    View All Annotations
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="profile-tab-content" id="favorites-content">
        <?php if (empty($favoriteMovies)): ?>
            <div class="empty-state">
                <p>No favorite movies yet.</p>
            </div>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($favoriteMovies as $movie): ?>
                    <div class="card">
                        <img src="<?= !empty($movie['poster_path']) ? $movie['poster_path'] : BASE_URL . '/img/no-poster.jpg' ?>" 
                             alt="<?= htmlspecialchars($movie['title']) ?>" class="card-img">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($movie['title']) ?></h5>
                            <p class="card-text"><?= $movie['release_year'] ?></p>
                            <a href="<?= BASE_URL ?>/movie?id=<?= $movie['id'] ?>" class="btn btn-primary btn-sm">View</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="<?= BASE_URL ?>/profile/favorites?username=<?= urlencode($user['username']) ?>" 
                   class="btn btn-outline">
                    View All Favorites
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="profile-tab-content" id="collections-content">
        <?php if (empty($collections)): ?>
            <div class="empty-state">
                <p>No collections yet.</p>
                
                <?php if ($isOwner): ?>
                    <a href="<?= BASE_URL ?>/collection/create" class="btn btn-primary mt-3">
                        <i class="fas fa-plus"></i> Create Collection
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php if ($isOwner): ?>
                <div class="text-right mb-3">
                    <a href="<?= BASE_URL ?>/collection/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Collection
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="grid">
                <?php foreach ($collections as $collection): ?>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($collection['title']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars(substr($collection['description'], 0, 100)) ?>...</p>
                            <a href="<?= BASE_URL ?>/collection?id=<?= $collection['id'] ?>" class="btn btn-primary btn-sm">View</a>
                        </div>
                        <div class="card-footer">
                            <?= $collection['movie_count'] ?? 0 ?> movies • 
                            <?= $collection['is_private'] ? '<i class="fas fa-lock"></i> Private' : '<i class="fas fa-globe"></i> Public' ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="<?= BASE_URL ?>/profile/collections?username=<?= urlencode($user['username']) ?>" 
                   class="btn btn-outline">
                    View All Collections
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>