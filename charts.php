<?php
require_once './includes/config.php';
require_once './includes/navbar.php';

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME,
    DB_USER,
    DB_PASS
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Default date range: Last 7 days
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Playback Device Usage (Pie Chart)
$stmt = $pdo->prepare(
    "SELECT playback_device, COUNT(*) AS device_count
     FROM playbacks
     WHERE played_at BETWEEN :start_date AND :end_date
     AND playback_device IS NOT NULL
     GROUP BY playback_device"
);
$stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
$deviceUsage = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Music Play Count by Hour (Column Chart)
$stmt = $pdo->prepare(
    "SELECT HOUR(played_at) AS hour, COUNT(*) AS song_count
     FROM playbacks
     WHERE played_at BETWEEN :start_date AND :end_date
     GROUP BY hour
     ORDER BY hour ASC"
);
$stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
$playCountByHour = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Playback Device by Hour (Column Chart)
$stmt = $pdo->prepare(
    "SELECT HOUR(played_at) AS hour, playback_device, SUM(seconds_played) / 60 AS total_minutes_played
     FROM playbacks
     WHERE played_at BETWEEN :start_date AND :end_date
     AND playback_device IS NOT NULL
     GROUP BY hour, playback_device
     ORDER BY hour ASC"
);
$stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
$deviceByHour = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Most Played Artists (Bar Chart)
$stmt = $pdo->prepare(
    "SELECT artist, SUM(seconds_played) / 60 AS total_minutes
     FROM playbacks
     WHERE played_at BETWEEN :start_date AND :end_date
     GROUP BY artist
     ORDER BY total_minutes DESC
     LIMIT 10"
);
$stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
$topArtists = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top Genres (Pie Chart)
$stmt = $pdo->prepare(
    "SELECT genres, COUNT(*) AS genre_count
     FROM playbacks
     WHERE played_at BETWEEN :start_date AND :end_date
     AND genres IS NOT NULL
     GROUP BY genres
     ORDER BY genre_count DESC
     LIMIT 5"
);
$stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
$topGenres = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Music Statistics Dashboard</title>
    <link rel="stylesheet" href="./assets/css/charts.css">
    <link rel="stylesheet" href="./assets/css/navbar.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<header>
    <div class="navbar">
        <div class="menu">Menu</div>
        <div class="nav-links">
            <?php renderNavbar("charts.php"); ?>
        </div>
    </div>
</header>
    
    <div class="main-content">
        <h1>Music Statistics Dashboard</h1>

        <!-- Date Range Selection -->
        <form method="GET" class="date-range-form">
            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" required>
            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" required>
            <button type="submit">Update</button>
        </form>

        <p>Displaying music stats from <?php echo htmlspecialchars($startDate); ?> to <?php echo htmlspecialchars($endDate); ?>.</p>

        <!-- Playback Device Usage (Pie Chart) -->
        <div class="chart-container">
            <canvas id="deviceUsageChart"></canvas>
        </div>

        <!-- Music Play Count by Hour (Column Chart) -->
        <div class="chart-container">
            <canvas id="playCountByHourChart"></canvas>
        </div>

        <!-- Playback Device by Hour (Column Chart) -->
        <div class="chart-container">
            <canvas id="deviceByHourChart"></canvas>
        </div>

        <!-- Most Played Artists (Bar Chart) -->
        <div class="chart-container">
            <canvas id="topArtistsChart"></canvas>
        </div>

        <!-- Top Genres (Pie Chart) -->
        <div class="chart-container">
            <canvas id="topGenresChart"></canvas>
        </div>
    </div>

    <script>
        // Playback Device Usage (Pie Chart)
        new Chart(document.getElementById('deviceUsageChart'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($deviceUsage, 'playback_device')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($deviceUsage, 'device_count')); ?>,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'],
                }]
            }
        });

        // Music Play Count by Hour (Column Chart)
        new Chart(document.getElementById('playCountByHourChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($playCountByHour, 'hour')); ?>,
                datasets: [{
                    label: 'Songs Played',
                    data: <?php echo json_encode(array_column($playCountByHour, 'song_count')); ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Most Played Artists (Bar Chart)
        new Chart(document.getElementById('topArtistsChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($topArtists, 'artist_name')); ?>,
                datasets: [{
                    label: 'Minutes Played',
                    data: <?php echo json_encode(array_column($topArtists, 'total_minutes')); ?>,
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }]
            }
        });

        // Top Genres (Pie Chart)
        new Chart(document.getElementById('topGenresChart'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($topGenres, 'genre')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($topGenres, 'genre_count')); ?>,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
                }]
            }
        });
    </script>
    <script src="./assets/js/script.js"></script>
</body>
</html>
