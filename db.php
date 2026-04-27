<?php
// Database configuration - supports both local XAMPP and Railway deployment
$servername = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: "localhost";
$username = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: "root"; // default XAMPP
$password = getenv('DB_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: ""; // default XAMPP
$dbname = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: "myproject";
$dbport = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: "3306";

try {
    $dsn = "mysql:host=$servername;dbname=$dbname;port=$dbport;charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // For Railway deployment, show a more user-friendly error
    if (getenv('RAILWAY_ENVIRONMENT')) {
        die("Database connection failed. Please check your Railway environment variables: DB_HOST/DB_USER/DB_PASSWORD/DB_NAME/DB_PORT or ensure MySQL database is added to your project.");
    } else {
        die("Connection failed: " . $e->getMessage());
    }
}
?>
