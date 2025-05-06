<!-- src/Views/movies/show.php -->
<div class="movie-details">
    <div class="movie-poster">
        <img src="<?= !empty($movie['poster_path']) ? $movie['poster_path'] : BASE_URL . '/img/no-poster.jpg' ?>" 
             alt="<?= htmlspecialchars($movie['title']) ?>">
    </div>
    
    <div class="movie-info">
        <div class="movie-header">
            <h1 class="movie-title"><?= htmlspecialchars($movie['title']) ?></h1>
            <h3 class="movie-year"><?= $movie['release_year'] ?></h3>
            
            <?php if (!empty($movie['director'])): ?>
                <div class="movie-director">
                    <strong>Director:</strong> <?= htmlspecialchars($movie['director']) ?>
                </div>
            <?php endif; ?>
            
            <div class="movie-rating">
                <div class="rating-stars">
                    <?php
                    $avgRating = $avgRating['avg_rating'] ?? 0;
                    $fullStars = floor($avgRating / 2);
                    $halfStar = ($avgRating / 2) - $fullStars >= 0.5;
                    
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $fullStars) {
                            echo '<i class="fas fa-star"></i>';
                        } elseif ($i == $fullStars + 1 && $halfStar) {
                            echo '<i class="fas fa-star-half-alt"></i>';
                        } else {
                            echo '<i class="far fa-star"></i>';
                        }
                    }
                    ?>
                    <span class="avg-rating"><?= number_format($avgRating, 1) ?></span>
                    <span class="rating-count">(<?= $avgRating['rating_count'] ?? 0 ?> ratings)</span>
                </div>
                
                <?php if ($isLoggedIn): ?>
                    <form class="rating-form" action="<?= BASE_URL ?>/movie/rate" method="POST" data-ajax="true">
                        <input type="hidden" name="movie_id" value="<?= $movie['id'] ?>">
                        <input type="hidden" name="rating" value="<?= $userRating['rating'] ?? 0 ?>">
                        
                        <div class="user-rating">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <span class="star <?= ($userRating && $i <= $userRating['rating']) ? 'filled' : '' ?>" 
                                      data-value="<?= $i ?>">
                                    <i class="<?= ($userRating && $i <= $userRating['rating']) ? 'fas' : 'far' ?> fa-star"></i>
                                </span>
                            <?php endfor; ?>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="movie-actions">
            <?php if ($isLoggedIn): ?>
                <button class="btn <?= $isFavorite ? 'btn-danger' : 'btn-outline' ?> favorite-btn" 
                        onclick="toggleFavorite(<?= $movie['id'] ?>, '<?= BASE_URL ?>/movie/toggle-favorite')">
                    <i class="<?= $isFavorite ? 'fas' : 'far' ?> fa-heart"></i>
                    <span><?= $isFavorite ? 'Remove from Favorites' : 'Add to Favorites' ?></span>
                </button>
                
                <button class="btn btn-primary annotation-form-toggle">
                    <i class="fas fa-comment-alt"></i> Add Annotation
                </button>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($movie['overview'])): ?>
            <div class="movie-overview">
                <h3>Overview</h3>
                <p><?= nl2br(htmlspecialchars($movie['overview'])) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Annotation Form -->
<?php if ($isLoggedIn): ?>
    <div class="annotation-form hidden mt-4">
        <h3>Add Annotation</h3>
        
        <form action="<?= BASE_URL ?>/movie/add-annotation" method="POST">
            <input type="hidden" name="movie_id" value="<?= $movie['id'] ?>">
            
            <div class="form-group">
                <label for="timestamp">Timestamp (hh:mm:ss)</label>
                <input type="text" name="timestamp_display" id="timestamp_display" class="form-control" 
                       placeholder="00:00:00" pattern="[0-9]{2}:[0-9]{2}:[0-9]{2}">
                <input type="hidden" name="timestamp" id="timestamp" value="">
                <small class="form-text text-muted">Leave blank for general annotation</small>
            </div>
            
            <div class="form-group">
                <label for="content">Your Annotation</label>
                <textarea name="content" id="content" class="form-control" rows="4" required></textarea>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_private"> 
                    Private (only visible to you)
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary">Save Annotation</button>
            <button type="button" class="btn btn-outline annotation-form-toggle">Cancel</button>
        </form>
    </div>
