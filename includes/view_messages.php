<?php
session_start();
require "includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION["user_type"], ["student", "instructor"])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION["user_id"] ?? null;
$isInstructor = $_SESSION["user_type"] === "instructor";
$dashboardLink = $isInstructor ? "instructor_portal.php" : "student_portal.php";
$message = "";

// Handle sending message
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["send_message"])) {
    $recipient_id = $_POST["recipient_id"];
    $subject = $_POST["subject"];
    $body = $_POST["body"];

    if ($recipient_id && $subject && $body) {
        try {
            $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, body, sent_date) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$user_id, $recipient_id, $subject, $body]);
            $message = "Message sent successfully!";
        } catch(PDOException $e) {
            $message = "Error sending message: " . $e->getMessage();
        }
    } else {
        $message = "Please fill in all fields.";
    }
}

// Handle marking as read
if (isset($_GET["mark_read"]) && is_numeric($_GET["mark_read"])) {
    $message_id = $_GET["mark_read"];
    try {
        $stmt = $conn->prepare("UPDATE messages SET is_read = 1, read_date = NOW() WHERE message_id = ? AND recipient_id = ?");
        $stmt->execute([$message_id, $user_id]);
    } catch(PDOException $e) {
        // Error silently
    }
    header("Location: view_messages.php");
    exit();
}

