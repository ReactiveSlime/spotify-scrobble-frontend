<?php
require_once './includes/config.php';
require_once './includes/navbar.php';

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME,
    DB_USER,
    DB_PASS
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template Page</title>
    <link rel="stylesheet" href="./assets/css/styles.css">
</head>
<body>
<header>
<div class="navbar">
    <!-- Optionally, you can add a logo or site name here -->
    <div class="logo">My Music</div>

    <!-- Burger Menu Icon -->
    <div class="burger-menu">
        <div></div>
        <div></div>
        <div></div>
    </div>

    <!-- Navigation Links -->
    <div class="nav-links">
    <!-- change template.php to the name of this file-->
    <?php renderNavbar("template.php"); ?>
    </div>
</div>
    </header>
    <div class="main-content">
    </div>
    <script src="./assets/js/script.js"></script>
</body>
</html>
