# Spotify Scrobbler

Spotify Scrobbler is a web application designed to track and display detailed listening statistics from your Spotify account. It integrates a Node.js backend and a PHP-based frontend to provide users with rich insights into their listening habits.

This is the fontend

----------

## Features

The frontend is built using PHP, HTML, JavaScript, and CSS, featuring eight main PHP files:

-   **`/includes/config.php`**: Contains configuration settings.
-   **`/includes/navbar.php`**: Manages the navigation bar for consistent layout across pages.
-   **`/index.php`**: Displays a brief summary of user stats.
-   **`/recent_songs.php`**: Lists the last 10 songs played.
-   **`/playtime_counts.php`**: Shows listening stats for the past 24 hours, week, and month (songs and minutes).
-   **`/top_five.php`**: Highlights the top 5 artists and songs by minutes played and the top and bottom 5 songs by Spotify popularity score.
-   **`/streaks.php`**: Tracks user listening streaks.
-   **`/charts.php`**: Visualizes data with charts.
-   **`/template.php`**: A blank template for creating new pages.

----------

## Installation

1.  Copy the frontend files to your web server's root directory.
2.  Configure the database connection in `/includes/config.php`:
    
    ```php
    <?php
    $db_host = 'your_database_host';
    $db_port = 'your_database_port';
    $db_user = 'your_database_user';
    $db_pass = 'your_database_password';
    $db_name = 'your_database_name';
    ?>
    ```
    
3.  Open the application in a web browser.

---

## Database Structure
`playbacks` Table

| Column | Data Type | Description |
|--|--|--|
| id | INT| Incremental Key |
| song | VARCHAR(255) | Song name |
| album| VARCHAR(255) | Album name |
| Artist| VARCHAR(255) | Artist name |
| genres| VARCHAR(255) | Song genres (If available) |
| duration_ms| INT| Songs duration in MS |
| seconds_played| INT | Seconds played |
| played_at| DATETIME | The time the song was played at stored as UTC |
| album_cover_url| VARCHAR(255) | The link to the album cover |
| song_uri| VARCHAR(255) | Spotifys song URI |
| track_popularity| INT | The popularity of the song according to spotify |
| playback_device| VARCHAR(255) | Device used to play the song |
| release_date| DATE | The date the song was released |
| playlist_name| VARCHAR(255) | The name of the playlist the song played from |

`artists` Table
| Column | Data Type | Description |
|--|--|--|
| id | INT| Incremental Key |
| artist_name| VARCHAR(255) | Artists name |
| seconds_played| INT | Seconds played |
| played_at| DATETIME| The date the artists were played stored as UTC |

----------

## Contributing

Feel free to fork the repository and submit pull requests for improvements or bug fixes.

----------

## License

This project is licensed under the MIT License.

----------

## Acknowledgments

-   [Spotify API](https://developer.spotify.com/documentation/web-api/)
    
-   Last.fm API
    
-   MusicBrainz API