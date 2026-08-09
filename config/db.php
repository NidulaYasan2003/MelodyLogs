<?php
// Database configuration parameters
$host = 'localhost';
$dbname = 'blog_db';
$username = 'root'; // Default XAMPP username
$password = '';     // Default XAMPP password is empty

try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set error mode to exception so we can catch database errors
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Stop execution and show error message if connection fails
    die("Database Connection Failed: " . $e->getMessage());
}
?>