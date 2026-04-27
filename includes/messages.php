<?php
session_start();
require "includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["user_type"] !== "student") {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION["user_id"];
$sent_view = isset($_GET["view"]) && $_GET["view"] === "sent";

// Handle sending message
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["send_message"])) {
    $recipient_id = htmlspecialchars($_POST["recipient_id"]);
    $subject = htmlspecialchars($_POST["subject"]);
    $message_body = htmlspecialchars($_POST["message"]);

    $stmt = $conn->prepare("
        INSERT INTO messages (sender_id, recipient_id, subject, message_body, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$student_id, $recipient_id, $subject, $message_body]);
    
    $success_message = "Message sent successfully!";
}

// Mark message as read
if (isset($_GET["mark_read"])) {
    $message_id = intval($_GET["mark_read"]);
    $stmt = $conn->prepare("UPDATE messages SET is_read = TRUE WHERE id = ? AND recipient_id = ?");
    $stmt->execute([$message_id, $student_id]);
}

// Get messages based on view
if ($sent_view) {
    $stmt = $conn->prepare("
        SELECT m.*, u.username as recipient_name
        FROM messages m
        JOIN users u ON m.recipient_id = u.id
        WHERE m.sender_id = ?
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$student_id]);
    $page_title = "Sent Messages";
} else {
    $stmt = $conn->prepare("
        SELECT m.*, u.username as sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.recipient_id = ?
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$student_id]);
    $page_title = "Inbox";
}

$messages = $stmt->fetchAll();

// Get list of instructors for reply
$stmt = $conn->prepare("SELECT id, username FROM users WHERE user_type = 'instructor'");
$stmt->execute();
$instructors = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include "includes/menu.php"; ?>

    <div class="container">
        <div class="page-header">
            <h1>💬 <?php echo $page_title; ?></h1>
            <a href="student_portal.php" class="back-link">← Back to Dashboard</a>
        </div>

        <div class="messages-container">
            <div class="messages-nav">
                <a href="messages.php?view=inbox" class="nav-link <?php echo !$sent_view ? "active" : ""; ?>">
                    📬 Inbox
                </a>
                <a href="messages.php?view=sent" class="nav-link <?php echo $sent_view ? "active" : ""; ?>">
                    📤 Sent
                </a>
                <button class="nav-link" onclick="toggleComposeForm()">✉️ New Message</button>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <div id="compose-form" class="compose-form" style="display:none;">
                <h3>Send New Message</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="recipient_id">To:</label>
                        <select name="recipient_id" id="recipient_id" required>
                            <option value="">Select Recipient</option>
                            <?php foreach ($instructors as $instructor): ?>
                                <option value="<?php echo $instructor["id"]; ?>">
                                    <?php echo htmlspecialchars($instructor["username"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject:</label>
                        <input type="text" name="subject" id="subject" required>
                    </div>

                    <div class="form-group">
                        <label for="message">Message:</label>
                        <textarea name="message" id="message" rows="5" required></textarea>
                    </div>

                    <button type="submit" name="send_message" class="btn btn-primary">Send Message</button>
                    <button type="button" class="btn btn-secondary" onclick="toggleComposeForm()">Cancel</button>
                </form>
            </div>

            <?php if (empty($messages)): ?>
                <div class="empty-state">
                    <p>No messages <?php echo $sent_view ? "sent" : "received"; ?>.</p>
                </div>
            <?php else: ?>
                <div class="messages-list">
                    <?php foreach ($messages as $msg): ?>
                        <div class="message-item <?php echo !$sent_view && !$msg["is_read"] ? "unread" : ""; ?>">
                            <div class="message-header">
                                <h4>
                                    <?php 
                                    if ($sent_view) {
                                        echo "To: " . htmlspecialchars($msg["recipient_name"]);
                                    } else {
                                        echo "From: " . htmlspecialchars($msg["sender_name"]);
                                    }
                                    ?>
                                </h4>
                                <small><?php echo date("M d, Y H:i", strtotime($msg["created_at"])); ?></small>
                            </div>
                            <h5><?php echo htmlspecialchars($msg["subject"] ?? "No Subject"); ?></h5>
                            <p><?php echo htmlspecialchars(substr($msg["message_body"], 0, 150)); ?>...</p>
                            <div class="message-actions">
                                <button class="btn btn-small">View Full</button>
                                <?php if (!$sent_view && !$msg["is_read"]): ?>
                                    <a href="messages.php?mark_read=<?php echo $msg["id"]; ?>" class="btn btn-small">Mark as Read</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleComposeForm() {
            const form = document.getElementById("compose-form");
            form.style.display = form.style.display === "none" ? "block" : "none";
        }
    </script>

</body>
</html>
