<?php
session_start();
require "includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["user_type"] !== "instructor") {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$stmt = $conn->prepare("SELECT u.username, u.email, si.student_id, si.department, si.status, si.profile_picture, si.first_name, si.last_name FROM users u LEFT JOIN student_info si ON u.id = si.user_id WHERE u.id = ?");
$stmt->execute([$user_id]);
$instructor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$instructor) {
    header("Location: logout.php");
    exit();
}

$displayName = trim(($instructor["first_name"] ?? "") . " " . ($instructor["last_name"] ?? ""));
if (!$displayName) {
    $displayName = $instructor["username"];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Portal - AME Zion University Nimba Extension</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(rgba(16, 14, 45, 0.8), rgba(69, 26, 123, 0.45)), url("Images/download%201.jpg") center/cover no-repeat fixed;
            color: white;
            min-height: 100vh;
        }
        header {
            background: rgba(0, 0, 0, 0.55);
            padding: 24px;
            text-align: center;
            border-bottom: 3px solid #ffd700;
        }
        header h1 {
            color: #ffd700;
            font-size: 2.1em;
            margin-bottom: 10px;
        }
        header p {
            color: #ddd;
            font-size: 1rem;
        }
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
            font-weight: 600;
        }
        .nav-tabs a:hover,
        .nav-tabs a.active {
            background: rgba(255, 215, 0, 0.1);
            border-bottom-color: #ffd700;
            color: #ffd700;
        }
        main {
            background: rgba(0, 0, 0, 0.35);
            max-width: 1000px;
            margin: 30px auto;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
            backdrop-filter: blur(8px);
        }
        .portal-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            margin-bottom: 35px;
        }
        .portal-header .welcome {
            max-width: 620px;
        }
        .portal-header h2 {
            color: #ffd700;
            font-size: 2rem;
        }
        .portal-header p {
            color: #ddd;
            line-height: 1.6;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }
        .card {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255,255,255,0.08);
            border-left: 4px solid #4CAF50;
            padding: 22px;
            border-radius: 14px;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            text-decoration: none;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 40px rgba(0,0,0,0.24);
        }
        .card h3 {
            color: #ffd700;
            margin-bottom: 10px;
        }
        .card p {
            color: #ddd;
            line-height: 1.6;
        }
        .card span {
            margin-top: 20px;
            display: inline-block;
            color: #aaa;
            font-size: 0.95em;
        }
        .dashboard-avatar {
            width: 92px;
            height: 92px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #ffd700;
            background: rgba(255,255,255,0.1);
        }
        .avatar-placeholder {
            width: 92px;
            height: 92px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            border: 3px solid #ffd700;
            background: rgba(255,255,255,0.1);
            color: #ffd700;
        }
    </style>
</head>
<body>
    <header>
        <h1>African Methodist Episcopal Zion University Nimba Extension</h1>
        <p>Instructor Portal — Manage teaching, messages, resources, and performance.</p>
    </header>
    <nav class="nav-tabs">
        <a href="instructor_portal.php" class="active">📊 Dashboard</a>
        <a href="view_assignments.php">📝 Assignments</a>
        <a href="view_grades.php">📈 Grades</a>
        <a href="view_resources.php">📖 Resources</a>
        <a href="view_messages.php">💬 Messages</a>
        <a href="chat_room.php">👥 Chat Room</a>
        <a href="view_profile.php">👤 Profile</a>
        <a href="logout.php">🚪 Logout</a>
    </nav>
    <main>
        <div class="portal-header">
            <div class="welcome">
                <h2>Welcome, <?php echo htmlspecialchars($displayName); ?></h2>
                <p>This instructor portal allows you to grade assignments, post resources, send messages to enrolled students, and monitor the student chat room. Use the links below to manage your teaching responsibilities.</p>
            </div>
            <div>
                <?php if (!empty($instructor["profile_picture"]) && file_exists($instructor["profile_picture"])): ?>
                    <img src="<?php echo htmlspecialchars($instructor["profile_picture"]);
?>" alt="Profile Picture" class="dashboard-avatar">
                <?php else: ?>
                    <div class="avatar-placeholder"><?php echo strtoupper(substr($displayName, 0, 1)); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="dashboard-grid">
            <a href="view_assignments.php" class="card">
                <h3>📝 Assignments</h3>
                <p>Grade student submissions and provide feedback for your courses.</p>
                <span>Review work and assign grades with detailed comments.</span>
            </a>
            <a href="view_grades.php" class="card">
                <h3>📈 Grades</h3>
                <p>Review course performance and grade summaries across your classes.</p>
                <span>Monitor class GPA and identify gaps early.</span>
            </a>
            <a href="view_resources.php" class="card">
                <h3>📖 Resources</h3>
                <p>Upload course materials and share files with enrolled students.</p>
                <span>Provide students with the documents and resources they need.</span>
            </a>
            <a href="view_messages.php" class="card">
                <h3>💬 Messages</h3>
                <p>Send messages to students enrolled in your courses.</p>
                <span>Communicate directly with your students and class.</span>
            </a>
            <a href="chat_room.php" class="card">
                <h3>👥 Chat Room</h3>
                <p>Access the student chat room to monitor discussions.</p>
                <span>Join student conversations and provide guidance.</span>
            </a>
            <a href="view_profile.php" class="card">
                <h3>👤 Profile</h3>
                <p>Update your instructor information and profile photo.</p>
                <span>Keep your teaching profile current and professional.</span>
            </a>
        </div>
    </main>
</body>
</html>
