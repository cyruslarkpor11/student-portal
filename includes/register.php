<?php
session_start();
require "includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || ($_SESSION["user_type"] !== "admin" && $_SESSION["user_type"] !== "administration")) {
    header("Location: index.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars($_POST["username"]);
    $email = htmlspecialchars($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $user_type = isset($_POST["user_type"]) ? htmlspecialchars($_POST["user_type"]) : "student";
    
    // Validate user_type
    $valid_types = ["student", "administration", "instructor"];
    if (!in_array($user_type, $valid_types)) {
        $user_type = "student";
    }

    if ($password !== $confirm_password) {
        $message = "Passwords do not match!";
    } else {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, user_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hashed_password, $user_type]);
            $message = "Registration successful! You can now login.";
            header("Refresh: 2; url=index.php");
        } catch(PDOException $e) {
            if (strpos($e->getMessage(), "Duplicate") !== false) {
                $message = "Username or email already exists!";
            } else {
                $message = "Registration failed!";
            }
        }
    }
}

$is_admin = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] && $_SESSION["user_type"] === "admin";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - AME Zion University Nimba Extension</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(rgba(102, 126, 234, 0.85), rgba(118, 75, 162, 0.85)), url("Images/Image%201.jpg") center/cover no-repeat fixed;
            color: white;
            min-height: 100vh;
        }

        header {
            background: rgba(0, 0, 0, 0.5);
            padding: 20px;
            text-align: center;
            border-bottom: 3px solid #ffd700;
            backdrop-filter: blur(10px);
        }

        header h1 {
            color: #ffd700;
            font-size: 1.8em;
            margin: 0;
        }

        .nav-tabs {
            background: rgba(0, 0, 0, 0.4);
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            border-bottom: 2px solid rgba(255, 215, 0, 0.3);
            backdrop-filter: blur(10px);
        }

        .nav-tabs a {
            color: white;
            text-decoration: none;
            padding: 15px 20px;
            display: inline-block;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            font-weight: 600;
        }

        .nav-tabs a:hover {
            background: rgba(255, 215, 0, 0.1);
            border-bottom-color: #ffd700;
        }

        .nav-tabs a.active {
            border-bottom-color: #ffd700;
            color: #ffd700;
            background: rgba(255, 215, 0, 0.1);
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        main {
            background: rgba(0, 0, 0, 0.3);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
            text-align: center;
            max-width: 400px;
            width: 100%;
            backdrop-filter: blur(10px);
        }
        h1 {
            margin-bottom: 20px;
            font-size: 2em;
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
        input, select {
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
        a {
            color: #ffd700;
            margin-top: 15px;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header>
        <h1>African Methodist Episcopal Zion University Nimba Extension</h1>
    </header>

    <nav class="nav-tabs">
        <a href="index.php">🏠 Home</a>
        <a href="register.php" class="active">📝 Register</a>
        <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"]): ?>
            <?php if ($_SESSION['user_type'] === 'admin'): ?>
                <a href="admin_portal.php">📊 Dashboard</a>
            <?php elseif ($_SESSION['user_type'] === 'instructor'): ?>
                <a href="instructor_portal.php">📊 Dashboard</a>
            <?php else: ?>
                <a href="student_portal.php">📊 Dashboard</a>
            <?php endif; ?>
            <a href="portal_account.php">👤 Account</a>
            <a href="portal_contact.php">✉️ Contact</a>
            <a href="logout.php">🚪 Logout</a>
        <?php endif; ?>
    </nav>

    <div class="container">
        <main>
        <h1>Register</h1>
        <?php if ($message): ?>
            <p><?php echo $message; ?></p>
        <?php endif; ?>
        <form action="register.php" method="post">
            <label for="username">Username:</label>
            <input id="username" type="text" name="username" placeholder="Enter username..." required>

            <label for="email">Email:</label>
            <input id="email" type="email" name="email" placeholder="Enter email..." required>

            <label for="password">Password:</label>
            <input id="password" type="password" name="password" placeholder="Enter password..." required>

            <label for="confirm_password">Confirm Password:</label>
            <input id="confirm_password" type="password" name="confirm_password" placeholder="Confirm password..." required>

            <label for="user_type">Account Type:</label>
            <select id="user_type" name="user_type" required>
                <option value="student">Student</option>
                <option value="administration">Administration</option>
                <option value="instructor">Instructor</option>
            </select>

            <button type="submit">Register</button>
        </form>
        <a href="admin_portal.php">← Back to Admin Portal</a>
        </main>
    </div>
</body>
</html>
