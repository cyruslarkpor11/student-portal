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

// Handle assignment submission for students
if (!$isInstructor && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_assignment"])) {
    $assignment_id = $_POST["assignment_id"];
    $submission_text = $_POST["submission_text"] ?? "";
    $submission_file = NULL;

    if (isset($_FILES["submission_file"]) && $_FILES["submission_file"]["error"] == 0) {
        $upload_dir = "submissions/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $file_name = time() . "_" . basename($_FILES["submission_file"]["name"]);
        $file_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES["submission_file"]["tmp_name"], $file_path)) {
            $submission_file = $file_path;
        }
    }

    try {
        $stmt = $conn->prepare("SELECT submission_id FROM student_assignments WHERE user_id = ? AND assignment_id = ?");
        $stmt->execute([$user_id, $assignment_id]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $conn->prepare("UPDATE student_assignments SET submission_text = ?, submission_file = COALESCE(?, submission_file), submitted_date = NOW(), status = 'submitted' WHERE user_id = ? AND assignment_id = ?");
            $stmt->execute([$submission_text, $submission_file, $user_id, $assignment_id]);
        } else {
            $stmt = $conn->prepare("INSERT INTO student_assignments (user_id, assignment_id, submission_text, submission_file, submitted_date, status) VALUES (?, ?, ?, ?, NOW(), 'submitted')");
            $stmt->execute([$user_id, $assignment_id, $submission_text, $submission_file]);
        }
        $message = "Assignment submitted successfully!";
    } catch(PDOException $e) {
        $message = "Error submitting assignment: " . $e->getMessage();
    }
}

// Handle grading submissions for instructors
if ($isInstructor && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["grade_submission"])) {
    $submission_id = $_POST["submission_id"];
    $points_earned = intval($_POST["points_earned"]);
    $feedback = $_POST["feedback"] ?? "";

    try {
        $stmt = $conn->prepare("SELECT sa.user_id, sa.assignment_id, a.course_id, a.total_points FROM student_assignments sa INNER JOIN assignments a ON sa.assignment_id = a.assignment_id WHERE sa.submission_id = ?");
        $stmt->execute([$submission_id]);
        $submission = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($submission) {
            $stmt = $conn->prepare("UPDATE student_assignments SET points_earned = ?, feedback = ?, status = 'graded' WHERE submission_id = ?");
            $stmt->execute([$points_earned, $feedback, $submission_id]);

            $grade_letter = 'F';
            if ($submission["total_points"] > 0) {
                $percentage = ($points_earned / $submission["total_points"]) * 100;
                if ($percentage >= 90) $grade_letter = 'A';
                elseif ($percentage >= 80) $grade_letter = 'B';
                elseif ($percentage >= 70) $grade_letter = 'C';
                elseif ($percentage >= 60) $grade_letter = 'D';
            }

            $stmt = $conn->prepare("SELECT grade_id FROM grades WHERE user_id = ? AND assignment_id = ?");
            $stmt->execute([$submission["user_id"], $submission["assignment_id"]]);
            $existingGrade = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingGrade) {
                $stmt = $conn->prepare("UPDATE grades SET points_earned = ?, total_points = ?, grade_letter = ?, feedback = ?, created_at = NOW() WHERE grade_id = ?");
                $stmt->execute([$points_earned, $submission["total_points"], $grade_letter, $feedback, $existingGrade["grade_id"]]);
            } else {
                $stmt = $conn->prepare("INSERT INTO grades (user_id, course_id, assignment_id, points_earned, total_points, grade_letter, feedback, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$submission["user_id"], $submission["course_id"], $submission["assignment_id"], $points_earned, $submission["total_points"], $grade_letter, $feedback]);
            }

            $message = "Grade recorded successfully.";
        }
    } catch(PDOException $e) {
        $message = "Error recording grade: " . $e->getMessage();
    }
}

