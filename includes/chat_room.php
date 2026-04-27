<?php
session_start();
require "includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$isInstructor = $_SESSION["user_type"] === "instructor";
$dashboardLink = $isInstructor ? "instructor_portal.php" : "student_portal.php";
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["send_chat"])) {
    $content = trim($_POST["content"] ?? "");
    if ($content !== "") {
        try {
            $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, message, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$user_id, $content]);
            header("Location: chat_room.php");
            exit();
        } catch (PDOException $e) {
            $message = "Could not send message: " . $e->getMessage();
        }
    } else {
        $message = "Please enter a message before sending.";
    }
}

$stmt = $conn->prepare("SELECT cm.message_id, cm.message, cm.created_at, u.username, u.user_type FROM chat_messages cm INNER JOIN users u ON cm.user_id = u.id ORDER BY cm.created_at DESC LIMIT 100");
$stmt->execute();
$messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Room - Student Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(rgba(102, 126, 234, 0.8), rgba(118, 75, 162, 0.8)), url("Images/download%201.jpg") center/cover no-repeat fixed;
            color: white;
            min-height: 100vh;
        }

        header {
            background: rgba(0, 0, 0, 0.5);
            padding: 20px;
            text-align: center;
            border-bottom: 3px solid #ffd700;
        }

        header h1 {
            color: #ffd700;
            font-size: 1.8em;
        }

        .nav-tabs {
            background: rgba(0, 0, 0, 0.4);
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            border-bottom: 2px solid rgba(255, 215, 0, 0.3);
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

        .nav-tabs a:hover,
        .nav-tabs a.active {
            background: rgba(255, 215, 0, 0.1);
            border-bottom-color: #ffd700;
            color: #ffd700;
        }

        main {
            background: rgba(0, 0, 0, 0.3);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
            max-width: 1000px;
            margin: 30px 20px;
            margin-left: auto;
            margin-right: auto;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 5px;
            color: #ffd700;
            text-decoration: none;
            transition: background 0.3s;
        }

        .back-link:hover {
            background: rgba(0, 0, 0, 0.4);
        }

        .chat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chat-header h2 {
            color: #ffd700;
        }

        .message {
            background: rgba(76, 175, 80, 0.3);
            padding: 15px;
            border-left: 4px solid #4CAF50;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #a8d8a8;
        }

        .chat-box {
            max-height: 450px;
            overflow-y: auto;
            padding: 20px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .chat-message {
            padding: 15px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.06);
            border-left: 4px solid #ffd700;
            margin-bottom: 12px;
        }

        .chat-message strong {
            color: #ffd700;
        }

        .chat-meta {
            color: #ccc;
            font-size: 0.85em;
            margin-top: 8px;
        }

        .chat-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        textarea {
            width: 100%;
            min-height: 120px;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            background: rgba(0, 0, 0, 0.3);
            color: white;
            font-family: Arial, sans-serif;
            resize: vertical;
        }

        button {
            background: #2196F3;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }

        button:hover {
            background: #1976D2;
        }
    </style>
</head>
<body>
    <header>
        <h1>African Methodist Episcopal Zion University Nimba Extension</h1>
    </header>

    <nav class="nav-tabs">
        <a href="<?php echo $dashboardLink; ?>">📊 Dashboard</a>
        <a href="view_courses.php">📚 Courses</a>
        <a href="view_assignments.php">📝 Assignments</a>
        <a href="view_grades.php">📊 Grades</a>
        <a href="view_resources.php">📖 Resources</a>
        <a href="view_messages.php">💬 Messages</a>
        <a href="chat_room.php" class="active">💬 Chat</a>
        <a href="view_profile.php">👤 Profile</a>
        <a href="logout.php">🚪 Logout</a>
    </nav>

    <main>
        <a href="<?php echo $dashboardLink; ?>" class="back-link">← Back to Dashboard</a>
        <div class="chat-header">
            <h2>Chat Room</h2>
            <span>Open conversation for all users.</span>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="chat-box">
            <?php if (count($messages) > 0): ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="chat-message">
                        <strong><?php echo htmlspecialchars($msg["username"]); ?></strong> <span class="chat-meta">(<?php echo htmlspecialchars($msg["user_type"]); ?>) • <?php echo date("M d, Y g:i A", strtotime($msg["created_at"])); ?></span>
                        <p style="margin-top: 10px; color: #eee; white-space: pre-wrap;"><?php echo htmlspecialchars($msg["message"]); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty">No chat messages yet. Post the first message to start the conversation.</div>
            <?php endif; ?>
        </div>

        <form class="chat-form" method="POST">
            <textarea name="content" placeholder="Write a message to the group..."></textarea>
            <button type="submit" name="send_chat">Send to Chat</button>
        </form>
    </main>
</body>
</html>