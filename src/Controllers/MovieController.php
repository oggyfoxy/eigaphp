<?php
namespace App\Controllers;

use App\Models\Movie;
use App\Services\Auth;
use App\Services\TMDBApi;

/**
 * Controller for movie-related pages and actions
 */
class MovieController extends BaseController {
    private $movieModel;
    private $tmdbApi;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->movieModel = new Movie();
        $this->tmdbApi = new TMDBApi();
    }
    
    /**
     * Movie listing page
     */
    public function index() {
        $page = (int)($_GET['page'] ?? 1);
        $limit = 12;
        $offset = ($page - 1) * $limit;
        
        // Get popular movies
        $popularMovies = $this->movieModel->getPopularMovies($limit, $offset);
        
        $data = [
            'pageTitle' => 'Explore Movies',
            'flashMessages' => $this->getFlashMessages(),
            'movies' => $popularMovies,
            'page' => $page,
            'limit' => $limit
        ];
        
        $this->render('movies/index', $data);
    }
    
    /**
     * Single movie details page
     */
    public function show($id = null) {
        if (!$id) {
            $id = $_GET['id'] ?? null;
        }
        
        if (!$id) {
            $this->notFound();
            return;
        }
        
        // Get movie data
        $movie = $this->movieModel->getMovieById($id);
        
        if (!$movie) {
            $this->notFound();
            return;
        }
        
        // Get movie annotations
        $annotations = $this->movieModel->getMovieAnnotations($id, 50);
        
        // Get average rating
        $avgRating = $this->movieModel->getAverageRating($id);
        
        // Get user's rating if logged in
        $userRating = null;
        if (Auth::isLoggedIn()) {
            $userRating = $this->movieModel->getUserRating(Auth::userId(), $id);
        }
        
        // Check if movie is in user's favorites
        $isFavorite = false;
        if (Auth::isLoggedIn()) {
            $isFavorite = $this->movieModel->isInFavorites(Auth::userId(), $id);
        }
        
        $data = [
            'pageTitle' => $movie['title'] . ' (' . $movie['release_year'] . ')',
            'flashMessages' => $this->getFlashMessages(),
            'movie' => $movie,
            'annotations' => $annotations,
            'avgRating' => $avgRating,
            'userRating' => $userRating,
            'isFavorite' => $isFavorite
        ];
        
        $this->render('movies/show', $data);
    }
    
    /**
     * Add a movie to the database
     */
    public function add() {
        // Check if user is logged in
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->getPostData(['tmdb_id']);
            
            if (!empty($result['errors'])) {
                $data = [
                    'pageTitle' => 'Add Movie',
                    'flashMessages' => $this->getFlashMessages(),
                    'errors' => $result['errors']
                ];
                
                $this->render('movies/add', $data);
                return;
            }
            
            $tmdbId = $result['data']['tmdb_id'];
            
            // Check if movie already exists in database
            $existingMovie = $this->movieModel->getMovieByTmdbId($tmdbId);
            
            if ($existingMovie) {
                // Redirect to existing movie
                $this->redirect("movie?id={$existingMovie['id']}");
                return;
            }
            
            // Fetch movie data from TMDB API
            $movieData = $this->tmdbApi->getMovie($tmdbId);
            
            if (!$movieData) {
                $data = [
                    'pageTitle' => 'Add Movie',
                    'flashMessages' => $this->getFlashMessages(),
                    'errors' => ['tmdb_id' => 'Could not fetch movie data from TMDB API.']
                ];
                
                $this->render('movies/add', $data);
                return;
            }
            
            // Save movie to database
            $movieId = $this->movieModel->saveMovie($movieData);
            
            if ($movieId) {
                $this->setFlashMessage('success', 'Movie added successfully!');
                $this->redirect("movie?id={$movieId}");
            } else {
                $data = [
                    'pageTitle' => 'Add Movie',
                    'flashMessages' => $this->getFlashMessages(),
                    'errors' => ['general' => 'Failed to save movie to database.']
                ];
                
                $this->render('movies/add', $data);
            }
        } else {
            $data = [
                'pageTitle' => 'Add Movie',
                'flashMessages' => $this->getFlashMessages()
            ];
            
            $this->render('movies/add', $data);
        }
    }
    
    /**
     * Search for movies via TMDB API
     */
    public function search() {
        // Check if user is logged in
        $this->requireAuth();
        
        $query = $_GET['q'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        
        $results = [];
        
        if (!empty($query)) {
            $results = $this->tmdbApi->searchMovies($query, $page);
        }
        
        $data = [
            'pageTitle' => 'Search for Movies',
            'flashMessages' => $this->getFlashMessages(),
            'query' => $query,
            'results' => $results,
            'page' => $page
        ];
        
        $this->render('movies/search', $data);
    }
    
    /**
     * Add annotation to a movie
     */
    public function addAnnotation() {
        // Check if user is logged in
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->getPostData(['movie_id', 'content', 'timestamp']);
            
            if (!empty($result['errors'])) {
                $this->setFlashMessage('error', 'Please fill in all required fields.');
                $this->redirect("movie?id={$result['data']['movie_id']}");
                return;
            }
            
            $movieId = $result['data']['movie_id'];
            $content = $result['data']['content'];
            $timestamp = !empty($result['data']['timestamp']) ? (int)$result['data']['timestamp'] : null;
            $isPrivate = isset($_POST['is_private']) ? (bool)$_POST['is_private'] : false;
            
            // Add annotation
            $annotationId = $this->movieModel->addAnnotation(
                Auth::userId(),
                $movieId,
                $content,
                $timestamp,
                $isPrivate
            );
            
            if ($annotationId) {
                $this->setFlashMessage('success', 'Annotation added successfully!');
            } else {
                $this->setFlashMessage('error', 'Failed to add annotation.');
            }
            
            $this->redirect("movie?id={$movieId}");
        } else {
            // Not a POST request, redirect to home
            $this->redirect('');
        }
    }
    
    /**
     * Rate a movie
     */
    public function rate() {
        // Check if user is logged in
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->getPostData(['movie_id', 'rating']);
            
            if (!empty($result['errors'])) {
                $this->jsonResponse(['success' => false, 'errors' => $result['errors']], 400);
                return;
            }
            
            $movieId = $result['data']['movie_id'];
            $rating = (float)$result['data']['rating'];
            
            // Validate rating (0-10)
            if ($rating < 0 || $rating > 10) {
                $this->jsonResponse(['success' => false, 'errors' => ['rating' => 'Rating must be between 0 and 10.']], 400);
                return;
            }
            
            // Add rating
            $ratingId = $this->movieModel->rateMovie(Auth::userId(), $movieId, $rating);
            
            if ($ratingId) {
                // Get updated average rating
                $avgRating = $this->movieModel->getAverageRating($movieId);
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Rating added successfully!',
                    'avgRating' => $avgRating['avg_rating'],
                    'ratingCount' => $avgRating['rating_count']
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'errors' => ['general' => 'Failed to add rating.']], 500);
            }
        } else {
            // Not a POST request, return error
            $this->jsonResponse(['success' => false, 'errors' => ['method' => 'Invalid request method.']], 405);
        }
    }
    
    /**
     * Add/remove movie from favorites
     */
    public function toggleFavorite() {
        // Check if user is logged in
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->getPostData(['movie_id']);
            
            if (!empty($result['errors'])) {
                $this->jsonResponse(['success' => false, 'errors' => $result['errors']], 400);
                return;
            }
            
            $movieId = $result['data']['movie_id'];
            
            // Check if already in favorites
            $isFavorite = $this->movieModel->isInFavorites(Auth::userId(), $movieId);
            
            if ($isFavorite) {
                // Remove from favorites
                $success = $this->movieModel->removeFromFavorites(Auth::userId(), $movieId);
                $message = 'Removed from favorites.';
                $newStatus = false;
            } else {
                // Add to favorites
                $success = $this->movieModel->addToFavorites(Auth::userId(), $movieId);
                $message = 'Added to favorites!';
                $newStatus = true;
            }
            
            if ($success) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => $message,
                    'isFavorite' => $newStatus
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'errors' => ['general' => 'Failed to update favorites.']], 500);
            }
        } else {
            // Not a POST request, return error
            $this->jsonResponse(['success' => false, 'errors' => ['method' => 'Invalid request method.']], 405);
        }
    }
}