<?php
session_start();
require "includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["user_type"] !== "student") {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION["user_id"];

// Get assignments for enrolled courses
$stmt = $conn->prepare("
    SELECT a.*, c.course_name, c.course_code 
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    WHERE a.course_id IN (SELECT course_id FROM enrollments WHERE student_id = ?)
    ORDER BY a.due_date ASC
");
$stmt->execute([$student_id]);
$assignments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include "includes/menu.php"; ?>

    <div class="container">
        <div class="page-header">
            <h1>📝 My Assignments</h1>
            <a href="student_portal.php" class="back-link">← Back to Dashboard</a>
        </div>

        <?php if (empty($assignments)): ?>
            <div class="empty-state">
                <p>No assignments assigned yet.</p>
            </div>
        <?php else: ?>
            <div class="assignments-list">
                <?php foreach ($assignments as $assignment): ?>
                    <div class="assignment-item">
                        <div class="assignment-header">
                            <h3><?php echo htmlspecialchars($assignment["title"]); ?></h3>
                            <span class="course-badge"><?php echo htmlspecialchars($assignment["course_code"]); ?></span>
                        </div>
                        <p class="course-name"><?php echo htmlspecialchars($assignment["course_name"]); ?></p>
                        <p class="assignment-description"><?php echo htmlspecialchars($assignment["description"] ?? "No description"); ?></p>
                        <div class="assignment-details">
                            <div class="detail-item">
                                <strong>Due Date:</strong> 
                                <span><?php echo date("M d, Y H:i", strtotime($assignment["due_date"])); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Total Marks:</strong> 
                                <span><?php echo $assignment["total_marks"]; ?></span>
                            </div>
                        </div>
                        <div class="assignment-actions">
                            <button class="btn btn-primary">View Details</button>
                            <button class="btn btn-secondary">Submit</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
