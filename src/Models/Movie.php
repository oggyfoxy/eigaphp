<?php
namespace App\Models;

class Movie {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get movie by ID
     */
    public function getMovieById($id) {
        $sql = "SELECT * FROM movies WHERE id = ?";
        $result = $this->db->select($sql, [$id]);
        
        return $result ? $result[0] : false;
    }
    
    /**
     * Get movie by TMDB ID
     */
    public function getMovieByTmdbId($tmdbId) {
        $sql = "SELECT * FROM movies WHERE tmdb_id = ?";
        $result = $this->db->select($sql, [$tmdbId]);
        
        return $result ? $result[0] : false;
    }
    
    /**
     * Search movies by title
     */
    public function searchMovies($query, $limit = 10, $offset = 0) {
        $searchQuery = "%$query%";
        
        $sql = "SELECT * FROM movies 
                WHERE title LIKE ? OR original_title LIKE ? 
                ORDER BY release_year DESC, title ASC 
                LIMIT ? OFFSET ?";
        
        return $this->db->select($sql, [$searchQuery, $searchQuery, $limit, $offset]);
    }
    
    /**
     * Get popular movies (based on annotation count)
     */
    public function getPopularMovies($limit = 12, $offset = 0) {
        $sql = "SELECT m.*, COUNT(a.id) as annotation_count 
                FROM movies m
                LEFT JOIN annotations a ON m.id = a.movie_id
                GROUP BY m.id
                ORDER BY annotation_count DESC, m.release_year DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->select($sql, [$limit, $offset]);
    }
    
    /**
     * Get recent movies (recently added to the database)
     */
    public function getRecentMovies($limit = 12, $offset = 0) {
        $sql = "SELECT * FROM movies 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        
        return $this->db->select($sql, [$limit, $offset]);
    }
    
