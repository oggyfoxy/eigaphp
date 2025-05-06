<!-- src/Views/home/search.php -->
<h1>Explore</h1>

<form action="<?= BASE_URL ?>/search" method="get" class="flex space-between mt-20">
    <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Search movies…" style="flex:1; padding:8px">
    <button type="submit" class="btn" style="margin-left:10px">Search</button>
</form>

<?php if ($query === ''): ?>
    <p class="mt-20">Start typing above to discover movies.</p>
<?php elseif (empty($results)): ?>
    <p class="mt-20">No results for "<?= htmlspecialchars($query) ?>".</p>
<?php else: ?>
    <h2 class="mt-20">Results for "<?= htmlspecialchars($query) ?>"</h2>

    <div class="movie-grid mt-20" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(150px, 1fr)); gap:20px">
        <?php foreach ($results as $m): ?>
            <div class="movie-card">
                <a href="<?= BASE_URL ?>/movie?id=<?= $m['tmdb_id'] ?? $m['id'] ?>">
                    <img src="<?= !empty($m['poster_path']) ? (new \App\Services\TMDBApi())->getPosterUrl($m['poster_path'],'w185') : BASE_URL . '/img/no-poster.jpg' ?>"
                         alt="<?= htmlspecialchars($m['title']) ?>"
                         style="width:100%; height:auto; border-radius:4px">
                </a>
                <div style="margin-top:5px; font-size:14px">
                    <?= htmlspecialchars($m['title']) ?>
                    <?php if (!empty($m['release_year'])): ?>
                        (<?= $m['release_year'] ?>)
                    <?php endif; ?>
                </div>
                <?php if (isset($isLoggedIn) && $isLoggedIn): ?>
                    <button class="btn btn-sm add-to-collection-btn" 
                            data-movie-id="<?= $m['tmdb_id'] ?? $m['id'] ?>" 
                            data-movie-title="<?= htmlspecialchars($m['title']) ?>">
                        Add to Collection
                    </button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Add Collection Modal -->
    <div id="collectionModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div class="modal-content" style="background:white; margin:10% auto; width:90%; max-width:500px; padding:20px; border-radius:8px;">
            <div class="modal-header">
                <h4>Add to Collection</h4>
                <span class="close-modal" style="float:right; cursor:pointer; font-size:20px;">&times;</span>
            </div>
            <div class="modal-body">
                <p>Add "<span id="selectedMovieTitle"></span>" to collection:</p>
                
                <input type="hidden" id="selectedMovieId">
                
                <div class="form-group">
                    <label for="collectionSelect">Select Collection:</label>
                    <select id="collectionSelect" class="form-control">
                        <option value="">-- Select Collection --</option>
                        <?php
                        if (Auth::isLoggedIn()) {
                            $collections = (new \App\Models\Collection())->getUserCollections(Auth::userId(), true);
                            if ($collections) {
                                foreach ($collections as $collection): ?>
                                    <option value="<?= $collection['id'] ?>"><?= htmlspecialchars($collection['title']) ?></option>
                                <?php endforeach;
                            }
                        }
                        ?>
                        <option value="new">+ Create New Collection</option>
                    </select>
                </div>
                
                <div id="newCollectionForm" style="display:none; margin-top:15px;">
                    <div class="form-group">
                        <label for="newCollectionTitle">Collection Title:</label>
                        <input type="text" id="newCollectionTitle" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="newCollectionDescription">Description (optional):</label>
                        <textarea id="newCollectionDescription" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="newCollectionPrivate"> 
                            Private Collection
                        </label>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top:15px;">
                    <label for="movieNotes">Notes about this movie (optional):</label>
                    <textarea id="movieNotes" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer" style="margin-top:20px; text-align:right;">
                <button class="btn close-modal">Cancel</button>
                <button class="btn btn-primary" id="saveToCollection">Add to Collection</button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Open modal when clicking "Add to Collection"
        const addButtons = document.querySelectorAll('.add-to-collection-btn');
        const modal = document.getElementById('collectionModal');
        const closeModalBtns = document.querySelectorAll('.close-modal');
        
        addButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const movieId = this.getAttribute('data-movie-id');
                const movieTitle = this.getAttribute('data-movie-title');
                
                document.getElementById('selectedMovieId').value = movieId;
                document.getElementById('selectedMovieTitle').textContent = movieTitle;
                
                modal.style.display = 'block';
            });
        });
        
        // Close modal
        closeModalBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                modal.style.display = 'none';
                document.getElementById('newCollectionForm').style.display = 'none';
                document.getElementById('collectionSelect').value = '';
                document.getElementById('movieNotes').value = '';
            });
        });
        
        // Toggle new collection form
        document.getElementById('collectionSelect').addEventListener('change', function() {
            const newCollectionForm = document.getElementById('newCollectionForm');
            if (this.value === 'new') {
                newCollectionForm.style.display = 'block';
            } else {
                newCollectionForm.style.display = 'none';
            }
        });
        
        // Save to collection
        document.getElementById('saveToCollection').addEventListener('click', function() {
            const movieId = document.getElementById('selectedMovieId').value;
            const collectionSelect = document.getElementById('collectionSelect');
            const notes = document.getElementById('movieNotes').value;
            
            if (collectionSelect.value === '') {
                alert('Please select a collection');
                return;
            }
            
            if (collectionSelect.value === 'new') {
                // Create new collection
                const title = document.getElementById('newCollectionTitle').value;
                if (!title) {
                    alert('Please enter a title for the new collection');
                    return;
                }
                
                const description = document.getElementById('newCollectionDescription').value;
                const isPrivate = document.getElementById('newCollectionPrivate').checked;
                
                // First create collection
                fetch('<?= BASE_URL ?>/collection/create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `title=${encodeURIComponent(title)}&description=${encodeURIComponent(description)}&is_private=${isPrivate ? 1 : 0}`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to create collection');
                    }
                    return response.text();
                })
                .then(html => {
                    // Extract collection ID from redirect URL in the response
                    const match = html.match(/collection\?id=(\d+)/);
                    if (match && match[1]) {
                        const newCollectionId = match[1];
                        addMovieToCollection(newCollectionId, movieId, notes);
                    } else {
                        throw new Error('Could not determine new collection ID');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to create collection: ' + error.message);
                });
            } else {
                // Add to existing collection
                addMovieToCollection(collectionSelect.value, movieId, notes);
            }
        });
        
        function addMovieToCollection(collectionId, movieId, notes) {
            fetch('<?= BASE_URL ?>/collection/add-movie', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `collection_id=${collectionId}&movie_id=${movieId}&notes=${encodeURIComponent(notes || '')}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to add movie to collection');
                }
                return response.text();
            })
            .then(result => {
                alert('Movie added to collection successfully!');
                modal.style.display = 'none';
                
                // Reset form
                document.getElementById('newCollectionForm').style.display = 'none';
                document.getElementById('collectionSelect').value = '';
                document.getElementById('movieNotes').value = '';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to add movie to collection: ' + error.message);
            });
        }
    });
    </script>
<?php endif; ?>