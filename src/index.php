<?php
session_start();
include_once "database.php";
include_once "functions.php";

// index.php - Main application file

// Process form submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Update fridge information
    if (isset($_POST["update_fridge"])) {
        $fridgeCode = $_POST["fridge_code"];
        $fridgeName = $_POST["fridge_name"];

        $pdo = getDbConnection();
        $stmt = $pdo->prepare(
            "UPDATE fridges SET fridge_code = ?, name = ? WHERE id = ?"
        );
        $stmt->execute([$fridgeCode, $fridgeName, $_SESSION["fridge_id"]]);

        $_SESSION["fridge_code"] = $fridgeCode;
        $_SESSION["fridge_name"] = $fridgeName;
    }

    // Add new banana lot
    if (isset($_POST["add_lot"])) {
        $name = $_POST["lot_name"] ?? "กล้วยชุดใหม่";
        $startDate = $_POST["start_date"] ?? date("Y-m-d");
        $bananaColor = $_POST["banana_color"] ?? "yellow";
        $refrigerated = isset($_POST["refrigerated"]) ? true : false;

        addBananaLot($name, $startDate, $bananaColor, $refrigerated);
    }

    // Update lot property
    if (isset($_POST["update_lot"])) {
        $lotId = $_POST["lot_id"];
        $field = $_POST["field"];

        // Handle checkbox for refrigerated
        if ($field === "refrigerated") {
            // When checkbox is unchecked, value might not be set in POST
            $value = isset($_POST["value"])
                ? $_POST["value"] === "true"
                : false;
        } else {
            $value = $_POST["value"] ?? "";
        }

        updateBananaLot($lotId, $field, $value);
    }

    // Remove lot
    if (isset($_POST["remove_lot"])) {
        $lotId = $_POST["lot_id"];
        removeBananaLot($lotId);
    }

    // Send message
    if (isset($_POST["send_message"])) {
        $toFridgeId = $_POST["to_fridge_id"];
        $message = $_POST["message"];

        if (!empty($message) && !empty($toFridgeId)) {
            sendMessage($toFridgeId, $message);
        }
    }

    // Avoid form resubmission on refresh
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit();
}

// Get current fridge information
$fridgeId = getCurrentFridgeId();
$fridgeCode = $_SESSION["fridge_code"];
$fridgeName = $_SESSION["fridge_name"];

// Get data for the page
$bananaLots = getBananaLots();
$otherFridges = getOtherFridges();
$recentMessages = getRecentMessages();
$colorOptions = getColorOptions();

// Count bananas in current fridge
$bananaCount = count($bananaLots);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เครื่องคำนวณอายุกล้วยในตู้เย็น</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.10.5/dist/cdn.min.js" defer></script>
    <script>
        function app() {
            return {
                currentTime: new Date().toLocaleTimeString(),
                selectedFridge: null,
                selectedFridgeName: '',
                floatingMessages: [],

                init() {
                    // Update clock every second
                    setInterval(() => {
                        this.currentTime = new Date().toLocaleTimeString();
                    }, 1000);
                },

                selectFridge(id, name) {
                    this.selectedFridge = id;
                    this.selectedFridgeName = name;
                },

                addFloatingMessage(text) {
                    const message = {
                        text: text,
                        top: Math.random() * 60 + 20,
                        left: Math.random() * 60 + 20,
                        opacity: 1
                    };

                    this.floatingMessages.push(message);

                    // Remove message after 5 seconds
                    setTimeout(() => {
                        this.removeFloatingMessage(this.floatingMessages.indexOf(message));
                    }, 5000);
                },

                removeFloatingMessage(index) {
                    if (index > -1 && index < this.floatingMessages.length) {
                        this.floatingMessages.splice(index, 1);
                    }
                }
            };
        }
    </script>
