<?php
// debug.php - Run this to find the error
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug Start</h1>";

// 1. Check PHP Version
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";

// 2. Check Paths
$root = __DIR__;
echo "<p><strong>Root Directory:</strong> " . $root . "</p>";

// 3. Test Include Paths
$dbPath = $root . '/includes/db.php';
echo "<p>Looking for db.php at: <code>" . $dbPath . "</code> ... ";
if (file_exists($dbPath)) {
    echo "<span style='color:green'>FOUND</span></p>";
    
    // 4. Try to Include it
    echo "<p>Attempting to include db.php...</p>";
    try {
        require_once $dbPath; 
        echo "<p style='color:green'>Successfully included db.php!</p>";
        
        // 5. Test Database Connection
        if (isset($conn) && $conn->ping()) {
            echo "<p style='color:green'>Database Connected Successfully!</p>";
        } else {
            echo "<p style='color:red'>Database Connection Failed: " . ($conn->connect_error ?? 'Unknown error') . "</p>";
        }
        
    } catch (Throwable $e) {
        echo "<p style='color:red'><strong>CRITICAL ERROR:</strong> " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<span style='color:red'>NOT FOUND</span></p>";
    echo "<p><em>Hint: Check if the 'includes' folder is named correctly (case sensitive!).</em></p>";
}