<?php
// Function to recursively scan a directory and get all files and folders
function scanDirectory($dir) {
    $filesAndDirs = ['files' => [], 'dirs' => []];
    if (is_dir($dir)) {
        $dirContent = scandir($dir);
        $dirContent = array_diff($dirContent, array('.', '..'));

        foreach ($dirContent as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_file($path)) {
                $filesAndDirs['files'][] = ['path' => $path, 'type' => 'file'];
            } elseif (is_dir($path)) {
                $filesAndDirs['dirs'][] = ['path' => $path, 'type' => 'dir'];
                // Recursively scan subdirectories
                $subFilesAndDirs = scanDirectory($path);
                $filesAndDirs['files'] = array_merge($filesAndDirs['files'], $subFilesAndDirs['files']);
                $filesAndDirs['dirs'] = array_merge($filesAndDirs['dirs'], $subFilesAndDirs['dirs']);
            }
        }
    }
    return $filesAndDirs;
}

function searchFilesAndDirs($directory, $searchTerm, $fullTextSearch = false) {
    $filesAndDirs = scanDirectory($directory);
    $results = [];
    foreach ($filesAndDirs['files'] as $item) {
        $path = $item['path'];
        if ($fullTextSearch) {
            if (strcasecmp(basename($path), $searchTerm) === 0) {
                $results[] = $item;
            }
        } else {
            if (stripos(basename($path), $searchTerm) !== false) {
                $results[] = $item;
            }
        }
    }
    foreach ($filesAndDirs['dirs'] as $item) {
        $path = $item['path'];
        if ($fullTextSearch) {
            if (strcasecmp(basename($path), $searchTerm) === 0) {
                $results[] = $item;
            }
        } else {
            if (stripos(basename($path), $searchTerm) !== false) {
                $results[] = $item;
            }
        }
    }
    return $results;
}

function getFileSize($filePath) {
    $size = filesize($filePath);
    if ($size < 1024) return $size . ' bytes';
    elseif ($size < 1048576) return round($size / 1024, 2) . ' KB';
    elseif ($size < 1073741824) return round($size / 1048576, 2) . ' MB';
    else return round($size / 1073741824, 2) . ' GB';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Files and Folders</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body, html { height: 100%; margin: 0; font-family: Arial, sans-serif; }
        .container { display: flex; flex-direction: column; height: 100%; padding: 0; }
        .form-section { flex: 0 0 15%; padding: 10px 15px; background-color: #f8f9fa; border-bottom: 2px solid #e9ecef; }
        .result-section { flex: 1; overflow-y: auto; padding: 15px; background-color: white; }
        h1 { text-align: center; margin-bottom: 20px; color: #343a40; }
        .form-control:focus { box-shadow: none; border-color: #28a745; }
        .btn-success { background-color: #28a745; border-color: #28a745; }
        .btn-success:hover { background-color: #218838; }
        .btn-danger { background-color: #dc3545; border-color: #dc3545; }
        .btn-danger:hover { background-color: #c82333; }
        .table { margin-top: 20px; }
        td a { color: #28a745; font-weight: bold; text-decoration: none; }
        td a:hover { color: #218838; text-decoration: underline; }
        .loading-spinner { display: none; text-align: center; }
        .file-row { background-color: #d1ecf1; } /* Light Blue for Files */
        .dir-row { background-color: #f8d7da; } /* Light Gray for Folders */
        @media (max-width: 768px) { .form-section { flex: 0 0 20%; } }
    </style>
</head>
<body>

<div class="container">
    <div class="form-section">
        <form method="GET" action="" id="searchForm">
            <div class="form-row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="directory">Directory Path:</label>
                        <input type="text" class="form-control" name="directory" id="directory" placeholder="Enter directory path" value="<?php echo isset($_GET['directory']) ? htmlspecialchars($_GET['directory']) : ''; ?>" required>
                        <small id="directoryError" class="form-text text-danger" style="display: none;">Please enter a valid directory path.</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="searchTerm">Search Term:</label>
                        <input type="text" class="form-control" name="searchTerm" id="searchTerm" placeholder="Enter file/folder name" value="<?php echo isset($_GET['searchTerm']) ? htmlspecialchars($_GET['searchTerm']) : ''; ?>" required>
                        <small id="searchTermError" class="form-text text-danger" style="display: none;">Please enter a search term.</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <button type="submit" class="btn btn-success btn-block" id="searchBtn">Search</button>
                        <button type="button" class="btn btn-danger btn-block" id="resetBtn" onclick="window.location.href=window.location.origin + window.location.pathname;">Reset</button>
                        <div class="form-check mt-2">
                            <input type="checkbox" class="form-check-input" name="fullTextSearch" id="fullTextSearch" <?php echo isset($_GET['fullTextSearch']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="fullTextSearch">Full Text Search</label>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="result-section">
        <div class="loading-spinner" id="loading">
            <span class="spinner-border text-success" role="status"></span>
        </div>

        <?php
        if (isset($_GET['searchTerm']) && isset($_GET['directory'])) {
            $searchTerm = $_GET['searchTerm'];
            $directory = $_GET['directory']; 
            $fullTextSearch = isset($_GET['fullTextSearch']) ? true : false;

            if (is_dir($directory)) {
                $results = searchFilesAndDirs($directory, $searchTerm, $fullTextSearch);
                $resultCount = count($results);
                $fileCount = 0;
                $dirCount = 0;

                foreach ($results as $result) {
                    if ($result['type'] == 'file') {
                        $fileCount++;
                    } elseif ($result['type'] == 'dir') {
                        $dirCount++;
                    }
                }

                echo "<h2 class='text-center'>Found $resultCount result(s) for '$searchTerm' in '$directory' (Files: $fileCount, Folders: $dirCount)</h2>";

                if ($resultCount > 0) {
                    echo "<table class='table table-striped table-bordered' id='resultTable'>";
                    echo "<thead><tr><th>Path</th><th>Type</th><th>Action</th><th>File Size</th></tr></thead><tbody>";

                    foreach ($results as $result) {
                        $path = $result['path'];
                        $type = $result['type'];
                        $fileSize = $type == 'file' ? getFileSize($path) : '-';
                        $action = $type == 'file' ? "<a href='download.php?file=" . urlencode($path) . "'>Download</a>" : '-';

                        // Add color for files and folders
                        $rowClass = ($type == 'file') ? 'file-row' : 'dir-row';

                        echo "<tr class='$rowClass'><td>" . htmlspecialchars($path) . "</td><td>$type</td><td>$action</td><td>$fileSize</td></tr>";
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

<script>
    // Show the loading spinner when search starts
    document.getElementById('searchBtn').addEventListener('click', function (e) {
        // Validation
        let directory = document.getElementById('directory').value.trim();
        let searchTerm = document.getElementById('searchTerm').value.trim();

        if (!directory || !searchTerm) {
            e.preventDefault();
            if (!directory) document.getElementById('directoryError').style.display = 'block';
            if (!searchTerm) document.getElementById('searchTermError').style.display = 'block';
        } else {
            document.getElementById('loading').style.display = 'block';
        }
    });

    // Hide error messages on input change
    document.getElementById('directory').addEventListener('input', function () {
        document.getElementById('directoryError').style.display = 'none';
    });

    document.getElementById('searchTerm').addEventListener('input', function () {
        document.getElementById('searchTermError').style.display = 'none';
    });
</script>

</body>
</html>
