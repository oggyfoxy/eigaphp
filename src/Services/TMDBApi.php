<?php
namespace App\Services;

/**
 * Service class for interacting with The Movie Database (TMDB) API
 */
class TMDBApi {
    private $apiKey;
    private $baseUrl = 'https://api.themoviedb.org/3';
    private $imageBaseUrl = 'https://image.tmdb.org/t/p/';
    
    public function __construct() {
        $this->apiKey = defined('TMDB_API_KEY') ? TMDB_API_KEY : '';
        
        if (empty($this->apiKey)) {
            error_log('TMDB API key not configured');
        }
    }
    
    /**
     * Make a request to the TMDB API
     */
    private function makeRequest($endpoint, $params = []) {
        $params['api_key'] = $this->apiKey;
        
        $url = $this->baseUrl . $endpoint . '?' . http_build_query($params);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("TMDB API error: HTTP code {$httpCode} for endpoint {$endpoint}");
            return false;
        }
        
        $data = json_decode($response, true);
        
        if (!$data) {
            error_log("Failed to decode TMDB API response for endpoint {$endpoint}");
            return false;
        }
        
        return $data;
    }
    
    /**
     * Get movie details by TMDB ID
     */
    public function getMovie($tmdbId) {
        $data = $this->makeRequest("/movie/{$tmdbId}", [
            'append_to_response' => 'credits,videos,images'
        ]);
        
        if (!$data) {
            return false;
        }
        
        // Extract director from credits
        $director = null;
        if (isset($data['credits']['crew'])) {
            foreach ($data['credits']['crew'] as $crew) {
                if ($crew['job'] === 'Director') {
                    $director = $crew['name'];
                    break;
                }
            }
        }
        
        // Format movie data
        $movie = [
            'tmdb_id' => $data['id'],
            'title' => $data['title'],
            'original_title' => $data['original_title'] ?? null,
            'release_year' => isset($data['release_date']) ? substr($data['release_date'], 0, 4) : null,
            'poster_path' => $data['poster_path'] ?? null,
            'backdrop_path' => $data['backdrop_path'] ?? null,
            'overview' => $data['overview'] ?? null,
            'director' => $director,
            'runtime' => $data['runtime'] ?? null,
            'genres' => isset($data['genres']) ? array_column($data['genres'], 'name') : [],
            'videos' => isset($data['videos']['results']) ? $data['videos']['results'] : [],
            'cast' => isset($data['credits']['cast']) ? array_slice($data['credits']['cast'], 0, 10) : [],
            'crew' => isset($data['credits']['crew']) ? $data['credits']['crew'] : []
        ];
        
        return $movie;
    }
    
    /**
     * Search for movies
     */
    public function searchMovies($query, $page = 1) {
        $data = $this->makeRequest('/search/movie', [
            'query' => $query,
            'page' => $page
        ]);
        
        if (!$data || !isset($data['results'])) {
            return false;
        }
        
        // Format results
        $results = [
            'page' => $data['page'],
            'total_pages' => $data['total_pages'],
            'total_results' => $data['total_results'],
            'results' => []
        ];
        
        foreach ($data['results'] as $movie) {
            $results['results'][] = [
                'tmdb_id' => $movie['id'],
                'title' => $movie['title'],
                'original_title' => $movie['original_title'] ?? null,
                'release_year' => isset($movie['release_date']) ? substr($movie['release_date'], 0, 4) : null,
                'poster_path' => $movie['poster_path'] ?? null,
                'overview' => $movie['overview'] ?? null
            ];
        }
        
        return $results;
    }
    
    /**
     * Get popular movies
     */
    public function getPopularMovies($page = 1) {
        $data = $this->makeRequest('/movie/popular', ['page' => $page]);
        
        if (!$data || !isset($data['results'])) {
            return false;
        }
        
        // Format results
        $results = [
            'page' => $data['page'],
            'total_pages' => $data['total_pages'],
            'total_results' => $data['total_results'],
            'results' => []
        ];
        
        foreach ($data['results'] as $movie) {
            $results['results'][] = [
                'tmdb_id' => $movie['id'],
                'title' => $movie['title'],
                'original_title' => $movie['original_title'] ?? null,
                'release_year' => isset($movie['release_date']) ? substr($movie['release_date'], 0, 4) : null,
                'poster_path' => $movie['poster_path'] ?? null,
                'overview' => $movie['overview'] ?? null
            ];
        }
        
        return $results;
    }
    
    /**
     * Get full image URL
     */
    public function getImageUrl($path, $size = 'original') {
        if (empty($path)) {
            return null;
        }
        
        return $this->imageBaseUrl . $size . $path;
    }
    
    /**
     * Get poster URL
     */
    public function getPosterUrl($path, $size = 'w500') {
        return $this->getImageUrl($path, $size);
    }
    
    /**
     * Get backdrop URL
     */
    public function getBackdropUrl($path, $size = 'w1280') {
        return $this->getImageUrl($path, $size);
    }
}