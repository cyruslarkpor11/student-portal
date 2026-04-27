<?php
session_start();
require "includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: index.php");
    exit();
}

if ($_SESSION["user_type"] === "instructor") {
    header("Location: instructor_portal.php");
    exit();
}

if ($_SESSION["user_type"] !== "student") {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION["user_id"] ?? null;
$student = null;

if ($user_id) {
    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.email, p.student_id, p.department, p.status, p.profile_picture
        FROM users u
        LEFT JOIN student_info p ON u.id = p.user_id
        WHERE u.id = ? AND u.user_type = ?
    ");
    $stmt->execute([$user_id, "student"]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$student) {
    $username = $_SESSION["username"] ?? "";
    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.email, p.student_id, p.department, p.status, p.profile_picture
        FROM users u
        LEFT JOIN student_info p ON u.id = p.user_id
        WHERE u.username = ? AND u.user_type = ?
    ");
    $stmt->execute([$username, "student"]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$student) {
    header("Location: logout.php");
    exit();
}

$_SESSION["user_id"] = $student["id"];
$_SESSION["username"] = $student["username"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - AME Zion University Nimba Extension</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(rgba(102, 126, 234, 0.8), rgba(118, 75, 162, 0.8)), url("Images/download%201.jpg") center/cover no-repeat fixed;
            margin: 0;
            padding: 0;
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

        main {
            background: rgba(0, 0, 0, 0.3);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
            max-width: 900px;
            margin: 30px 20px;
            margin-left: auto;
            margin-right: auto;
            backdrop-filter: blur(10px);
        }
        h1 {
            margin-top: 0;
            font-size: 2em;
            text-align: center;
            color: #ffd700;
        }
        .welcome {
            text-align: center;
            font-size: 1.2em;
            margin-bottom: 10px;
        }
        .dashboard-welcome {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 18px;
            margin-bottom: 30px;
        }
        .dashboard-avatar {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #ffd700;
            box-shadow: 0 4px 15px rgba(0,0,0,0.35);
            background: rgba(0, 0, 0, 0.25);
        }
        .avatar-placeholder {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            border: 3px solid #ffd700;
            background: rgba(0, 0, 0, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffd700;
            font-size: 2rem;
            font-weight: bold;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .card {
            background: rgba(0, 0, 0, 0.2);
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #4CAF50;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            color: white;
            display: block;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
            background: rgba(0, 0, 0, 0.3);
        }
        .card h3 {
            margin-top: 0;
            color: #ffd700;
        }
        .card p {
            margin: 10px 0 0 0;
            color: #ddd;
        }
    </style>
</head>
<body>
    <header>
        <h1>African Methodist Episcopal Zion University Nimba Extension</h1>
    </header>

    <nav class="nav-tabs">
        <a href="student_portal.php" style="border-bottom-color: #ffd700; color: #ffd700; background: rgba(255, 215, 0, 0.1);">📊 Dashboard</a>
        <a href="view_courses.php">📚 Courses</a>
        <a href="view_assignments.php">📝 Assignments</a>
        <a href="view_grades.php">📈 Grades</a>
        <a href="view_resources.php">📖 Resources</a>
        <a href="view_messages.php">💬 Messages</a>
        <a href="view_profile.php">👤 Profile</a>
        <a href="logout.php">🚪 Logout</a>
    </nav>

    <main>
        <h1>Student Portal</h1>
        <div class="dashboard-welcome">
            <?php if (!empty($student["profile_picture"]) && file_exists($student["profile_picture"])): ?>
                <img src="<?php echo htmlspecialchars($student["profile_picture"]); ?>" alt="Profile avatar" class="dashboard-avatar">
            <?php else: ?>
                <div class="avatar-placeholder"><?php echo strtoupper(substr($student["username"], 0, 1)); ?></div>
            <?php endif; ?>
            <div class="welcome">
                Welcome, <strong><?php echo htmlspecialchars($student["username"]); ?></strong>!
            </div>
        </div>

        <h2>Available Services</h2>
        <div class="dashboard-grid">
            <a href="view_courses.php" class="card" style="border-left-color: #4CAF50;">
                <h3>📚 Courses</h3>
                <p>See enrolled courses and enroll in available classes.</p>
            </a>
            <a href="view_assignments.php" class="card" style="border-left-color: #2196F3;">
                <h3>📝 Assignments</h3>
                <p>Track due dates and submit your assignment work.</p>
            </a>
            <a href="view_grades_simple.php" class="card" style="border-left-color: #FF9800;">
                <h3>📈 View Grade Sheet</h3>
                <p>Open your full academic grade sheet with totals and GPA.</p>
            </a>
            <a href="view_resources.php" class="card" style="border-left-color: #9C27B0;">
                <h3>📖 Resources</h3>
                <p>Access course documents, links, and materials.</p>
            </a>
            <a href="view_messages.php" class="card" style="border-left-color: #00BCD4;">
                <h3>💬 Messages</h3>
                <p>Read inbox messages and contact staff or classmates.</p>
            </a>
            <a href="view_profile.php" class="card" style="border-left-color: #ffd700;">
                <h3>👤 Profile</h3>
                <p>Update status, department, student ID, and photo.</p>
            </a>
        </div>
    </main>
</body>
</html>
