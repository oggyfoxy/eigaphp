<?php
// This file is included by the BaseController's render method.
// Variables passed in the $data array are available here (e.g., $pageTitle).
?>

<h1>Welcome, user_xyz</h1> <!-- We'll make the username dynamic later -->

<section>
    <h2>search movies</h2>
    <form action="/search" method="GET"> <!-- Action points to a route we'll define later -->
        <input type="text" name="query" placeholder="Search movies..." aria-label="Search movies">
        <button type="submit">Search</button>
    </form>
</section>

<section>
    <h2>recently annotated</h2>
    <!-- Placeholder for recently annotated movies -->
    <!-- We will fetch this data from the database later -->
    <p>(Recently annotated movie list will appear here)</p>
    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
        <div class="movie-card-placeholder" style="border: 1px solid #ccc; padding: 10px; width: 150px;">
            Movie Poster<br>
            paprika (2006)<br>
            <small>4 annotations</small>
        </div>
         <div class="movie-card-placeholder" style="border: 1px solid #ccc; padding: 10px; width: 150px;">
            Movie Poster<br>
            perfect blue (1997)<br>
            <small>2 annotations</small>
        </div>
         <div class="movie-card-placeholder" style="border: 1px solid #ccc; padding: 10px; width: 150px;">
            Movie Poster<br>
            akira (1988)<br>
            <small>7 annotations</small>
        </div>
         <div class="movie-card-placeholder" style="border: 1px solid #ccc; padding: 10px; width: 150px;">
            Movie Poster<br>
            ghost in the shell (1995)<br>
            <small>3 annotations</small>
        </div>
    </div>
</section>