<?php

$host = "db";
$dbname = "banana_tracker";
$user = "postgres";
$password = "your_password";

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
?>
