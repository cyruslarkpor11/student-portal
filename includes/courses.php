<?php
session_start();
require "includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["user_type"] !== "student") {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION["user_id"];

// Get enrolled courses
$stmt = $conn->prepare("
    SELECT c.* FROM courses c
    JOIN enrollments e ON c.id = e.course_id
    WHERE e.student_id = ?
    ORDER BY c.course_name
");
$stmt->execute([$student_id]);
$courses = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include "includes/menu.php"; ?>

    <div class="container">
        <div class="page-header">
            <h1>📚 My Courses</h1>
            <a href="student_portal.php" class="back-link">← Back to Dashboard</a>
        </div>

        <?php if (empty($courses)): ?>
            <div class="empty-state">
                <p>You are not enrolled in any courses yet.</p>
            </div>
        <?php else: ?>
            <div class="courses-grid">
                <?php foreach ($courses as $course): ?>
                    <div class="course-card">
                        <div class="course-header">
                            <h3><?php echo htmlspecialchars($course["course_code"]); ?></h3>
                            <span class="credits"><?php echo $course["credits"]; ?> Credits</span>
                        </div>
                        <h4><?php echo htmlspecialchars($course["course_name"]); ?></h4>
                        <p class="course-description"><?php echo htmlspecialchars(substr($course["description"] ?? "No description", 0, 100)); ?>...</p>
                        <div class="course-info">
                            <p><strong>Semester:</strong> <?php echo htmlspecialchars($course["semester"] ?? "N/A"); ?></p>
                        </div>
                        <div class="course-actions">
                            <button class="btn btn-primary">View Details</button>
                            <button class="btn btn-secondary">Course Materials</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
