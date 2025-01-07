<?php
require_once './includes/config.php';
require_once './includes/navbar.php';

// Establish PDO connection
$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME,
    DB_USER,
    DB_PASS
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Function to convert UTC to AEST
function convertToAEST($utcDateTime) {
    $utc = new DateTime($utcDateTime, new DateTimeZone('UTC'));
    $utc->setTimezone(new DateTimeZone('Australia/Brisbane'));
    return $utc->format('jS \o\f F \a\t g:ia');
}

// Function to get filtered songs by date range
function getFilteredSongs($pdo, $startDate, $endDate, $order = 'DESC') {
    $sql = "SELECT song, album, artist, played_at, song_uri
            FROM playbacks
            WHERE played_at BETWEEN :start_date AND :end_date
            ORDER BY played_at $order";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':start_date', $startDate);
    $stmt->bindValue(':end_date', $endDate);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get recent songs
function getRecentSongs($pdo, $limit, $order = 'DESC') {
    $sql = "SELECT song, album, artist, played_at, song_uri
            FROM playbacks
            ORDER BY played_at $order
            LIMIT :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function formatSongUri($uri) {
    return str_replace("spotify:track:", "", $uri);
}

// Get the start and end dates from the form (default to today if not set)
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$order = isset($_GET['order']) && $_GET['order'] == 'ASC' ? 'ASC' : 'DESC'; // Default to DESC if not set

// Fetch songs based on date range if selected, or fetch the most recent 10 songs by default
$songs = ($startDate === date('Y-m-d') && $endDate === date('Y-m-d')) 
         ? getRecentSongs($pdo, 10, $order) 
         : getFilteredSongs($pdo, $startDate, $endDate, $order);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Songs by Date</title>
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
            <?php renderNavbar("songs.php"); ?>
        </div>
    </div>
</header>
<div class="main-content">
    <h1>View Songs by Date</h1>

    <!-- Filter Form -->
    <form method="get" action="songs.php" style="margin-bottom: 20px; display: flex; gap: 15px; align-items: center;">
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
        <button type="button" onclick="window.location.href='songs.php';" class="btn-reset">Reset</button>
    </form>

    <!-- Order Button -->
    <form method="get" action="songs.php" style="margin-bottom: 20px;">
        <input type="hidden" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
        <input type="hidden" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
        <button type="submit" name="order" value="<?= $order == 'ASC' ? 'DESC' : 'ASC' ?>" class="btn-submit">
            Order: <?= $order == 'ASC' ? 'Descending' : 'Ascending' ?>
        </button>
    </form>

    <!-- Display Songs -->
    <ul>
    <?php if ($songs): ?>
        <?php foreach ($songs as $song): ?>
            <li>
                <!-- Use the formatted song_uri -->
                <a href="https://open.spotify.com/track/<?= htmlspecialchars(formatSongUri($song['song_uri'])) ?>" target="_blank" class="spotify-link">
                    <?= htmlspecialchars($song['song']) ?> by <?= htmlspecialchars($song['artist']) ?>
                </a>
                (Played on: <?= convertToAEST($song['played_at']) ?>)
            </li>
        <?php endforeach; ?>
    <?php else: ?>
        <li>No songs found for the selected date(s).</li>
    <?php endif; ?>
</ul>
</div>

<script src="./assets/js/script.js"></script>
</body>
</html>
