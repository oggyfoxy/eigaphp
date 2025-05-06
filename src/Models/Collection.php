<?php
namespace App\Models;

class Collection {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get collection by ID
     */
    public function getCollectionById($id) {
        $sql = "SELECT c.*, u.username, u.avatar,
                (SELECT COUNT(*) FROM collection_movies WHERE collection_id = c.id) as movie_count
                FROM collections c
                JOIN users u ON c.user_id = u.id
                WHERE c.id = ?";
        $result = $this->db->select($sql, [$id]);
        
        return $result ? $result[0] : false;
    }
    
    /**
     * Get user's collections
     */
    public function getUserCollections($userId, $includePrivate = false, $limit = 10, $offset = 0) {
        $sql = "SELECT c.*,
                (SELECT COUNT(*) FROM collection_movies WHERE collection_id = c.id) as movie_count
                FROM collections c
                WHERE c.user_id = ?";
        
        $params = [$userId];
        
        if (!$includePrivate) {
            $sql .= " AND c.is_private = 0";
        }
        
        $sql .= " ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->select($sql, $params);
    }
    
    /**
     * Create a new collection
     */
    public function createCollection($userId, $title, $description = '', $isPrivate = false) {
        $sql = "INSERT INTO collections (user_id, title, description, is_private) 
                VALUES (?, ?, ?, ?)";
        
        return $this->db->insert($sql, [$userId, $title, $description, $isPrivate]);
    }
    
    /**
     * Update collection details
     */
    public function updateCollection($collectionId, $userId, $data) {
        // First verify the user owns this collection
        $collection = $this->getCollectionById($collectionId);
        if (!$collection || $collection['user_id'] != $userId) {
            return false;
        }
        
        $allowedFields = ['title', 'description', 'is_private'];
        $updates = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "$key = ?";
                $params[] = $value;
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $params[] = $collectionId;
        $sql = "UPDATE collections 
                SET " . implode(', ', $updates) . " 
                WHERE id = ?";
        
        return $this->db->update($sql, $params);
    }
    
    /**
     * Delete a collection
     */
    public function deleteCollection($collectionId, $userId) {
        // First verify the user owns this collection
        $collection = $this->getCollectionById($collectionId);
        if (!$collection || $collection['user_id'] != $userId) {
            return false;
        }
        
        // Delete the collection (cascade will remove collection_movies entries)
        $sql = "DELETE FROM collections WHERE id = ?";
        
        return $this->db->delete($sql, [$collectionId]);
    }
    
    /**
     * Get movies in a collection
     */
    public function getCollectionMovies($collectionId, $limit = 50, $offset = 0) {
        $sql = "SELECT cm.*, m.title, m.release_year, m.poster_path, m.director
                FROM collection_movies cm
                JOIN movies m ON cm.movie_id = m.id
                WHERE cm.collection_id = ?
                ORDER BY cm.position ASC, cm.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->select($sql, [$collectionId, $limit, $offset]);
    }
    
    /**
     * Add movie to collection
     */
    public function addMovieToCollection($collectionId, $userId, $movieId, $notes = null) {
        // First verify the user owns this collection
        $collection = $this->getCollectionById($collectionId);
        if (!$collection || $collection['user_id'] != $userId) {
            return false;
        }
        
        // Check if movie is already in the collection
        $checkSql = "SELECT id FROM collection_movies 
                     WHERE collection_id = ? AND movie_id = ?";
        $exists = $this->db->select($checkSql, [$collectionId, $movieId]);
        
        if ($exists) {
            // Update position or notes if needed
            if ($notes !== null) {
                $sql = "UPDATE collection_movies 
                        SET notes = ? 
                        WHERE collection_id = ? AND movie_id = ?";
                $this->db->update($sql, [$notes, $collectionId, $movieId]);
            }
            return $exists[0]['id'];
        }
        
        // Get the highest current position
        $positionSql = "SELECT MAX(position) as max_pos 
                        FROM collection_movies 
                        WHERE collection_id = ?";
        $posResult = $this->db->select($positionSql, [$collectionId]);
        $position = $posResult && isset($posResult[0]['max_pos']) ? ($posResult[0]['max_pos'] + 1) : 0;
        
        // Insert new movie to collection
        $sql = "INSERT INTO collection_movies (collection_id, movie_id, notes, position) 
                VALUES (?, ?, ?, ?)";
        
        return $this->db->insert($sql, [$collectionId, $movieId, $notes, $position]);
    }
    
    /**
     * Remove movie from collection
     */
    public function removeMovieFromCollection($collectionId, $userId, $movieId) {
        // First verify the user owns this collection
        $collection = $this->getCollectionById($collectionId);
        if (!$collection || $collection['user_id'] != $userId) {
            return false;
        }
        
        $sql = "DELETE FROM collection_movies 
                WHERE collection_id = ? AND movie_id = ?";
        
        return $this->db->delete($sql, [$collectionId, $movieId]);
    }
    
    /**
     * Update movie position in collection
     */
    public function updateMoviePosition($collectionId, $userId, $movieId, $newPosition) {
        // First verify the user owns this collection
        $collection = $this->getCollectionById($collectionId);
        if (!$collection || $collection['user_id'] != $userId) {
            return false;
        }
        
        // Update the position
        $sql = "UPDATE collection_movies 
                SET position = ? 
                WHERE collection_id = ? AND movie_id = ?";
        
        return $this->db->update($sql, [$newPosition, $collectionId, $movieId]);
    }
    
    /**
     * Get popular collections
     */
    public function getPopularCollections($limit = 10, $offset = 0) {
        $sql = "SELECT c.*, u.username, u.avatar,
                (SELECT COUNT(*) FROM collection_movies WHERE collection_id = c.id) as movie_count
                FROM collections c
                JOIN users u ON c.user_id = u.id
                WHERE c.is_private = 0
                GROUP BY c.id
                ORDER BY movie_count DESC, c.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->select($sql, [$limit, $offset]);
    }
}