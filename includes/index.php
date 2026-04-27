<?php
session_start();
$message = "";
if (isset($_GET["login"])) {
    if ($_GET["login"] == "success") {
        $message = "Login successful! Welcome, " . $_SESSION["username"];
    } elseif ($_GET["login"] == "failed") {
        $message = "Invalid username or password.";
    }
}
if (isset($_GET["logout"]) && $_GET["logout"] == "success") {
    $message = "You have been logged out.";
}
$loggedIn = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AME Zion University Nimba Extension - Portal</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            color: white;
            background: linear-gradient(rgba(16, 14, 45, 0.55), rgba(69, 26, 123, 0.35)),
                        url("Images/Image 1.jpg") center/cover fixed;
            background-attachment: fixed;
            overflow-x: hidden;
            position: relative;
        }
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background: radial-gradient(circle at 15% 20%, rgba(255,255,255,0.45), transparent 10%),
                        radial-gradient(circle at 80% 10%, rgba(255,255,255,0.30), transparent 8%);
            mix-blend-mode: screen;
            pointer-events: none;
            opacity: 0.8;
        }
        body::after {
            content: "";
            position: fixed;
            inset: 0;
            background: repeating-linear-gradient(
              45deg,
              rgba(255,255,255,0.05),
              rgba(255,255,255,0.05) 1px,
              transparent 1px,
              transparent 20px
            );
            opacity: 0.35;
            pointer-events: none;
            animation: shine 18s linear infinite;
        }
        @keyframes shine {
            from { transform: translateX(-100%); }
            to { transform: translateX(100%); }
        }
        .header {
            text-align: center;            http://localhost/xampp/htdocs/Myproject/includes/create_tables.php
            padding: 40px 20px;
            background: rgba(0, 0, 0, 0.3);
        }
        .header img {
            max-width: 100px;
            margin-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5em;
            color: #ffd700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        .header p {
            margin: 10px 0 0 0;
            font-size: 1.1em;
            color: #fff;
        }
        main {
            background: rgba(0, 0, 0, 0.2);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
            text-align: center;
            max-width: 400px;
            width: 100%;
            margin: auto;
            backdrop-filter: blur(10px);
        }
        h2 {
            margin-bottom: 20px;
            font-size: 1.8em;
            color: #ffd700;
        }
        p {
            margin: 10px 0;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-top: 10px;
            text-align: left;
        }
        input {
            padding: 10px;
            margin-top: 5px;
            border: none;
            border-radius: 5px;
            font-size: 1em;
        }
        button {
            margin-top: 20px;
            padding: 10px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1em;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #45a049;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .action-buttons a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            transition: background 0.3s;
            display: inline-block;
        }
        .action-buttons a:hover {
            background: rgba(255, 255, 255, 0.4);
        }
    </style>
</head>
<body>
    <?php include "includes/menu.php"; ?>
    <div class="header">
        <img src="Images/Zion.png" alt="AME Zion Logo">
        <h1>African Methodist Episcopal Zion University Nimba Extension</h1>
        <p>Student & Administration Portal</p>
    </div>
    
    <main>
        <?php if ($loggedIn): ?>
        <h2>Dashboard</h2>
        <p>You are logged in as <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong></p>
        <p>Portal Type: <strong><?php echo ucfirst($_SESSION["user_type"]); ?></strong></p>
        <?php if ($_SESSION["user_type"] === "admin" || $_SESSION["user_type"] === "administration"): ?>
            <p><a href="admin_portal.php" style="color: #ffd700;">Go to Admin Portal →</a></p>
        <?php elseif ($_SESSION["user_type"] === "instructor"): ?>
            <p><a href="instructor_portal.php" style="color: #ffd700;">Go to Instructor Portal →</a></p>
        <?php else: ?>
            <p><a href="student_portal.php" style="color: #ffd700;">Go to Student Portal →</a></p>
        <?php endif; ?>
        <?php else: ?>
        <h2>Login</h2>
        <?php if ($message): ?>
            <p><?php echo $message; ?></p>
        <?php endif; ?>
        <form action="includes/formhandler.php" method="post">
            <label for="username">Username:</label>
            <input id="username" type="text" name="username" placeholder="Enter username..." required>

            <label for="password">Password:</label>
            <input id="password" type="password" name="password" placeholder="Enter password..." required>

            <button type="submit">Login</button>
        </form>
        
        <div class="action-buttons">
            <a href="index.php">Home</a>
            <a href="contact.php">Contact Us</a>
            <a href="terms.php" target="_blank" rel="noopener noreferrer">Terms & Conditions</a>
            <a href="privacy.php" target="_blank" rel="noopener noreferrer">Privacy Policy</a>
            <a href="admission_portal.php" target="_blank" rel="noopener noreferrer" style="background: rgba(255, 215, 0, 0.15); color: #ffd700; border: 1px solid rgba(255,215,0,0.2);">Apply for Admission</a>
        </div>
        <?php endif; ?>
    </main>

</body>

</html>
