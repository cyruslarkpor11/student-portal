<?php
session_start();
require "includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["user_type"] !== "student") {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION["user_id"];

// Get resources for enrolled courses
$stmt = $conn->prepare("
    SELECT r.*, c.course_name, c.course_code
    FROM resources r
    JOIN courses c ON r.course_id = c.id
    WHERE r.course_id IN (SELECT course_id FROM enrollments WHERE student_id = ?)
    ORDER BY r.upload_date DESC
");
$stmt->execute([$student_id]);
$resources = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include "includes/menu.php"; ?>

    <div class="container">
        <div class="page-header">
            <h1>📖 Learning Resources</h1>
            <a href="student_portal.php" class="back-link">← Back to Dashboard</a>
        </div>

        <?php if (empty($resources)): ?>
            <div class="empty-state">
                <p>No resources available yet.</p>
            </div>
        <?php else: ?>
            <div class="resources-list">
                <?php foreach ($resources as $resource): ?>
                    <div class="resource-item">
                        <div class="resource-icon">
                            <?php
                            $file_type = strtoupper($resource["file_type"] ?? "FILE");
                            echo "📄";
                            ?>
                        </div>
                        <div class="resource-content">
                            <h3><?php echo htmlspecialchars($resource["title"]); ?></h3>
                            <p class="course-info"><?php echo htmlspecialchars($resource["course_code"]); ?> - <?php echo htmlspecialchars($resource["course_name"]); ?></p>
                            <p class="resource-description"><?php echo htmlspecialchars($resource["description"] ?? "No description"); ?></p>
                            <p class="upload-date">
                                <small>Uploaded: <?php echo date("M d, Y", strtotime($resource["upload_date"])); ?></small>
                            </p>
                        </div>
                        <div class="resource-actions">
                            <a href="#" class="btn btn-primary">Download</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
