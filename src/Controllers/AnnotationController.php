<?php
namespace App\Controllers;

use App\Models\Movie;
use App\Services\Auth;

/**
 * Controller for annotation-related actions
 */
class AnnotationController extends BaseController {
    private $movieModel;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->movieModel = new Movie();
    }
    
    /**
     * Show a single annotation and its comments
     */
    public function show($id = null) {
        if (!$id) {
            $id = $_GET['id'] ?? null;
        }
        
        if (!$id) {
            $this->notFound();
            return;
        }
        
        // Get annotation data
        $sql = "SELECT a.*, m.title as movie_title, m.release_year, m.poster_path, 
                u.username, u.avatar, 
                (SELECT COUNT(*) FROM annotation_likes WHERE annotation_id = a.id) as likes_count
                FROM annotations a
                JOIN movies m ON a.movie_id = m.id
                JOIN users u ON a.user_id = u.id
                WHERE a.id = ?";
        
        $db = \App\Models\Database::getInstance();
        $result = $db->select($sql, [$id]);
        
        if (!$result) {
            $this->notFound();
            return;
        }
        
        $annotation = $result[0];
        
        // If annotation is private, check if current user is the owner
        if ($annotation['is_private'] && (!Auth::isLoggedIn() || Auth::userId() != $annotation['user_id'])) {
            $this->setFlashMessage('error', 'This annotation is private.');
            $this->redirect('');
            return;
        }
        
        // Get annotation comments
        $comments = $this->movieModel->getAnnotationComments($id);
        
        // Check if user has liked this annotation
        $hasLiked = false;
        if (Auth::isLoggedIn()) {
            $checkSql = "SELECT id FROM annotation_likes 
                         WHERE user_id = ? AND annotation_id = ?";
            $likeResult = $db->select($checkSql, [Auth::userId(), $id]);
            $hasLiked = !empty($likeResult);
        }
        
        // Check if current user is the owner
        $isOwner = Auth::isLoggedIn() && Auth::userId() == $annotation['user_id'];
        
        $data = [
            'pageTitle' => 'Annotation for ' . $annotation['movie_title'],
            'flashMessages' => $this->getFlashMessages(),
            'annotation' => $annotation,
            'comments' => $comments,
            'hasLiked' => $hasLiked,
            'isOwner' => $isOwner
        ];
        
        $this->render('annotations/show', $data);
    }
    
    /**
     * Edit annotation page
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
        
        // Get annotation data
        $sql = "SELECT a.*, m.title as movie_title, m.id as movie_id 
                FROM annotations a
                JOIN movies m ON a.movie_id = m.id
                WHERE a.id = ?";
        
        $db = \App\Models\Database::getInstance();
        $result = $db->select($sql, [$id]);
        
        if (!$result) {
            $this->notFound();
            return;
        }
        
        $annotation = $result[0];
        
        // Check if user is the owner
        if (Auth::userId() != $annotation['user_id']) {
            $this->setFlashMessage('error', 'You do not have permission to edit this annotation.');
            $this->redirect("annotation?id={$id}");
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->getPostData(['content']);
            
            if (!empty($result['errors'])) {
                $data = [
                    'pageTitle' => 'Edit Annotation',
                    'flashMessages' => $this->getFlashMessages(),
                    'errors' => $result['errors'],
                    'annotation' => $annotation
                ];
                
                $this->render('annotations/edit', $data);
                return;
            }
            
            $content = $result['data']['content'];
            $isPrivate = isset($_POST['is_private']) ? (bool)$_POST['is_private'] : false;
            
            // Update annotation
            $sql = "UPDATE annotations 
                    SET content = ?, is_private = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ? AND user_id = ?";
            
            $success = $db->update($sql, [$content, $isPrivate, $id, Auth::userId()]);
            
            if ($success) {
                $this->setFlashMessage('success', 'Annotation updated successfully!');
                $this->redirect("annotation?id={$id}");
            } else {
                $data = [
                    'pageTitle' => 'Edit Annotation',
                    'flashMessages' => $this->getFlashMessages(),
                    'errors' => ['general' => 'Failed to update annotation.'],
                    'annotation' => $annotation
                ];
                
                $this->render('annotations/edit', $data);
            }
        } else {
            $data = [
                'pageTitle' => 'Edit Annotation',
                'flashMessages' => $this->getFlashMessages(),
                'annotation' => $annotation
            ];
            
            $this->render('annotations/edit', $data);
        }
    }
    
    /**
     * Delete annotation
     */
    public function delete() {
        // Check if user is logged in
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->getPostData(['annotation_id']);
            
            if (!empty($result['errors'])) {
                $this->jsonResponse(['success' => false, 'errors' => $result['errors']], 400);
                return;
            }
            
            $annotationId = $result['data']['annotation_id'];
            
            // Get annotation data to check ownership
            $sql = "SELECT user_id, movie_id FROM annotations WHERE id = ?";
            $db = \App\Models\Database::getInstance();
            $result = $db->select($sql, [$annotationId]);
            
            if (!$result) {
                $this->jsonResponse(['success' => false, 'errors' => ['annotation_id' => 'Annotation not found.']], 404);
                return;
            }
            
            $annotation = $result[0];
            
            // Check if user is the owner
            if (Auth::userId() != $annotation['user_id']) {
                $this->jsonResponse(['success' => false, 'errors' => ['permission' => 'You do not have permission to delete this annotation.']], 403);
                return;
            }
            
            // Delete annotation
            $sql = "DELETE FROM annotations WHERE id = ?";
            $success = $db->delete($sql, [$annotationId]);
            
            if ($success) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Annotation deleted successfully!',
                    'redirect' => "movie?id={$annotation['movie_id']}"
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'errors' => ['general' => 'Failed to delete annotation.']], 500);
            }
        } else {
            // Not a POST request, return error
            $this->jsonResponse(['success' => false, 'errors' => ['method' => 'Invalid request method.']], 405);
        }
    }
    
    /**
     * Like/unlike an annotation
     */
    public function toggleLike() {
        // Check if user is logged in
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->getPostData(['annotation_id']);
            
            if (!empty($result['errors'])) {
                $this->jsonResponse(['success' => false, 'errors' => $result['errors']], 400);
                return;
            }
            
            $annotationId = $result['data']['annotation_id'];
            
            // Check if annotation exists
            $sql = "SELECT id FROM annotations WHERE id = ?";
            $db = \App\Models\Database::getInstance();
            $result = $db->select($sql, [$annotationId]);
            
            if (!$result) {
                $this->jsonResponse(['success' => false, 'errors' => ['annotation_id' => 'Annotation not found.']], 404);
                return;
            }
            
            // Check if user has already liked this annotation
            $checkSql = "SELECT id FROM annotation_likes 
                         WHERE user_id = ? AND annotation_id = ?";
            $likeResult = $db->select($checkSql, [Auth::userId(), $annotationId]);
            
            if ($likeResult) {
                // Unlike
                $unlikeSql = "DELETE FROM annotation_likes 
                              WHERE user_id = ? AND annotation_id = ?";
                $success = $db->delete($unlikeSql, [Auth::userId(), $annotationId]);
                $message = 'Unliked successfully.';
                $liked = false;
            } else {
                // Like
                $likeSql = "INSERT INTO annotation_likes (user_id, annotation_id) 
                            VALUES (?, ?)";
                $success = $db->insert($likeSql, [Auth::userId(), $annotationId]);
                $message = 'Liked successfully!';
                $liked = true;
            }
            
            if ($success) {
                // Get updated like count
                $countSql = "SELECT COUNT(*) as count FROM annotation_likes 
                             WHERE annotation_id = ?";
                $countResult = $db->select($countSql, [$annotationId]);
                $likeCount = $countResult[0]['count'] ?? 0;
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => $message,
                    'liked' => $liked,
                    'likeCount' => $likeCount
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'errors' => ['general' => 'Failed to update like status.']], 500);
            }
        } else {
            // Not a POST request, return error
            $this->jsonResponse(['success' => false, 'errors' => ['method' => 'Invalid request method.']], 405);
        }
    }
    
    /**
     * Add comment to an annotation
     */
    public function addComment() {
        // Check if user is logged in
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->getPostData(['annotation_id', 'content']);
            
            if (!empty($result['errors'])) {
                $this->jsonResponse(['success' => false, 'errors' => $result['errors']], 400);
                return;
            }
            
            $annotationId = $result['data']['annotation_id'];
            $content = $result['data']['content'];
            
            // Add comment
            $commentId = $this->movieModel->addComment(Auth::userId(), $annotationId, $content);
            
            if ($commentId) {
                // Get the new comment with user data
                $sql = "SELECT ac.*, u.username, u.avatar 
                        FROM annotation_comments ac
                        JOIN users u ON ac.user_id = u.id
                        WHERE ac.id = ?";
                
                $db = \App\Models\Database::getInstance();
                $result = $db->select($sql, [$commentId]);
                
                if ($result) {
                    $this->jsonResponse([
                        'success' => true,
                        'message' => 'Comment added successfully!',
                        'comment' => $result[0]
                    ]);
                } else {
                    $this->jsonResponse(['success' => true, 'message' => 'Comment added successfully!']);
                }
            } else {
                $this->jsonResponse(['success' => false, 'errors' => ['general' => 'Failed to add comment.']], 500);
            }
        } else {
            // Not a POST request, return error
            $this->jsonResponse(['success' => false, 'errors' => ['method' => 'Invalid request method.']], 405);
        }
    }
    
    /**
     * Delete comment
     */
    public function deleteComment() {
        // Check if user is logged in
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->getPostData(['comment_id']);
            
            if (!empty($result['errors'])) {
                $this->jsonResponse(['success' => false, 'errors' => $result['errors']], 400);
                return;
            }
            
            $commentId = $result['data']['comment_id'];
            
            // Get comment data to check ownership
            $sql = "SELECT user_id FROM annotation_comments WHERE id = ?";
            $db = \App\Models\Database::getInstance();
            $result = $db->select($sql, [$commentId]);
            
            if (!$result) {
                $this->jsonResponse(['success' => false, 'errors' => ['comment_id' => 'Comment not found.']], 404);
                return;
            }
            
            $comment = $result[0];
            
            // Check if user is the owner
            if (Auth::userId() != $comment['user_id']) {
                $this->jsonResponse(['success' => false, 'errors' => ['permission' => 'You do not have permission to delete this comment.']], 403);
                return;
            }
            
            // Delete comment
            $sql = "DELETE FROM annotation_comments WHERE id = ?";
            $success = $db->delete($sql, [$commentId]);
            
            if ($success) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Comment deleted successfully!'
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'errors' => ['general' => 'Failed to delete comment.']], 500);
            }
        } else {
            // Not a POST request, return error
            $this->jsonResponse(['success' => false, 'errors' => ['method' => 'Invalid request method.']], 405);
        }
    }
}