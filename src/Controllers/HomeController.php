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
     * GET /search
     */
    public function search()
    {
        $query = trim($_GET['q'] ?? '');
        $results = [];

        if ($query !== '') {
            $tmdb    = new \App\Services\TMDBApi();
            $payload = $tmdb->searchMovies($query);
            $results = $payload ? $payload['results'] : [];
        }

        $this->render('home/search', [
            'pageTitle' => 'Explore',
            'query'     => $query,
            'results'   => $results
        ]);
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