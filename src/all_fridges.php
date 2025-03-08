<?php
session_start();
include_once "database.php";
include_once "functions.php";

// Check if the user is authenticated
if (!isset($_SESSION["fridge_id"])) {
    header("Location: login.php");
    exit();
}

// Function to get all fridges
function getAllFridges()
{
    $pdo = getDbConnection();
    $stmt = $pdo->query(
        "SELECT id, fridge_code, name, (SELECT COUNT(*) FROM banana_lots WHERE fridge_id = fridges.id) as banana_count FROM fridges"
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$allFridges = getAllFridges();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการตู้เย็นทั้งหมด</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container max-w-4xl mx-auto p-6">
        <h1 class="text-2xl font-bold text-yellow-600 mb-6">รายการตู้เย็นทั้งหมด</h1>
        <table class="min-w-full bg-white border border-gray-200 rounded-lg">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b">ชื่อตู้เย็น</th>
                    <th class="py-2 px-4 border-b">รหัสตู้เย็น</th>
                    <th class="py-2 px-4 border-b">จำนวนกล้วย (ชุด)</th>
                    <th class="py-2 px-4 border-b">การดำเนินการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allFridges as $fridge): ?>
                    <tr>
                        <td class="py-2 px-4 border-b"><?= htmlspecialchars(
                            $fridge["name"]
                        ) ?></td>
                        <td class="py-2 px-4 border-b"><?= htmlspecialchars(
                            $fridge["fridge_code"]
                        ) ?></td>
                        <td class="py-2 px-4 border-b"><?= $fridge[
                            "banana_count"
                        ] ?></td>
                        <td class="py-2 px-4 border-b">
                            <a href="view_fridge.php?fridge_id=<?= $fridge[
                                "id"
                            ] ?>" class="text-blue-500 hover:underline">ดูเนื้อหา</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="mt-4 text-sm text-gray-600"><a href="index.php" class="text-blue-500 hover:underline">กลับไปที่หน้าจัดการตู้เย็นของคุณ</a></p>
    </div>
</body>
</html>
