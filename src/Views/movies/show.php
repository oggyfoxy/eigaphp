<!-- movies/show.php view -->
<!-- File path: src/Views/movies/show.php -->
<div class="movie-details">
    <div class="movie-poster">
        <img src="<?= !empty($movie['poster_path']) ? $movie['poster_path'] : BASE_URL . '/img/no-poster.jpg' ?>" 
             alt="<?= htmlspecialchars($movie['title']) ?>">
    </div>
    
    <div class="movie-info">
        <div class="movie-header">
            <h1 class="movie-title"><?= htmlspecialchars($movie['title']) ?></h1>
            <div class="movie-year"><?= $movie['release_year'] ?></div>
            
            <div class="movie-meta">
                <?php if (!empty($movie['director'])): ?>
                    <span class="badge badge-primary"><?= htmlspecialchars($movie['director']) ?></span>
                <?php endif; ?>
                
                <div class="movie-rating">
                    <div class="rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php if ($i <= round($avgRating['avg_rating'] / 2)): ?>
                                <i class="fas fa-star"></i>
                            <?php elseif ($i - 0.5 <= round($avgRating['avg_rating'] / 2)): ?>
                                <i class="fas fa-star-half-alt"></i>
                            <?php else: ?>
                                <i class="far fa-star"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    <span class="avg-rating"><?= number_format($avgRating['avg_rating'], 1) ?></span>
                    <span class="rating-count">(<?= $avgRating['rating_count'] ?>)</span>
                </div>
            </div>
        </div>
        
        <div class="movie-actions">
            <?php if ($isLoggedIn): ?>
                <button class="btn btn-outline <?= $isFavorite ? 'active' : '' ?> favorite-btn" 
                        onclick="toggleFavorite(<?= $movie['id'] ?>, '<?= BASE_URL ?>/movie/toggle-favorite')">
                    <i class="<?= $isFavorite ? 'fas' : 'far' ?> fa-heart"></i>
                    <span><?= $isFavorite ? 'Remove from Favorites' : 'Add to Favorites' ?></span>
                </button>
                
                <!-- Add to Collection Button/Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-outline dropdown-toggle">
                        <i class="far fa-list-alt"></i> Add to Collection
                    </button>
                    <div class="dropdown-menu">
                        <div class="dropdown-item">
                            <a href="<?= BASE_URL ?>/collection/create" class="dropdown-link">Create New Collection</a>
                        </div>
                        <!-- Collections would be loaded here via AJAX -->
                    </div>
                </div>
                
                <!-- Rate Movie Button (shows modal) -->
                <button class="btn btn-outline rate-btn" data-toggle="modal" data-target="#rateModal">
                    <i class="far fa-star"></i> Rate Movie
                </button>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/login" class="btn btn-outline">
                    <i class="far fa-user"></i> Log in to rate & save
                </a>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($movie['overview'])): ?>
            <div class="movie-overview">
                <h3>Overview</h3>
                <p><?= htmlspecialchars($movie['overview']) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Rating Modal -->
<?php if ($isLoggedIn): ?>
<div class="modal" id="rateModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Rate this movie</h5>
            <button class="close-btn" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
            <form class="rating-form" action="<?= BASE_URL ?>/movie/rate" method="POST" data-ajax="true">
                <input type="hidden" name="movie_id" value="<?= $movie['id'] ?>">
                <input type="hidden" name="rating" value="<?= $userRating ? $userRating['rating'] : '0' ?>">
                
                <div class="rating-container mb-3">
                    <?php for ($i = 1; $i <= 10; $i += 0.5): ?>
                        <span class="star <?= $userRating && $userRating['rating'] >= $i ? 'filled' : '' ?>" 
                             data-value="<?= $i ?>">
                            <?php if ($i % 1 === 0): ?>
                                <i class="fas fa-star"></i>
                            <?php else: ?>
                                <i class="fas fa-star-half-alt"></i>
                            <?php endif; ?>
                        </span>
                    <?php endfor; ?>
                </div>
                
                <button type="submit" class="btn btn-primary">Submit Rating</button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Annotations Section -->