// Get inbox (received messages)
$stmt = $conn->prepare("
    SELECT m.message_id, m.sender_id, u.username, m.subject, m.body, m.sent_date, m.is_read,
           (SELECT COUNT(*) FROM messages WHERE recipient_id = ? AND is_read = 0) as unread_count
    FROM messages m
    INNER JOIN users u ON m.sender_id = u.id
    WHERE m.recipient_id = ?
    ORDER BY m.sent_date DESC
    LIMIT 50
");
$stmt->execute([$user_id, $user_id]);
$inbox_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get sent messages
$stmt = $conn->prepare("
    SELECT m.message_id, m.recipient_id, u.username, m.subject, m.body, m.sent_date
    FROM messages m
    INNER JOIN users u ON m.recipient_id = u.id
    WHERE m.sender_id = ?
    ORDER BY m.sent_date DESC
    LIMIT 50
");
$stmt->execute([$user_id]);
$sent_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get list of recipients for compose
if ($isInstructor) {
    $instructor_name = ucwords(str_replace(["_", "."], " ", $_SESSION["username"]));
    $stmt = $conn->prepare("
        SELECT DISTINCT u.id, u.username FROM users u
        INNER JOIN student_courses sc ON u.id = sc.user_id
        INNER JOIN courses c ON sc.course_id = c.course_id
        WHERE c.instructor = ?
        ORDER BY u.username
    ");
    $stmt->execute([$instructor_name]);
    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $conn->prepare("
        SELECT DISTINCT x.id, x.username FROM (
            SELECT ui.id, ui.username
            FROM users ui
            WHERE ui.user_type = 'instructor' AND ui.username IN (
                SELECT DISTINCT c.instructor FROM courses c INNER JOIN student_courses sc ON c.course_id = sc.course_id WHERE sc.user_id = ?
            )
            UNION
            SELECT us.id, us.username
            FROM users us
            INNER JOIN student_courses sc2 ON us.id = sc2.user_id
            WHERE sc2.course_id IN (SELECT course_id FROM student_courses WHERE user_id = ?) AND us.id != ?
        ) x
        ORDER BY x.username
    ");
    $stmt->execute([$user_id, $user_id, $user_id]);
    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get selected message content (if viewing a message)
$selected_message = null;
if (isset($_GET["view"]) && is_numeric($_GET["view"])) {
    $message_id = $_GET["view"];
    $stmt = $conn->prepare("SELECT * FROM messages WHERE message_id = ? AND (recipient_id = ? OR sender_id = ?)");
    $stmt->execute([$message_id, $user_id, $user_id]);
    $selected_message = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get unread count
$stmt = $conn->prepare("SELECT COUNT(*) as unread FROM messages WHERE recipient_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$unread_result = $stmt->fetch(PDO::FETCH_ASSOC);
$unread_count = $unread_result["unread"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Student Portal</title>
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

        .nav-tabs a:hover {
            background: rgba(255, 215, 0, 0.1);
            border-bottom-color: #ffd700;
        }

        .nav-tabs a.active {
            border-bottom-color: #ffd700;
            color: #ffd700;
            background: rgba(255, 215, 0, 0.1);
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

        h2 {
            color: #ffd700;
            margin-top: 0;
            font-size: 1.8em;
            border-bottom: 2px solid #ffd700;
            padding-bottom: 10px;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            border-bottom: 1px solid rgba(255, 215, 0, 0.3);
        }

        .tab-btn {
            padding: 10px 20px;
            background: rgba(0, 0, 0, 0.2);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab-btn:hover {
            background: rgba(0, 0, 0, 0.3);
        }

        .tab-btn.active {
            border-bottom-color: #ffd700;
            color: #ffd700;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .message-item {
            background: rgba(0, 0, 0, 0.2);
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #2196F3;
            cursor: pointer;
            transition: all 0.3s;
        }

        .message-item:hover {
            background: rgba(0, 0, 0, 0.3);
            transform: translateX(5px);
        }

        .message-item.unread {
            background: rgba(33, 150, 243, 0.1);
            border-left-color: #64b5f6;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .message-from {
            color: #ffd700;
            font-weight: bold;
        }

        .message-date {
            color: #aaa;
            font-size: 0.9em;
        }

        .message-subject {
            color: #ddd;
            margin: 5px 0;
            font-weight: 600;
        }

        .message-preview {
            color: #bbb;
            font-size: 0.9em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .unread-badge {
            display: inline-block;
            background: #2196F3;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .message-detail {
            background: rgba(0, 0, 0, 0.4);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .message-detail-header {
            border-bottom: 1px solid rgba(255, 215, 0, 0.3);
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .message-detail-header h3 {
            color: #ffd700;
            margin: 0 0 10px 0;
        }

        .message-detail-info {
            color: #bbb;
            font-size: 0.9em;
            line-height: 1.6;
        }

        .message-detail-info strong {
            color: #ffd700;
        }

        .message-body {
            background: rgba(0, 0, 0, 0.2);
            padding: 15px;
            border-radius: 5px;
            line-height: 1.6;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .compose-form {
            background: rgba(0, 0, 0, 0.4);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .form-group {
            margin: 15px 0;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #ffd700;
            font-weight: bold;
        }

        input[type="text"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 5px;
            background: rgba(0, 0, 0, 0.3);
            color: white;
            font-family: Arial, sans-serif;
        }

        textarea {
            min-height: 150px;
            resize: vertical;
        }

        button {
            background: #2196F3;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }

        button:hover {
            background: #1976D2;
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

        .message {
            background: rgba(76, 175, 80, 0.3);
            padding: 15px;
            border-left: 4px solid #4CAF50;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #a8d8a8;
        }

        .empty {
            text-align: center;
            padding: 40px;
            color: #aaa;
            font-style: italic;
        }

        .inbox-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .inbox-header h3 {
            color: #ffd700;
            margin: 0;
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
        <a href="view_messages.php" class="active">💬 Messages</a>
        <a href="chat_room.php">💬 Chat</a>
        <a href="view_profile.php">👤 Profile</a>
        <a href="logout.php">🚪 Logout</a>
    </nav>

    <main>
        <a href="<?php echo $dashboardLink; ?>" class="back-link">← Back to Dashboard</a>
        <h2>💬 Messages</h2>

        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('inbox')">📥 Inbox (<?php echo count($inbox_messages); ?>)</button>
            <button class="tab-btn" onclick="switchTab('sent')">📤 Sent (<?php echo count($sent_messages); ?>)</button>
            <button class="tab-btn" onclick="switchTab('compose')">✍️ Compose</button>
        </div>

        <!-- Inbox Tab -->
        <div id="inbox" class="tab-content active">
            <div class="inbox-header">
                <h3>Inbox</h3>
                <?php if ($unread_count > 0): ?>
                    <span class="unread-badge"><?php echo $unread_count; ?> Unread</span>
                <?php endif; ?>
            </div>

            <?php if (count($inbox_messages) > 0): ?>
                <?php foreach ($inbox_messages as $msg): ?>
                    <div class="message-item <?php echo $msg["is_read"] ? "" : "unread"; ?>" onclick="window.location.href='?view=<?php echo $msg["message_id"]; ?>'">
                        <div class="message-header">
                            <span class="message-from"><?php echo htmlspecialchars($msg["username"]); ?></span>
                            <span class="message-date"><?php echo date("M d, g:i A", strtotime($msg["sent_date"])); ?></span>
                        </div>
                        <div class="message-subject"><?php echo htmlspecialchars($msg["subject"]); ?></div>
                        <div class="message-preview"><?php echo htmlspecialchars(substr($msg["body"], 0, 100)); ?>...</div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty">No messages in inbox.</div>
            <?php endif; ?>
        </div>

        <!-- Sent Tab -->
        <div id="sent" class="tab-content">
            <h3>Sent Messages</h3>
            <?php if (count($sent_messages) > 0): ?>
                <?php foreach ($sent_messages as $msg): ?>
                    <div class="message-item" onclick="window.location.href='?view=<?php echo $msg["message_id"]; ?>'">
                        <div class="message-header">
                            <span class="message-from">To: <?php echo htmlspecialchars($msg["username"]); ?></span>
                            <span class="message-date"><?php echo date("M d, g:i A", strtotime($msg["sent_date"])); ?></span>
                        </div>
                        <div class="message-subject"><?php echo htmlspecialchars($msg["subject"]); ?></div>
                        <div class="message-preview"><?php echo htmlspecialchars(substr($msg["body"], 0, 100)); ?>...</div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty">No messages in sent folder.</div>
            <?php endif; ?>
        </div>

        <!-- Compose Tab -->
        <div id="compose" class="tab-content">
            <form method="POST" class="compose-form">
                <div class="form-group">
                    <label for="recipient_id">Send To:</label>
                    <select name="recipient_id" id="recipient_id" required>
                        <option value="">-- Select Recipient --</option>
                        <?php foreach ($recipients as $recipient): ?>
                            <option value="<?php echo $recipient["id"]; ?>">
                                <?php echo htmlspecialchars($recipient["username"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <input type="text" name="subject" id="subject" placeholder="Message subject..." required>
                </div>
                <div class="form-group">
                    <label for="body">Message:</label>
                    <textarea name="body" id="body" placeholder="Type your message here..." required></textarea>
                </div>
                <button type="submit" name="send_message">📤 Send Message</button>
            </form>
        </div>

        <!-- Message Detail View (if viewing a specific message) -->
        <?php if ($selected_message): ?>
            <div class="message-detail">
                <div class="message-detail-header">
                    <h3><?php echo htmlspecialchars($selected_message["subject"]); ?></h3>
                    <div class="message-detail-info">
                        <strong>From:</strong> 
                        <?php 
                        $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
                        $stmt->execute([$selected_message["sender_id"]]);
                        $sender = $stmt->fetch();
                        echo htmlspecialchars($sender["username"]);
                        ?>
                        <br>
                        <strong>To:</strong> 
                        <?php 
                        $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
                        $stmt->execute([$selected_message["recipient_id"]]);
                        $recipient = $stmt->fetch();
                        echo htmlspecialchars($recipient["username"]);
                        ?>
                        <br>
                        <strong>Date:</strong> <?php echo date("F j, Y g:i A", strtotime($selected_message["sent_date"])); ?>
                    </div>
                </div>
                <div class="message-body">
                    <?php echo htmlspecialchars($selected_message["body"]); ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        function switchTab(tabName) {
            // Hide all tabs
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));

            // Remove active class from all buttons
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));

            // Show selected tab
            document.getElementById(tabName).classList.add('active');

            // Add active class to clicked button
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
