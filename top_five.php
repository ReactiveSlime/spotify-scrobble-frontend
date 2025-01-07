<?php

require_once './includes/config.php';
require_once './includes/navbar.php';

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME,
    DB_USER,
    DB_PASS
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function getTopArtists($pdo, $interval, $limit = 5)
{
    $validIntervals = ['1 DAY' => '1 DAY', '1 WEEK' => '1 WEEK', '1 MONTH' => '1 MONTH'];
    if (!isset($validIntervals[$interval])) {
        throw new InvalidArgumentException("Invalid interval: $interval");
    }
    $stmt = $pdo->prepare(
        "SELECT artist_name, artist_uri, SUM(seconds_played) / 60 AS minutes_listened
         FROM artists
         WHERE played_at >= NOW() - INTERVAL " . $validIntervals[$interval] . "
         AND artist_name IS NOT NULL AND TRIM(artist_name) != ''
         GROUP BY artist_name, artist_uri
         ORDER BY minutes_listened DESC
         LIMIT :limit"
    );
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTopSongs($pdo, $interval, $limit = 5)
{
    $validIntervals = ['1 DAY' => '1 DAY', '1 WEEK' => '1 WEEK', '1 MONTH' => '1 MONTH'];
    if (!isset($validIntervals[$interval])) {
        throw new InvalidArgumentException("Invalid interval: $interval");
    }
    $stmt = $pdo->prepare(
        "SELECT song, song_uri, SUM(seconds_played) / 60 AS minutes_listened
         FROM playbacks
         WHERE played_at >= NOW() - INTERVAL " . $validIntervals[$interval] . "
         GROUP BY song, song_uri
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

    // Modified query with song_uri in GROUP BY clause
    $stmt = $pdo->prepare(
        "SELECT song, song_uri, MAX(track_popularity) AS track_popularity, SUM(seconds_played) / 60 AS minutes_listened
         FROM playbacks
         WHERE played_at >= NOW() - INTERVAL " . $validIntervals[$interval] . "
         AND song_uri IS NOT NULL
         GROUP BY song, song_uri
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
        "SELECT 
            song, 
            AVG(track_popularity) AS track_popularity,
            (SELECT song_uri 
             FROM playbacks AS p2 
             WHERE p2.song = p1.song
             ORDER BY ABS(track_popularity - (SELECT AVG(track_popularity) FROM playbacks WHERE song = p1.song)) 
             LIMIT 1) AS song_uri,
            SUM(seconds_played) / 60 AS minutes_listened
         FROM playbacks AS p1
         WHERE played_at >= NOW() - INTERVAL " . $validIntervals[$interval] . "
         AND track_popularity > 0
         AND track_popularity IS NOT NULL
         AND song_uri IS NOT NULL
         GROUP BY song
         ORDER BY track_popularity ASC
         LIMIT :limit"
    );
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
                            <a href="<?= spotifyUriToLink($song['song_uri']) ?>" class="spotify-link" target="_blank">
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
                        <a href="<?= spotifyUriToLink($song['song_uri']) ?>" class="spotify-link" target="_blank">
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
            <a href="<?= spotifyUriToLink($song['song_uri']) ?>" class="spotify-link" target="_blank">
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