<div class="annotations-section">
    <h2>Annotations</h2>
    
    <?php if ($isLoggedIn): ?>
        <button class="btn btn-primary mb-3 annotation-form-toggle">
            <i class="fas fa-plus"></i> Add Annotation
        </button>
        
        <form class="annotation-form <?= empty($_GET['annotate']) ? 'hidden' : '' ?>" 
              action="<?= BASE_URL ?>/movie/add-annotation" method="POST">
            <input type="hidden" name="movie_id" value="<?= $movie['id'] ?>">
            
            <div class="form-group">
                <label for="annotation-timestamp">Timestamp (optional, in seconds)</label>
                <input type="number" name="timestamp" id="annotation-timestamp" 
                       class="form-control" min="0" step="1">
                <small class="form-text text-muted">
                    Add a timestamp to reference a specific scene (e.g., 3600 for 1:00:00)
                </small>
            </div>
            
            <div class="form-group">
                <label for="annotation-content">Your Annotation</label>
                <textarea name="content" id="annotation-content" 
                          class="form-control" rows="4" required></textarea>
            </div>
            
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" name="is_private" id="annotation-private" 
                           class="form-check-input" value="1">
                    <label for="annotation-private" class="form-check-label">
                        Private (only visible to you)
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Submit Annotation</button>
        </form>
    <?php endif; ?>
    
    <?php if (empty($annotations)): ?>
        <div class="empty-state">
            <p>No annotations yet. Be the first to add one!</p>
        </div>
    <?php else: ?>
        <?php if (!empty($movie['runtime'])): ?>
            <div class="timeline" data-duration="<?= $movie['runtime'] * 60 ?>">
                <!-- Timeline annotations will be added by JavaScript -->
            </div>
        <?php endif; ?>
        
        <div class="annotations">
            <?php foreach ($annotations as $annotation): ?>
                <div class="annotation" id="annotation-<?= $annotation['id'] ?>" 
                     data-timestamp="<?= $annotation['timestamp_seconds'] ?>">
                    <div class="annotation-header">
                        <div class="annotation-meta">
                            <div class="annotation-user">
                                <img src="<?= !empty($annotation['avatar']) ? $annotation['avatar'] : BASE_URL . '/img/default-avatar.jpg' ?>" 
                                     alt="<?= htmlspecialchars($annotation['username']) ?>" class="annotation-avatar">
                                <span class="annotation-username">
                                    <a href="<?= BASE_URL ?>/profile?username=<?= urlencode($annotation['username']) ?>">
                                        <?= htmlspecialchars($annotation['username']) ?>
                                    </a>
                                </span>
                            </div>
                            
                            <?php if (!empty($annotation['timestamp_seconds'])): ?>
                                <span class="annotation-timestamp" title="Jump to this scene">
                                    <i class="fas fa-clock"></i>
                                    <?= gmdate("H:i:s", $annotation['timestamp_seconds']) ?>
                                </span>
                            <?php endif; ?>
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
                            <a href="#" class="annotation-action like-button <?= $isLoggedIn && $annotation['has_liked'] ? 'active' : '' ?>"
                               data-annotation-id="<?= $annotation['id'] ?>"
                               data-url="<?= BASE_URL ?>/annotation/toggle-like"
                               data-authenticated="<?= $isLoggedIn ? 'true' : 'false' ?>"
                               data-login-url="<?= BASE_URL ?>/login">
                                <i class="<?= $isLoggedIn && $annotation['has_liked'] ? 'fas' : 'far' ?> fa-heart"></i>
                                <span id="like-count-<?= $annotation['id'] ?>"><?= $annotation['likes_count'] ?></span>
                            </a>
                            
                            <a href="#comments-<?= $annotation['id'] ?>" class="annotation-action toggle-comments">
                                <i class="far fa-comment"></i>
                                <span><?= $annotation['comments_count'] ?? 0 ?></span>
                            </a>
                            
                            <a href="<?= BASE_URL ?>/annotation?id=<?= $annotation['id'] ?>" class="annotation-action">
                                <i class="far fa-eye"></i>
                                <span>View</span>
                            </a>
                            
                            <?php if ($isLoggedIn && $currentUser['id'] == $annotation['user_id']): ?>
                                <a href="<?= BASE_URL ?>/annotation/edit?id=<?= $annotation['id'] ?>" class="annotation-action">
                                    <i class="far fa-edit"></i>
                                    <span>Edit</span>
                                </a>
                                
                                <a href="#" class="annotation-action text-danger"
                                   onclick="deleteAnnotation(<?= $annotation['id'] ?>, '<?= BASE_URL ?>/annotation/delete'); return false;">
                                    <i class="far fa-trash-alt"></i>
                                    <span>Delete</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="comments" id="comments-<?= $annotation['id'] ?>">
                        <?php
                        // Comments would be loaded via AJAX when clicking the "toggle-comments" button
                        ?>
                        
                        <?php if ($isLoggedIn): ?>
                            <form class="comment-form mt-3" action="<?= BASE_URL ?>/annotation/add-comment" method="POST">
                                <input type="hidden" name="annotation_id" value="<?= $annotation['id'] ?>">
                                
                                <div class="form-group">
                                    <textarea name="content" class="form-control" 
                                              placeholder="Add a comment..." rows="2" required></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-sm btn-primary">Submit</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