<?php endif; ?>

<!-- Timeline -->
<div class="timeline-section mt-4">
    <h3>Scene Timeline</h3>
    
    <div class="timeline" data-duration="<?= $movie['runtime'] * 60 ?? 9000 ?>">
        <?php
        // Group annotations by timestamp for the tooltip
        $timestampGroups = [];
        foreach ($annotations as $annotation) {
            if ($annotation['timestamp_seconds']) {
                $key = $annotation['timestamp_seconds'];
                if (!isset($timestampGroups[$key])) {
                    $timestampGroups[$key] = [];
                }
                $timestampGroups[$key][] = $annotation;
            }
        }
        ?>
        
        <?php foreach ($timestampGroups as $timestamp => $annotationGroup): ?>
            <?php
            $position = ($timestamp / ($movie['runtime'] * 60 ?? 9000)) * 100;
            $tooltipContent = count($annotationGroup) . ' annotation' . (count($annotationGroup) > 1 ? 's' : '');
            $formattedTime = gmdate("H:i:s", $timestamp);
            ?>
            <div class="timeline-annotation" style="left: <?= $position ?>%;" 
                 title="<?= $formattedTime ?> - <?= $tooltipContent ?>"
                 data-timestamp="<?= $timestamp ?>">
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Annotations -->
<div class="annotations mt-4">
    <h3>Annotations</h3>
    
    <?php if (empty($annotations)): ?>
        <div class="empty-state">
            <p>No annotations yet. Be the first to add one!</p>
        </div>
    <?php else: ?>
        <!-- Filters -->
        <div class="annotation-filters mb-3">
            <button class="btn btn-sm btn-outline annotation-filter active" data-filter="all">All</button>
            <button class="btn btn-sm btn-outline annotation-filter" data-filter="timestamped">Timestamped</button>
            <button class="btn btn-sm btn-outline annotation-filter" data-filter="general">General</button>
        </div>
        
        <div class="annotation-list">
            <?php foreach ($annotations as $annotation): ?>
                <div class="annotation" id="annotation-<?= $annotation['id'] ?>" 
                     data-timestamp="<?= $annotation['timestamp_seconds'] ?>"
                     data-type="<?= $annotation['timestamp_seconds'] ? 'timestamped' : 'general' ?>">
                    <div class="annotation-header">
                        <div class="annotation-meta">
                            <div class="annotation-user">
                                <img src="<?= !empty($annotation['avatar']) ? $annotation['avatar'] : BASE_URL . '/img/default-avatar.jpg' ?>" 
                                     alt="<?= htmlspecialchars($annotation['username']) ?>" class="annotation-avatar">
                                <span class="annotation-username"><?= htmlspecialchars($annotation['username']) ?></span>
                            </div>
                            
                            <?php if ($annotation['timestamp_seconds']): ?>
                                <span class="annotation-timestamp">
                                    <i class="fas fa-clock"></i>
                                    <?= gmdate("H:i:s", $annotation['timestamp_seconds']) ?>
                                </span>
                            <?php else: ?>
                                <span class="annotation-timestamp">
                                    <i class="fas fa-comment"></i>
                                    General
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
                            <?php if ($isLoggedIn): ?>
                                <button class="annotation-action like-button <?= isset($annotation['has_liked']) && $annotation['has_liked'] ? 'active' : '' ?>"
                                        data-annotation-id="<?= $annotation['id'] ?>"
                                        data-url="<?= BASE_URL ?>/annotation/toggle-like"
                                        data-authenticated="true"
                                        data-login-url="<?= BASE_URL ?>/login">
                                    <i class="<?= isset($annotation['has_liked']) && $annotation['has_liked'] ? 'fas' : 'far' ?> fa-heart"></i>
                                    <span id="like-count-<?= $annotation['id'] ?>"><?= $annotation['likes_count'] ?></span>
                                </button>
                                
                                <button class="annotation-action comment-button" 
                                        data-toggle="collapse" 
                                        data-target="#comments-collapse-<?= $annotation['id'] ?>">
                                    <i class="far fa-comment"></i>
                                    <span id="comment-count-<?= $annotation['id'] ?>"><?= $annotation['comments_count'] ?? 0 ?></span>
                                </button>
                            <?php else: ?>
                                <span class="annotation-action">
                                    <i class="far fa-heart"></i>
                                    <span><?= $annotation['likes_count'] ?></span>
                                </span>
                                
                                <span class="annotation-action">
                                    <i class="far fa-comment"></i>
                                    <span><?= $annotation['comments_count'] ?? 0 ?></span>
                                </span>
                            <?php endif; ?>
                            
                            <a href="<?= BASE_URL ?>/annotation?id=<?= $annotation['id'] ?>" class="annotation-action">
                                <i class="far fa-eye"></i>
                                <span>View</span>
                            </a>
                            
                            <?php if ($isLoggedIn && Auth::userId() == $annotation['user_id']): ?>
                                <a href="<?= BASE_URL ?>/annotation/edit?id=<?= $annotation['id'] ?>" class="annotation-action">
                                    <i class="far fa-edit"></i>
                                    <span>Edit</span>
                                </a>
                                
                                <button class="annotation-action delete-annotation-btn" 
                                        data-annotation-id="<?= $annotation['id'] ?>"
                                        onclick="deleteAnnotation(<?= $annotation['id'] ?>, '<?= BASE_URL ?>/annotation/delete')">
                                    <i class="far fa-trash-alt"></i>
                                    <span>Delete</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Comments Collapse -->
                    <div class="collapse" id="comments-collapse-<?= $annotation['id'] ?>">
                        <div class="comments mt-3" id="comments-<?= $annotation['id'] ?>">
                            <!-- Comments will be loaded here -->
                        </div>
                        
                        <?php if ($isLoggedIn): ?>
                            <form class="comment-form mt-3" action="<?= BASE_URL ?>/annotation/add-comment" method="POST">
                                <input type="hidden" name="annotation_id" value="<?= $annotation['id'] ?>">
                                
                                <div class="form-group">
                                    <textarea name="content" class="form-control" rows="2" placeholder="Add a comment..." required></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-sm btn-primary">Post Comment</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize timestamp conversion
    const timestampDisplay = document.getElementById('timestamp_display');
    const timestampInput = document.getElementById('timestamp');
    
    if (timestampDisplay && timestampInput) {
        timestampDisplay.addEventListener('input', function() {
            // Convert HH:MM:SS to seconds
            const parts = this.value.split(':');
            if (parts.length === 3) {
                const hours = parseInt(parts[0]) || 0;
                const minutes = parseInt(parts[1]) || 0;
                const seconds = parseInt(parts[2]) || 0;
                
                const totalSeconds = (hours * 3600) + (minutes * 60) + seconds;
                timestampInput.value = totalSeconds;
            } else {
                timestampInput.value = '';
            }
        });
    }
    
    // Initialize annotation form toggle
    const formToggles = document.querySelectorAll('.annotation-form-toggle');
    const annotationForm = document.querySelector('.annotation-form');
    
    if (formToggles.length && annotationForm) {
        formToggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                annotationForm.classList.toggle('hidden');
                
                // Focus on form input after showing
                if (!annotationForm.classList.contains('hidden')) {
                    const contentInput = annotationForm.querySelector('textarea[name="content"]');
                    if (contentInput) {
                        contentInput.focus();
                    }
                }
            });
        });
    }
    
    // Initialize timeline annotations
    const timelineAnnotations = document.querySelectorAll('.timeline-annotation');
    
    timelineAnnotations.forEach(annotation => {
        annotation.addEventListener('click', function() {
            const timestamp = this.dataset.timestamp;
            
            // Scroll to annotations with this timestamp
            const annotationElements = document.querySelectorAll(`.annotation[data-timestamp="${timestamp}"]`);
            if (annotationElements.length) {
                annotationElements[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                annotationElements.forEach(el => {
                    el.classList.add('highlight');
                    setTimeout(() => {
                        el.classList.remove('highlight');
                    }, 2000);
                });
            }
            
            // Set the timestamp in the annotation form
            if (timestampDisplay && timestampInput) {
                const formattedTime = new Date(timestamp * 1000).toISOString().substr(11, 8);
                timestampDisplay.value = formattedTime;
                timestampInput.value = timestamp;
                
                // Show the annotation form
                if (annotationForm) {
                    annotationForm.classList.remove('hidden');
                }
            }
        });
    });
    
    // Initialize annotation filters
    const filters = document.querySelectorAll('.annotation-filter');
    const annotations = document.querySelectorAll('.annotation');
    
    filters.forEach(filter => {
        filter.addEventListener('click', function() {
            // Remove active class from all filters
            filters.forEach(f => f.classList.remove('active'));
            
            // Add active class to clicked filter
            this.classList.add('active');
            
            const filterType = this.dataset.filter;
            
            // Filter annotations
            annotations.forEach(annotation => {
                if (filterType === 'all') {
                    annotation.style.display = 'block';
                } else {
                    if (annotation.dataset.type === filterType) {
                        annotation.style.display = 'block';
                    } else {
                        annotation.style.display = 'none';
                    }
                }
            });
        });
    });
    
    // Initialize likes
    const likeButtons = document.querySelectorAll('.like-button');
    // Load and handle comments for an annotation
    likeButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (this.dataset.authenticated !== 'true') {
                window.location.href = this.dataset.loginUrl;
                return;
            }
            
            const annotationId = this.dataset.annotationId;
            const url = this.dataset.url;
            
            // Send AJAX request
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `annotation_id=${annotationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update like count
                    const likeCount = document.querySelector(`#like-count-${annotationId}`);
                    if (likeCount) {
                        likeCount.textContent = data.likeCount;
                    }
                    
                    // Toggle like button appearance
                    if (data.liked) {
                        this.classList.add('active');
                        this.querySelector('i').classList.remove('far');
                        this.querySelector('i').classList.add('fas');
                    } else {
                        this.classList.remove('active');
                        this.querySelector('i').classList.remove('fas');
                        this.querySelector('i').classList.add('far');
                    }
                    
                    // Show success toast
                    showToast('Success', data.message);
                } else {
                    // Show error toast
                    showToast('Error', data.errors.general || 'Failed to update like status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error', 'Failed to update like status. Please try again.');
            });
        });
    });

    // Initialize comment buttons
    const commentButtons = document.querySelectorAll('.comment-button');
    
    commentButtons.forEach(button => {
        button.addEventListener('click', function() {
            const annotationId = this.dataset.annotationId || this.closest('.annotation').id.replace('annotation-', '');
            const commentsContainer = document.querySelector(`#comments-${annotationId}`);
            
            // Toggle comments visibility
            const collapse = this.dataset.target;
            const collapseElement = document.querySelector(collapse);
            
            if (collapseElement) {
                const isHidden = collapseElement.classList.toggle('show');
                
                // Load comments if showing and not already loaded
                if (isHidden && commentsContainer && commentsContainer.childElementCount === 0) {
                    loadComments(annotationId, commentsContainer);
                }
            }
        });
    });
    
    // Load comments for an annotation
    function loadComments(annotationId, container) {
        container.innerHTML = '<div class="spinner"></div>';
        
        fetch(`<?= BASE_URL ?>/api/annotation-comments?annotation_id=${annotationId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.data.length === 0) {
                    container.innerHTML = '<p class="text-muted">No comments yet. Be the first to comment!</p>';
                } else {
                    container.innerHTML = '';
                    data.data.forEach(comment => {
                        container.appendChild(createCommentElement(comment));
                    });
                }
            } else {
                container.innerHTML = '<p class="text-danger">Failed to load comments.</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = '<p class="text-danger">Failed to load comments.</p>';
        });
    }
    
    // Create comment element
    function createCommentElement(comment) {
        const commentEl = document.createElement('div');
        commentEl.classList.add('comment');
        commentEl.id = `comment-${comment.id}`;
        
        const date = new Date(comment.created_at);
        const formattedDate = date.toLocaleString();
        
        commentEl.innerHTML = `
            <div class="comment-header">
                <div class="comment-user">
                    <img src="${comment.avatar || '<?= BASE_URL ?>/img/default-avatar.jpg'}" alt="${comment.username}" class="comment-avatar">
                    <span class="comment-username">${comment.username}</span>
                </div>
                <span class="comment-timestamp">${formattedDate}</span>
            </div>
            <div class="comment-content">
                ${comment.content}
            </div>
            ${comment.user_id === <?= Auth::userId() ?: 0 ?> ? `
                <div class="comment-actions">
                    <button class="delete-comment-btn" data-comment-id="${comment.id}" data-url="<?= BASE_URL ?>/annotation/delete-comment">
                        <i class="far fa-trash-alt"></i> Delete
                    </button>
                </div>
            ` : ''}
        `;
        
        // Add event listener for delete button
        const deleteBtn = commentEl.querySelector('.delete-comment-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function() {
                deleteComment(this.dataset.commentId, this.dataset.url);
            });
        }
        
        return commentEl;
    }
    
    // Comment form submission
    const commentForms = document.querySelectorAll('.comment-form');
    
    commentForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const annotationId = this.querySelector('input[name="annotation_id"]').value;
            const content = this.querySelector('textarea[name="content"]').value;
            const url = this.getAttribute('action');
            
            if (!content.trim()) {
                showToast('Error', 'Comment cannot be empty');
                return;
            }
            
            // Create form data
            const formData = new FormData();
            formData.append('annotation_id', annotationId);
            formData.append('content', content);
            
            // Send AJAX request
            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear form
                    this.querySelector('textarea[name="content"]').value = '';
                    
                    // Append new comment
                    const commentsContainer = document.querySelector(`#comments-${annotationId}`);
                    if (commentsContainer && data.comment) {
                        const commentEl = createCommentElement(data.comment);
                        commentsContainer.appendChild(commentEl);
                        
                        // Animate new comment
                        commentEl.classList.add('animate-fadeIn');
                    }
                    
                    // Update comment count
                    const commentCount = document.querySelector(`#comment-count-${annotationId}`);
                    if (commentCount) {
                        commentCount.textContent = parseInt(commentCount.textContent) + 1;
                    }
                    
                    // Show success toast
                    showToast('Success', data.message);
                } else {
                    // Show error toast
                    showToast('Error', data.errors.general || 'Failed to add comment');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error', 'Failed to add comment. Please try again.');
            });
        });
    });
    
    // Delete comment
    function deleteComment(commentId, url) {
        if (confirm('Are you sure you want to delete this comment?')) {
            // Send AJAX request
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `comment_id=${commentId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove comment from DOM
                    const comment = document.querySelector(`#comment-${commentId}`);
                    if (comment) {
                        comment.style.opacity = '0';
                        setTimeout(() => {
                            comment.remove();
                        }, 300);
                    }
                    
                    // Show success toast
                    showToast('Success', data.message);
                } else {
                    // Show error toast
                    showToast('Error', data.errors.general || 'Failed to delete comment');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error', 'Failed to delete comment. Please try again.');
            });
        }
    }
    
    // Delete annotation
    window.deleteAnnotation = function(annotationId, url) {
        if (confirm('Are you sure you want to delete this annotation?')) {
            // Send AJAX request
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `annotation_id=${annotationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove annotation from DOM
                    const annotation = document.querySelector(`#annotation-${annotationId}`);
                    if (annotation) {
                        annotation.style.opacity = '0';
                        setTimeout(() => {
                            annotation.remove();
                        }, 300);
                    }
                    
                    // Show success toast
                    showToast('Success', data.message);
                    
                    // Redirect if specified
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    }
                } else {
                    // Show error toast
                    showToast('Error', data.errors.general || 'Failed to delete annotation');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error', 'Failed to delete annotation. Please try again.');
            });
        }
    };
    
    // Helper function to show toast notifications
    function showToast(title, message) {
        // Create toast container if it doesn't exist
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.classList.add('toast-container');
            document.body.appendChild(toastContainer);
        }
        
        // Create toast element
        const toast = document.createElement('div');
        toast.classList.add('toast');
        toast.innerHTML = `
            <div class="toast-header">
                <span class="toast-title">${title}</span>
                <button class="toast-close">&times;</button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        `;
        
        // Add toast to container
        toastContainer.appendChild(toast);
        
        // Add close button functionality
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.addEventListener('click', () => {
            toast.style.opacity = '0';
            setTimeout(() => {
                toast.remove();
            }, 300);
        });
        
        // Auto-hide toast after 5 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 5000);
    }
});
</script>