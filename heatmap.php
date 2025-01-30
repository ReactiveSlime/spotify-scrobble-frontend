<?php
require_once './includes/config.php';
require_once './includes/navbar.php';

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME,
    DB_USER,
    DB_PASS
);

function getHeatmapData($pdo, $startDate, $endDate) {
    $sql = "SELECT played_at, seconds_played FROM playbacks WHERE played_at BETWEEN :startDate AND :endDate";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':startDate', $startDate);
    $stmt->bindValue(':endDate', $endDate);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Default to the past week if no dates are selected
$endDate = date('Y-m-d 23:59:59');
$startDate = date('Y-m-d 00:00:00', strtotime('-7 days'));

// Check for user-specified date range
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['start_date'], $_GET['end_date'])) {
    $startDate = $_GET['start_date'] . ' 00:00:00';
    $endDate = $_GET['end_date'] . ' 23:59:59';
}

$heatmapData = getHeatmapData($pdo, $startDate, $endDate);
$grid = array_fill(0, 8, array_fill(0, 24, 0));

$maxMinutes = 0;

foreach ($heatmapData as $data) {
    $localDateTime = convertToLocalTimeZoneNotFormated($data['played_at']); // Returns a DateTime object
    $dayIndex = $localDateTime->format('N'); // Day of the week (1 for Monday, 7 for Sunday)
    $hour = (int)$localDateTime->format('G'); // Hour of the day (0-23)
    $minutesPlayed = $data['seconds_played'] / 60;

    $grid[$dayIndex][$hour] += $minutesPlayed;
    $maxMinutes = max($maxMinutes, $grid[$dayIndex][$hour]);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heatmap</title>
    <link rel="stylesheet" href="./assets/css/styles.css">
</head>
<body>
<header>
    <div class="navbar">
        <div class="menu">Menu</div>

        <div class="nav-links">
            <?php renderNavbar("heatmap.php"); ?>
        </div>
    </div>
</header>
<div class="main-content">
    <h1>Minutes Listened Heatmap</h1>

    <!-- Date Filter Form -->
    <form method="GET" action="heatmap.php">
        <label for="start_date">Start Date:</label>
        <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars(substr($startDate, 0, 10)) ?>" required>
        <label for="end_date">End Date:</label>
        <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars(substr($endDate, 0, 10)) ?>" required>
        <button type="submit">Filter</button>
    </form>

    <div class="heatmap-container">
        <div></div>
        <?php for ($hour = 0; $hour < 24; $hour++): ?>
            <div class="hour-label"><?= $hour ?>:00</div>
        <?php endfor; ?>

        <?php
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        foreach ($days as $dayIndex => $dayName):
        ?>
            <div class="day-label"><?= $dayName ?></div>
            <?php for ($hour = 0; $hour < 24; $hour++): ?>
                <?php
                $minutes = $grid[$dayIndex + 1][$hour];
                // Calculate color intensity dynamically based on the maximum value
                $colorIntensity = ($maxMinutes > 0) ? $minutes / $maxMinutes : 0;
                ?>
                <div class="heatmap-item" style="background-color: rgba(255, 0, 0, <?= $colorIntensity ?>);">
                    <span><?= ($minutes > 0) ? round($minutes) : '' ?></span>
                </div>
            <?php endfor; ?>
        <?php endforeach; ?>
    </div>
    <div class="legend">
        <h3>Color Intensity Legend</h3>
        <p>The intensity of the red color represents the number of minutes listened. Darker red indicates more minutes.</p>
        <div class="color-scale">
            <div style="background-color: rgba(255, 0, 0, 0.1);"></div>
            <div style="background-color: rgba(255, 0, 0, 0.4);"></div>
            <div style="background-color: rgba(255, 0, 0, 0.7);"></div>
            <div style="background-color: rgba(255, 0, 0, 1);"></div>
        </div>
        <p>Low → High</p>
    </div>
</div>

<script src="./assets/js/script.js"></script>
</body>
</html>
