<?php
require_once './includes/config.php';
require_once './includes/navbar.php';

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME,
    DB_USER,
    DB_PASS
);

// Function to search the database based on user input
function searchDatabase($pdo, $searchTerm, $searchType) {
    $sql = "";
    switch ($searchType) {
        case 'artist':
            $sql = "SELECT * FROM playbacks WHERE artist LIKE :searchTerm";
            break;
        case 'song':
            $sql = "SELECT * FROM playbacks WHERE song LIKE :searchTerm";
            break;
        case 'album':
            $sql = "SELECT * FROM playbacks WHERE album LIKE :searchTerm";
            break;
        case 'playlist':
            $sql = "SELECT * FROM playbacks WHERE playlist_name LIKE :searchTerm";
            break;
        case 'device':
            $sql = "SELECT * FROM playbacks WHERE playback_device LIKE :searchTerm";
            break;
        case 'genre':
            $sql = "SELECT * FROM playbacks WHERE genres LIKE :searchTerm";
            break;
        default:
            return []; // If no valid search type, return empty
    }

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%', PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to calculate stats
function calculateStats($results) {
    $totalSongs = count($results);
    $totalMinutes = 0;

    foreach ($results as $result) {
        $totalMinutes += (int)($result['seconds_played'] / 60);
    }

    return ['totalSongs' => $totalSongs, 'totalMinutes' => $totalMinutes];
}


$searchResults = [];
$searchTerm = "";
$searchType = "artist"; // Default search type
$stats = ['totalSongs' => 0, 'totalMinutes' => 0];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $searchTerm = $_POST['searchTerm'];  // User input for the search term
    $searchType = $_POST['searchType'];  // 'artist', 'song', 'album', etc.
    $results = searchDatabase($pdo, $searchTerm, $searchType);
    $searchResults = $results; // Store the results to display
    $stats = calculateStats($results);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Music</title>
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
            <?php renderNavbar("search.php"); ?>
        </div>
    </div>
</header>

<div class="main-content">
    <h1>Search Music</h1>

    <!-- Search Form -->
    <form method="POST" action="search.php">
        <input type="text" name="searchTerm" placeholder="Enter search term" value="<?= htmlspecialchars($searchTerm) ?>" required>
        <select name="searchType">
            <option value="artist" <?= ($searchType == "artist") ? "selected" : "" ?>>Artist</option>
            <option value="song" <?= ($searchType == "song") ? "selected" : "" ?>>Song</option>
            <option value="album" <?= ($searchType == "album") ? "selected" : "" ?>>Album</option>
            <option value="playlist" <?= ($searchType == "playlist") ? "selected" : "" ?>>Playlist</option>
            <option value="device" <?= ($searchType == "device") ? "selected" : "" ?>>Playback Device</option>
            <option value="genre" <?= ($searchType == "genre") ? "selected" : "" ?>>Genre</option>
        </select>
        <button type="submit">Search</button>
    </form>

    <!-- Display Stats -->
    <div class="stats">
        Total Songs: <?= $stats['totalSongs'] ?> | Total Minutes: <?= $stats['totalMinutes'] ?>
    </div>

    <h2>Results</h2>
    <ul class="result-list">
        <?php if (count($searchResults) > 0): ?>
            <?php foreach ($searchResults as $result): ?>
                <li>
                    <?php if ($searchType == "artist"): ?>
                        <strong>Artist:</strong><span class="data"><?= htmlspecialchars($result['artist']) ?></span> <br>
                        <strong>Song:</strong><span class="data"><?= htmlspecialchars($result['song']) ?></span>
                    <?php elseif ($searchType == "song"): ?>
                        <strong>Song:</strong><span class="data"><?= htmlspecialchars($result['song']) ?></span> <br>
                        <strong>Artist:</strong><span class="data"><?= htmlspecialchars($result['artist']) ?></span>
                    <?php elseif ($searchType == "album"): ?>
                        <strong>Album:</strong><span class="data"><?= htmlspecialchars($result['album']) ?></span> <br>
                        <strong>Song:</strong><span class="data"><?= htmlspecialchars($result['song']) ?></span>
                    <?php elseif ($searchType == "playlist"): ?>
                        <strong>Playlist:</strong><span class="data"><?= htmlspecialchars($result['playlist_name']) ?></span> <br>
                        <strong>Song:</strong><span class="data"><?= htmlspecialchars($result['song']) ?></span>
                    <?php elseif ($searchType == "device"): ?>
                        <strong>Device:</strong><span class="data"><?= htmlspecialchars($result['playback_device']) ?></span> <br>
                        <strong>Song:</strong><span class="data"><?= htmlspecialchars($result['song']) ?></span>
                    <?php elseif ($searchType == "genre"): ?>
                        <strong>Genre:</strong><span class="data"><?= htmlspecialchars($result['genres']) ?></span> <br>
                        <strong>Song:</strong><span class="data"><?= htmlspecialchars($result['song']) ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li>No results found for your search.</li>
        <?php endif; ?>
    </ul>
</div>

<script src="./assets/js/script.js"></script>
</body>
</html>