    /**
     * Add or update movie in database
     */
    public function saveMovie($movieData) {
        // Check if movie already exists by TMDB ID
        $existingMovie = $this->getMovieByTmdbId($movieData['tmdb_id']);
        
        if ($existingMovie) {
            // Update existing movie
            $movieId = $existingMovie['id'];
            
            $sql = "UPDATE movies 
                    SET title = ?, original_title = ?, release_year = ?, 
                        poster_path = ?, backdrop_path = ?, overview = ?, 
                        director = ?, runtime = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?";
            
            $params = [
                $movieData['title'],
                $movieData['original_title'] ?? null,
                $movieData['release_year'],
                $movieData['poster_path'] ?? null,
                $movieData['backdrop_path'] ?? null,
                $movieData['overview'] ?? null,
                $movieData['director'] ?? null,
                $movieData['runtime'] ?? null,
                $movieId
            ];
            
            $this->db->update($sql, $params);
            return $movieId;
        } else {
            // Insert new movie
            $sql = "INSERT INTO movies 
                    (tmdb_id, title, original_title, release_year, poster_path, 
                     backdrop_path, overview, director, runtime) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $movieData['tmdb_id'],
                $movieData['title'],
                $movieData['original_title'] ?? null,
                $movieData['release_year'],
                $movieData['poster_path'] ?? null,
                $movieData['backdrop_path'] ?? null,
                $movieData['overview'] ?? null,
                $movieData['director'] ?? null,
                $movieData['runtime'] ?? null
            ];
            
            return $this->db->insert($sql, $params);
        }
    }
    
    /**
     * Get movie annotations
     */
    public function getMovieAnnotations($movieId, $limit = 20, $offset = 0) {
        $sql = "SELECT a.*, 
                u.username, u.avatar,
                (SELECT COUNT(*) FROM annotation_likes WHERE annotation_id = a.id) as likes_count
                FROM annotations a
                JOIN users u ON a.user_id = u.id
                WHERE a.movie_id = ?
                ORDER BY a.timestamp_seconds ASC, a.created_at DESC 
                LIMIT ? OFFSET ?";
        
        return $this->db->select($sql, [$movieId, $limit, $offset]);
    }
    
    /**
     * Get scene annotations (for a specific timestamp)
     */
    public function getSceneAnnotations($movieId, $timestamp, $timeRange = 5, $limit = 10) {
        $minTime = $timestamp - $timeRange;
        $maxTime = $timestamp + $timeRange;
        
        $sql = "SELECT a.*, 
                u.username, u.avatar,
                (SELECT COUNT(*) FROM annotation_likes WHERE annotation_id = a.id) as likes_count
                FROM annotations a
                JOIN users u ON a.user_id = u.id
                WHERE a.movie_id = ? 
                  AND a.timestamp_seconds BETWEEN ? AND ?
                ORDER BY a.created_at DESC 
                LIMIT ?";
        
        return $this->db->select($sql, [$movieId, $minTime, $maxTime, $limit]);
    }
    
    /**
     * Add annotation to a movie
     */
    public function addAnnotation($userId, $movieId, $content, $timestamp = null, $isPrivate = false) {
        $sql = "INSERT INTO annotations 
                (user_id, movie_id, content, timestamp_seconds, is_private) 
                VALUES (?, ?, ?, ?, ?)";
        
        $annotationId = $this->db->insert($sql, [$userId, $movieId, $content, $timestamp, $isPrivate]);
        
        if ($annotationId) {
            // Add to activity feed
            $this->addActivity($userId, 'annotation', $annotationId);
        }
        
        return $annotationId;
    }
    
    /**
     * Add user activity
     */
    private function addActivity($userId, $type, $relatedId) {
        $sql = "INSERT INTO activity (user_id, type, related_id) VALUES (?, ?, ?)";
        return $this->db->insert($sql, [$userId, $type, $relatedId]);
    }
    
    /**
     * Like an annotation
     */
    public function likeAnnotation($userId, $annotationId) {
        // Check if already liked
        $checkSql = "SELECT id FROM annotation_likes 
                     WHERE user_id = ? AND annotation_id = ?";
        $exists = $this->db->select($checkSql, [$userId, $annotationId]);
        
        if ($exists) {
            return false; // Already liked
        }
        
        $sql = "INSERT INTO annotation_likes (user_id, annotation_id) VALUES (?, ?)";
        $likeId = $this->db->insert($sql, [$userId, $annotationId]);
        
        if ($likeId) {
            // Add to activity feed
            $this->addActivity($userId, 'like', $likeId);
        }
        
        return $likeId;
    }
    
    /**
     * Unlike an annotation
     */
    public function unlikeAnnotation($userId, $annotationId) {
        $sql = "DELETE FROM annotation_likes 
                WHERE user_id = ? AND annotation_id = ?";
        
        return $this->db->delete($sql, [$userId, $annotationId]);
    }
    
    /**
     * Add comment to an annotation
     */
    public function addComment($userId, $annotationId, $content) {
        $sql = "INSERT INTO annotation_comments 
                (user_id, annotation_id, content) 
                VALUES (?, ?, ?)";
        
        $commentId = $this->db->insert($sql, [$userId, $annotationId, $content]);
        
        if ($commentId) {
            // Add to activity feed
            $this->addActivity($userId, 'comment', $commentId);
        }
        
        return $commentId;
    }
    
    /**
     * Get annotation comments
     */
    public function getAnnotationComments($annotationId, $limit = 20, $offset = 0) {
        $sql = "SELECT ac.*, u.username, u.avatar 
                FROM annotation_comments ac
                JOIN users u ON ac.user_id = u.id
                WHERE ac.annotation_id = ?
                ORDER BY ac.created_at ASC
                LIMIT ? OFFSET ?";
        
        return $this->db->select($sql, [$annotationId, $limit, $offset]);
    }
    
    /**
     * Rate a movie
     */
    public function rateMovie($userId, $movieId, $rating) {
        // Check if user already rated this movie
        $checkSql = "SELECT id FROM user_ratings 
                     WHERE user_id = ? AND movie_id = ?";
        $exists = $this->db->select($checkSql, [$userId, $movieId]);
        
        if ($exists) {
            // Update existing rating
            $sql = "UPDATE user_ratings 
                    SET rating = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE user_id = ? AND movie_id = ?";
            $success = $this->db->update($sql, [$rating, $userId, $movieId]);
            return $exists[0]['id'];
        } else {
            // Insert new rating
            $sql = "INSERT INTO user_ratings (user_id, movie_id, rating) 
                    VALUES (?, ?, ?)";
            $ratingId = $this->db->insert($sql, [$userId, $movieId, $rating]);
            
            if ($ratingId) {
                // Add to activity feed
                $this->addActivity($userId, 'rating', $ratingId);
            }
            
            return $ratingId;
        }
    }
    
    /**
     * Get user's rating for a movie
     */
    public function getUserRating($userId, $movieId) {
        $sql = "SELECT rating, created_at, updated_at 
                FROM user_ratings 
                WHERE user_id = ? AND movie_id = ?";
        $result = $this->db->select($sql, [$userId, $movieId]);
        
        return $result ? $result[0] : false;
    }
    
    /**
     * Get average rating for a movie
     */
    public function getAverageRating($movieId) {
        $sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as rating_count 
                FROM user_ratings 
                WHERE movie_id = ?";
        $result = $this->db->select($sql, [$movieId]);
        
        return $result ? $result[0] : ['avg_rating' => 0, 'rating_count' => 0];
    }
    
    /**
     * Add movie to user's favorites
     */
    public function addToFavorites($userId, $movieId) {
        // Check if already in favorites
        $checkSql = "SELECT id FROM user_favorites 
                     WHERE user_id = ? AND movie_id = ?";
        $exists = $this->db->select($checkSql, [$userId, $movieId]);
        
        if ($exists) {
            return false; // Already in favorites
        }
        
        $sql = "INSERT INTO user_favorites (user_id, movie_id) 
                VALUES (?, ?)";
        
        return $this->db->insert($sql, [$userId, $movieId]);
    }
    
    /**
     * Remove movie from user's favorites
     */
    public function removeFromFavorites($userId, $movieId) {
        $sql = "DELETE FROM user_favorites 
                WHERE user_id = ? AND movie_id = ?";
        
        return $this->db->delete($sql, [$userId, $movieId]);
    }
    
    /**
     * Check if movie is in user's favorites
     */
    public function isInFavorites($userId, $movieId) {
        $sql = "SELECT id FROM user_favorites 
                WHERE user_id = ? AND movie_id = ?";
        $result = $this->db->select($sql, [$userId, $movieId]);
        
        return !empty($result);
    }
    /**
     * Return all movies this user has favorited.
     */
    public function getFavoritesByUser(int $userId): array
    {
        $sql  = "SELECT m.*, f.movie_id AS tmdb_id
                FROM movies m
                JOIN favorites f ON f.movie_id = m.id
                WHERE f.user_id = ?";
        $result = $this->db->select($sql, [$userId]);

        // If the query returned false (no results), return an empty array
        if ($result === false) {
            return [];
        }

        return $result;
    }



    /**
     * Fetch movie data from TMDB API
     */
    public function fetchMovieFromTMDB($tmdbId) {
        $apiKey = defined('TMDB_API_KEY') ? TMDB_API_KEY : '';
        
        if (empty($apiKey)) {
            error_log('TMDB API key not configured');
            return false;
        }
        
        $url = "https://api.themoviedb.org/3/movie/{$tmdbId}?api_key={$apiKey}&append_to_response=credits";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("TMDB API error: HTTP code {$httpCode}");
            return false;
        }
        
        $data = json_decode($response, true);
        
        if (!$data) {
            error_log('Failed to decode TMDB API response');
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
        
        // Map TMDB data to our format
        $movieData = [
            'tmdb_id' => $data['id'],
            'title' => $data['title'],
            'original_title' => $data['original_title'] ?? null,
            'release_year' => isset($data['release_date']) ? substr($data['release_date'], 0, 4) : null,
            'poster_path' => $data['poster_path'] ?? null,
            'backdrop_path' => $data['backdrop_path'] ?? null,
            'overview' => $data['overview'] ?? null,
            'director' => $director,
            'runtime' => $data['runtime'] ?? null
        ];
        
        return $movieData;
    }
}