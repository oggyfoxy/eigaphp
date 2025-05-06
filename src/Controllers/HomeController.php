<?php
namespace App\Controllers;

class HomeController extends BaseController {
    /**
     * Handles the request for the homepage.
     */
    public function index() {
        $data = [
            'pageTitle' => 'Welcome'
        ];
        $this->render('home/index', $data);
    }
    
    /**
     * About page
     */
    public function about() {
        $data = [
            'pageTitle' => 'About Us'
        ];
        $this->render('home/about', $data);
    }
    
    /**
     * Search functionality
     */
    public function search() {
        $query = $_GET['q'] ?? '';
        $data = [
            'pageTitle' => 'Search Results',
            'query' => $query
        ];
        $this->render('home/search', $data);
    }
    
    /**
     * 404 page
     */
    public function notFound() {
        $data = [
            'pageTitle' => 'Page Not Found'
        ];
        $this->render('errors/404', $data);
    }
}