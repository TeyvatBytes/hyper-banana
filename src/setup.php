<?php
// setup.php - Database initialization script in PHP
// This script creates the database tables and inserts sample data

// Include database connection file
require_once "database.php";

// Function to execute SQL queries
function executeSQL($pdo, $sql, $params = [])
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return true;
    } catch (PDOException $e) {
        echo "Error executing SQL: " . $e->getMessage() . "\n";
        return false;
    }
}

// Main setup function
function setupDatabase()
{
    // Get database connection
    try {
        // First try to connect to postgres to create the database
        global $host, $user, $password;
        $pdo = new PDO("pgsql:host=$host", $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create the database if it doesn't exist
        try {
            $sql = "SELECT 1 FROM pg_database WHERE datname = 'banana_tracker'";
            $result = $pdo->query($sql);

            if ($result->rowCount() == 0) {
                echo "Creating database 'banana_tracker'...\n";
                $sql = "CREATE DATABASE banana_tracker";
                $pdo->exec($sql);
                echo "Database created successfully!\n";
            } else {
                echo "Database 'banana_tracker' already exists.\n";
            }
        } catch (PDOException $e) {
            echo "Error checking/creating database: " . $e->getMessage() . "\n";
        }

        // Close the connection to postgres
        $pdo = null;
    } catch (PDOException $e) {
        echo "Error connecting to PostgreSQL: " . $e->getMessage() . "\n";
        exit(1);
    }

    // Now connect to the banana_tracker database
    $pdo = getDbConnection();

    // Create fridges table
    $sql = "
    CREATE TABLE IF NOT EXISTS fridges (
        id SERIAL PRIMARY KEY,
        fridge_code VARCHAR(50) UNIQUE NOT NULL,
        name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    if (executeSQL($pdo, $sql)) {
        echo "Table 'fridges' created or already exists.\n";
    }

    // Create banana_lots table
    $sql = "
    CREATE TABLE IF NOT EXISTS banana_lots (
        id SERIAL PRIMARY KEY,
        fridge_id INTEGER REFERENCES fridges(id) ON DELETE CASCADE,
        name VARCHAR(100) NOT NULL,
        start_date DATE,
        banana_color VARCHAR(20) NOT NULL,
        refrigerated BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    if (executeSQL($pdo, $sql)) {
        echo "Table 'banana_lots' created or already exists.\n";
    }

    // Create messages table
    $sql = "
    CREATE TABLE IF NOT EXISTS messages (
        id SERIAL PRIMARY KEY,
        from_fridge_id INTEGER REFERENCES fridges(id),
        to_fridge_id INTEGER REFERENCES fridges(id),
        message TEXT NOT NULL,
        read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    if (executeSQL($pdo, $sql)) {
        echo "Table 'messages' created or already exists.\n";
    }

    // Check if sample data already exists
    $sql = "SELECT COUNT(*) FROM fridges";
    $stmt = $pdo->query($sql);
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        echo "Inserting sample data...\n";

        // Insert sample fridges
        $sampleFridges = [
            ["ตู้เย็น-123", "ตู้เย็นของแม่"],
            ["ตู้เย็น-456", "ตู้เย็นที่ออฟฟิศ"],
            ["ตู้เย็น-789", "ตู้เย็นคุณยาย"],
        ];

        $sql = "INSERT INTO fridges (fridge_code, name) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);

        foreach ($sampleFridges as $fridge) {
            $stmt->execute($fridge);
        }

        // Insert sample banana lots
        $sampleBananaLots = [
            [
                1,
                "กล้วยชุดที่ 1",
                date("Y-m-d", strtotime("-2 days")),
                "yellow",
                true,
            ],
            [
                1,
                "กล้วยชุดที่ 2",
                date("Y-m-d", strtotime("-4 days")),
                "green",
                true,
            ],
            [
                2,
                "กล้วยนำเข้า",
                date("Y-m-d", strtotime("-1 day")),
                "yellow-green",
                true,
            ],
            [
                3,
                "กล้วยหอม",
                date("Y-m-d", strtotime("-3 days")),
                "spotted",
                false,
            ],
        ];

        $sql =
            "INSERT INTO banana_lots (fridge_id, name, start_date, banana_color, refrigerated) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);

        foreach ($sampleBananaLots as $lot) {
            $stmt->execute($lot);
        }

        // Insert sample messages
        $sampleMessages = [
            [
                1,
                2,
                "กล้วยที่ส่งมาเริ่มเหลืองแล้ว",
                date("Y-m-d H:i:s", strtotime("-2 days")),
            ],
            [
                2,
                1,
                "ขอบคุณที่แจ้งให้ทราบ",
                date("Y-m-d H:i:s", strtotime("-1 day")),
            ],
            [
                3,
                1,
                "มีกล้วยเหลือไหม?",
                date("Y-m-d H:i:s", strtotime("-12 hours")),
            ],
        ];

        $sql =
            "INSERT INTO messages (from_fridge_id, to_fridge_id, message, created_at) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);

        foreach ($sampleMessages as $message) {
            $stmt->execute($message);
        }

        echo "Sample data inserted successfully!\n";
    } else {
        echo "Sample data already exists. Skipping insertion.\n";
    }

    echo "Database setup completed successfully!\n";
}

// Run the setup
setupDatabase();

// Optional: Add a web interface for setup
if (php_sapi_name() !== "cli") {
    echo "<html><head><title>Banana Tracker Setup</title>";
    echo "<style>body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }";
    echo "pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }</style>";
    echo "</head><body>";
    echo "<h1>Banana Tracker Database Setup</h1>";
    echo "<p>Setup completed. You can now <a href='index.php'>access the application</a>.</p>";
    echo "</body></html>";
}
