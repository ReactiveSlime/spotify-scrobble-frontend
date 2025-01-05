<?php
require_once './includes/config.php';
require_once './includes/navbar.php';

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME,
    DB_USER,
    DB_PASS
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function calculateStreaks($pdo) {
    // Get unique dates when songs were played
    $stmt = $pdo->query(
        "SELECT DISTINCT DATE(played_at) as play_date 
         FROM artists
         ORDER BY play_date ASC"
    );
    $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $currentStreak = 0;
    $longestStreak = 0;
    $previousDate = null;

    foreach ($dates as $date) {
        if ($previousDate && (strtotime($date) - strtotime($previousDate) === 86400)) {
            // Increment streak if the current date is consecutive
            $currentStreak++;
        } else {
            // Reset current streak
            $currentStreak = 1;
        }
        $longestStreak = max($longestStreak, $currentStreak);
        $previousDate = $date;
    }

    return [
        'currentStreak' => $currentStreak,
        'longestStreak' => $longestStreak,
    ];
}

$streaks = calculateStreaks($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listening Streaks</title>
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
    <?php renderNavbar("streaks.php"); ?>
    </div>
</div>
    </header>
    <div class="main-content">
        <h1>Listening Streaks</h1>
        <ul>
            <li>Current Streak: <?= htmlspecialchars($streaks['currentStreak']) ?> days</li>
            <li>Longest Streak: <?= htmlspecialchars($streaks['longestStreak']) ?> days</li>
        </ul>
    </div>
    <script src="./assets/js/script.js"></script>
</body>
</html>
