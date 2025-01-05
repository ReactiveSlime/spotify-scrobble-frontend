<?php
require_once './includes/config.php';
require_once './includes/navbar.php';

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME,
    DB_USER,
    DB_PASS
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fetch Playback Device Usage (Pie Chart)
$stmt = $pdo->prepare(
    "SELECT playback_device, COUNT(*) AS device_count
     FROM playbacks
     WHERE played_at >= NOW() - INTERVAL 1 WEEK
     AND playback_device IS NOT NULL
     GROUP BY playback_device"
);
$stmt->execute();
$deviceUsage = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Music Play Count by Hour (Column Chart)
$stmt = $pdo->prepare(
    "SELECT HOUR(played_at) AS hour, COUNT(*) AS song_count
     FROM playbacks
     WHERE played_at >= NOW() - INTERVAL 1 WEEK
     GROUP BY hour
     ORDER BY hour ASC"
);
$stmt->execute();
$playCountByHour = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Playback Device by Hour (Column Chart)
$stmt = $pdo->prepare(
    "SELECT HOUR(played_at) AS hour, playback_device, SUM(seconds_played) / 60 AS total_minutes_played
     FROM playbacks
     WHERE played_at >= NOW() - INTERVAL 1 WEEK
     AND playback_device IS NOT NULL
     GROUP BY hour, playback_device
     ORDER BY hour ASC"
);
$stmt->execute();
$deviceByHour = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Music Statistics Dashboard</title>
    <link rel="stylesheet" href="./assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <?php renderNavbar("charts.php"); ?>
            </div>
        </div>
    </header>
    
    <div class="main-content">
        <h1>Music Statistics Dashboard</h1>
        <p>Displaying your music stats for device usage and listening times.</p>

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

        <script>
            // Playback Device Usage (Pie Chart)
            var deviceUsageCtx = document.getElementById('deviceUsageChart').getContext('2d');
            var deviceUsageChart = new Chart(deviceUsageCtx, {
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
            var playCountByHourCtx = document.getElementById('playCountByHourChart').getContext('2d');
            var playCountByHourChart = new Chart(playCountByHourCtx, {
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
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Playback Device by Hour (Column Chart showing total minutes used per device)
            var deviceByHourCtx = document.getElementById('deviceByHourChart').getContext('2d');
            
            // Group devices by hour for the chart
            var deviceData = <?php echo json_encode($deviceByHour); ?>;
            var hours = [];
            var devices = {};
            var datasets = [];

            // Loop through the data and structure it
            deviceData.forEach(function(row) {
                var hour = row.hour;
                var device = row.playback_device;
                var minutesPlayed = row.total_minutes_played;

                if (!hours.includes(hour)) {
                    hours.push(hour);
                }

                if (!devices[device]) {
                    devices[device] = new Array(24).fill(0); // Initialize an array for each device with 24 values (for 24 hours)
                }

                // Add the minutes played for this device in the corresponding hour
                devices[device][hour] = minutesPlayed;
            });

            // Create the datasets for each device
            for (var device in devices) {
                datasets.push({
                    label: device,
                    data: devices[device],
                    backgroundColor: '#FF6384',  // Change color for each device if needed
                    borderColor: '#FF6384',
                    borderWidth: 1
                });
            }

            // Create the chart
            var deviceByHourChart = new Chart(deviceByHourCtx, {
                type: 'bar',
                data: {
                    labels: hours,  // Hours (0-23)
                    datasets: datasets  // Datasets for each device
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Minutes Played'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Hour of Day'
                            }
                        }
                    }
                }
            });
        </script>
    </div>

    <script src="./assets/js/script.js"></script>
</body>
</html>
