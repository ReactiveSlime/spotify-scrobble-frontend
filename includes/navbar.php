<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

function renderNavbar($currentPage) {
    $pages = [
        "index.php" => "Home",
        "songs.php" => "Songs",
        "playtime_counts.php" => "Playtime And Counts",
        "top_five.php" => "Top Five",
        "search.php" => "Search",
        "streaks.php" => "Streaks",
        "heatmap.php" => "Heatmap",
        "charts.php" => "Charts",
        "links.php" => "Links"
        /* Edit this to make the changes to the navbar 
           "filename.php" => "Page Name"
        */
        "template.php" => "Template",
    ];
    foreach ($pages as $page => $title) {
        $class = ($page === $currentPage) ? 'active' : '';
        echo '<a href="' . $page . '" class="' . $class . '">' . $title . '</a>';
    }
}

?>