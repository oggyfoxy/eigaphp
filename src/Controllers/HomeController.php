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
}