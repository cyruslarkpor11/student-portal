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

// Handle course enrollment for students
if (!$isInstructor && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["enroll_course"])) {
    $course_id = $_POST["course_id"];
    try {
        $stmt = $conn->prepare("INSERT INTO student_courses (user_id, course_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $course_id]);
        $message = "Successfully enrolled in course!";
    } catch(PDOException $e) {
        $message = "Already enrolled or error occurred!";
    }
}

if ($isInstructor) {
    $instructor_name = ucwords(str_replace(["_", "."], " ", $_SESSION["username"]));
    $stmt = $conn->prepare("SELECT c.* FROM courses c WHERE c.instructor = ? ORDER BY c.course_name");
    $stmt->execute([$instructor_name]);
    $enrolled_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT u.id, u.username, si.first_name, si.last_name, sc.course_id FROM users u LEFT JOIN student_info si ON u.id = si.user_id INNER JOIN student_courses sc ON u.id = sc.user_id INNER JOIN courses c ON sc.course_id = c.course_id WHERE c.instructor = ? ORDER BY c.course_name, u.username");
    $stmt->execute([$instructor_name]);
    $students_by_course = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $student) {
        $students_by_course[$student["course_id"]][] = $student;
    }
} else {
    // Get enrolled courses
    $stmt = $conn->prepare("
    SELECT c.* FROM courses c
    INNER JOIN student_courses sc ON c.course_id = sc.course_id
    WHERE sc.user_id = ?
    ORDER BY c.course_name
");
    $stmt->execute([$user_id]);
    $enrolled_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get available courses (not enrolled)
    $stmt = $conn->prepare("
    SELECT c.* FROM courses c
    WHERE c.course_id NOT IN (
        SELECT course_id FROM student_courses WHERE user_id = ?
    )
    ORDER BY c.course_name
");
    $stmt->execute([$user_id]);
    $available_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Courses - Student Portal</title>
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

        .section {
            margin: 30px 0;
        }

        .course-card {
            background: rgba(0, 0, 0, 0.2);
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #4CAF50;
            margin: 15px 0;
            transition: transform 0.3s;
        }

        .course-card:hover {
            transform: translateX(5px);
            background: rgba(0, 0, 0, 0.3);
        }

        .course-card h3 {
            color: #ffd700;
            margin: 0 0 10px 0;
        }

        .course-code {
            color: #aaa;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .course-info {
            margin: 10px 0;
            line-height: 1.6;
        }

        .course-info strong {
            color: #ffd700;
        }

        button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }

        button:hover {
            background: #45a049;
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
            padding: 20px;
            color: #aaa;
            font-style: italic;
        }
    </style>
</head>
<body>
    <header>
        <h1>African Methodist Episcopal Zion University Nimba Extension</h1>
    </header>

    <nav class="nav-tabs">
        <a href="<?php echo $dashboardLink; ?>">📊 Dashboard</a>
        <a href="view_courses.php" class="active">📚 Courses</a>
        <a href="view_assignments.php">📝 Assignments</a>
        <a href="view_grades.php">📊 Grades</a>
        <a href="view_resources.php">📖 Resources</a>
        <a href="view_messages.php">💬 Messages</a>
        <a href="chat_room.php">💬 Chat</a>
        <a href="view_profile.php">👤 Profile</a>
        <a href="logout.php">🚪 Logout</a>
    </nav>

    <main>
        <a href="<?php echo $dashboardLink; ?>" class="back-link">← Back to Dashboard</a>
        <h2>📚 <?php echo $isInstructor ? 'Courses You Teach' : 'My Courses'; ?></h2>

        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($isInstructor): ?>
            <div class="section">
                <?php if (count($enrolled_courses) > 0): ?>
                    <?php foreach ($enrolled_courses as $course): ?>
                        <div class="course-card">
                            <h3><?php echo htmlspecialchars($course["course_name"]); ?></h3>
                            <div class="course-code"><?php echo htmlspecialchars($course["course_code"]); ?></div>
                            <div class="course-info">
                                <strong>Credits:</strong> <?php echo htmlspecialchars($course["credits"]); ?><br>
                                <strong>Semester:</strong> <?php echo htmlspecialchars($course["semester"]); ?><br>
                                <strong>Description:</strong> <?php echo htmlspecialchars($course["description"]); ?><br>
                                <strong>Students Enrolled:</strong> <?php echo count($students_by_course[$course["course_id"]] ?? []); ?>
                            </div>
                            <div class="course-info">
                                <strong>Student List:</strong>
                                <?php if (!empty($students_by_course[$course["course_id"]])): ?>
                                    <ul style="margin-top: 10px; list-style: none; padding-left: 0; color: #ddd;">
                                        <?php foreach ($students_by_course[$course["course_id"]] as $student): ?>
                                            <li>• <?php echo htmlspecialchars(trim(($student["first_name"] ?? "") . " " . ($student["last_name"] ?? ""))) ?: htmlspecialchars($student["username"]); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="empty" style="margin: 0; padding: 10px;">No students enrolled yet.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty">You are not assigned to teach any courses yet.</div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="section">
                <h3 style="color: #ffd700; margin: 20px 0 15px 0;">Enrolled Courses (<?php echo count($enrolled_courses); ?>)</h3>
                <?php if (count($enrolled_courses) > 0): ?>
                    <?php foreach ($enrolled_courses as $course): ?>
                        <div class="course-card">
                            <h3><?php echo htmlspecialchars($course["course_name"]); ?></h3>
                            <div class="course-code"><?php echo htmlspecialchars($course["course_code"]); ?></div>
                            <div class="course-info">
                                <strong>Instructor:</strong> <?php echo htmlspecialchars($course["instructor"]); ?><br>
                                <strong>Credits:</strong> <?php echo htmlspecialchars($course["credits"]); ?><br>
                                <strong>Semester:</strong> <?php echo htmlspecialchars($course["semester"]); ?><br>
                                <strong>Description:</strong> <?php echo htmlspecialchars($course["description"]); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty">No courses enrolled yet. Browse available courses below!</div>
                <?php endif; ?>
            </div>

            <div class="section">
                <h3 style="color: #ffd700; margin: 20px 0 15px 0;">Available Courses (<?php echo count($available_courses); ?>)</h3>
                <?php if (count($available_courses) > 0): ?>
                    <?php foreach ($available_courses as $course): ?>
                        <div class="course-card">
                            <h3><?php echo htmlspecialchars($course["course_name"]); ?></h3>
                            <div class="course-code"><?php echo htmlspecialchars($course["course_code"]); ?></div>
                            <div class="course-info">
                                <strong>Instructor:</strong> <?php echo htmlspecialchars($course["instructor"]); ?><br>
                                <strong>Credits:</strong> <?php echo htmlspecialchars($course["credits"]); ?><br>
                                <strong>Semester:</strong> <?php echo htmlspecialchars($course["semester"]); ?><br>
                                <strong>Description:</strong> <?php echo htmlspecialchars($course["description"]); ?>
                            </div>
                            <form method="POST" style="margin-top: 10px;">
                                <input type="hidden" name="course_id" value="<?php echo $course["course_id"]; ?>">
                                <button type="submit" name="enroll_course">Enroll Now</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty">All available courses have been enrolled!</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