if ($isInstructor) {
    $instructor_name = ucwords(str_replace(["_", "."], " ", $_SESSION["username"]));
    $stmt = $conn->prepare("SELECT DISTINCT c.course_id, c.course_name FROM courses c WHERE c.instructor = ? ORDER BY c.course_name");
    $stmt->execute([$instructor_name]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($courses)) {
        $stmt = $conn->query("SELECT DISTINCT c.course_id, c.course_name FROM courses c ORDER BY c.course_name");
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $stmt = $conn->prepare("SELECT a.assignment_id, a.title, a.description, a.due_date, a.total_points, c.course_name, c.course_id FROM assignments a INNER JOIN courses c ON a.course_id = c.course_id WHERE c.instructor = ? ORDER BY a.due_date DESC");
    $stmt->execute([$instructor_name]);
    $assignments = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $row["submissions"] = [];
        $assignments[$row["assignment_id"]] = $row;
    }

    $stmt = $conn->prepare("SELECT sa.*, u.username, u.email, a.assignment_id FROM student_assignments sa INNER JOIN assignments a ON sa.assignment_id = a.assignment_id INNER JOIN courses c ON a.course_id = c.course_id LEFT JOIN users u ON sa.user_id = u.id WHERE c.instructor = ? ORDER BY sa.submitted_date DESC");
    $stmt->execute([$instructor_name]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $submission) {
        if (isset($assignments[$submission["assignment_id"]])) {
            $assignments[$submission["assignment_id"]]["submissions"][] = $submission;
        }
    }

    $assignments = array_values($assignments);
} else {
    $stmt = $conn->prepare("
        SELECT DISTINCT c.course_id, c.course_name FROM courses c
        INNER JOIN student_courses sc ON c.course_id = sc.course_id
        WHERE sc.user_id = ?
        ORDER BY c.course_name
    ");
    $stmt->execute([$user_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("
        SELECT a.assignment_id, a.title, a.description, a.due_date, a.total_points, c.course_name, c.course_id,
               COALESCE(sa.status, 'not_started') as submission_status,
               COALESCE(sa.submitted_date, NULL) as submitted_date,
               COALESCE(sa.points_earned, NULL) as points_earned,
               COALESCE(sa.feedback, NULL) as feedback
        FROM assignments a
        INNER JOIN courses c ON a.course_id = c.course_id
        INNER JOIN student_courses sc ON c.course_id = sc.course_id
        LEFT JOIN student_assignments sa ON a.assignment_id = sa.assignment_id AND sa.user_id = ?
        WHERE sc.user_id = ?
        ORDER BY a.due_date DESC
    ");
    $stmt->execute([$user_id, $user_id]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$submission_data = null;
if (isset($_GET["assignment_id"]) && is_numeric($_GET["assignment_id"])) {
    $assignment_id = $_GET["assignment_id"];
    $stmt = $conn->prepare("SELECT * FROM student_assignments WHERE user_id = ? AND assignment_id = ?");
    $stmt->execute([$user_id, $assignment_id]);
    $submission_data = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Assignments - Student Portal</title>
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

        h3 {
            color: #ffd700;
            margin: 20px 0 15px 0;
        }

        .assignment-card {
            background: rgba(0, 0, 0, 0.2);
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #2196F3;
            margin: 15px 0;
            transition: transform 0.3s;
        }

        .assignment-card:hover {
            transform: translateX(5px);
            background: rgba(0, 0, 0, 0.3);
        }

        .assignment-card h4 {
            color: #ffd700;
            margin: 0 0 10px 0;
        }

        .assignment-info {
            margin: 10px 0;
            line-height: 1.6;
        }

        .assignment-info strong {
            color: #ffd700;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.85em;
            font-weight: bold;
            margin-top: 10px;
        }

        .status-not_started {
            background: rgba(244, 67, 54, 0.3);
            color: #ff8a80;
        }

        .status-in_progress {
            background: rgba(255, 152, 0, 0.3);
            color: #ffb74d;
        }

        .status-submitted {
            background: rgba(76, 175, 80, 0.3);
            color: #a8d8a8;
        }

        .status-graded {
            background: rgba(33, 150, 243, 0.3);
            color: #64b5f6;
        }

        button {
            background: #2196F3;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }

        button:hover {
            background: #1976D2;
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

        .submission-form {
            background: rgba(0, 0, 0, 0.4);
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .form-group {
            margin: 15px 0;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #ffd700;
            font-weight: bold;
        }

        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 5px;
            background: rgba(0, 0, 0, 0.3);
            color: white;
            font-family: Arial, sans-serif;
            min-height: 150px;
            resize: vertical;
        }

        input[type="file"] {
            padding: 8px;
        }

        .empty {
            text-align: center;
            padding: 20px;
            color: #aaa;
            font-style: italic;
        }

        .feedback-box {
            background: rgba(33, 150, 243, 0.1);
            padding: 15px;
            border-left: 4px solid #2196F3;
            border-radius: 5px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <header>
        <h1>African Methodist Episcopal Zion University Nimba Extension</h1>
    </header>

    <nav class="nav-tabs">
        <a href="<?php echo $dashboardLink; ?>">📊 Dashboard</a>
        <a href="view_courses.php">📚 Courses</a>
        <a href="view_assignments.php" class="active">📝 Assignments</a>
        <a href="view_grades.php">📊 Grades</a>
        <a href="view_resources.php">📖 Resources</a>
        <a href="view_messages.php">💬 Messages</a>
        <a href="chat_room.php">💬 Chat</a>
        <a href="view_profile.php">👤 Profile</a>
        <a href="logout.php">🚪 Logout</a>
    </nav>

    <main>
        <a href="<?php echo $dashboardLink; ?>" class="back-link">← Back to Dashboard</a>
        <h2>📝 <?php echo $isInstructor ? 'Assignment Submissions' : 'My Assignments'; ?></h2>

        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (count($assignments) > 0): ?>
            <?php foreach ($assignments as $assignment): ?>
                <div class="assignment-card">
                    <h4><?php echo htmlspecialchars($assignment["title"]); ?></h4>
                    <div class="assignment-info">
                        <strong>Course:</strong> <?php echo htmlspecialchars($assignment["course_name"]); ?><br>
                        <strong>Due Date:</strong> <?php echo date("F j, Y g:i A", strtotime($assignment["due_date"])); ?><br>
                        <strong>Total Points:</strong> <?php echo $assignment["total_points"]; ?><br>
                        <strong>Description:</strong> <?php echo htmlspecialchars($assignment["description"]); ?>
                    </div>

                    <?php if ($isInstructor): ?>
                        <?php if (!empty($assignment["submissions"])): ?>
                            <?php foreach ($assignment["submissions"] as $submission): ?>
                                <div class="assignment-card" style="border-left-color: #4CAF50; margin-top: 15px;">
                                    <h4 style="font-size: 1.1em;">Submission by <?php echo htmlspecialchars($submission["username"] ?? 'Student'); ?></h4>
                                    <div class="assignment-info">
                                        <strong>Status:</strong> <?php echo htmlspecialchars($submission["status"]); ?><br>
                                        <strong>Submitted:</strong> <?php echo htmlspecialchars($submission["submitted_date"] ? date("F j, Y g:i A", strtotime($submission["submitted_date"])) : 'Not submitted'); ?><br>
                                        <?php if ($submission["submission_text"]): ?><strong>Text:</strong> <?php echo nl2br(htmlspecialchars($submission["submission_text"])); ?><br><?php endif; ?>
                                        <?php if ($submission["submission_file"]): ?><strong>File:</strong> <a href="<?php echo htmlspecialchars($submission["submission_file"]); ?>" target="_blank" style="color: #ffd700;">View file</a><br><?php endif; ?>
                                    </div>
                                    <?php if ($submission["points_earned"] !== null || $submission["status"] === 'graded'): ?>
                                        <div class="feedback-box">
                                            <strong>Points Earned:</strong> <?php echo htmlspecialchars($submission["points_earned"] ?? '0'); ?> / <?php echo htmlspecialchars($assignment["total_points"]); ?><br>
                                            <?php if ($submission["feedback"]): ?><strong>Feedback:</strong> <?php echo nl2br(htmlspecialchars($submission["feedback"])); ?><br><?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="submission-form">
                                        <form method="POST">
                                            <input type="hidden" name="submission_id" value="<?php echo $submission["submission_id"]; ?>">
                                            <div class="form-group">
                                                <label for="points_earned_<?php echo $submission["submission_id"]; ?>">Points Earned</label>
                                                <input type="number" name="points_earned" id="points_earned_<?php echo $submission["submission_id"]; ?>" value="<?php echo htmlspecialchars($submission["points_earned"] ?? ''); ?>" min="0" max="<?php echo htmlspecialchars($assignment["total_points"]); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="feedback_<?php echo $submission["submission_id"]; ?>">Feedback</label>
                                                <textarea name="feedback" id="feedback_<?php echo $submission["submission_id"]; ?>" placeholder="Enter feedback for this student..."><?php echo htmlspecialchars($submission["feedback"] ?? ''); ?></textarea>
                                            </div>
                                            <button type="submit" name="grade_submission">Save Grade</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty">No submissions yet for this assignment.</div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div>
                            <span class="status-badge status-<?php echo $assignment["submission_status"]; ?>">
                                <?php echo ucfirst(str_replace("_", " ", $assignment["submission_status"])); ?>
                            </span>
                        </div>
                        <?php if ($assignment["submitted_date"]): ?>
                            <div class="assignment-info" style="margin-top: 10px;">
                                <strong>Submitted:</strong> <?php echo date("F j, Y g:i A", strtotime($assignment["submitted_date"])); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($assignment["submission_status"] == "graded"): ?>
                            <div class="feedback-box">
                                <strong>Points Earned:</strong> <?php echo $assignment["points_earned"]; ?> / <?php echo $assignment["total_points"]; ?><br>
                                <?php if ($assignment["feedback"]): ?>
                                    <strong>Feedback:</strong> <?php echo htmlspecialchars($assignment["feedback"]); ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="submission-form">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="assignment_id" value="<?php echo $assignment["assignment_id"]; ?>">
                                <div class="form-group">
                                    <label for="submission_text_<?php echo $assignment["assignment_id"]; ?>">Your Answer:</label>
                                    <textarea name="submission_text" id="submission_text_<?php echo $assignment["assignment_id"]; ?>" placeholder="Enter your assignment answer here..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="submission_file_<?php echo $assignment["assignment_id"]; ?>">Upload File (Optional):</label>
                                    <input type="file" name="submission_file" id="submission_file_<?php echo $assignment["assignment_id"]; ?>" accept=".pdf,.doc,.docx,.txt,.zip">
                                </div>
                                <button type="submit" name="submit_assignment">Submit Assignment</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty">No assignments available for your courses yet.</div>
        <?php endif; ?>
    </main>
</body>
</html>
