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

$searchResults = [];
$searchTerm = "";
$searchType = "artist"; // Default search type

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $searchTerm = $_POST['searchTerm'];  // User input for the search term
    $searchType = $_POST['searchType'];  // 'artist', 'song', 'album', etc.
    $results = searchDatabase($pdo, $searchTerm, $searchType);
    $searchResults = $results; // Store the results to display
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Music</title>
    <link rel="stylesheet" href="./assets/css/styles.css">
    <style>
        /* Add some basic styles for consistent spacing */
        .result-list {
            list-style-type: none;
            padding: 0;
        }
        .result-list li {
            margin-bottom: 20px; /* Add space between each result */
            padding: 10px;
            border-bottom: 1px solid #ddd; /* Light gray line between results */
        }
        .result-list li strong {
            display: inline-block;
            width: 120px; /* Align the labels (e.g. "Song", "Artist") consistently */
        }
        .result-list li .data {
            display: inline-block;
            margin-left: 10px; /* Add space between label and data */
        }
        /* Add spacing around the form elements */
        form {
            margin-bottom: 30px;
        }
        form input, form select, form button {
            margin-right: 10px;
            padding: 10px;
            font-size: 16px;
        }
    </style>
</head>
<body>
<header>
    <div class="navbar">
        <!-- Logo -->
        <div class="logo">My Music</div>

        <!-- Burger Menu Icon -->
        <div class="burger-menu">
            <div></div>
            <div></div>
            <div></div>
        </div>

        <!-- Navigation Links -->
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
            <option value="album" <?= ($searchType == "album") ? "selected" : "" ?>>Album</option>
            <option value="playlist" <?= ($searchType == "playlist") ? "selected" : "" ?>>Playlist</option>
            <option value="device" <?= ($searchType == "device") ? "selected" : "" ?>>Playback Device</option>
            <option value="genre" <?= ($searchType == "genre") ? "selected" : "" ?>>Genre</option>
        </select>
        <button type="submit">Search</button>
    </form>

    <h2>Results</h2>
    <ul class="result-list">
        <?php if (count($searchResults) > 0): ?>
            <?php foreach ($searchResults as $result): ?>
                <li>
                    <!-- Display relevant data depending on the search type -->
                    <?php if ($searchType == "artist"): ?>
                        <strong>Song:</strong><span class="data"><?= htmlspecialchars($result['song']) ?></span> <br>
                        <strong>Artist:</strong><span class="data"><?= htmlspecialchars($result['artist']) ?></span>
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
