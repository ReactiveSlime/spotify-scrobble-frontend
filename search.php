<?php
require_once './includes/config.php';
require_once './includes/navbar.php';

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME,
    DB_USER,
    DB_PASS
);

// Initialize $searchTerm to avoid undefined variable notice
$searchTerm = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['searchTerm'])) {
    $searchTerm = $_POST['searchTerm'];
}

// Function to search for songs by name
function searchSongs($pdo, $searchTerm) {
    $sql = "SELECT DISTINCT song, song_uri FROM playbacks WHERE song LIKE :searchTerm";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%', PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to fetch detailed information about a specific song
function getSongDetails($pdo, $songUri) {
    // Ensure the songUri includes the full Spotify URI format
    if (strpos($songUri, 'spotify:track:') === false) {
        $songUri = 'spotify:track:' . $songUri;
    }

    $sql = "SELECT * FROM playbacks WHERE song_uri = :songUri ORDER BY played_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':songUri', $songUri, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// Function to fetch artist URI from the artists table
function getArtistUri($pdo, $artistName) {
    $sql = "SELECT artist_uri FROM artists WHERE artist_name = :artistName";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':artistName', $artistName, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchColumn(); // Returns the artist URI or false if not found
}

// Function to extract the last portion of the Spotify URI (strip "spotify:" part and any duplicate portions)
function getSpotifyIdFromUri($uri) {
    // Remove 'spotify:' if present
    $uri = str_replace('spotify:', '', $uri);
    // Split URI by the colon to ensure only the final portion is used
    $uriParts = explode(':', $uri);
    // Return the last part of the URI as the Spotify ID
    return end($uriParts);
}

// Handle whether to display search results or song details
$songDetails = [];
if (isset($_GET['song'])) {
    $songUri = $_GET['song'];
    $songDetails = getSongDetails($pdo, $songUri);
} else {
    $searchResults = [];
    $searchTerm = "";
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['searchTerm'])) {
        $searchTerm = $_POST['searchTerm'];
        $searchResults = searchSongs($pdo, $searchTerm);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if (!empty($songDetails)): ?>
        <?php $firstEntry = $songDetails[0]; ?>
        <title><?= htmlspecialchars($firstEntry['song']) ?></title>
        <!--<link rel="icon" href="<?= htmlspecialchars($firstEntry['album_cover_url']) ?>" type="image/png"> -->
    <?php else: ?>
        <title>Search Songs</title>
    <?php endif; ?>
    <link rel="stylesheet" href="./assets/css/styles.css">
</head>
<body>
<header>
    <div class="navbar">
        <div class="menu">Menu</div>
        <div class="nav-links">
            <?php renderNavbar("search.php"); ?>
        </div>
    </div>
</header>
<div class="main-content">
    <h1>Search Songs</h1>

    <!-- Search Form -->
    <form method="POST" action="search.php">
        <input type="text" name="searchTerm" placeholder="Enter song name" value="<?= htmlspecialchars($searchTerm) ?>" required>
        <button type="submit">Search</button>
    </form>

    <?php if (empty($songDetails)): ?>
        <!-- Search Results -->
        <h2>Search Results</h2>
        <ul class="result-list">
            <?php if (!empty($searchResults)): ?>
                <?php foreach ($searchResults as $result): ?>
                    <li>
                        <a href="search.php?song=<?= urlencode(getSpotifyIdFromUri($result['song_uri'])) ?>" class="spotify-link">
                            <?= htmlspecialchars($result['song']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>No songs found.</li>
            <?php endif; ?>
        </ul>
    <?php else: ?>
        <!-- Song Details -->
        <h2>Song Details</h2>
        <?php if (!empty($songDetails)): ?>
            <?php
            $firstEntry = $songDetails[0];
            $totalMinutesPlayed = 0;
            $playbacks = [];
            foreach ($songDetails as $entry) {
                $totalMinutesPlayed += $entry['seconds_played'] / 60;
                $playbacks[] = [
                    'played_at' => $entry['played_at'],
                    'device' => $entry['playback_device'],
                    'playlist_name' => $entry['playlist_name'],
                    'playlist_uri' => $entry['playlist_uri'],
                    'seconds_played' => $entry['seconds_played']
                ];
            }

            $artistUri = getArtistUri($pdo, $firstEntry['artist']);
            ?>
            <div class="song-details-container">
                <!-- Album Cover -->
                <div class="album-cover">
                    <img src="<?= htmlspecialchars($firstEntry['album_cover_url']) ?>" alt="Album Cover" style="max-width: 300px;">
                </div>

                <!-- Song Info -->
                <div class="song-info">
                    <p>
                        <strong>Song:</strong> 
                        <a href="https://open.spotify.com/track/<?= htmlspecialchars(getSpotifyIdFromUri($firstEntry['song_uri'])) ?>" target="_blank" class="spotify-link">
                            <?= htmlspecialchars($firstEntry['song']) ?>
                        </a>
                    </p>
                    <p>
                        <strong>Artist(s):</strong> 
                        <?php
                            // Split the artists string by commas
                            $artists = explode(',', $firstEntry['artist']);
                            $artistLinks = [];
                                
                            foreach ($artists as $artistName) {
                                $artistName = trim($artistName); // Remove any extra whitespace
                                $artistUri = getArtistUri($pdo, $artistName);
                                
                                if ($artistUri) {
                                    // Create a clickable link if the artist URI exists
                                    $artistLinks[] = '<a href="https://open.spotify.com/artist/' . htmlspecialchars(explode(':', $artistUri)[2]) . '" target="_blank" class="spotify-link">'
                                        . htmlspecialchars($artistName) . '</a>';
                                } else {
                                    // Otherwise, just display the artist name
                                    $artistLinks[] = htmlspecialchars($artistName);
                                }
                            }
                        
                            // Display the artist links separated by commas
                            echo implode(', ', $artistLinks);
                        ?>
                    </p>

                    <p>
                        <strong>Genres:</strong> <?= htmlspecialchars($firstEntry['genres']) ?>
                    </p>
                    <p>
                        <strong>Release Date:</strong> <?= htmlspecialchars($firstEntry['release_date']) ?>
                    </p>
                    <p>
                        <strong>Track Popularity:</strong> <?= htmlspecialchars($firstEntry['track_popularity']) ?>
                    </p>
                    <p>
                        <strong>Total Minutes Played:</strong> <?= round($totalMinutesPlayed, 2) ?>
                    </p>
                </div>
            </div>

            <h3>Playback Details</h3>
            <table class="info-table">
                <thead>
                    <tr>
                        <th>Played At</th>
                        <th>Seconds Played</th>
                        <th>Playback Device</th>
                        <th>Playlist Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($playbacks as $playback): ?>
                        <tr>
                            <td><?= htmlspecialchars($playback['played_at']) ?></td>
                            <td><?= htmlspecialchars($playback['seconds_played']) ?></td>
                            <td><?= htmlspecialchars($playback['device']) ?></td>
                            <td>
                                <?php if (!empty($playback['playlist_name'])): ?>
                                    <a href="https://open.spotify.com/playlist/<?= urlencode(getSpotifyIdFromUri($playback['playlist_uri'])) ?>" target="_blank" class="spotify-link">
                                        <?= htmlspecialchars($playback['playlist_name']) ?>
                                    </a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php else: ?>
            <p>No details found for this song.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>
<script src="./assets/js/script.js"></script>
</body>
</html>
