<?php
namespace App\Controllers;

use App\Models\Collection;
use App\Models\Movie;
use App\Services\Auth;

/**
 * Controller for movie collection-related pages and actions
 */
class CollectionController extends BaseController {
    private $collectionModel;
    private $movieModel;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->collectionModel = new Collection();
        $this->movieModel = new Movie();
    }
    
    /**
     * Collection listing page
     */
    public function index() {
        $page = (int)($_GET['page'] ?? 1);
        $limit = 12;
        $offset = ($page - 1) * $limit;
        
        // Get popular collections
        $collections = $this->collectionModel->getPopularCollections($limit, $offset);
        
        $data = [
            'pageTitle' => 'Movie Collections',
            'flashMessages' => $this->getFlashMessages(),
            'collections' => $collections,
            'page' => $page,
            'limit' => $limit
        ];
        
        $this->render('collections/index', $data);
    }
    
    /**
     * Single collection details page
     */
    public function show($id = null) {
        if (!$id) {
            $id = $_GET['id'] ?? null;
        }
        
        if (!$id) {
            $this->notFound();
            return;
        }
        
        // Get collection data
        $collection = $this->collectionModel->getCollectionById($id);
        
        if (!$collection) {
            $this->notFound();
            return;
        }
        
        // If collection is private, check if the current user is the owner
        if ($collection['is_private'] && (!Auth::isLoggedIn() || Auth::userId() != $collection['user_id'])) {
            $this->setFlashMessage('error', 'This collection is private.');
            $this->redirect('collections');
            return;
        }
        
        // Get movies in collection
        $movies = $this->collectionModel->getCollectionMovies($id);
        
        // Check if current user is the owner
        $isOwner = Auth::isLoggedIn() && Auth::userId() == $collection['user_id'];
        
        $data = [
            'pageTitle' => $collection['title'],
            'flashMessages' => $this->getFlashMessages(),
            'collection' => $collection,
            'movies' => $movies,
            'isOwner' => $isOwner
        ];
        
        $this->render('collections/show', $data);
    }
    
    /**
     * Create new collection page
     */
    public function create() {
        // Check if user is logged in
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->getPostData(['title', 'description']);
            
            if (!empty($result['errors'])) {
                $data = [
                    'pageTitle' => 'Create Collection',
                    'flashMessages' => $this->getFlashMessages(),
                    'errors' => $result['errors'],
                    'formData' => $result['data']
                ];
                
                $this->render('collections/create', $data);
                return;
            }
            
            $title = $result['data']['title'];
            $description = $result['data']['description'];
            $isPrivate = isset($_POST['is_private']) ? (bool)$_POST['is_private'] : false;
            
            // Create collection
            $collectionId = $this->collectionModel->createCollection(
                Auth::userId(),
                $title,
                $description,
                $isPrivate
            );
            
            if ($collectionId) {
                $this->setFlashMessage('success', 'Collection created successfully!');
                $this->redirect("collection?id={$collectionId}");
            } else {
                $data = [
                    'pageTitle' => 'Create Collection',
                    'flashMessages' => $this->getFlashMessages(),
                    'errors' => ['general' => 'Failed to create collection.'],
                    'formData' => $result['data']
                ];
                
                $this->render('collections/create', $data);
            }
        } else {
            $data = [
                'pageTitle' => 'Create Collection',
                'flashMessages' => $this->getFlashMessages()
            ];
            
            $this->render('collections/create', $data);
        }
    }
    
    /**
     * Edit collection page
     */
    public function edit($id = null) {
        // Check if user is logged in
        $this->requireAuth();
        
        if (!$id) {
            $id = $_GET['id'] ?? null;
        }
        
        if (!$id) {
            $this->notFound();
            return;
        }
        
        // Get collection data
        $collection = $this->collectionModel->getCollectionById($id);
        
        if (!$collection) {
            $this->notFound();
            return;
        }
        
        // Check if user is the owner
        if (Auth::userId() != $collection['user_id']) {
            $this->setFlashMessage('error', 'You do not have permission to edit this collection.');
            $this->redirect("collection?id={$id}");
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->getPostData(['title', 'description']);
            
            if (!empty($result['errors'])) {
                $data = [
                    'pageTitle' => 'Edit Collection',
                    'flashMessages' => $this->getFlashMessages(),
                    'errors' => $result['errors'],
                    'collection' => $collection
                ];
                
                $this->render('collections/edit', $data);
                return;
            }
            
            $title = $result['data']['title'];
            $description = $result['data']['description'];
            $isPrivate = isset($_POST['is_private']) ? (bool)$_POST['is_private'] : false;
            
            // Update collection
            $success = $this->collectionModel->updateCollection(
                $id,
                Auth::userId(),
                [
                    'title' => $title,
                    'description' => $description,
                    'is_private' => $isPrivate
                ]
            );
            
            if ($success) {
                $this->setFlashMessage('success', 'Collection updated successfully!');
                $this->redirect("collection?id={$id}");
            } else {
                $data = [
                    'pageTitle' => 'Edit Collection',
                    'flashMessages' => $this->getFlashMessages(),
                    'errors' => ['general' => 'Failed to update collection.'],
                    'collection' => $collection
                ];
                
                $this->render('collections/edit', $data);
            }
        } else {
            $data = [
                'pageTitle' => 'Edit Collection',
                'flashMessages' => $this->getFlashMessages(),
                'collection' => $collection
            ];
            
            $this->render('collections/edit', $data);
        }
    }
    
    /**
     * Delete collection
     */
    public function delete() {
        // Check if user is logged in
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->getPostData(['collection_id']);
            
            if (!empty($result['errors'])) {
                $this->jsonResponse(['success' => false, 'errors' => $result['errors']], 400);
                return;
            }
            
            $collectionId = $result['data']['collection_id'];
            
            // Delete collection
            $success = $this->collectionModel->deleteCollection($collectionId, Auth::userId());
            
            if ($success) {
                $this->setFlashMessage('success', 'Collection deleted successfully.');
                $this->redirect('profile');
            } else {
                $this->setFlashMessage('error', 'Failed to delete collection.');
                $this->redirect("collection?id={$collectionId}");
            }
        } else {
            // Not a POST request, redirect to home
            $this->redirect('');
        }
    }
        /**
     * Add movie to collection
     */
    public function addMovie() {
        // Check if user is logged in
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $collectionId = $_POST['collection_id'] ?? null;
            $movieId = $_POST['movie_id'] ?? null;
            $notes = $_POST['notes'] ?? null;
            
            if (!$collectionId || !$movieId) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    $this->jsonResponse(['success' => false, 'errors' => [
                        'general' => 'Collection ID and Movie ID are required.'
                    ]], 400);
                } else {
                    $this->setFlashMessage('error', 'Collection ID and Movie ID are required.');
                    $this->redirect('');
                }
                return;
            }

            // Check if movie exists already in db by TMDB id
            $movie = $this->movieModel->getMovieByTmdbId($movieId);
            
            // If not, fetch from TMDB API and save
            if (!$movie) {
                $movieData = $this->tmdbApi->getMovie($movieId);
                if ($movieData) {
                    $movieId = $this->movieModel->saveMovie($movieData);
                    if (!$movieId) {
                        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                            $this->jsonResponse(['success' => false, 'errors' => [
                                'general' => 'Failed to save movie to database.'
                            ]], 500);
                        } else {
                            $this->setFlashMessage('error', 'Failed to save movie to database.');
                            $this->redirect("collection?id={$collectionId}");
                        }
                        return;
                    }
                } else {
                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                        $this->jsonResponse(['success' => false, 'errors' => [
                            'general' => 'Failed to fetch movie data from TMDB API.'
                        ]], 500);
                    } else {
                        $this->setFlashMessage('error', 'Failed to fetch movie data from TMDB API.');
                        $this->redirect("collection?id={$collectionId}");
                    }
                    return;
                }
            } else {
                $movieId = $movie['id'];
            }
            
            // Add movie to collection
            $success = $this->collectionModel->addMovieToCollection(
                $collectionId,
                Auth::userId(),
                $movieId,
                $notes
            );
            
            if ($success) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    $this->jsonResponse([
                        'success' => true,
                        'message' => 'Movie added to collection successfully!'
                    ]);
                } else {
                    $this->setFlashMessage('success', 'Movie added to collection successfully!');
                    $this->redirect("collection?id={$collectionId}");
                }
            } else {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    $this->jsonResponse([
                        'success' => false,
                        'errors' => ['general' => 'Failed to add movie to collection.']
                    ], 500);
                } else {
                    $this->setFlashMessage('error', 'Failed to add movie to collection.');
                    $this->redirect("collection?id={$collectionId}");
                }
            }
        } else {
            // Not a POST request, redirect to home
            $this->redirect('');
        }
}
    /**
     * Remove movie from collection
     */
    public function removeMovie() {
        // Check if user is logged in
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->getPostData(['collection_id', 'movie_id']);
            
            if (!empty($result['errors'])) {
                $this->jsonResponse(['success' => false, 'errors' => $result['errors']], 400);
                return;
            }
            
            $collectionId = $result['data']['collection_id'];
            $movieId = $result['data']['movie_id'];
            
            // Remove movie from collection
            $success = $this->collectionModel->removeMovieFromCollection(
                $collectionId,
                Auth::userId(),
                $movieId
            );
            
            if ($success) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Movie removed from collection successfully!'
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'errors' => ['general' => 'Failed to remove movie from collection.']
                ], 500);
            }
        } else {
            // Not a POST request, return error
            $this->jsonResponse([
                'success' => false,
                'errors' => ['method' => 'Invalid request method.']
            ], 405);
        }
    }
    
    /**
     * Update movie position in collection
     */
    public function updateMoviePosition() {
        // Check if user is logged in
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->getPostData(['collection_id', 'movie_id', 'position']);
            
            if (!empty($result['errors'])) {
                $this->jsonResponse(['success' => false, 'errors' => $result['errors']], 400);
                return;
            }
            
            $collectionId = $result['data']['collection_id'];
            $movieId = $result['data']['movie_id'];
            $position = (int)$result['data']['position'];
            
            // Update movie position
            $success = $this->collectionModel->updateMoviePosition(
                $collectionId,
                Auth::userId(),
                $movieId,
                $position
            );
            
            if ($success) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Movie position updated successfully!'
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'errors' => ['general' => 'Failed to update movie position.']
                ], 500);
            }
        } else {
            // Not a POST request, return error
            $this->jsonResponse([
                'success' => false,
                'errors' => ['method' => 'Invalid request method.']
            ], 405);
        }
    }


    /**
     * Create collection (AJAX version)
     */
    public function createAjax() {
        // Check if user is logged in
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->getPostData(['title', 'description']);
            
            if (!empty($result['errors'])) {
                $this->jsonResponse(['success' => false, 'errors' => $result['errors']], 400);
                return;
            }
            
            $title = $result['data']['title'];
            $description = $result['data']['description'];
            $isPrivate = isset($_POST['is_private']) ? (bool)$_POST['is_private'] : false;
            
            // Create collection
            $collectionId = $this->collectionModel->createCollection(
                Auth::userId(),
                $title,
                $description,
                $isPrivate
            );
            
            if ($collectionId) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Collection created successfully!',
                    'collection_id' => $collectionId
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'errors' => ['general' => 'Failed to create collection.']
                ], 500);
            }
        } else {
            // Not a POST request, return error
            $this->jsonResponse([
                'success' => false,
                'errors' => ['method' => 'Invalid request method.']
            ], 405);
        }
    }
}