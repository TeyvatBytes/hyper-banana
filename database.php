<?php
// database.php - Database configuration and connection handling

// Get database connection parameters from environment variables
$host = getenv("DB_HOST") ?: "localhost";
$dbname = getenv("DB_NAME") ?: "banana_tracker";
$user = getenv("DB_USER") ?: "postgres";
$password = getenv("DB_PASSWORD") ?: "your_password";

// Create database connection
function getDbConnection()
{
    global $host, $dbname, $user, $password;

    try {
        $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}
