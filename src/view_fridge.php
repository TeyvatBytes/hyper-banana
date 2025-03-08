<?php
session_start();
include_once "database.php";
include_once "functions.php";

// Check if the user is authenticated
if (!isset($_SESSION["fridge_id"])) {
    header("Location: login.php");
    exit();
}

// Get fridge_id from URL
$fridgeId = $_GET["fridge_id"] ?? null;
if (!$fridgeId) {
    header("Location: all_fridges.php");
    exit();
}

// Function to get fridge by ID
function getFridgeById($fridgeId)
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM fridges WHERE id = ?");
    $stmt->execute([$fridgeId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to get banana lots by fridge ID
function getBananaLotsByFridgeId($fridgeId)
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM banana_lots WHERE fridge_id = ?");
    $stmt->execute([$fridgeId]);
    $lots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Replicate age and remaining days calculation from getBananaLots()
    foreach ($lots as &$lot) {
        $startDate = new DateTime($lot["start_date"]);
        $today = new DateTime();
        $interval = $startDate->diff($today);
        $lot["banana_age"] = $interval->days;
        $baseDays = array_reduce(
            getColorOptions(),
            function ($carry, $option) use ($lot) {
                return $option["value"] === $lot["banana_color"]
                    ? $option["days"]
                    : $carry;
            },
            0
        );
        $maxDays = $lot["refrigerated"] ? $baseDays * 1.5 : $baseDays;
        $lot["remaining_days"] = max(0, $maxDays - $lot["banana_age"]);
    }
    return $lots;
}

$fridge = getFridgeById($fridgeId);
if (!$fridge) {
    header("Location: all_fridges.php");
    exit();
}
$bananaLots = getBananaLotsByFridgeId($fridgeId);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เนื้อหาของตู้เย็น <?= htmlspecialchars($fridge["name"]) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container max-w-4xl mx-auto p-6">
        <h1 class="text-2xl font-bold text-yellow-600 mb-6">เนื้อหาของตู้เย็น: <?= htmlspecialchars(
            $fridge["name"]
        ) ?></h1>
        <p class="text-sm text-gray-600 mb-4">รหัสตู้เย็น: <?= htmlspecialchars(
            $fridge["fridge_code"]
        ) ?></p>
        <div class="space-y-4">
            <?php if (empty($bananaLots)): ?>
                <p class="text-sm text-gray-500 italic">ไม่มีกล้วยในตู้เย็นนี้</p>
            <?php else: ?>
                <?php foreach ($bananaLots as $lot): ?>
                    <?php $statusInfo = getStatusInfo(
                        $lot["remaining_days"]
                    ); ?>
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <h2 class="text-lg font-medium"><?= htmlspecialchars(
                            $lot["name"]
                        ) ?></h2>
                        <p class="text-sm">วันที่นำเข้าตู้เย็น: <?= $lot[
                            "start_date"
                        ] ?></p>
                        <p class="text-sm">สีของกล้วย: <?= $lot[
                            "banana_color"
                        ] ?></p>
                        <p class="text-sm">เก็บในตู้เย็น: <?= $lot[
                            "refrigerated"
                        ]
                            ? "ใช่"
                            : "ไม่" ?></p>
                        <div class="mt-2 p-3 bg-gray-50 rounded-lg">
                            <p class="text-sm">อยู่ในตู้เย็นมาแล้ว: <span class="font-medium"><?= $lot[
                                "banana_age"
                            ] ?> วัน</span></p>
                            <p class="text-sm">อายุการเก็บที่เหลือ: <span class="font-medium"><?= $lot[
                                "remaining_days"
                            ] ?> วัน</span></p>
                            <p class="text-sm font-medium <?= $statusInfo[
                                "color"
                            ] ?>"><?= $statusInfo["message"] ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if ($fridgeId == $_SESSION["fridge_id"]): ?>
            <p class="mt-4 text-sm text-gray-600">นี่คือตู้เย็นของคุณ <a href="index.php" class="text-blue-500 hover:underline">ไปที่หน้าจัดการ</a></p>
        <?php endif; ?>
        <p class="mt-4 text-sm text-gray-600"><a href="all_fridges.php" class="text-blue-500 hover:underline">กลับไปที่รายการตู้เย็น</a></p>
    </div>
</body>
</html>
