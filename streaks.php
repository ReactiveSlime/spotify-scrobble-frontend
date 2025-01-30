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
    // Fetch unique dates and the count of songs listened to on each day
    $stmt = $pdo->query(
        "SELECT DATE(played_at) as play_date, COUNT(*) as song_count
         FROM artists
         GROUP BY play_date
         ORDER BY play_date ASC"
    );
    $dates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $currentStreak = 0;
    $longestStreak = 0;
    $previousDate = null;

    foreach ($dates as $date) {
        if ($previousDate && (strtotime($date['play_date']) - strtotime($previousDate) === 86400)) {
            $currentStreak++;
        } else {
            $currentStreak = 1;
        }
        $longestStreak = max($longestStreak, $currentStreak);
        $previousDate = $date['play_date'];
    }

    return [
        'currentStreak' => $currentStreak,
        'longestStreak' => $longestStreak,
        'dates' => $dates,
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
        <div class="menu">Menu</div>
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

    <!-- Achievements Section -->
    <div class="achievements">
        <h2>Achievements</h2>
        <ul>
            <li class="<?= $streaks['longestStreak'] >= 7 ? 'unlocked' : 'locked' ?>">7-Day Streak</li>
            <li class="<?= $streaks['longestStreak'] >= 30 ? 'unlocked' : 'locked' ?>">30-Day Streak</li>
            <li class="<?= $streaks['longestStreak'] >= 100 ? 'unlocked' : 'locked' ?>">100-Day Streak</li>
            <li class="<?= $streaks['longestStreak'] >= 365 ? 'unlocked' : 'locked' ?>">1 Year Streak</li>
        </ul>
    </div>

    <!-- Calendar View -->
    <h2>Calendar View</h2>
    <div class="calendar">
        <?php
        foreach ($streaks['dates'] as $date) {
            $day = htmlspecialchars($date['play_date']);
            $songCount = htmlspecialchars($date['song_count']);
            echo "<div class='calendar-day active'>
                    <div class='date'>$day</div>
                    <div class='count'>$songCount song(s)</div>
                  </div>";
        }
        ?>
    </div>
</div>

<!-- JavaScript and Styles -->
<script src="./assets/js/script.js"></script>
</body>
</html>
