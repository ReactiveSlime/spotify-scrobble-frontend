<?php
require_once './includes/navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitHub Repositories</title>
    <link rel="stylesheet" href="./assets/css/styles.css">
</head>
<body>
<header>
    <div class="navbar">
        <div class="logo">My Music</div>
        <div class="burger-menu">
            <div></div>
            <div></div>
            <div></div>
        </div>
        <div class="nav-links">
            <?php renderNavbar("github.php"); ?>
        </div>
    </div>
</header>

<div class="main-content">
    <h1>GitHub Repositories</h1>
    <p>Click the buttons below to visit the backend and frontend repositories on GitHub:</p>

    <!-- GitHub Links Section -->
    <section>
        <h2>Backend Repository</h2>
        <button type="button" onclick="window.location.href='https://github.com/ReactiveSlime/spotify-scrobble-backend';" class="btn-reset">Visit Backend Repo</button>
    </section>

    <section>
        <h2>Frontend Repository</h2>
        <button type="button" onclick="window.location.href='https://github.com/ReactiveSlime/spotify-scrobble-frontend';" class="btn-reset">Visit Frontend Repo</button>
    </section>
</div>

<script src="./assets/js/script.js"></script>
</body>
</html>
