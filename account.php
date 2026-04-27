<?php
session_start();
require "includes/db.php";

if (!isset($_SESSION["loggedin"]) || !$_SESSION["loggedin"]) {
    header("Location: index.php");
    exit();
}

$username = $_SESSION["username"];
$stmt = $conn->prepare("SELECT username, email, user_type FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - AME Zion Nimba Extension</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(rgba(102, 126, 234, 0.85), rgba(118, 75, 162, 0.85)), url("Images/graduation.jpg") center/cover no-repeat fixed;
            margin: 0;
            padding: 20px;
            color: white;
            min-height: 100vh;
        }
        main {
            background: rgba(0, 0, 0, 0.3);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
            max-width: 600px;
            margin: 20px auto;
            backdrop-filter: blur(10px);
        }
        h1 {
            margin-top: 0;
            font-size: 2em;
            color: #ffd700;
        }
        .account-info {
            background: rgba(0, 0, 0, 0.2);
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info-item {
            margin: 15px 0;
            font-size: 1.1em;
        }
        .label {
            font-weight: bold;
            color: #ffd700;
        }
        button {
            padding: 10px 20px;
            background: #f44336;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            margin-top: 20px;
            transition: background 0.3s;
        }
        button:hover {
            background: #da190b;
        }
    </style>
</head>
<body>
    <?php include "includes/menu.php"; ?>
    <main>
        <h1>My Account</h1>
        <div class="account-info">
            <div class="info-item">
                <span class="label">Username:</span> <?php echo htmlspecialchars($user["username"]); ?>
            </div>
            <div class="info-item">
                <span class="label">Email:</span> <?php echo htmlspecialchars($user["email"]); ?>
            </div>
            <div class="info-item">
                <span class="label">Account Type:</span> <?php echo ucfirst($user["user_type"]); ?>
            </div>
        </div>
        <a href="logout.php"><button>Logout</button></a>
    </main>
</body>
</html>
