<?php
session_start();
require "includes/admin_helpers.php";
adminEnsureSupportTables($conn);

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: index.php");
    exit();
}

$user_type = $_SESSION["user_type"] ?? null;
$username = $_SESSION["username"] ?? null;
$success_message = "";
$error_message = "";

$stmt = $conn->prepare("SELECT username, email, user_type FROM users WHERE username = ? AND user_type = ?");
$stmt->execute([$username, $user_type]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: logout.php");
    exit();
}

// Handle contact form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST["name"] ?? "";
    $email = $_POST["email"] ?? "";
    $subject = $_POST["subject"] ?? "";
    $message = $_POST["message"] ?? "";

    if (!empty($name) && !empty($email) && !empty($subject) && !empty($message)) {
        try {
            $insert_stmt = $conn->prepare("
                INSERT INTO contacts (name, username, email, subject, message, status)
                VALUES (?, ?, ?, ?, ?, 'new')
            ");
            $insert_stmt->execute([$name, $username, $email, $subject, $message]);
            $success_message = "✅ Your message has been sent successfully! We'll get back to you soon.";
        } catch (PDOException $e) {
            $error_message = "❌ Error sending message. Please try again later.";
        }
    } else {
        $error_message = "❌ Please fill in all fields.";
    }
}

$bg_image = ($user_type === "admin") ? "Images/download%202.jpg" : "Images/download%201.jpg";
$bg_color1 = ($user_type === "admin") ? "rgba(139, 0, 0, 0.8)" : "rgba(102, 126, 234, 0.8)";
$bg_color2 = ($user_type === "admin") ? "rgba(178, 34, 34, 0.8)" : "rgba(118, 75, 162, 0.8)";
$accent_color = ($user_type === "admin") ? "#ff6b6b" : "#4CAF50";
$portal_name = ($user_type === "admin") ? "ADMIN PORTAL" : "STUDENT PORTAL";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - African Methodist Episcopal Zion University Nimba Extension</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(<?php echo $bg_color1; ?>, <?php echo $bg_color2; ?>), url("<?php echo $bg_image; ?>") center/cover no-repeat fixed;
            color: white;
            min-height: 100vh;
        }

        header {
            background: rgba(0, 0, 0, 0.5);
            padding: 25px 20px;
            text-align: center;
            border-bottom: 4px solid #ffd700;
            backdrop-filter: blur(10px);
        }

        header .institution-name {
            font-size: 1.5em;
            font-weight: bold;
            color: #ffd700;
            margin-bottom: 5px;
        }

        header .portal-name {
            font-size: 2em;
            font-weight: bold;
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
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
            padding: 15px 25px;
            display: inline-block;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            font-weight: 600;
            font-size: 1em;
        }

        .nav-tabs a:hover {
            background: rgba(255, 215, 0, 0.1);
            border-bottom-color: #ffd700;
        }

        .nav-tabs a.active {
            background: rgba(255, 215, 0, 0.15);
            border-bottom-color: #ffd700;
            color: #ffd700;
        }

        main {
            background: rgba(0, 0, 0, 0.3);
            padding: 40px;
            margin: 30px 20px;
            border-radius: 12px;
            max-width: 1000px;
            margin-left: auto;
            margin-right: auto;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        h1 {
            color: #ffd700;
            margin-bottom: 30px;
            font-size: 2.5em;
            text-align: center;
        }

        h2 {
            color: #ffd700;
            margin-top: 25px;
            margin-bottom: 15px;
            font-size: 1.6em;
            border-bottom: 2px solid rgba(255, 215, 0, 0.3);
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #ffd700;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            color: white;
            border-radius: 5px;
            font-size: 1em;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            border-color: #ffd700;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        textarea {
            resize: vertical;
            min-height: 150px;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin: 10px 10px 10px 0;
            background: <?php echo $accent_color; ?>;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .success-message {
            background: rgba(76, 175, 80, 0.2);
            border-left: 4px solid #4CAF50;
            color: #4CAF50;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .error-message {
            background: rgba(244, 67, 54, 0.2);
            border-left: 4px solid #f44336;
            color: #ff9999;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .contact-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .contact-card {
            background: rgba(255, 215, 0, 0.1);
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #ffd700;
            text-align: center;
        }

        .contact-card .icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .contact-card h3 {
            color: #ffd700;
            margin-bottom: 10px;
        }

        .contact-card p {
            color: #b0b0b0;
            margin: 5px 0;
        }

        footer {
            background: rgba(0, 0, 0, 0.5);
            padding: 20px;
            text-align: center;
            border-top: 2px solid #ffd700;
            margin-top: 40px;
            color: #b0b0b0;
        }

        @media (max-width: 768px) {
            main {
                margin: 20px 10px;
                padding: 20px;
            }

            h1 {
                font-size: 1.8em;
            }

            .nav-tabs a {
                padding: 12px 15px;
                font-size: 0.9em;
            }

            .contact-info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="institution-name">African Methodist Episcopal Zion University Nimba Extension</div>
        <div class="portal-name"><?php echo $portal_name; ?></div>
    </header>

    <nav class="nav-tabs">
        <a href="index.php">🏠 Home</a>
        <a href="student_portal.php">📊 Dashboard</a>
        <a href="portal_account.php">👤 Account</a>
        <a href="portal_contact.php" class="active">📧 Contact</a>
        <a href="logout.php">🚪 Logout</a>
    </nav>

    <main>
        <h1>✉️ Contact Us</h1>

        <?php if ($success_message): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <h2>📞 Contact Information</h2>
        <div class="contact-info-grid">
            <div class="contact-card">
                <div class="icon">📍</div>
                <h3>Address</h3>
                <p>Nimba County</p>
                <p>Liberia</p>
            </div>
            <div class="contact-card">
                <div class="icon">📧</div>
                <h3>Email</h3>
                <p>info@amezion.edu.lr</p>
                <p>support@amezion.edu.lr</p>
            </div>
            <div class="contact-card">
                <div class="icon">📱</div>
                <h3>Phone</h3>
                <p>+231-7-123-4567</p>
                <p>+231-77-654-3210</p>
            </div>
        </div>

        <h2>💬 Send us a Message</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Your Name:</label>
                <input type="text" id="name" name="name" required placeholder="Enter your full name" value="<?php echo htmlspecialchars($user['username']); ?>">
            </div>

            <div class="form-group">
                <label for="email">Your Email:</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email address" value="<?php echo htmlspecialchars($user['email']); ?>">
            </div>

            <div class="form-group">
                <label for="subject">Subject:</label>
                <input type="text" id="subject" name="subject" required placeholder="What is this about?">
            </div>

            <div class="form-group">
                <label for="message">Message:</label>
                <textarea id="message" name="message" required placeholder="Please write your message here..."></textarea>
            </div>

            <button type="submit" class="btn">📤 Send Message</button>
            <button type="reset" class="btn" style="background: rgba(255, 255, 255, 0.2);">✖️ Clear Form</button>
        </form>

        <h2 style="margin-top: 40px;">📋 Frequently Asked Questions</h2>
        <div style="margin: 20px 0;">
            <div style="background: rgba(0, 0, 0, 0.2); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                <h3 style="color: #ffd700; margin-bottom: 10px;">Q: How do I reset my password?</h3>
                <p style="color: #b0b0b0;">A: Go to your Account page and use the "Change Password" section to update your password securely.</p>
            </div>

            <div style="background: rgba(0, 0, 0, 0.2); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                <h3 style="color: #ffd700; margin-bottom: 10px;">Q: How do I enroll in a course?</h3>
                <p style="color: #b0b0b0;">A: Navigate to Dashboard and select the desired course from the course listing to enroll.</p>
            </div>

            <div style="background: rgba(0, 0, 0, 0.2); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                <h3 style="color: #ffd700; margin-bottom: 10px;">Q: How do I access my grades?</h3>
                <p style="color: #b0b0b0;">A: Visit the Dashboard to view your current grades and academic progress.</p>
            </div>

            <div style="background: rgba(0, 0, 0, 0.2); padding: 15px; border-radius: 8px;">
                <h3 style="color: #ffd700; margin-bottom: 10px;">Q: What is the response time for support?</h3>
                <p style="color: #b0b0b0;">A: Our support team typically responds within 24 hours during business days.</p>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 African Methodist Episcopal Zion University Nimba Extension</p>
        <p>Last Updated: <?php echo date("Y-m-d H:i:s"); ?></p>
    </footer>
</body>
</html>
