<?php
require_once './includes/config.php';
require_once './includes/navbar.php';

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME,
    DB_USER,
    DB_PASS
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Function to get recent songs with song URI
function getRecentSongs($pdo, $limit = 5) {
    $stmt = $pdo->prepare(
        "SELECT song, artist, played_at, song_uri
         FROM playbacks
         ORDER BY played_at DESC
         LIMIT :limit"
    );
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$recentSongs = getRecentSongs($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="./assets/css/styles.css">
</head>
<body>
<header>
    <div class="navbar">
        <div class="menu">Menu</div>

        <div class="nav-links">
            <?php renderNavbar("index.php"); ?>
        </div>
    </div>
</header>

    <div class="main-content">
        <h1>Welcome to Your Music Dashboard</h1>
        <p>Explore your listening habits, top songs, streaks, and more!</p>

        <h2>Recent Songs</h2>
        <ul>
            <?php foreach ($recentSongs as $song): ?>
                <li>
                    <!-- Use the formatted song_uri -->
                    <a href="./search.php?song=<?= htmlspecialchars(formatSongUri($song['song_uri'])) ?>" class="spotify-link">
                        <?= htmlspecialchars($song['song']) ?> by <?= htmlspecialchars($song['artist']) ?>
                    </a>
                    (Played on: <?= convertToLocalTimeZone($song['played_at']) ?>)
                </li>
            <?php endforeach; ?>
        </ul>

        <p>Click on the navigation links above to explore other sections.</p>
    </div>

    <script src="./assets/js/script.js"></script>
</body>
</html>
