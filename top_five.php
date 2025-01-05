<?php
require_once './includes/config.php';
require_once './includes/navbar.php';

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME,
    DB_USER,
    DB_PASS
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function getTopArtists($pdo, $interval, $limit = 5) {
    $validIntervals = ['1 DAY' => '1 DAY', '1 WEEK' => '1 WEEK', '1 MONTH' => '1 MONTH'];
    if (!isset($validIntervals[$interval])) {
        throw new InvalidArgumentException("Invalid interval: $interval");
    }
    $stmt = $pdo->prepare(
        "SELECT artist_name, SUM(seconds_played) / 60 AS minutes_listened
         FROM artists
         WHERE played_at >= NOW() - INTERVAL " . $validIntervals[$interval] . "
         AND artist_name IS NOT NULL AND TRIM(artist_name) != ''
         GROUP BY artist_name
         ORDER BY minutes_listened DESC
         LIMIT :limit"
    );
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTopSongs($pdo, $interval, $limit = 5) {
    $validIntervals = ['1 DAY' => '1 DAY', '1 WEEK' => '1 WEEK', '1 MONTH' => '1 MONTH'];
    if (!isset($validIntervals[$interval])) {
        throw new InvalidArgumentException("Invalid interval: $interval");
    }
    $stmt = $pdo->prepare(
        "SELECT song, SUM(seconds_played) / 60 AS minutes_listened
         FROM playbacks
         WHERE played_at >= NOW() - INTERVAL " . $validIntervals[$interval] . "
         GROUP BY song
         ORDER BY minutes_listened DESC
         LIMIT :limit"
    );
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTopPopularSongs($pdo, $interval, $limit = 5) {
    $validIntervals = ['1 DAY' => '1 DAY', '1 WEEK' => '1 WEEK', '1 MONTH' => '1 MONTH'];
    if (!isset($validIntervals[$interval])) {
        throw new InvalidArgumentException("Invalid interval: $interval");
    }
    $stmt = $pdo->prepare(
        "SELECT song, MAX(track_popularity) AS track_popularity, SUM(seconds_played) / 60 AS minutes_listened
         FROM playbacks
         WHERE played_at >= NOW() - INTERVAL " . $validIntervals[$interval] . "
         AND track_popularity IS NOT NULL AND track_popularity > 0
         GROUP BY song
         ORDER BY track_popularity DESC
         LIMIT :limit"
    );
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTopNicheSongs($pdo, $interval, $limit = 5) {
    $validIntervals = ['1 DAY' => '1 DAY', '1 WEEK' => '1 WEEK', '1 MONTH' => '1 MONTH'];
    if (!isset($validIntervals[$interval])) {
        throw new InvalidArgumentException("Invalid interval: $interval");
    }
    $stmt = $pdo->prepare(
        "SELECT song, MAX(track_popularity) AS track_popularity, SUM(seconds_played) / 60 AS minutes_listened
         FROM playbacks
         WHERE played_at >= NOW() - INTERVAL " . $validIntervals[$interval] . "
         AND track_popularity IS NOT NULL AND track_popularity > 0 AND track_popularity <= 50
         GROUP BY song
         ORDER BY track_popularity ASC
         LIMIT :limit"
    );
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$topSongs = getTopSongs($pdo, '1 WEEK');
$topPopularSongs = getTopPopularSongs($pdo, '1 WEEK');
$topNicheSongs = getTopNicheSongs($pdo, '1 WEEK');
$topArtists = getTopArtists($pdo, '1 WEEK');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Five</title>
    <link rel="stylesheet" href="./assets/css/styles.css">
    <style>
        .top-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        .top-row {
            display: flex;
            justify-content: space-between;
            gap: 2rem;
        }
        .top-column {
            flex: 1;
        }
    </style>
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
            <?php renderNavbar("top_five.php"); ?>
        </div>
    </div>
</header>

<div class="main-content">
    <h1>Top Five</h1>
    <div class="top-content">
        <div class="top-row">
            <div class="top-column top-artists">
                <h2>Top Artists</h2>
                <ul>
                    <?php foreach ($topArtists as $artist): ?>
                        <li>
                            <?= htmlspecialchars($artist['artist_name']) ?> 
                            (<?= ceil($artist['minutes_listened']) ?> minutes)
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="top-column top-songs">
                <h2>Top Songs</h2>
                <ul>
                    <?php foreach ($topSongs as $song): ?>
                        <li>
                            <?= htmlspecialchars($song['song']) ?> 
                            (<?= ceil($song['minutes_listened']) ?> minutes)
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="top-row">
            <div class="top-column top-popular-songs">
                <h2>Top Popular Songs</h2>
                <ul>
                    <?php foreach ($topPopularSongs as $song): ?>
                        <li>
                            <?= htmlspecialchars($song['song']) ?> 
                            (Popularity Score: <?= htmlspecialchars($song['track_popularity']) ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="top-column top-niche-songs">
                <h2>Top Niche Songs</h2>
                <ul>
                    <?php foreach ($topNicheSongs as $song): ?>
                        <li>
                            <?= htmlspecialchars($song['song']) ?> 
                            (Popularity Score: <?= htmlspecialchars($song['track_popularity']) ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="./assets/js/script.js"></script>
</body>
</html>
