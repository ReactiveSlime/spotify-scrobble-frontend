<?php
require_once './includes/config.php';
require_once './includes/navbar.php';
$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME,
    DB_USER,
    DB_PASS
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function getSongStats($pdo, $interval) {
    $validIntervals = ['1 DAY' => '1 DAY', '1 WEEK' => '1 WEEK', '1 MONTH' => '1 MONTH'];
    if (!isset($validIntervals[$interval])) {
        throw new InvalidArgumentException("Invalid interval: $interval");
    }
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS song_count 
         FROM playbacks WHERE played_at >= NOW() - INTERVAL " . $validIntervals[$interval]
    );
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$daySongStats = getSongStats($pdo, '1 DAY');
$weekSongStats = getSongStats($pdo, '1 WEEK');
$monthSongStats = getSongStats($pdo, '1 MONTH');

function getListeningStats($pdo, $interval) {
    $validIntervals = ['1 DAY' => '1 DAY', '1 WEEK' => '1 WEEK', '1 MONTH' => '1 MONTH'];
    if (!isset($validIntervals[$interval])) {
        throw new InvalidArgumentException("Invalid interval: $interval");
    }
    $stmt = $pdo->prepare(
        "SELECT SUM(seconds_played) / 60 AS minutes_listened 
         FROM playbacks WHERE played_at >= NOW() - INTERVAL " . $validIntervals[$interval]
    );
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$dayListeningStats = getListeningStats($pdo, '1 DAY');
$weekListeningStats = getListeningStats($pdo, '1 WEEK');
$monthListeningStats = getListeningStats($pdo, '1 MONTH');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Song Counts and Listening Stats</title>
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
            <?php renderNavbar("playtime_counts.php"); ?>
        </div>
    </div>
</header>

<div class="main-content">
    <section class="song-stats">
        <h1>Song Counts</h1>
        <ul>
            <li>
                <strong>Past 24 Hours:</strong>
                <span class="count"><?= $daySongStats['song_count'] ?> songs</span>
            </li>
            <li>
                <strong>Past Week:</strong>
                <span class="count"><?= $weekSongStats['song_count'] ?> songs</span>
            </li>
            <li>
                <strong>Past Month:</strong>
                <span class="count"><?= $monthSongStats['song_count'] ?> songs</span>
            </li>
        </ul>
    </section>

    <section class="listening-stats">
        <h1>Listening Time</h1>
        <ul>
            <li>
                <strong>Past 24 Hours:</strong>
                <span class="time"><?= ceil($dayListeningStats['minutes_listened']) ?> minutes</span>
            </li>
            <li>
                <strong>Past Week:</strong>
                <span class="time"><?= ceil($weekListeningStats['minutes_listened']) ?> minutes</span>
            </li>
            <li>
                <strong>Past Month:</strong>
                <span class="time"><?= ceil($monthListeningStats['minutes_listened']) ?> minutes</span>
            </li>
        </ul>
    </section>
</div>

<script src="./assets/js/script.js"></script>
</body>
</html>