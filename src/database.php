<?php
// Get database configuration from environment variables

$host = getenv("DB_HOST") ?: "db";
$dbname = getenv("DB_NAME") ?: "banana_tracker";
$user = getenv("DB_USER") ?: "postgres";
$password = getenv("DB_PASSWORD") ?: "your_password";

function getDbConnection()
{
    try {
        global $host, $user, $password, $dbname;
        $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}
?>
