<?php
session_start();
include_once "database.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        "SELECT f.id, f.fridge_code, f.name FROM fridges f WHERE f.username = ? AND f.password = ?"
    );
    $stmt->execute([$username, $password]);
    $fridge = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($fridge) {
        $_SESSION["fridge_id"] = $fridge["id"];
        $_SESSION["fridge_code"] = $fridge["fridge_code"];
        $_SESSION["fridge_name"] = $fridge["name"];
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid credentials";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container max-w-md mx-auto p-6">
        <h1 class="text-2xl font-bold mb-4">Login</h1>
        <?php if (
            isset($error)
        ): ?><p class="text-red-500"><?php echo $error; ?></p><?php endif; ?>
        <form method="POST">
            <div class="mb-4">
                <label class="block text-sm font-medium">Username</label>
                <input type="text" name="username" class="w-full p-2 border rounded" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium">Password</label>
                <input type="password" name="password" class="w-full p-2 border rounded" required>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded">Login</button>
        </form>
    </div>
</body>
</html>
