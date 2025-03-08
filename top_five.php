<?php

require_once './includes/config.php';
require_once './includes/navbar.php';

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME,
    DB_USER,
    DB_PASS
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function getTopArtists($pdo, $startDate, $endDate, $limit = 5)
{
    $query = "SELECT artist_name, artist_uri, SUM(seconds_played) / 60 AS minutes_listened
              FROM artists
              WHERE artist_name IS NOT NULL 
              AND TRIM(artist_name) != ''";
    
    $params = [];

    if (!empty($startDate) && !empty($endDate)) {
        $query .= " AND played_at BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $startDate . " 00:00:00";
        $params[':end_date'] = $endDate . " 23:59:59";
    }

    $query .= " GROUP BY artist_name, artist_uri
                ORDER BY minutes_listened DESC
                LIMIT :limit";

    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTopSongs($pdo, $startDate, $endDate, $limit = 5)
{
    $query = "SELECT song, song_uri, SUM(seconds_played) / 60 AS minutes_listened
              FROM playbacks
              WHERE 1=1";
    
    $params = [];

    if (!empty($startDate) && !empty($endDate)) {
        $query .= " AND played_at BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $startDate . " 00:00:00";
        $params[':end_date'] = $endDate . " 23:59:59";
    }

    $query .= " GROUP BY song, song_uri
                ORDER BY minutes_listened DESC
                LIMIT :limit";

    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTopPopularSongs($pdo, $startDate, $endDate, $limit = 5)
{
    $query = "SELECT song, song_uri, MAX(track_popularity) AS track_popularity, SUM(seconds_played) / 60 AS minutes_listened
              FROM playbacks
              WHERE song_uri IS NOT NULL";
    
    $params = [];

    if (!empty($startDate) && !empty($endDate)) {
        $query .= " AND played_at BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $startDate . " 00:00:00";
        $params[':end_date'] = $endDate . " 23:59:59";
    }

    $query .= " GROUP BY song, song_uri
                ORDER BY track_popularity DESC
                LIMIT :limit";

    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTopNicheSongs($pdo, $startDate, $endDate, $limit = 5)
{
    $query = "SELECT 
                song, 
                AVG(track_popularity) AS track_popularity,
                (SELECT song_uri 
                 FROM playbacks AS p2 
                 WHERE p2.song = p1.song
                 ORDER BY ABS(track_popularity - (SELECT AVG(track_popularity) FROM playbacks WHERE song = p1.song)) 
                 LIMIT 1) AS song_uri,
                SUM(seconds_played) / 60 AS minutes_listened
              FROM playbacks AS p1
              WHERE track_popularity > 0
              AND track_popularity IS NOT NULL
              AND song_uri IS NOT NULL";
    
    $params = [];

    if (!empty($startDate) && !empty($endDate)) {
        $query .= " AND played_at BETWEEN :start_date AND :end_date";
        $params[':start_date'] = $startDate . " 00:00:00";
        $params[':end_date'] = $endDate . " 23:59:59";
    }

    $query .= " GROUP BY song
                ORDER BY track_popularity ASC
                LIMIT :limit";

    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function spotifyUriToLink($uri) {
    // Check if the URI is null or empty
    if (empty($uri)) {
        return '#'; // Return a fallback link or placeholder
    }

    // Ensure the URI has the correct format (spotify:artist:id or spotify:track:id)
    $parts = explode(':', $uri);
    if (count($parts) === 3) {
        $type = $parts[1];
        $id = $parts[2];
        return "https://open.spotify.com/$type/$id";
    }

    // If URI is not in expected format, return a placeholder
    return '#';
}

$defaultStartDate = date('Y-m-d', strtotime('-365 days'));
$defaultEndDate = date('Y-m-d');

$startDate = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : $defaultStartDate;
$endDate = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : $defaultEndDate;

$topArtists = getTopArtists($pdo, $startDate, $endDate);
$topSongs = getTopSongs($pdo, $startDate, $endDate);
$topPopularSongs = getTopPopularSongs($pdo, $startDate, $endDate);
$topNicheSongs = getTopNicheSongs($pdo, $startDate, $endDate);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Five</title>
    <link rel="stylesheet" href="./assets/css/top_five.css">
    <link rel="stylesheet" href="./assets/css/navbar.css">
</head>
<body>
<header>
    <div class="navbar">
        <div class="menu">Menu</div>

        <div class="nav-links">
            <?php renderNavbar("top_five.php"); ?>
        </div>
    </div>
</header>

<div class="main-content">

    <!-- Filter Form -->
    <form method="get" action="top_five.php" style="margin-bottom: 20px; display: flex; gap: 15px; align-items: center;">
        <div>
            <label for="start_date" style="color: #b3b3b3; font-weight: bold;">Start Date:</label>
            <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="date-input">
        </div>
        <div>
            <label for="end_date" style="color: #b3b3b3; font-weight: bold;">End Date:</label>
            <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="date-input">
        </div>
        <button type="submit" class="btn-submit">Filter</button>
        <!-- Reset Button -->
        <button type="button" onclick="window.location.href='top_five.php';" class="btn-reset">Reset</button>
    </form>


    <h1>Top Five</h1>
    <div class="top-content">
        <div class="top-row">
            <div class="top-column top-artists">
                <h2>Top Artists</h2>
                <ul>
                    <?php foreach ($topArtists as $artist): ?>
                        <li>
                            <a href="<?= spotifyUriToLink($artist['artist_uri']) ?>" class="spotify-link" target="_blank">
                                <?= htmlspecialchars($artist['artist_name']) ?>
                            </a>
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
                            <a href="./search.php?song=<?= formatsonguri($song['song_uri']) ?>" class="spotify-link">
                                <?= htmlspecialchars($song['song']) ?>
                            </a>
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
                    <?php if (isset($song['song_uri'])): ?>
                        <a href="./search.php?song=<?= formatsonguri($song['song_uri']) ?>" class="spotify-link">
                            <?= htmlspecialchars($song['song']) ?>
                        </a>
                    <?php else: ?>
                        <?= htmlspecialchars($song['song']) ?>
                    <?php endif; ?>
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
        <?php if (isset($song['song_uri'])): ?>
            <a href="./search.php?song=<?= formatsonguri($song['song_uri']) ?>" class="spotify-link">
                <?= htmlspecialchars($song['song']) ?>
            </a>
        <?php else: ?>
            <?= htmlspecialchars($song['song']) ?>
        <?php endif; ?>
        (Popularity Score: <?= number_format($song['track_popularity'], 0, '.', '') ?>)
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
