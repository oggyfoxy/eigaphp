<?php
namespace App\Controllers;

use App\Models\Movie;
use App\Models\User;
use App\Services\TMDBApi;
use App\Services\Auth;

/**
 * Controller for API endpoints
 */
class ApiController extends BaseController {
    private $movieModel;
    private $userModel;
    private $tmdbApi;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->movieModel = new Movie();
        $this->userModel = new User();
        $this->tmdbApi = new TMDBApi();
    }
    
    /**
     * Search movies API endpoint
     */
    public function searchMovies() {
        $query = $_GET['q'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        
        if (empty($query)) {
            $this->jsonResponse(['success' => false, 'error' => 'Query parameter is required.'], 400);
            return;
        }
        
        // Search database first
        $dbResults = $this->movieModel->searchMovies($query, 5, 0);
        
        // Then search TMDB API
        $apiResults = $this->tmdbApi->searchMovies($query, $page);
        
        if (!$apiResults) {
            $apiResults = ['results' => []];
        }
        
        // Combine results, prioritizing database results
        $results = [
            'db_results' => $dbResults,
            'api_results' => $apiResults['results'],
            'page' => $apiResults['page'] ?? $page,
            'total_pages' => $apiResults['total_pages'] ?? 1,
            'total_results' => $apiResults['total_results'] ?? count($apiResults['results'])
        ];
        
        $this->jsonResponse(['success' => true, 'data' => $results]);
    }
    
    /**
     * Get movie details API endpoint
     */
    public function getMovie() {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $this->jsonResponse(['success' => false, 'error' => 'Movie ID is required.'], 400);
            return;
        }
        
        // Get movie from database
        $movie = $this->movieModel->getMovieById($id);
        
        if (!$movie) {
            $this->jsonResponse(['success' => false, 'error' => 'Movie not found.'], 404);
            return;
        }
        
        // Get movie annotations
        $annotations = $this->movieModel->getMovieAnnotations($id, 10);
        
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
        
        $result = [
            'movie' => $movie,
            'annotations' => $annotations,
            'avg_rating' => $avgRating,
            'user_rating' => $userRating,
            'is_favorite' => $isFavorite
        ];
        
        $this->jsonResponse(['success' => true, 'data' => $result]);
    }
    
    /**
     * Get movie annotations API endpoint
     */
    public function getMovieAnnotations() {
        $movieId = $_GET['movie_id'] ?? null;
        $timestamp = isset($_GET['timestamp']) ? (int)$_GET['timestamp'] : null;
        
        if (!$movieId) {
            $this->jsonResponse(['success' => false, 'error' => 'Movie ID is required.'], 400);
            return;
        }
        
        // Get annotations
        if ($timestamp !== null) {
            // Get annotations for a specific scene
            $annotations = $this->movieModel->getSceneAnnotations($movieId, $timestamp);
        } else {
            // Get all annotations for the movie
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $offset = ($page - 1) * $limit;
            
            $annotations = $this->movieModel->getMovieAnnotations($movieId, $limit, $offset);
        }
        
        $this->jsonResponse(['success' => true, 'data' => $annotations]);
    }
    
    /**
     * Get annotation comments API endpoint
     */
    public function getAnnotationComments() {
        $annotationId = $_GET['annotation_id'] ?? null;
        
        if (!$annotationId) {
            $this->jsonResponse(['success' => false, 'error' => 'Annotation ID is required.'], 400);
            return;
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        
        // Get comments
        $comments = $this->movieModel->getAnnotationComments($annotationId, $limit, $offset);
        
        $this->jsonResponse(['success' => true, 'data' => $comments]);
    }
        
    /**
     * Get TMDB movie details API endpoint
     */
    public function getTmdbMovie() {
        $tmdbId = $_GET['tmdb_id'] ?? null;
        
        if (!$tmdbId) {
            $this->jsonResponse(['success' => false, 'error' => 'TMDB ID is required.'], 400);
            return;
        }
        
        // Get movie from TMDB API
        $movie = $this->tmdbApi->getMovie($tmdbId);
        
        if (!$movie) {
            $this->jsonResponse(['success' => false, 'error' => 'Failed to fetch movie data from TMDB API.'], 500);
            return;
        }
        
        $this->jsonResponse(['success' => true, 'data' => $movie]);
    }
    
    /**
     * Get user profile API endpoint
     */
    public function getUserProfile() {
        $username = $_GET['username'] ?? null;
        
        if (!$username) {
            $this->jsonResponse(['success' => false, 'error' => 'Username is required.'], 400);
            return;
        }
        
        // Get user data
        $user = $this->userModel->getUserByUsername($username);
        
        if (!$user) {
            $this->jsonResponse(['success' => false, 'error' => 'User not found.'], 404);
            return;
        }
        
        // Get user's recent annotations
        $annotations = $this->userModel->getRecentAnnotations($user['id'], 5);
        
        // Get user's favorite movies
        $favoriteMovies = $this->userModel->getFavoriteMovies($user['id'], 5);
        
        // Check if current user is following this user
        $isFollowing = false;
        if (Auth::isLoggedIn() && Auth::userId() != $user['id']) {
            $isFollowing = $this->userModel->isFollowing(Auth::userId(), $user['id']);
        }
        
        // Remove sensitive data
        unset($user['email']);
        
        $result = [
            'user' => $user,
            'annotations' => $annotations,
            'favorite_movies' => $favoriteMovies,
            'is_following' => $isFollowing
        ];
        
        $this->jsonResponse(['success' => true, 'data' => $result]);
    }
}