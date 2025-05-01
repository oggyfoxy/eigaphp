<?php
namespace App\Controllers;

// HomeController inherits methods like render() from BaseController
class HomeController extends BaseController {

    /**
     * Handles the request for the homepage.
     */
    public function index() {
        // Data to pass to the view
        $data = [
            'pageTitle' => 'Welcome' // Specific title for the home page
            // Add any other data needed for the home view here later
        ];

        // Render the 'home/index' view using the method from BaseController
        // This will load src/Views/home/index.php into the main layout
        $this->render('home/index', $data);
    }
}