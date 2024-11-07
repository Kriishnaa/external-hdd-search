<?php
// Function to recursively scan a directory and get all files and folders
function scanDirectory($dir) {
    $filesAndDirs = [];

    // Make sure the directory exists
    if (is_dir($dir)) {
        // Scan directory and get all files and subdirectories
        $dirContent = scandir($dir);

        // Filter out '.' and '..' entries
        $dirContent = array_diff($dirContent, array('.', '..'));

        foreach ($dirContent as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_file($path)) {
                $filesAndDirs[] = ['path' => $path, 'type' => 'file']; // Add file to the list
            } elseif (is_dir($path)) {
                $filesAndDirs[] = ['path' => $path, 'type' => 'dir']; // Add directory to the list
                // Recursively scan subdirectories
                $filesAndDirs = array_merge($filesAndDirs, scanDirectory($path));
            }
        }
    }

    return $filesAndDirs;
}

// Function to search files/folders by name (either partial or full match)
function searchFilesAndDirs($directory, $searchTerm, $fullTextSearch = false) {
    $filesAndDirs = scanDirectory($directory);
    $results = [];

    // Search for the term in file names and folder names based on the selected search type
    foreach ($filesAndDirs as $item) {
        $path = $item['path'];
        $type = $item['type'];

        if ($fullTextSearch) {
            // Full-text search: exact match of file/folder name
            if (strcasecmp(basename($path), $searchTerm) === 0) {
                $results[] = $item;
            }
        } else {
            // Partial search: match the search term anywhere in the file/folder name
            if (stripos(basename($path), $searchTerm) !== false) {
                $results[] = $item;
            }
        }
    }

    return $results;
}

