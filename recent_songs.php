<?php
require_once './includes/config.php';
require_once './includes/navbar.php';

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME,
    DB_USER,
    DB_PASS
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function convertToAEST($utcDateTime) {
    $utc = new DateTime($utcDateTime, new DateTimeZone('UTC'));
    $utc->setTimezone(new DateTimeZone('Australia/Brisbane'));
    return $utc->format('jS \o\f F \a\t g:ia');
}

function getRecentSongs($pdo, $limit) {
    $stmt = $pdo->prepare("SELECT song, album, artist, played_at FROM playbacks ORDER BY played_at DESC LIMIT :limit");
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$recentSongs = getRecentSongs($pdo, 10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recent Songs</title>
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
    <?php renderNavbar("recent_songs.php"); ?>
    </div>
</div>
    </header>
    <div class="main-content">
        <h1>Recent Songs</h1>
        <ul>
            <?php foreach ($recentSongs as $song): ?>
                <li>
                    <?= htmlspecialchars($song['song']) ?> by <?= htmlspecialchars($song['artist']) ?>
                    (Played on: <?= convertToAEST($song['played_at']) ?>)
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <script src="./assets/js/script.js"></script>
</body>
</html>
