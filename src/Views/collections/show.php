<!-- collections/show.php view -->
<!-- File path: src/Views/collections/show.php -->
<div class="collection-header">
    <h1 class="collection-title"><?= htmlspecialchars($collection['title']) ?></h1>
    
    <div class="collection-meta">
        <div class="collection-user">
            <img src="<?= !empty($collection['avatar']) ? $collection['avatar'] : BASE_URL . '/img/default-avatar.jpg' ?>" 
                 alt="<?= htmlspecialchars($collection['username']) ?>" class="avatar avatar-sm">
            <span>
                <a href="<?= BASE_URL ?>/profile?username=<?= urlencode($collection['username']) ?>">
                    <?= htmlspecialchars($collection['username']) ?>
                </a>
            </span>
        </div>
        
        <span class="collection-date">
            <i class="far fa-calendar-alt"></i>
            <?= date('M j, Y', strtotime($collection['created_at'])) ?>
        </span>
        
        <span class="collection-visibility">
            <i class="fas <?= $collection['is_private'] ? 'fa-lock' : 'fa-globe' ?>"></i>
            <?= $collection['is_private'] ? 'Private' : 'Public' ?>
        </span>
    </div>
    
    <?php if (!empty($collection['description'])): ?>
        <div class="collection-description">
            <p><?= nl2br(htmlspecialchars($collection['description'])) ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($isOwner): ?>
        <div class="collection-actions">
            <a href="<?= BASE_URL ?>/collection/edit?id=<?= $collection['id'] ?>" class="btn btn-outline">
                <i class="far fa-edit"></i> Edit Collection
            </a>
            
            <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteModal">
                <i class="far fa-trash-alt"></i> Delete Collection
            </button>
        </div>
    <?php endif; ?>
</div>

<?php if (empty($movies)): ?>
    <div class="empty-state">
        <p>No movies in this collection yet.</p>
        <?php if ($isOwner): ?>
            <a href="<?= BASE_URL ?>/movies" class="btn btn-primary mt-3">
                <i class="fas fa-plus"></i> Add Movies
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="collection-items" data-collection-id="<?= $collection['id'] ?>" 
         data-update-url="<?= BASE_URL ?>/collection/update-movie-position">
        <?php foreach ($movies as $movie): ?>
            <div class="collection-item" data-movie-id="<?= $movie['movie_id'] ?>">
                <div class="item-handle <?= $isOwner ? 'drag-handle' : '' ?>">
                    <?php if ($isOwner): ?>
                        <i class="fas fa-grip-vertical"></i>
                    <?php else: ?>
                        <span class="item-number"><?= $movie['position'] + 1 ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="item-poster">
                    <img src="<?= !empty($movie['poster_path']) ? $movie['poster_path'] : BASE_URL . '/img/no-poster.jpg' ?>" 
                         alt="<?= htmlspecialchars($movie['title']) ?>">
                </div>
                
                <div class="item-info">
                    <h4 class="item-title">
                        <a href="<?= BASE_URL ?>/movie?id=<?= $movie['movie_id'] ?>">
                            <?= htmlspecialchars($movie['title']) ?> (<?= $movie['release_year'] ?>)
                        </a>
                    </h4>
                    
                    <?php if (!empty($movie['director'])): ?>
                        <div class="item-director">
                            <strong>Director:</strong> <?= htmlspecialchars($movie['director']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($movie['notes'])): ?>
                        <div class="item-notes">
                            <strong>Notes:</strong> <?= nl2br(htmlspecialchars($movie['notes'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($isOwner): ?>
                    <div class="item-actions">
                        <button type="button" class="btn btn-sm btn-outline edit-notes-btn" 
                                data-movie-id="<?= $movie['movie_id'] ?>" 
                                data-notes="<?= htmlspecialchars($movie['notes'] ?? '') ?>">
                            <i class="far fa-edit"></i>
                        </button>
                        
                        <button type="button" class="btn btn-sm btn-danger remove-movie-btn" 
                                data-movie-id="<?= $movie['movie_id'] ?>" 
                                data-url="<?= BASE_URL ?>/collection/remove-movie">
                            <i class="far fa-trash-alt"></i>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Delete Collection Modal -->
<?php if ($isOwner): ?>
<div class="modal" id="deleteModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Delete Collection</h5>
            <button class="close-btn" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this collection? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" data-dismiss="modal">Cancel</button>
            <form action="<?= BASE_URL ?>/collection/delete" method="POST">
                <input type="hidden" name="collection_id" value="<?= $collection['id'] ?>">
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        </div>
    </div>
</div>

<!-- Edit Notes Modal -->
<div class="modal" id="editNotesModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Edit Notes</h5>
            <button class="close-btn" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editNotesForm" action="<?= BASE_URL ?>/collection/update-notes" method="POST">
                <input type="hidden" name="collection_id" value="<?= $collection['id'] ?>">
                <input type="hidden" name="movie_id" id="edit-notes-movie-id">
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea name="notes" id="edit-notes-content" class="form-control" rows="4"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Save Notes</button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>