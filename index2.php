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
    <title>Search Files and Folders</title>
    <!-- Bootstrap 4 CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        .container {
            display: flex;
            flex-direction: column;
            height: 100%;
            padding: 0;
        }

        /* Form section takes up 15% of the height */
        .form-section {
            flex: 0 0 15%;
            padding: 20px;
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }

        /* Results section takes up 85% of the height */
        .result-section {
            flex: 1;
            overflow-y: auto; /* Scrollable if results exceed available space */
            padding: 20px;
            background-color: white;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #343a40;
        }

        .form-group label {
            font-weight: bold;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #28a745;
        }

        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }

        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }

        .spinner-border {
            display: none;
        }

        table {
            margin-top: 30px;
            width: 100%;
        }

        td a {
            color: #28a745;
            font-weight: bold;
            text-decoration: none;
        }

        td a:hover {
            color: #218838;
            text-decoration: underline;
        }

        .loading-spinner {
            display: none;
            text-align: center;
        }

        /* Adjustments for small screens */
        @media (max-width: 768px) {
            .form-section {
                flex: 0 0 20%; /* Adjust for smaller screens */
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Form Section (15% height) -->
    <div class="form-section">
        <h1>Search for Files and Folders</h1>
        <form method="GET" action="" id="searchForm">
            <div class="form-group">
                <label for="directory">Directory Path:</label>
                <input type="text" class="form-control" name="directory" id="directory" placeholder="Enter directory path (e.g., E:/)" value="<?php echo isset($_GET['directory']) ? htmlspecialchars($_GET['directory']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="searchTerm">Search Term:</label>
                <input type="text" class="form-control" name="searchTerm" id="searchTerm" placeholder="Enter the file or folder name to search" value="<?php echo isset($_GET['searchTerm']) ? htmlspecialchars($_GET['searchTerm']) : ''; ?>" required>
            </div>

            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="fullTextSearch" id="fullTextSearch" <?php echo isset($_GET['fullTextSearch']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="fullTextSearch">Full Text Search</label>
            </div>

            <button type="submit" class="btn btn-success btn-block mt-4" id="searchBtn">Search</button>
            <button type="button" class="btn btn-danger btn-block mt-2" id="resetBtn" onclick="window.location.href=window.location.origin + window.location.pathname;">Reset</button>
        </form>
    </div>

    <!-- Result Section (85% height) -->
    <div class="result-section">
        <!-- Loading Spinner -->
        <div class="loading-spinner mt-3" id="loading">
            <span class="spinner-border text-success" role="status"></span>
        </div>

        <?php
        // Handle the search when form is submitted
        if (isset($_GET['searchTerm']) && isset($_GET['directory'])) {
            $searchTerm = $_GET['searchTerm'];
            $directory = $_GET['directory']; 
            $fullTextSearch = isset($_GET['fullTextSearch']) ? true : false;

            if (is_dir($directory)) {
                $results = searchFilesAndDirs($directory, $searchTerm, $fullTextSearch);
                $resultCount = count($results);

                echo "<h2 class='text-center'>Found $resultCount result(s) for '$searchTerm' in '$directory'</h2>";

                if ($resultCount > 0) {
                    echo "<table class='table table-striped table-bordered'>";
                    echo "<thead><tr><th>Path</th><th>Type</th><th>Action</th><th>File Size</th></tr></thead>";
                    echo "<tbody>";

                    foreach ($results as $result) {
                        $path = $result['path'];
                        $type = $result['type'];

                        if ($type == 'file') {
                            $fileSize = getFileSize($path);
                            $fileUrl = "download.php?file=" . urlencode($path);

                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($path) . "</td>";
                            echo "<td>File</td>";
                            echo "<td><a href='" . htmlspecialchars($fileUrl) . "'>Download</a></td>";
                            echo "<td>" . $fileSize . "</td>";
                            echo "</tr>";
                        } elseif ($type == 'dir') {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($path) . "</td>";
                            echo "<td>Folder</td>";
                            echo "<td>-</td>";
                            echo "<td>-</td>";
                            echo "</tr>";
                        }
                    }

                    echo "</tbody></table>";
                } else {
                    echo "<p class='text-center text-danger'>No files or folders found for '$searchTerm'.</p>";
                }
            } else {
                echo "<p class='text-center text-danger'>The directory '$directory' does not exist or cannot be accessed.</p>";
            }
        }
        ?>
    </div>
</div>

<!-- Bootstrap 4 JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

<script>
    document.getElementById('searchBtn').addEventListener('click', function () {
        // Show the loading spinner when search starts
        document.getElementById('loading').style.display = 'block';
    });
</script>

</body>
</html>
