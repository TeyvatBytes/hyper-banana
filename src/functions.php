<?php
// functions.php - Common functions for the application
function getColorOptions()
{
    return [
        ["value" => "green", "label" => "สีเขียว (ดิบ)", "days" => 7],
        ["value" => "yellow-green", "label" => "สีเขียวอมเหลือง", "days" => 5],
        ["value" => "yellow", "label" => "สีเหลือง (สุกพอดี)", "days" => 3],
        ["value" => "spotted", "label" => "สีเหลืองจุดน้ำตาล", "days" => 2],
        ["value" => "brown", "label" => "สีน้ำตาล (สุกมาก)", "days" => 1],
    ];
}

// Get banana status based on remaining days
function getStatusInfo($remainingDays)
{
    if ($remainingDays === null) {
        return ["message" => "", "color" => "text-gray-500"];
    }

    if ($remainingDays <= 0) {
        return [
            "message" => "กล้วยควรใช้ทำขนมแล้ว 🍌🍰",
            "color" => "text-red-500",
            "img" => "/assets/gy_banana.webp",
        ];
    } elseif ($remainingDays === 1) {
        return [
            "message" => "ควรรีบทาน ใกล้หมดอายุแล้ว 🍌⏰",
            "color" => "text-amber-500",
            "img" => "/assets/gy_banana.webp",
        ];
    } elseif ($remainingDays <= 3) {
        return [
            "message" => "ทานได้ปกติ 🍌😋",
            "color" => "text-green-500",
            "img" => "/assets/yellow_banana.webp",
        ];
    } else {
        return [
            "message" => "ยังเก็บได้อีกหลายวัน 🍌✨",
            "color" => "text-emerald-600",
            "img" => "/assets/green_banana.webp",
        ];
    }
}

// Calculate banana age and remaining days
function calculateBananaAge($startDate, $bananaColor, $refrigerated)
{
    $colorOptions = getColorOptions();

    if (empty($startDate)) {
        return [null, null];
    }

    // Calculate days difference
    $start = new DateTime($startDate);
    $today = new DateTime();
    $diffDays = $start->diff($today)->days;

    // Get base shelf life from selected color
    $shelfLife = 3; // Default
    foreach ($colorOptions as $option) {
        if ($option["value"] === $bananaColor) {
            $shelfLife = $option["days"];
            break;
        }
    }

    // Refrigeration extends shelf life by 50%
    if ($refrigerated) {
        $shelfLife = round($shelfLife * 1.5);
    }

    // Calculate remaining days
    $remaining = max(0, $shelfLife - $diffDays);

    return [$diffDays, $remaining];
}

// Get current fridge ID from session or create a new one
function getCurrentFridgeId()
{
    $pdo = getDbConnection();

    if (!isset($_SESSION["fridge_id"])) {
        // Create a new fridge
        $fridgeCode = "ตู้เย็น-" . mt_rand(100, 999);
        $fridgeName = "ตู้เย็นของฉัน";

        $stmt = $pdo->prepare(
            "INSERT INTO fridges (fridge_code, name) VALUES (?, ?) RETURNING id"
        );
        $stmt->execute([$fridgeCode, $fridgeName]);

        $fridgeId = $stmt->fetchColumn();
        $_SESSION["fridge_id"] = $fridgeId;
        $_SESSION["fridge_code"] = $fridgeCode;
        $_SESSION["fridge_name"] = $fridgeName;
    }

    return $_SESSION["fridge_id"];
}

// Get other fridges excluding the current one
function getOtherFridges()
{
    $pdo = getDbConnection();
    $currentFridgeId = getCurrentFridgeId();

    $stmt = $pdo->prepare("
        SELECT f.id, f.fridge_code, f.name, COUNT(bl.id) as banana_count
        FROM fridges f
        LEFT JOIN banana_lots bl ON f.id = bl.fridge_id
        WHERE f.id != ?
        GROUP BY f.id, f.fridge_code, f.name
        ORDER BY f.name
    ");
    $stmt->execute([$currentFridgeId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get recent messages for the current fridge
function getRecentMessages($limit = 5)
{
    $pdo = getDbConnection();
    $currentFridgeId = getCurrentFridgeId();

    $stmt = $pdo->prepare("
        SELECT m.*, f_from.fridge_code as from_code, f_from.name as from_name,
               f_to.fridge_code as to_code, f_to.name as to_name
        FROM messages m
        JOIN fridges f_from ON m.from_fridge_id = f_from.id
        JOIN fridges f_to ON m.to_fridge_id = f_to.id
        WHERE m.from_fridge_id = ? OR m.to_fridge_id = ?
        ORDER BY m.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$currentFridgeId, $currentFridgeId, $limit]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Send a message to another fridge
function sendMessage($toFridgeId, $messageText)
{
    $pdo = getDbConnection();
    $fromFridgeId = getCurrentFridgeId();

    $stmt = $pdo->prepare("
        INSERT INTO messages (from_fridge_id, to_fridge_id, message)
        VALUES (?, ?, ?)
        RETURNING id
    ");
    $stmt->execute([$fromFridgeId, $toFridgeId, $messageText]);

    return $stmt->fetchColumn();
}

// Get banana lots for current fridge
function getBananaLots()
{
    $pdo = getDbConnection();
    $fridgeId = getCurrentFridgeId();

    $stmt = $pdo->prepare("
        SELECT * FROM banana_lots
        WHERE fridge_id = ?
        ORDER BY id
    ");
    $stmt->execute([$fridgeId]);

    $lots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result = [];

    foreach ($lots as $lot) {
        list($bananaAge, $remainingDays) = calculateBananaAge(
            $lot["start_date"],
            $lot["banana_color"],
            $lot["refrigerated"]
        );

        $lot["banana_age"] = $bananaAge;
        $lot["remaining_days"] = $remainingDays;
        $result[] = $lot;
    }

    // Sort by remaining days (lower first)
    usort($result, function ($a, $b) {
        if ($a["remaining_days"] === null) {
            return 1;
        }
        if ($b["remaining_days"] === null) {
            return -1;
        }
        return $a["remaining_days"] - $b["remaining_days"];
    });

    return $result;
}

// Add a new banana lot
function addBananaLot($name, $startDate, $bananaColor, $refrigerated)
{
    $pdo = getDbConnection();
    $fridgeId = getCurrentFridgeId();

    $stmt = $pdo->prepare("
        INSERT INTO banana_lots (fridge_id, name, start_date, banana_color, refrigerated)
        VALUES (?, ?, ?, ?, ?)
        RETURNING id
    ");
    $stmt->execute([
        $fridgeId,
        $name,
        $startDate,
        $bananaColor,
        $refrigerated ? true : false,
    ]);

    return $stmt->fetchColumn();
}

// Update a banana lot
function updateBananaLot($id, $field, $value)
{
    $pdo = getDbConnection();
    $fridgeId = getCurrentFridgeId();

    // Make sure the lot belongs to the current fridge
    $stmt = $pdo->prepare("
        UPDATE banana_lots
        SET $field = ?
        WHERE id = ? AND fridge_id = ?
    ");
    $stmt->execute([$value, $id, $fridgeId]);

    return $stmt->rowCount() > 0;
}

// Remove a banana lot
function removeBananaLot($id)
{
    $pdo = getDbConnection();
    $fridgeId = getCurrentFridgeId();

    // Make sure the lot belongs to the current fridge
    $stmt = $pdo->prepare("
        DELETE FROM banana_lots
        WHERE id = ? AND fridge_id = ?
    ");
    $stmt->execute([$id, $fridgeId]);

    return $stmt->rowCount() > 0;
}
?>