// Function to get the file size in a human-readable format
function getFileSize($filePath) {
    // Get the file size in bytes
    $size = filesize($filePath);

    // Convert to a human-readable format (KB, MB, GB)
    if ($size < 1024) {
        return $size . ' bytes';
    } elseif ($size < 1048576) {
        return round($size / 1024, 2) . ' KB';
    } elseif ($size < 1073741824) {
        return round($size / 1048576, 2) . ' MB';
    } else {
        return round($size / 1073741824, 2) . ' GB';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Files and Folders on E: Drive</title>
    <style>
        /* General Styles */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
            transition: background-color 0.3s ease;
        }

        h1 {
            text-align: center;
            padding: 20px;
            background-color: #67b26f;
            color: white;
            margin: 0;
            font-size: 32px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        form {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 30px 0;
            padding: 15px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 30px auto;
            transition: all 0.3s ease;
        }

        input[type="text"] {
            padding: 12px;
            margin-right: 10px;
            width: 60%;
            max-width: 400px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus {
            border-color: #67b26f;
        }

        button {
            padding: 12px 16px;
            background-color: #67b26f;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease, transform 0.2s;
        }

        button:hover {
            background-color: #5a9c5d;
            transform: scale(1.05);
        }

        button:active {
            transform: scale(0.98);
        }

        label {
            font-size: 16px;
            margin-right: 10px;
        }

        /* Table Styling */
        table {
            width: 90%;
            max-width: 1000px;
            margin: 30px auto;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        th, td {
            padding: 12px;
            text-align: left;
            font-size: 14px;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #67b26f;
            color: white;
        }

        td a {
            color: #67b26f;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        td a:hover {
            color: #3b9a55;
            text-decoration: underline;
        }

        /* Loading Spinner */
        #loading {
            display: none;
            text-align: center;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 18px;
            font-weight: bold;
            color: #67b26f;
            text-transform: uppercase;
            animation: fadeIn 0.5s ease-in-out;
        }

        #loading img {
            width: 50px;
            animation: rotate 1.5s infinite linear;
            margin-right: 15px;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }

        #timer {
            font-weight: bold;
            font-size: 18px;
            margin-top: 10px;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            form {
                flex-direction: column;
                align-items: stretch;
            }

            input[type="text"] {
                width: 100%;
                margin-right: 0;
                margin-bottom: 10px;
            }

            button {
                width: 100%;
                padding: 14px;
            }

            table {
                width: 95%;
            }

            td, th {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>

<h1>Search for Files and Folders on E: Drive</h1>

<!-- Form to accept search term -->
<form method="GET" action="">
    <label for="searchTerm">Search Term:</label>
    <input type="text" name="searchTerm" id="searchTerm" value="<?php echo isset($_GET['searchTerm']) ? htmlspecialchars($_GET['searchTerm']) : ''; ?>" required>
    <label for="fullTextSearch">Full Text Search</label>
    <input type="checkbox" name="fullTextSearch" id="fullTextSearch" <?php echo isset($_GET['fullTextSearch']) ? 'checked' : ''; ?>>
    <button type="submit" id="searchBtn">Search</button>
</form>

<!-- Loading spinner -->
<div id="loading">
    Searching...
    <img src="https://i.imgur.com/6RMxTQf.gif" alt="Loading">
    <br>
    <!-- <div id="timer">Time Elapsed: 0s</div> -->
</div>

<?php
// Handle the search when form is submitted
if (isset($_GET['searchTerm'])) {
    $searchTerm = $_GET['searchTerm'];
    $fullTextSearch = isset($_GET['fullTextSearch']) ? true : false; // Check if the full-text search checkbox is checked
    //$directory = "E:/KRISHNAKUMAR/"; // Path to the E: drive
    $directory = "E:/"; 

    // Check if the directory exists
    if (is_dir($directory)) {
        // Perform the search
        $results = searchFilesAndDirs($directory, $searchTerm, $fullTextSearch);

        // Count the results
        $resultCount = count($results);

        // Display the result count and search results in a table
        echo "<h2 align='center'>Found $resultCount result(s) for '$searchTerm'</h2>";

        if ($resultCount > 0) {
            echo "<table>";
            echo "<tr><th>Path</th><th>Type</th><th>Action</th><th>File Size</th></tr>";

            foreach ($results as $result) {
                $path = $result['path'];
                $type = $result['type'];

                if ($type == 'file') {
                    $fileSize = getFileSize($path); // Get the size of the file
                    $fileUrl = "download.php?file=" . urlencode($path); // Link to the download handler
                    $fileName = basename($path); // Get the file name without the path

                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($path) . "</td>";
                    echo "<td>File</td>";
                    echo "<td><a href='" . htmlspecialchars($fileUrl) . "'>Download</a></td>";
                    echo "<td>" . $fileSize . "</td>";
                    echo "</tr>";
                } elseif ($type == 'dir') {
                    // For directories, no file size and download option
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($path) . "</td>";
                    echo "<td>Folder</td>";
                    echo "<td>-</td>";
                    echo "<td>-</td>";
                    echo "</tr>";
                }
            }

            echo "</table>";
        } else {
            echo "<h3 align='center' style='color:red'>No files or folders found for '$searchTerm'.</h3>";
        }
    } else {
        echo "<h3 align='center' style='color:blue'>The directory E:/ does not exist or cannot be accessed.</h3>";
    }
}
?>

<script>
    // JavaScript for loading effect and timer
    document.getElementById('searchBtn').addEventListener('click', function () {
        if(document.getElementById('searchTerm').value == ''){
            return false;
        }
        // Show the loading spinner
        document.getElementById('loading').style.display = 'flex';

        // Start the timer
        let seconds = 0;
        const timerElement = document.getElementById('timer');
        const timerInterval = setInterval(function () {
            seconds++;
            timerElement.textContent = 'Time Elapsed: ' + seconds + 's';
        }, 1000);

        // Hide the loading spinner and stop the timer once the form is submitted
        setTimeout(function () {
            clearInterval(timerInterval); // Stop the timer
        }, 10000); // Stop after 10 seconds (simulate processing time)
    });
</script>

</body>
</html>
