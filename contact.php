<?php
session_start();
$message = "";
$loggedIn = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require "includes/admin_helpers.php";
    adminEnsureSupportTables($conn);

    $name = htmlspecialchars($_POST["username"] ?? "");
    $username = $loggedIn ? ($_SESSION["username"] ?? "") : null;
    if ($loggedIn && $name === "") {
        $name = $username ?: "Portal User";
    }
    $email = htmlspecialchars($_POST["email"]);
    $subject = htmlspecialchars($_POST["subject"]);
    $message_content = htmlspecialchars($_POST["message"]);

    try {
        $stmt = $conn->prepare("
            INSERT INTO contacts (name, username, email, subject, message, status)
            VALUES (?, ?, ?, ?, ?, 'new')
        ");
        $stmt->execute([$name, $username, $email, $subject, $message_content]);
        $message = "Message sent successfully! We will get back to you soon.";
    } catch(PDOException $e) {
        $message = "Failed to send message!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - AME Zion Nimba Extension</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(rgba(102, 126, 234, 0.85), rgba(118, 75, 162, 0.85)), url("Images/download%201.jpg") center/cover no-repeat fixed;
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
            text-align: center;
            color: #ffd700;
        }
        p {
            margin: 10px 0;
            text-align: center;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-top: 15px;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, textarea {
            padding: 10px;
            border: none;
            border-radius: 5px;
            font-family: Arial, sans-serif;
            font-size: 1em;
            color: #333;
        }
        textarea {
            resize: vertical;
            min-height: 150px;
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
    </style>
</head>
<body>
    <?php include "includes/menu.php"; ?>
    <main>
        <h1>Contact Us</h1>
        <?php if ($message): ?>
            <p><?php echo $message; ?></p>
        <?php endif; ?>
        <form action="contact.php" method="post">
            <?php if (!$loggedIn): ?>
            <label for="username">Name:</label>
            <input id="username" type="text" name="username" placeholder="Enter your name..." required>
            <?php else: ?>
            <p>Sending from: <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong></p>
            <?php endif; ?>

            <label for="email">Email:</label>
            <input id="email" type="email" name="email" placeholder="Enter your email..." required>

            <label for="subject">Subject:</label>
            <input id="subject" type="text" name="subject" placeholder="Enter subject..." required>

            <label for="message">Message:</label>
            <textarea id="message" name="message" placeholder="Enter your message..." required></textarea>

            <button type="submit">Send Message</button>
        </form>
    </main>
</body>
</html>
