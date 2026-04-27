<?php
session_start();
require "includes/db.php";

if (!isset($_SESSION["loggedin"]) || !in_array($_SESSION["user_type"], ["student", "instructor"])) {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION["user_id"];
$update_message = "";
$error_message = "";

// Get student/instructor info
$stmt = $conn->prepare("SELECT * FROM student_info WHERE user_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    $stmt = $conn->prepare("INSERT IGNORE INTO student_info (user_id, student_id, department, status) VALUES (?, '', '', 'active')");
    $stmt->execute([$student_id]);
    $stmt = $conn->prepare("SELECT * FROM student_info WHERE user_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
}

// Get user email
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$student_id]);
$user = $stmt->fetch();

// Handle profile picture upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["profile_picture"])) {
    $file = $_FILES["profile_picture"];
    $allowed = ["jpg", "jpeg", "png", "gif"];
    $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    
    if ($file["size"] > 5242880) {
        $error_message = "File size must be less than 5MB";
    } elseif (!in_array($ext, $allowed)) {
        $error_message = "Only JPG, PNG, and GIF allowed";
    } else {
        // Try to create directory, but don't fail if it doesn't work
        $upload_dir = "uploads/profiles/";
        
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0755, true);
            if (!is_dir($upload_dir)) {
                // If directory creation fails, use uploads folder directly
                $upload_dir = "uploads/";
            }
        }
        
        $filename = "profile_" . $student_id . "_" . time() . "." . $ext;
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($file["tmp_name"], $filepath)) {
            // Delete old picture
            if ($student["profile_picture"] && file_exists($student["profile_picture"])) {
                @unlink($student["profile_picture"]);
            }
            
            $stmt = $conn->prepare("UPDATE student_info SET profile_picture = ? WHERE user_id = ?");
            $stmt->execute([$filepath, $student_id]);
            $update_message = "Picture updated successfully!";
            
            $stmt = $conn->prepare("SELECT * FROM student_info WHERE user_id = ?");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch();
        } else {
            $error_message = "Error saving file. Please make sure uploads folder exists and is writable.";
        }
    }
}

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_profile"])) {
    $first_name = htmlspecialchars($_POST["first_name"]);
    $last_name = htmlspecialchars($_POST["last_name"]);
    $phone = htmlspecialchars($_POST["phone"]);
    $address = htmlspecialchars($_POST["address"]);
    $student_id_input = htmlspecialchars($_POST["student_id_input"]);
    $department = htmlspecialchars($_POST["department_input"]);
    $major = htmlspecialchars($_POST["major"]);
    $minor = htmlspecialchars($_POST["minor"]);
    $bio = htmlspecialchars($_POST["bio"]);

    $stmt = $conn->prepare("
        UPDATE student_info 
        SET first_name = ?, last_name = ?, phone = ?, address = ?, student_id = ?, department = ?, major = ?, minor = ?, bio = ?
        WHERE user_id = ?
    ");
    $stmt->execute([$first_name, $last_name, $phone, $address, $student_id_input, $department, $major, $minor, $bio, $student_id]);
    $update_message = "Profile updated successfully!";
    
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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
        header h1 { color: #ffd700; margin: 0; }
        .nav-tabs {
            background: rgba(0, 0, 0, 0.4);
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            border-bottom: 2px solid rgba(255, 215, 0, 0.3);
        }
        .nav-tabs a {
            color: white;
            text-decoration: none;
            padding: 15px 20px;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }
        .nav-tabs a:hover { background: rgba(255, 215, 0, 0.1); border-bottom-color: #ffd700; }
        main {
            background: rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 1000px;
            margin: 30px 20px auto;
            border-radius: 10px;
        }
        h1 { color: #ffd700; margin-bottom: 20px; }
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        .profile-section {
            background: rgba(0, 0, 0, 0.2);
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #ffd700;
        }
        .profile-section h2 { color: #ffd700; margin-bottom: 20px; font-size: 1.3em; }
        .profile-section h3 { color: #667eea; margin-top: 20px; margin-bottom: 15px; font-size: 1.1em; }
        .profile-pic {
            text-align: center;
            margin-bottom: 20px;
        }
        .profile-pic img {
            width: 150px;
            height: 150px;
            border-radius: 10px;
            object-fit: cover;
            border: 3px solid #ffd700;
        }
        .profile-placeholder {
            width: 150px;
            height: 150px;
            background: rgba(102, 126, 234, 0.2);
            border: 3px dashed #ffd700;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            color: #ffd700;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            color: #ffd700;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #667eea;
            border-radius: 5px;
            background: rgba(0, 0, 0, 0.3);
            color: white;
            font-family: Arial, sans-serif;
        }
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #999;
        }
        .info-display {
            background: rgba(0, 0, 0, 0.15);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .info-item {
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .info-item:last-child { border-bottom: none; }
        .info-label { color: #ffd700; font-weight: bold; }
        .info-value { color: #ddd; }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .status-active { background: #4CAF50; }
        .status-inactive { background: #f44336; }
        .status-graduated { background: #2196F3; }
        .btn {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 15px;
        }
        .success {
            background: rgba(76, 175, 80, 0.3);
            border: 1px solid #4CAF50;
            color: #4CAF50;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error {
            background: rgba(244, 67, 54, 0.3);
            border: 1px solid #f44336;
            color: #f44336;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .back-btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin-bottom: 20px;
        }
        .small-text { font-size: 0.85em; color: #999; }
        @media (max-width: 768px) {
            .profile-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header>
        <h1>African Methodist Episcopal Zion University Nimba Extension</h1>
    </header>

    <nav class="nav-tabs">
        <a href="student_portal.php">📊 Dashboard</a>
        <a href="view_courses.php">📚 Courses</a>
        <a href="view_assignments.php">📝 Assignments</a>
        <a href="view_grades.php">📈 Grades</a>
        <a href="view_resources.php">📖 Resources</a>
        <a href="view_messages.php">💬 Messages</a>
        <a href="view_profile.php" style="border-bottom-color: #ffd700; background: rgba(255, 215, 0, 0.1);">👤 Profile</a>
        <a href="logout.php">🚪 Logout</a>
    </nav>

    <main>
        <a href="student_portal.php" class="back-btn">← Back to Dashboard</a>
        <h1>👤 My Profile</h1>

        <?php if ($update_message): ?>
            <div class="success"><?php echo $update_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="profile-grid">
            <div class="profile-section">
                <h2>Profile Picture</h2>
                <div class="profile-pic">
                    <?php if ($student && $student["profile_picture"]): ?>
                        <img src="<?php echo htmlspecialchars($student["profile_picture"]); ?>" alt="Profile">
                    <?php else: ?>
                        <div class="profile-placeholder">No Picture</div>
                    <?php endif; ?>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Upload Picture:</label>
                        <input type="file" name="profile_picture" accept="image/*" required>
                        <p class="small-text">Max 5MB (JPG, PNG, GIF)</p>
                    </div>
                    <button type="submit" class="btn">Upload Picture</button>
                </form>
            </div>

            <div class="profile-section">
                <h2>Student Information</h2>
                <div class="info-display">
                    <div class="info-item">
                        <span class="info-label">Student ID:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student["student_id"] ?? "N/A"); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Department:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student["department"] ?? "N/A"); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status:</span>
                        <span class="status-badge status-<?php echo strtolower($student["status"] ?? "inactive"); ?>">
                            <?php echo ucfirst($student["status"] ?? "Inactive"); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user["email"]); ?></span>
                    </div>
                </div>

                <h3>Edit Personal Information</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Student ID:</label>
                        <input type="text" name="student_id_input" value="<?php echo htmlspecialchars($student["student_id"] ?? ""); ?>">
                    </div>
                    <div class="form-group">
                        <label>Department:</label>
                        <input type="text" name="department_input" value="<?php echo htmlspecialchars($student["department"] ?? ""); ?>">
                    </div>
                    <div class="form-group">
                        <label>First Name:</label>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($student["first_name"] ?? ""); ?>">
                    </div>
                    <div class="form-group">
                        <label>Last Name:</label>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($student["last_name"] ?? ""); ?>">
                    </div>
                    <div class="form-group">
                        <label>Major:</label>
                        <input type="text" name="major" value="<?php echo htmlspecialchars($student["major"] ?? ""); ?>">
                    </div>
                    <div class="form-group">
                        <label>Minor:</label>
                        <input type="text" name="minor" value="<?php echo htmlspecialchars($student["minor"] ?? ""); ?>">
                    </div>
                    <div class="form-group">
                        <label>Phone:</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($student["phone"] ?? ""); ?>">
                    </div>
                    <div class="form-group">
                        <label>Address:</label>
                        <textarea name="address" rows="3"><?php echo htmlspecialchars($student["address"] ?? ""); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Bio:</label>
                        <textarea name="bio" rows="4" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($student["bio"] ?? ""); ?></textarea>
                    </div>
                    <button type="submit" name="update_profile" class="btn">Update Profile</button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
