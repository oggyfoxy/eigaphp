/**
 * eiganights - Collaborative Movie Annotation Platform
 * Main JavaScript file
 */

// Document ready function
document.addEventListener('DOMContentLoaded', function() {
    // Initialize mobile menu
    initMobileMenu();
    
    // Initialize dark mode toggle
    initThemeToggle();
    
    // Initialize flash message auto-hide
    initFlashMessages();
    
    // Initialize movie rating system if on movie page
    if (document.querySelector('.rating-container')) {
      initRatingSystem();
    }
    
    // Initialize annotation timeline if on movie page
    if (document.querySelector('.timeline')) {
      initAnnotationTimeline();
    }
    
    // Initialize annotation form toggle
    if (document.querySelector('.annotation-form-toggle')) {
      initAnnotationForm();
    }
    
    // Initialize likes and comments functionality
    initSocialInteractions();
    
    // Initialize collections drag and drop if on collections page
    if (document.querySelector('.collection-items')) {
      initCollectionDragDrop();
    }
  });
  
  /**
   * Initialize mobile menu functionality
   */
  function initMobileMenu() {
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    
    if (menuToggle && navLinks) {
      menuToggle.addEventListener('click', function() {
        navLinks.classList.toggle('active');
      });
      
      // Close menu when clicking outside
      document.addEventListener('click', function(event) {
        if (!navLinks.contains(event.target) && !menuToggle.contains(event.target)) {
          navLinks.classList.remove('active');
        }
      });
    }
  }
  
  /**
   * Initialize theme toggle (dark/light mode)
   */
  function initThemeToggle() {
    const themeToggle = document.querySelector('.theme-toggle');
    
    if (themeToggle) {
      // Check for saved theme preference or use preferred color scheme
      const savedTheme = localStorage.getItem('theme');
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      
      if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
        document.body.classList.add('dark-mode');
        themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
      } else {
        themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
      }
      
      // Theme toggle click handler
      themeToggle.addEventListener('click', function() {
        document.body.classList.toggle('dark-mode');
        
        if (document.body.classList.contains('dark-mode')) {
          localStorage.setItem('theme', 'dark');
          themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        } else {
          localStorage.setItem('theme', 'light');
          themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
        }
      });
    }
  }
  
  /**
   * Initialize auto-hide for flash messages
   */
  function initFlashMessages() {
    const flashMessages = document.querySelectorAll('.flash-message');
    
    flashMessages.forEach(message => {
      // Auto-hide flash messages after 5 seconds
      setTimeout(() => {
        message.style.opacity = '0';
        setTimeout(() => {
          message.style.display = 'none';
        }, 300);
      }, 5000);
      
      // Add close button functionality
      const closeBtn = message.querySelector('.close-btn');
      if (closeBtn) {
        closeBtn.addEventListener('click', () => {
          message.style.opacity = '0';
          setTimeout(() => {
            message.style.display = 'none';
          }, 300);
        });
      }
    });
  }
  
  /**
   * Initialize movie rating system
   */
  function initRatingSystem() {
    const stars = document.querySelectorAll('.star');
    const ratingInput = document.querySelector('input[name="rating"]');
    const ratingForm = document.querySelector('.rating-form');
    
    if (stars.length && ratingInput) {
      // Set initial rating if user has already rated
      const initialRating = parseFloat(ratingInput.value) || 0;
      updateStars(initialRating);
      
      // Star hover effect
      stars.forEach(star => {
        const value = parseFloat(star.dataset.value);
        
        star.addEventListener('mouseover', () => {
          updateStars(value);
        });
        
        star.addEventListener('mouseout', () => {
          const currentRating = parseFloat(ratingInput.value) || 0;
          updateStars(currentRating);
        });
        
        star.addEventListener('click', () => {
          ratingInput.value = value;
          
          // If AJAX rating enabled, submit form
          if (ratingForm && ratingForm.dataset.ajax === 'true') {
            submitRatingForm(ratingForm, value);
          }
        });
      });
    }
    
    /**
     * Update star display based on rating value
     */
    function updateStars(rating) {
      stars.forEach(star => {
        const value = parseFloat(star.dataset.value);
        if (value <= rating) {
          star.classList.add('filled');
        } else {
          star.classList.remove('filled');
        }
      });
    }
    
    /**
     * Submit rating form via AJAX
     */
    function submitRatingForm(form, rating) {
      const movieId = form.querySelector('input[name="movie_id"]').value;
      const url = form.getAttribute('action');
      
      // Create form data
      const formData = new FormData();
      formData.append('movie_id', movieId);
      formData.append('rating', rating);
      
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
          // Update average rating display
          const avgRatingEl = document.querySelector('.avg-rating');
          const ratingCountEl = document.querySelector('.rating-count');
          
          if (avgRatingEl) {
            avgRatingEl.textContent = parseFloat(data.avgRating).toFixed(1);
          }
          
          if (ratingCountEl) {
            ratingCountEl.textContent = data.ratingCount;
          }
          
          // Show success toast
          showToast('Rating Submitted', data.message);
        } else {
          // Show error toast
          showToast('Error', data.errors.general || 'Failed to submit rating');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showToast('Error', 'Failed to submit rating. Please try again.');
      });
    }
  }
  
  /**
   * Initialize annotation timeline
   */
  function initAnnotationTimeline() {
    const timeline = document.querySelector('.timeline');
    const annotations = document.querySelectorAll('.annotation');
    
    if (timeline && annotations.length) {
      // Plot annotations on timeline based on timestamp
      annotations.forEach(annotation => {
        const timestamp = parseInt(annotation.dataset.timestamp);
        if (!isNaN(timestamp)) {
          const duration = parseInt(timeline.dataset.duration) || 9000; // Movie duration in seconds
          const position = (timestamp / duration) * 100; // Position as percentage
          
          // Create timeline marker
          const marker = document.createElement('div');
          marker.classList.add('timeline-annotation');
          marker.style.left = `${position}%`;
          marker.style.top = '50%';
          marker.dataset.annotationId = annotation.id;
          marker.title = `${formatTimestamp(timestamp)} - ${annotation.querySelector('.annotation-username').textContent}`;
          
          // Add click event to scroll to annotation
          marker.addEventListener('click', () => {
            annotation.scrollIntoView({ behavior: 'smooth', block: 'center' });
            annotation.classList.add('highlight');
            setTimeout(() => {
              annotation.classList.remove('highlight');
            }, 2000);
          });
          
          timeline.appendChild(marker);
        }
      });
      
      // Add current position marker if video player exists
      const videoPlayer = document.querySelector('.movie-player');
      if (videoPlayer) {
        const marker = document.createElement('div');
        marker.classList.add('timeline-marker');
        timeline.appendChild(marker);
        
        videoPlayer.addEventListener('timeupdate', () => {
          const duration = videoPlayer.duration;
          const currentTime = videoPlayer.currentTime;
          const position = (currentTime / duration) * 100;
          marker.style.left = `${position}%`;
        });
      }
    }
    
    /**
     * Format seconds into HH:MM:SS
     */
    function formatTimestamp(seconds) {
      const hours = Math.floor(seconds / 3600);
      const minutes = Math.floor((seconds % 3600) / 60);
      const secs = seconds % 60;
      
      return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
  }
  
  /**
   * Initialize annotation form toggle
   */
  function initAnnotationForm() {
    const formToggle = document.querySelector('.annotation-form-toggle');
    const annotationForm = document.querySelector('.annotation-form');
    
    if (formToggle && annotationForm) {
      formToggle.addEventListener('click', function(e) {
        e.preventDefault();
        annotationForm.classList.toggle('hidden');
        
        // Focus on form input after showing
        if (!annotationForm.classList.contains('hidden')) {
          const contentInput = annotationForm.querySelector('textarea[name="content"]');
          if (contentInput) {
            contentInput.focus();
          }
        }
      });
    }
  }
  
  /**
   * Initialize social interactions (likes, comments)
   */
  function initSocialInteractions() {
    // Like/Unlike functionality
    const likeButtons = document.querySelectorAll('.like-button');
    likeButtons.forEach(button => {
      button.addEventListener('click', function(e) {
        e.preventDefault();
        
        const annotationId = this.dataset.annotationId;
        const url = this.dataset.url;
        const isAuthenticated = this.dataset.authenticated === 'true';
        
        if (!isAuthenticated) {
          // Redirect to login if not authenticated
          window.location.href = this.dataset.loginUrl;
          return;
        }
        
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
              const commentHtml = createCommentElement(data.comment);
              commentsContainer.insertAdjacentHTML('beforeend', commentHtml);
              
              // Animate new comment
              const newComment = commentsContainer.lastElementChild;
              newComment.classList.add('animate-fadeIn');
            }
            
            // Show success toast
            showToast('Comment Added', data.message);
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
    
    // Comment delete functionality
    document.addEventListener('click', function(e) {
      if (e.target.classList.contains('delete-comment-btn') || e.target.closest('.delete-comment-btn')) {
        e.preventDefault();
        
        const btn = e.target.classList.contains('delete-comment-btn') ? 
                    e.target : e.target.closest('.delete-comment-btn');
        
        const commentId = btn.dataset.commentId;
        const url = btn.dataset.url;
        
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
              showToast('Comment Deleted', data.message);
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
    });
    
    /**
     * Create comment HTML element
     */
    function createCommentElement(comment) {
      const date = new Date(comment.created_at);
      const formattedDate = date.toLocaleString();
      
      return `
        <div class="comment" id="comment-${comment.id}">
          <div class="comment-header">
            <div class="comment-user">
              <img src="${comment.avatar || '/img/default-avatar.jpg'}" alt="${comment.username}" class="comment-avatar">
              <span class="comment-username">${comment.username}</span>
            </div>
            <span class="comment-timestamp">${formattedDate}</span>
          </div>
          <div class="comment-content">
            ${comment.content}
          </div>
          ${comment.user_id === currentUserId ? `
            <div class="comment-actions">
              <button class="delete-comment-btn" data-comment-id="${comment.id}" data-url="/annotation/delete-comment">
                <i class="far fa-trash-alt"></i> Delete
              </button>
            </div>
          ` : ''}
        </div>
      `;
    }
  }
  
  /**
   * Initialize collection drag and drop
   */
  function initCollectionDragDrop() {
    const collectionItems = document.querySelector('.collection-items');
    
    if (collectionItems && window.Sortable) {
      // Initialize Sortable.js
      const sortable = new Sortable(collectionItems, {
        animation: 150,
        handle: '.drag-handle',
        ghostClass: 'sortable-ghost',
        onEnd: function(evt) {
          updateMoviePositions();
        }
      });
      
      /**
       * Update movie positions after drag and drop
       */
      function updateMoviePositions() {
        const items = collectionItems.querySelectorAll('.collection-item');
        const collectionId = collectionItems.dataset.collectionId;
        const updateUrl = collectionItems.dataset.updateUrl;
        
        // Create array of position updates
        const positions = [];
        items.forEach((item, index) => {
          positions.push({
            movieId: item.dataset.movieId,
            position: index
          });
        });
        
        // Send AJAX request to update positions
        fetch(updateUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            collection_id: collectionId,
            positions: positions
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showToast('Success', 'Collection order updated');
          } else {
            showToast('Error', data.errors.general || 'Failed to update collection order');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showToast('Error', 'Failed to update collection order. Please try again.');
        });
      }
    }
  }
  
  /**
   * Show toast notification
   */
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
  
  /**
   * Toggle favorite movie status
   */
  function toggleFavorite(movieId, url) {
    // Send AJAX request
    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: `movie_id=${movieId}`
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Toggle favorite button appearance
        const favBtn = document.querySelector('.favorite-btn');
        if (favBtn) {
          if (data.isFavorite) {
            favBtn.classList.add('active');
            favBtn.querySelector('i').classList.remove('far');
            favBtn.querySelector('i').classList.add('fas');
            favBtn.querySelector('span').textContent = 'Remove from Favorites';
          } else {
            favBtn.classList.remove('active');
            favBtn.querySelector('i').classList.remove('fas');
            favBtn.querySelector('i').classList.add('far');
            favBtn.querySelector('span').textContent = 'Add to Favorites';
          }
        }
        
        // Show success toast
        showToast('Success', data.message);
      } else {
        // Show error toast
        showToast('Error', data.errors.general || 'Failed to update favorite status');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showToast('Error', 'Failed to update favorite status. Please try again.');
    });
  }
  
  /**
   * Toggle follow user status
   */
  function toggleFollow(userId, url) {
    // Send AJAX request
    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: `user_id=${userId}`
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Toggle follow button appearance
        const followBtn = document.querySelector('.follow-btn');
        if (followBtn) {
          if (data.isFollowing) {
            followBtn.classList.add('btn-outline');
            followBtn.classList.remove('btn-primary');
            followBtn.querySelector('span').textContent = 'Unfollow';
          } else {
            followBtn.classList.remove('btn-outline');
            followBtn.classList.add('btn-primary');
            followBtn.querySelector('span').textContent = 'Follow';
          }
        }
        
        // Show success toast
        showToast('Success', data.message);
      } else {
        // Show error toast
        showToast('Error', data.errors.general || 'Failed to update follow status');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showToast('Error', 'Failed to update follow status. Please try again.');
    });
  }
  
  /**
   * Search movie API
   */
  function searchMovies(query, url, resultContainerId) {
    const resultsContainer = document.getElementById(resultContainerId);
    
    if (!query.trim()) {
      resultsContainer.innerHTML = '';
      return;
    }
    
    // Show loading spinner
    resultsContainer.innerHTML = '<div class="spinner"></div>';
    
    // Send AJAX request
    fetch(`${url}?q=${encodeURIComponent(query)}`, {
      method: 'GET',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Generate results HTML
        let html = '';
        
        if (data.data.db_results.length > 0) {
          html += '<h3>From Our Database</h3>';
          html += '<div class="grid">';
          
          data.data.db_results.forEach(movie => {
            html += createMovieCard(movie, true);
          });
          
          html += '</div>';
        }
        
        if (data.data.api_results.length > 0) {
          html += '<h3>From TMDB</h3>';
          html += '<div class="grid">';
          
          data.data.api_results.forEach(movie => {
            html += createMovieCard(movie, false);
          });
          
          html += '</div>';
        }
        
        if (data.data.db_results.length === 0 && data.data.api_results.length === 0) {
          html = '<div class="text-center"><p>No results found</p></div>';
        }
        
        // Update results container
        resultsContainer.innerHTML = html;
      } else {
        // Show error message
        resultsContainer.innerHTML = `<div class="text-center"><p>Error: ${data.error || 'Failed to search movies'}</p></div>`;
      }
    })
    .catch(error => {
      console.error('Error:', error);
      resultsContainer.innerHTML = '<div class="text-center"><p>Error: Failed to search movies. Please try again.</p></div>';
    });
    
    /**
     * Create movie card HTML
     */
    function createMovieCard(movie, isDbMovie) {
      const posterUrl = movie.poster_path ? 
                       (isDbMovie ? movie.poster_path : `https://image.tmdb.org/t/p/w500${movie.poster_path}`) : 
                       '/img/no-poster.jpg';
      
      const movieUrl = isDbMovie ? 
                      `/movie?id=${movie.id}` : 
                      `/movie/add?tmdb_id=${movie.tmdb_id || movie.id}`;
      
      return `
        <div class="card">
          <img src="${posterUrl}" alt="${movie.title}" class="card-img">
          <div class="card-body">
            <h5 class="card-title">${movie.title}</h5>
            <p class="card-text">${movie.release_year || (movie.release_date ? movie.release_date.substring(0, 4) : 'N/A')}</p>
            <a href="${movieUrl}" class="btn btn-primary btn-sm">${isDbMovie ? 'View' : 'Add to Database'}</a>
          </div>
        </div>
      `;
    }
  }
  
  /**
   * Delete annotation
   */
  function deleteAnnotation(annotationId, url) {
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
  }