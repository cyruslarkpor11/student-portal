<?php
session_start();
require "includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["user_type"] !== "student") {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION["user_id"];
$update_message = "";
$error_message = "";

// Get current student info
$stmt = $conn->prepare("SELECT * FROM student_info WHERE user_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

// Handle profile picture upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["profile_picture"])) {
    $file = $_FILES["profile_picture"];
    $allowed_extensions = ["jpg", "jpeg", "png", "gif"];
    $max_size = 5 * 1024 * 1024; // 5MB

    // Validate file
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    
    if ($file["size"] > $max_size) {
        $error_message = "File size must be less than 5MB";
    } elseif (!in_array($file_extension, $allowed_extensions)) {
        $error_message = "Only JPG, JPEG, PNG, and GIF files are allowed";
    } elseif ($file["error"] !== UPLOAD_ERR_OK) {
        $error_message = "Error uploading file";
    } else {
        // Create uploads directory if not exists
        $upload_dir = "uploads/profiles/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Generate unique filename
        $filename = "profile_" . $student_id . "_" . time() . "." . $file_extension;
        $filepath = $upload_dir . $filename;

        if (move_uploaded_file($file["tmp_name"], $filepath)) {
            // Delete old profile picture
            if ($student["profile_picture"] && file_exists($student["profile_picture"])) {
                unlink($student["profile_picture"]);
            }

            // Update database
            $stmt = $conn->prepare("UPDATE student_info SET profile_picture = ? WHERE user_id = ?");
            $stmt->execute([$filepath, $student_id]);
            $update_message = "Profile picture updated successfully!";
            
            // Refresh student data
            $stmt = $conn->prepare("SELECT * FROM student_info WHERE user_id = ?");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch();
        } else {
            $error_message = "Error saving file";
        }
    }
}

// Handle profile information update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_profile"])) {
    $first_name = htmlspecialchars($_POST["first_name"]);
    $last_name = htmlspecialchars($_POST["last_name"]);
    $phone = htmlspecialchars($_POST["phone"]);
    $address = htmlspecialchars($_POST["address"]);

    $stmt = $conn->prepare("
        UPDATE student_info 
        SET first_name = ?, last_name = ?, phone = ?, address = ?
        WHERE user_id = ?
    ");
    $stmt->execute([$first_name, $last_name, $phone, $address, $student_id]);
    $update_message = "Profile updated successfully!";
    
    // Refresh student data
    $stmt = $conn->prepare("SELECT * FROM student_info WHERE user_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include "includes/menu.php"; ?>

    <div class="container">
        <div class="page-header">
            <h1>👤 My Profile</h1>
            <a href="student_portal.php" class="back-link">← Back to Dashboard</a>
        </div>

        <?php if ($update_message): ?>
            <div class="alert alert-success"><?php echo $update_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="profile-container">
            <div class="profile-section">
                <h2>Profile Picture</h2>
                <div class="profile-picture-area">
                    <?php if ($student && $student["profile_picture"]): ?>
                        <img src="<?php echo htmlspecialchars($student["profile_picture"]); ?>" alt="Profile Picture" class="profile-image">
                    <?php else: ?>
                        <div class="profile-placeholder">
                            <span>No Picture</span>
                        </div>
                    <?php endif; ?>
                </div>

                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <div class="form-group">
                        <label for="profile_picture">Choose Profile Picture:</label>
                        <input type="file" name="profile_picture" id="profile_picture" accept="image/*" required>
                        <small>Maximum file size: 5MB (JPG, PNG, GIF)</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Upload Picture</button>
                </form>
            </div>

            <div class="profile-section">
                <h2>Personal Information</h2>
                
                <div class="info-display">
                    <div class="info-item">
                        <label>Student ID:</label>
                        <span><?php echo htmlspecialchars($student["student_id"] ?? "N/A"); ?></span>
                    </div>

                    <div class="info-item">
                        <label>Department:</label>
                        <span><?php echo htmlspecialchars($student["department"] ?? "N/A"); ?></span>
                    </div>

                    <div class="info-item">
                        <label>Status:</label>
                        <span class="status-badge status-<?php echo strtolower($student["status"] ?? "inactive"); ?>">
                            <?php echo ucfirst($student["status"] ?? "Inactive"); ?>
                        </span>
                    </div>

                    <div class="info-item">
                        <label>Email:</label>
                        <span><?php 
                            $user_stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
                            $user_stmt->execute([$student_id]);
                            $user_email = $user_stmt->fetch();
                            echo htmlspecialchars($user_email["email"] ?? "N/A");
                        ?></span>
                    </div>
                </div>

                <h3>Edit Profile</h3>
                <form method="POST" class="profile-form">
                    <div class="form-group">
                        <label for="first_name">First Name:</label>
                        <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($student["first_name"] ?? ""); ?>">
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name:</label>
                        <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($student["last_name"] ?? ""); ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number:</label>
                        <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($student["phone"] ?? ""); ?>">
                    </div>

                    <div class="form-group">
                        <label for="address">Address:</label>
                        <textarea name="address" id="address" rows="3"><?php echo htmlspecialchars($student["address"] ?? ""); ?></textarea>
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