</head>
<body class="bg-gray-100">
    <div class="container max-w-4xl mx-auto p-6" x-data="app()" x-init="init()">
        <div class="bg-white rounded-xl shadow-lg p-6 relative">
            <!-- Floating messages (using Alpine.js) -->
            <template x-for="(msg, index) in floatingMessages" :key="index">
                <div
                    class="absolute z-10 bg-yellow-100 p-3 rounded-lg shadow-md text-sm animate-bounce"
                    :style="`top: ${msg.top}%; left: ${msg.left}%; opacity: ${msg.opacity};`"
                    x-text="msg.text">
                </div>
            </template>

            <!-- Header with real-time clock -->
            <div class="flex justify-between items-center mb-6">
                <a href="all_fridges.php" class="text-blue-500 hover:underline">ดูตู้เย็นทั้งหมด</a>
                <h1 class="text-2xl font-bold text-yellow-600">
                    เครื่องคำนวณอายุกล้วยในตู้เย็น 🍌❄️
                </h1>
                <div class="flex flex-col items-end">
                    <div class="text-lg font-mono bg-gray-100 p-2 rounded-md text-gray-800" x-text="currentTime">
                        <?= date("H:i:s") ?>
                    </div>
                    <div class="text-sm text-gray-500 mt-1">
                        <?= date("l, d F Y") ?>
                    </div>
                </div>
            </div>

            <!-- Fridge ID -->
            <form method="POST" class="mb-4 p-3 bg-blue-50 rounded-lg flex justify-between items-center">
                <div>
                    <span class="text-sm font-medium text-blue-700">รหัสตู้เย็นของคุณ: </span>
                    <input
                        type="text"
                        name="fridge_code"
                        value="<?= htmlspecialchars($fridgeCode) ?>"
                        class="ml-2 p-1 border border-blue-300 rounded"
                    >
                    <input
                        type="text"
                        name="fridge_name"
                        value="<?= htmlspecialchars($fridgeName) ?>"
                        class="ml-2 p-1 border border-blue-300 rounded"
                        placeholder="ชื่อตู้เย็น"
                    >
                    <button type="submit" name="update_fridge" class="ml-2 px-2 py-1 bg-blue-500 text-white rounded text-xs">บันทึก</button>
                </div>
                <div class="text-sm text-blue-600">
                    จำนวนกล้วยทั้งหมด: <?= $bananaCount ?> ชุด
                </div>
            </form>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Main calculator area -->
                <div class="md:col-span-2 space-y-6">
                    <?php foreach ($bananaLots as $lot): ?>
                        <?php $statusInfo = getStatusInfo(
                            $lot["remaining_days"]
                        ); ?>

                        <div class="p-4 border border-gray-200 rounded-lg relative">

                            <div class="w-full relative">
                                <div class="absolute top-5 right-5 border px-2 text-blue-600 py-1 bg-blue-100 rounded-lg flex gap-2 items-center"><div class="w-2 h-2 relative "> <div class="w-full h-full rounded-full bg-blue-500 absolute animate-ping"></div> <div class="w-full h-full rounded-full bg-blue-500 "></div></div>ภาพสดจากในตู้เย็น </div>
                                <img src="<?= $statusInfo[
                                    "img"
                                ] ?>" alt="Green Banana" class="w-full rounded-lg my-5">
                            </div>
                            <!-- Lot header with name and delete button -->
                            <div class="flex justify-between items-center mb-4">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="update_lot" value="1">
                                    <input type="hidden" name="lot_id" value="<?= $lot[
                                        "id"
                                    ] ?>">
                                    <input type="hidden" name="field" value="name">
                                    <input
                                        type="text"
                                        name="value"
                                        value="<?= htmlspecialchars(
                                            $lot["name"]
                                        ) ?>"
                                        onchange="this.form.submit()"
                                        class="font-medium text-gray-800 border-b border-dashed border-gray-300 focus:outline-none focus:border-yellow-500 px-1"
                                    >
                                </form>

                                <?php if ($bananaCount > 1): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="remove_lot" value="1">
                                        <input type="hidden" name="lot_id" value="<?= $lot[
                                            "id"
                                        ] ?>">
                                        <button
                                            type="submit"
                                            class="text-gray-400 hover:text-red-500 transition-colors"
                                            aria-label="ลบกล้วยชุดนี้"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <!-- Lot inputs -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="update_lot" value="1">
                                    <input type="hidden" name="lot_id" value="<?= $lot[
                                        "id"
                                    ] ?>">
                                    <input type="hidden" name="field" value="start_date">

                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        วันที่นำกล้วยเข้าตู้เย็น
                                    </label>
                                    <input
                                        type="date"
                                        name="value"
                                        value="<?= $lot["start_date"] ?>"
                                        onchange="this.form.submit()"
                                        class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                                    >
                                </form>

                                <form method="POST" class="inline">
                                    <input type="hidden" name="update_lot" value="1">
                                    <input type="hidden" name="lot_id" value="<?= $lot[
                                        "id"
                                    ] ?>">
                                    <input type="hidden" name="field" value="banana_color">

                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        สีของกล้วยตอนเริ่มเก็บ
                                    </label>
                                    <select
                                        name="value"
                                        onchange="this.form.submit()"
                                        class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                                    >
                                        <?php foreach (
                                            $colorOptions
                                            as $option
                                        ): ?>
                                            <option
                                                value="<?= $option["value"] ?>"
                                                <?= $lot["banana_color"] ===
                                                $option["value"]
                                                    ? "selected"
                                                    : "" ?>
                                            >
                                                <?= $option["label"] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>

                                <form method="POST" class="inline flex items-center">
                                    <input type="hidden" name="update_lot" value="1">
                                    <input type="hidden" name="lot_id" value="<?= $lot[
                                        "id"
                                    ] ?>">
                                    <input type="hidden" name="field" value="refrigerated">

                                    <input
                                        type="checkbox"
                                        id="refrigerated-<?= $lot["id"] ?>"
                                        name="value"
                                        value="true"
                                        <?= $lot["refrigerated"]
                                            ? "checked"
                                            : "" ?>
                                        onchange="this.value = this.checked ? 'true' : 'false'; this.form.submit()"
                                        class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded"
                                    >
                                    <label for="refrigerated-<?= $lot[
                                        "id"
                                    ] ?>" class="ml-2 block text-sm text-gray-700">
                                        เก็บในตู้เย็น (ยืดอายุกล้วยได้ 50%)
                                    </label>
                                </form>
                            </div>

                            <!-- Results section -->
                            <?php if ($lot["banana_age"] !== null): ?>
                                <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                                    <div class="flex flex-wrap justify-between items-center">
                                        <div>
                                            <p class="text-sm">
                                                อยู่ในตู้เย็นมาแล้ว: <span class="font-medium"><?= $lot[
                                                    "banana_age"
                                                ] ?> วัน</span>
                                            </p>
                                            <p class="text-sm">
                                                อายุการเก็บที่เหลือ: <span class="font-medium"><?= $lot[
                                                    "remaining_days"
                                                ] ?> วัน</span>
                                            </p>
                                        </div>
                                        <p class="text-sm font-medium mt-2 md:mt-0 <?= $statusInfo[
                                            "color"
                                        ] ?>">
                                            <?= $statusInfo["message"] ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <!-- Add lot form -->
                    <div class="mt-4 flex justify-between">
                        <form method="POST">
                            <input type="hidden" name="add_lot" value="1">
                            <input type="hidden" name="lot_name" value="กล้วยชุดที่ <?= $bananaCount +
                                1 ?>">
                            <input type="hidden" name="start_date" value="<?= date(
                                "Y-m-d"
                            ) ?>">
                            <input type="hidden" name="banana_color" value="yellow">
                            <input type="hidden" name="refrigerated" value="1">

                            <button
                                type="submit"
                                class="py-2 px-4 bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium rounded-md transition duration-200 flex items-center"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                </svg>
                                เพิ่มชุดกล้วย
                            </button>
                        </form>

                        <button
                            onclick="window.location.reload()"
                            class="py-2 px-6 bg-yellow-500 hover:bg-yellow-600 text-white font-medium rounded-md transition duration-200 ease-in-out transform hover:scale-105"
                        >
                            คำนวณอายุกล้วยทั้งหมด
                        </button>
                    </div>
                </div>

                <!-- Chat with other fridges section -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <h2 class="text-lg font-medium text-gray-800 mb-3">ตู้เย็นอื่นๆ ในเครือข่าย</h2>

                    <div class="max-h-60 overflow-y-auto mb-4 space-y-2">
                        <?php foreach ($otherFridges as $fridge): ?>
                            <div
                                class="p-2 border rounded-md cursor-pointer transition-colors hover:bg-gray-50"
                                x-bind:class="{ 'border-blue-500 bg-blue-50': selectedFridge === <?= $fridge[
                                    "id"
                                ] ?> }"
                                x-on:click="selectFridge(<?= $fridge[
                                    "id"
                                ] ?>, '<?= htmlspecialchars(
    $fridge["name"]
) ?>')"
                            >
                                <div class="flex justify-between items-center">
                                    <span class="font-medium"><?= htmlspecialchars(
                                        $fridge["name"]
                                    ) ?></span>
                                    <span class="text-xs bg-yellow-100 px-2 py-1 rounded-full">
                                        <?= $fridge["banana_count"] ?? 0 ?> 🍌
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500 mt-1"><?= htmlspecialchars(
                                    $fridge["fridge_code"]
                                ) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="border-t pt-3">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">
                            ส่งข้อความ <span x-text="selectedFridgeName ? ` ไปยัง ${selectedFridgeName}` : ''"></span>
                        </h3>

                        <form method="POST" x-bind:class="{ 'opacity-50 pointer-events-none': !selectedFridge }">
                            <input type="hidden" name="send_message" value="1">
                            <input type="hidden" name="to_fridge_id" x-bind:value="selectedFridge">

                            <textarea
                                name="message"
                                rows="2"
                                placeholder="พิมพ์ข้อความที่นี่..."
                                class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-yellow-500 focus:border-transparent resize-none mb-2"
                                x-bind:disabled="!selectedFridge"
                            ></textarea>

                            <div class="flex justify-between">
                                <button
                                    type="button"
                                    class="text-sm text-blue-600 hover:text-blue-800"
                                    x-bind:disabled="!selectedFridge"
                                    x-on:click="addFloatingMessage('👋 สวัสดีจากตู้เย็น <?= htmlspecialchars(
                                        $fridgeName
                                    ) ?>!')"
                                >
                                    ส่งสติกเกอร์ 👋
                                </button>

                                <button
                                    type="submit"
                                    class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 text-sm"
                                    x-bind:disabled="!selectedFridge"
                                >
                                    ส่งข้อความ
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Recent messages -->
                    <div class="mt-4 border-t pt-3">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">ข้อความล่าสุด</h3>

                        <div class="space-y-2 max-h-40 overflow-y-auto">
                            <?php if (empty($recentMessages)): ?>
                                <p class="text-sm text-gray-500 italic">ยังไม่มีข้อความ</p>
                            <?php else: ?>
                                <?php foreach ($recentMessages as $msg): ?>
                                    <?php
                                    $isFromMe =
                                        $msg["from_fridge_id"] == $fridgeId;
                                    $displayName = $isFromMe
                                        ? "คุณ"
                                        : htmlspecialchars($msg["from_name"]);
                                    ?>
                                    <div class="text-sm p-2 rounded-md <?= $isFromMe
                                        ? "bg-blue-50 text-right"
                                        : "bg-gray-50" ?>">
                                        <div class="font-medium text-xs text-gray-500 mb-1">
                                            <?= $displayName ?> → <?= $msg[
     "to_fridge_id"
 ] == $fridgeId
     ? "คุณ"
     : htmlspecialchars($msg["to_name"]) ?>
                                        </div>
                                        <div><?= htmlspecialchars(
                                            $msg["message"]
                                        ) ?></div>
                                        <div class="text-xs text-gray-400 mt-1">
                                            <?= date(
                                                "d/m/Y H:i",
                                                strtotime($msg["created_at"])
                                            ) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info bar -->
            <div class="mt-6 bg-gray-50 rounded-lg p-4">
                <h2 class="text-lg font-medium text-gray-800 mb-2">เกี่ยวกับอายุกล้วย</h2>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
                    <?php foreach ($colorOptions as $option): ?>
                        <div class="text-sm p-2 bg-white rounded border">
                            <div class="font-medium"><?= $option[
                                "label"
                            ] ?></div>
                            <div class="text-gray-600">อยู่ได้ประมาณ: <?= $option[
                                "days"
                            ] ?> วัน</div>
                            <div class="text-gray-600">แช่เย็น: <?= round(
                                $option["days"] * 1.5
                            ) ?> วัน</div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-gray-500 mt-3">
                    *อายุกล้วยเป็นเพียงการประมาณการณ์ ขึ้นอยู่กับสภาพแวดล้อมและคุณภาพของกล้วย
                </p>
            </div>
        </div> <!-- Closing bg-white div -->
        <footer class="mt-6 text-center text-sm text-gray-500">
            <p>เครื่องคำนวณอายุกล้วยในตู้เย็น © 2025</p>
            <p class="mt-1">ระบบนี้ช่วยให้คุณติดตามอายุการเก็บรักษากล้วยเพื่อลดการทิ้งอาหาร</p>
        </footer>
    </div> <!-- Closing container div -->
</body>
</html>
