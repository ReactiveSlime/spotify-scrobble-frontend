<?php
require_once './includes/config.php';
require_once './includes/navbar.php';

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME,
    DB_USER,
    DB_PASS
);

function getHeatmapData($pdo) {
    $sql = "SELECT played_at, seconds_played FROM playbacks";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$heatmapData = getHeatmapData($pdo);
$grid = array_fill(0, 8, array_fill(0, 24, 0));

foreach ($heatmapData as $data) {
    $localTime = convertToLocalTimeZone($data['played_at']);
    $dateTime = DateTime::createFromFormat('jS \o\f F \a\t g:ia', $localTime);
    $dayIndex = $dateTime->format('N');
    $hour = (int)$dateTime->format('G');
    $grid[$dayIndex][$hour] += $data['seconds_played'] / 60;
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
        <div class="logo">My Music</div>
        <div class="burger-menu">
            <div></div>
            <div></div>
            <div></div>
        </div>
        <div class="nav-links">
            <?php renderNavbar("heatmap.php"); ?>
        </div>
    </div>
</header>
<div class="main-content">
    <h1>Minutes Listened Heatmap</h1>
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
                $colorIntensity = min(255, round($minutes * 10));
                ?>
                <div class="heatmap-item" style="background-color: rgba(255, 0, 0, <?= $colorIntensity / 255 ?>);">
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
