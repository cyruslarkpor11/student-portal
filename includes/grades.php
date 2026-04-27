<?php
session_start();
require "includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["user_type"] !== "student") {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION["user_id"];

// Get grades for student
$stmt = $conn->prepare("
    SELECT g.*, a.title as assignment_title, a.total_marks, c.course_name, c.course_code
    FROM grades g
    JOIN assignments a ON g.assignment_id = a.id
    JOIN courses c ON a.course_id = c.id
    WHERE g.student_id = ?
    ORDER BY g.graded_date DESC
");
$stmt->execute([$student_id]);
$grades = $stmt->fetchAll();

// Calculate GPA
$stmt = $conn->prepare("
    SELECT AVG((g.score/a.total_marks)*100) as average_score
    FROM grades g
    JOIN assignments a ON g.assignment_id = a.id
    WHERE g.student_id = ? AND g.score IS NOT NULL
");
$stmt->execute([$student_id]);
$gpa_result = $stmt->fetch();
$average_score = $gpa_result["average_score"] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Grades</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include "includes/menu.php"; ?>

    <div class="container">
        <div class="page-header">
            <h1>📊 My Grades</h1>
            <a href="student_portal.php" class="back-link">← Back to Dashboard</a>
        </div>

        <div class="gpa-card">
            <h3>Overall Performance</h3>
            <div class="gpa-score"><?php echo round($average_score, 2); ?>%</div>
            <p>Average Score</p>
        </div>

        <?php if (empty($grades)): ?>
            <div class="empty-state">
                <p>No grades available yet.</p>
            </div>
        <?php else: ?>
            <div class="grades-table">
                <table>
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Assignment</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Feedback</th>
                            <th>Graded Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grades as $grade): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($grade["course_code"]); ?></td>
                                <td><?php echo htmlspecialchars($grade["course_name"]); ?></td>
                                <td><?php echo htmlspecialchars($grade["assignment_title"]); ?></td>
                                <td><?php echo $grade["score"] ?? "N/A"; ?>/<?php echo $grade["total_marks"]; ?></td>
                                <td>
                                    <?php 
                                    if ($grade["score"]) {
                                        $percentage = ($grade["score"] / $grade["total_marks"]) * 100;
                                        echo round($percentage, 2) . "%";
                                    } else {
                                        echo "N/A";
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($grade["feedback"] ?? "-"); ?></td>
                                <td><?php echo $grade["graded_date"] ? date("M d, Y", strtotime($grade["graded_date"])) : "Pending"; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
