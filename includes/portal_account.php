<?php
session_start();
require "includes/db.php";

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

$profile_picture = null;
if ($user_type === "student") {
    $profile_stmt = $conn->prepare("SELECT profile_picture FROM student_info WHERE user_id = ?");
    $profile_stmt->execute([$_SESSION["user_id"]]);
    $profile_data = $profile_stmt->fetch(PDO::FETCH_ASSOC);
    $profile_picture = $profile_data["profile_picture"] ?? null;
}

// Handle password change
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $old_password = $_POST["old_password"] ?? "";
    $new_password = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (!empty($old_password) && !empty($new_password) && !empty($confirm_password)) {
        // Verify old password
        $check_stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
        $check_stmt->execute([$username]);
        $pwd_data = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if ($pwd_data && password_verify($old_password, $pwd_data["password"])) {
            if ($new_password === $confirm_password) {
                $hashed_pwd = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
                $update_stmt->execute([$hashed_pwd, $username]);
                $success_message = "✅ Password updated successfully!";
            } else {
                $error_message = "❌ New passwords do not match!";
            }
        } else {
            $error_message = "❌ Old password is incorrect!";
        }
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
    <title>Account - African Methodist Episcopal Zion University Nimba Extension</title>
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
            max-width: 900px;
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

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .account-avatar-container {
            text-align: center;
            margin-bottom: 25px;
        }

        .account-avatar {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #ffd700;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.35);
            background: rgba(255, 255, 255, 0.1);
        }

        .avatar-placeholder {
            width: 110px;
            height: 110px;
            line-height: 110px;
            border-radius: 50%;
            border: 3px solid #ffd700;
            background: rgba(255, 255, 255, 0.08);
            color: #ffd700;
            font-size: 2.2rem;
            display: inline-block;
            font-weight: bold;
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

        .info-box {
            background: rgba(255, 215, 0, 0.1);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #ffd700;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            margin: 12px 0;
            padding: 8px 0;
        }

        .info-label {
            font-weight: bold;
            color: #ffd700;
        }

        .info-value {
            color: #e0e0e0;
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

        .section-divider {
            border-top: 2px solid rgba(255, 215, 0, 0.2);
            margin: 40px 0;
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
        <a href="portal_account.php" class="active">👤 Account</a>
        <a href="portal_contact.php">📧 Contact</a>
        <a href="logout.php">🚪 Logout</a>
    </nav>

    <main>
        <h1>👤 My Account</h1>

        <?php if (!empty($profile_picture) && file_exists($profile_picture)): ?>
            <div class="account-avatar-container">
                <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile picture" class="account-avatar">
            </div>
        <?php else: ?>
            <div class="account-avatar-container">
                <div class="avatar-placeholder"><?php echo strtoupper(substr($user["username"], 0, 1)); ?></div>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <h2>📋 Account Information</h2>
        <div class="info-box">
            <div class="info-item">
                <span class="info-label">Username:</span>
                <span class="info-value"><?php echo htmlspecialchars($user["username"]); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Email:</span>
                <span class="info-value"><?php echo htmlspecialchars($user["email"]); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Account Type:</span>
                <span class="info-value"><?php echo ucfirst($user["user_type"]); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Account Status:</span>
                <span class="info-value">✅ Active</span>
            </div>
            <div class="info-item">
                <span class="info-label">Member Since:</span>
                <span class="info-value">2023-01-15</span>
            </div>
        </div>

        <div class="section-divider"></div>

        <h2>🔐 Change Password</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="old_password">Current Password:</label>
                <input type="password" id="old_password" name="old_password" required placeholder="Enter your current password">
            </div>

            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" required placeholder="Enter your new password">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your new password">
            </div>

            <button type="submit" class="btn">🔄 Update Password</button>
            <button type="reset" class="btn" style="background: rgba(255, 255, 255, 0.2);">✖️ Clear Form</button>
        </form>

        <div class="section-divider"></div>

        <h2>⚙️ Account Settings</h2>
        <div class="form-group">
            <label>
                <input type="checkbox" checked> Receive email notifications
            </label>
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" checked> Receive system updates
            </label>
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox"> Receive promotional emails
            </label>
        </div>

        <div class="section-divider"></div>

        <h2>🗑️ Danger Zone</h2>
        <div style="background: rgba(244, 67, 54, 0.15); padding: 20px; border-radius: 8px; border-left: 4px solid #f44336;">
            <p style="margin-bottom: 15px;">⚠️ <strong>Delete Account:</strong> This action cannot be undone. Your account and all associated data will be permanently deleted.</p>
            <button class="btn" style="background: #f44336;" onclick="if(confirm('Are you sure you want to delete your account? This action cannot be undone.')) { alert('Account deletion request submitted. Contact support for confirmation.'); }">🗑️ Delete Account</button>
        </div>

    </main>

    <footer>
        <p>&copy; 2024 African Methodist Episcopal Zion University Nimba Extension</p>
        <p>Last Updated: <?php echo date("Y-m-d H:i:s"); ?></p>
    </footer>
</body>
</html>
