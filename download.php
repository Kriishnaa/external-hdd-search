<?php
// Handle the download functionality
if (isset($_GET['file']) && !empty($_GET['file'])) {
    $filePath = $_GET['file'];

    // Make sure the file exists
    if (file_exists($filePath)) {
        // Get the file name from the path
        $fileName = basename($filePath);

        // Set headers to trigger the download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Clear output buffer and output the file content
        flush();
        readfile($filePath);
        exit;
    } else {
        echo "File not found.";
    }
}
