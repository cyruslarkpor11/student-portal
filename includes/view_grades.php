<?php
// Redirect to simplified grades page
header("Location: view_grades_simple.php");
exit();
?>

$user_id = $_SESSION["user_id"] ?? null;
$isInstructor = $_SESSION["user_type"] === "instructor";
$dashboardLink = $isInstructor ? "instructor_portal.php" : "student_portal.php";

// Initialize arrays
$grade_details = [];
$course_grades = [];
$assignment_grades = [];
$student_info = [];
$total_credits = 0;
$total_points = 0;
$gpa = "N/A";

if ($isInstructor) {
    $instructor_name = ucwords(str_replace(["_", "."], " ", $_SESSION["username"]));
    $stmt = $conn->prepare("
        SELECT AVG(CAST(g.points_earned AS DECIMAL(10,2)) / CAST(g.total_points AS DECIMAL(10,2)) * 4.0) as gpa
        FROM grades g
        INNER JOIN courses c ON g.course_id = c.course_id
        WHERE c.instructor = ? AND g.total_points > 0
    ");
    $stmt->execute([$instructor_name]);
    $gpa_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $gpa = $gpa_result["gpa"] ? number_format($gpa_result["gpa"], 2) : "N/A";

    $stmt = $conn->prepare("
        SELECT c.course_id, c.course_name, c.course_code,
               AVG(CAST(g.points_earned AS DECIMAL(10,2)) / CAST(g.total_points AS DECIMAL(10,2)) * 100) as course_percentage,
               COUNT(g.grade_id) as grade_count,
               SUM(g.points_earned) as total_points_earned,
               SUM(g.total_points) as total_points_possible
        FROM courses c
        LEFT JOIN grades g ON c.course_id = g.course_id
        WHERE c.instructor = ?
        GROUP BY c.course_id, c.course_name, c.course_code
        ORDER BY c.course_name
    ");
    $stmt->execute([$instructor_name]);
    $course_grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("
        SELECT a.assignment_id, a.title, c.course_name, g.points_earned, g.total_points,
               ROUND(CAST(g.points_earned AS DECIMAL(10,2)) / CAST(g.total_points AS DECIMAL(10,2)) * 100) as percentage,
               g.created_at
        FROM grades g
        INNER JOIN assignments a ON g.assignment_id = a.assignment_id
        INNER JOIN courses c ON g.course_id = c.course_id
        WHERE c.instructor = ?
        ORDER BY g.created_at DESC
    ");
    $stmt->execute([$instructor_name]);
    $assignment_grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Get student information
    try {
        $stmt = $conn->prepare("
            SELECT u.username, u.email, si.student_id, si.first_name, si.last_name, si.department, si.major, si.gender, si.program
            FROM users u 
            LEFT JOIN student_info si ON u.id = si.user_id 
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        $student_info = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get comprehensive grade sheet data
        $stmt = $conn->prepare("
            SELECT 
                c.course_code,
                c.course_name,
                c.credits,
                c.instructor,
                g.points_earned,
                g.total_points,
                g.grade_letter,
                g.created_at
            FROM grades g
            INNER JOIN courses c ON g.course_id = c.course_id
            WHERE g.user_id = ?
            ORDER BY c.course_name
        ");
        $stmt->execute([$user_id]);
        $grade_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate totals
        $total_credits = 0;
        $total_points = 0;
        $weighted_gpa_points = 0;

        foreach ($grade_details as $grade) {
            $total_credits += $grade['credits'];
            $total_points += $grade['points_earned'];
            
            // Calculate GPA points (4.0 scale)
            if ($grade['total_points'] > 0) {
                $percentage = ($grade['points_earned'] / $grade['total_points']) * 100;
                $gpa_points = 0;
                if ($percentage >= 90) $gpa_points = 4.0;
                else if ($percentage >= 80) $gpa_points = 3.0;
                else if ($percentage >= 70) $gpa_points = 2.0;
                else if ($percentage >= 60) $gpa_points = 1.0;
                else $gpa_points = 0.0;
                
                $weighted_gpa_points += ($gpa_points * $grade['credits']);
            }
        }

        $gpa = $total_credits > 0 ? number_format($weighted_gpa_points / $total_credits, 2) : "N/A";

        $stmt = $conn->prepare("
            SELECT c.course_id, c.course_name, c.course_code,
                   AVG(CAST(g.points_earned AS DECIMAL(10,2)) / CAST(g.total_points AS DECIMAL(10,2)) * 100) as course_percentage,
                   COUNT(g.grade_id) as grade_count,
                   SUM(g.points_earned) as total_points_earned,
                   SUM(g.total_points) as total_points_possible
            FROM courses c
            INNER JOIN student_courses sc ON c.course_id = sc.course_id
            LEFT JOIN grades g ON c.course_id = g.course_id AND g.user_id = ?
            WHERE sc.user_id = ?
            GROUP BY c.course_id, c.course_name, c.course_code
            ORDER BY c.course_name
        ");
        $stmt->execute([$user_id, $user_id]);
        $course_grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $conn->prepare("
            SELECT a.assignment_id, a.title, c.course_name, g.points_earned, g.total_points,
                   ROUND(CAST(g.points_earned AS DECIMAL(10,2)) / CAST(g.total_points AS DECIMAL(10,2)) * 100) as percentage,
                   g.created_at
            FROM grades g
            INNER JOIN assignments a ON g.assignment_id = a.assignment_id
            INNER JOIN courses c ON g.course_id = c.course_id
            WHERE g.user_id = ?
            ORDER BY g.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $assignment_grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Grade page error for user $user_id: " . $e->getMessage());
    }
}

function getLetterGrade($percentage) {
    if ($percentage >= 90) return "A";
    if ($percentage >= 80) return "B";
    if ($percentage >= 70) return "C";
    if ($percentage >= 60) return "D";
    return "F";
}

function getGradeColor($percentage) {
    if ($percentage >= 90) return "#4CAF50";
    if ($percentage >= 80) return "#8BC34A";
    if ($percentage >= 70) return "#FFC107";
    if ($percentage >= 60) return "#FF9800";
    return "#F44336";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Grades - Student Portal</title>
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
            margin: 30px 0 15px 0;
            border-bottom: 1px solid rgba(255, 215, 0, 0.3);
            padding-bottom: 10px;
        }

        .gpa-box {
            background: rgba(0, 0, 0, 0.3);
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
            border: 2px solid #ffd700;
        }

        .gpa-label {
            font-size: 1.2em;
            color: #aaa;
            margin-bottom: 10px;
        }

        .gpa-value {
            font-size: 3em;
            color: #ffd700;
            font-weight: bold;
        }

        .course-grade-card {
            background: rgba(0, 0, 0, 0.2);
            padding: 20px;
            border-radius: 8px;
            margin: 15px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .course-grade-card h4 {
            color: #ffd700;
            margin: 0 0 5px 0;
        }

        .course-grade-info {
            flex: 1;
        }

        .course-grade-code {
            color: #aaa;
            font-size: 0.9em;
        }

        .course-grade-details {
            color: #bbb;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .grade-badge {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 80px;
            padding: 15px;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.3);
            border: 2px solid #666;
        }

        .grade-badge .percentage {
            font-size: 1.8em;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .grade-badge .letter {
            font-size: 1.2em;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            overflow: hidden;
        }

        th {
            background: rgba(0, 0, 0, 0.4);
            padding: 15px;
            text-align: left;
            color: #ffd700;
            font-weight: bold;
            border-bottom: 2px solid rgba(255, 215, 0, 0.3);
        }

        td {
            padding: 15px;
            border-bottom: 1px solid rgba(255, 215, 0, 0.1);
        }

        tr:hover {
            background: rgba(0, 0, 0, 0.3);
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

        .empty {
            text-align: center;
            padding: 20px;
            color: #aaa;
            font-style: italic;
        }

        .progress-bar {
            width: 100%;
            height: 20px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
        }

        .progress-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8em;
            font-weight: bold;
        }

        /* Grade Sheet Styles */
        .grade-sheet-container {
            background: rgba(0, 0, 0, 0.2);
            padding: 30px;
            border-radius: 12px;
            margin: 30px 0;
            border: 1px solid rgba(255, 215, 0, 0.2);
        }

        .student-info-header {
            background: rgba(255, 215, 0, 0.1);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border: 1px solid rgba(255, 215, 0, 0.3);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-item {
            flex: 1;
            min-width: 200px;
            padding: 8px 0;
            color: #ddd;
        }

        .info-item strong {
            color: #ffd700;
        }

        .grade-sheet-table-container {
            overflow-x: auto;
        }

        .grade-sheet-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            overflow: hidden;
            margin-top: 20px;
        }

        .grade-sheet-table th {
            background: rgba(255, 215, 0, 0.8);
            color: #000;
            padding: 15px 12px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid rgba(255, 215, 0, 0.3);
        }

        .grade-sheet-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 215, 0, 0.1);
            color: #ddd;
        }

        .grade-sheet-table tr:hover {
            background: rgba(255, 215, 0, 0.05);
        }

        .grade-sheet-table tfoot tr {
            background: rgba(255, 215, 0, 0.1);
        }

        .grade-sheet-table tfoot td {
            font-weight: bold;
            color: #ffd700;
            border-top: 2px solid rgba(255, 215, 0, 0.3);
        }

        .totals-row td {
            text-align: center;
            font-size: 1.1em;
        }

        .totals-row td:first-child {
            text-align: left;
        }

        .totals-row td:last-child {
            text-align: right;
        }
</head>
<body>
    <header>
        <h1>African Methodist Episcopal Zion University Nimba Extension</h1>
    </header>

    <nav class="nav-tabs">
        <a href="<?php echo $dashboardLink; ?>">📊 Dashboard</a>
        <a href="view_courses.php">📚 Courses</a>
        <a href="view_assignments.php">📝 Assignments</a>
        <a href="view_grades.php" class="active">📊 Grades</a>
        <a href="view_resources.php">📖 Resources</a>
        <a href="view_messages.php">💬 Messages</a>
        <a href="chat_room.php">💬 Chat</a>
        <a href="view_profile.php">👤 Profile</a>
        <a href="logout.php">🚪 Logout</a>
    </nav>

    <main>
        <a href="<?php echo $dashboardLink; ?>" class="back-link">← Back to Dashboard</a>
        <h2>📊 My Grades</h2>

        <?php if (!$isInstructor): ?>
        <!-- Student Grade Sheet -->
        <div class="grade-sheet-container">
            <h3>📋 Academic Grade Sheet</h3>
            
            <!-- Debug Info -->
            <div style="background: rgba(100,200,100,0.2); padding: 10px; margin: 10px 0; border-radius: 5px; font-size: 0.9em;">
                <strong>Debug:</strong> User ID: <?php echo htmlspecialchars($user_id); ?> | 
                Grades Found: <?php echo count($grade_details); ?> | 
                Student Info: <?php echo htmlspecialchars(json_encode($student_info)); ?>
            </div>
            
            <!-- Student Information Header -->
            <div class="student-info-header">
                <div class="info-row">
                    <div class="info-item"><strong>Student Name:</strong> <?php echo htmlspecialchars(($student_info['first_name'] ?? '') . ' ' . ($student_info['last_name'] ?? '')); ?></div>
                    <div class="info-item"><strong>Student ID:</strong> <?php echo htmlspecialchars($student_info['student_id'] ?? 'N/A'); ?></div>
                    <div class="info-item"><strong>Sex:</strong> <?php echo htmlspecialchars(ucfirst($student_info['gender'] ?? 'N/A')); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-item"><strong>Major:</strong> <?php echo htmlspecialchars($student_info['major'] ?? 'N/A'); ?></div>
                    <div class="info-item"><strong>Program:</strong> <?php echo htmlspecialchars(ucfirst($student_info['program'] ?? 'undergraduate')); ?></div>
                    <div class="info-item"><strong>Department:</strong> <?php echo htmlspecialchars($student_info['department'] ?? 'N/A'); ?></div>
                </div>
            </div>

            <!-- Grade Sheet Table -->
            <?php if (count($grade_details) > 0): ?>
            <div class="grade-sheet-table-container">
                <table class="grade-sheet-table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Course Title</th>
                            <th>Credit</th>
                            <th>Points</th>
                            <th>Grade</th>
                            <th>Instructor Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grade_details as $grade): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($grade['course_code']); ?></td>
                            <td><?php echo htmlspecialchars($grade['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($grade['credits']); ?></td>
                            <td><?php echo htmlspecialchars($grade['points_earned'] . '/' . $grade['total_points']); ?></td>
                            <td><?php echo htmlspecialchars($grade['grade_letter'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($grade['instructor']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="totals-row">
                            <td colspan="2"><strong>TOTALS</strong></td>
                            <td><strong><?php echo $total_credits; ?></strong></td>
                            <td><strong><?php echo $total_points; ?></strong></td>
                            <td colspan="2"><strong>GPA: <?php echo $gpa; ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php else: ?>
            <div class="empty">No grades available yet.</div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="gpa-box">
            <div class="gpa-label">Overall GPA</div>
            <div class="gpa-value"><?php echo $gpa; ?></div>
        </div>

        <h3>Grades by Course</h3>
        <?php if (count($course_grades) > 0): ?>
            <?php foreach ($course_grades as $course): ?>
                <?php 
                    $percentage = $course["course_percentage"] ? round($course["course_percentage"]) : 0;
                    $letter = getLetterGrade($percentage);
                    $color = getGradeColor($percentage);
                ?>
                <div class="course-grade-card">
                    <div class="course-grade-info">
                        <h4><?php echo htmlspecialchars($course["course_name"]); ?></h4>
                        <div class="course-grade-code"><?php echo htmlspecialchars($course["course_code"]); ?></div>
                        <div class="course-grade-details">
                            <?php echo $course["total_points_earned"] ?? "0"; ?> / <?php echo $course["total_points_possible"] ?? "0"; ?> points
                            (<?php echo $course["grade_count"]; ?> assignments)
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%; background: <?php echo $color; ?>;">
                                <?php echo $percentage; ?>%
                            </div>
                        </div>
                    </div>
                    <div class="grade-badge" style="border-color: <?php echo $color; ?>;">
                        <div class="percentage" style="color: <?php echo $color; ?>;"><?php echo $percentage; ?>%</div>
                        <div class="letter" style="color: <?php echo $color; ?>;"><?php echo $letter; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty">No course grades available yet.</div>
        <?php endif; ?>

        <h3>All Assignment Grades</h3>
        <?php if (count($assignment_grades) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Assignment</th>
                        <th>Course</th>
                        <th>Points</th>
                        <th>Percentage</th>
                        <th>Grade</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignment_grades as $grade): ?>
                        <?php 
                            $percentage = round(($grade["points_earned"] / $grade["total_points"]) * 100);
                            $letter = getLetterGrade($percentage);
                            $color = getGradeColor($percentage);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($grade["title"]); ?></td>
                            <td><?php echo htmlspecialchars($grade["course_name"]); ?></td>
                            <td><?php echo $grade["points_earned"]; ?> / <?php echo $grade["total_points"]; ?></td>
                            <td><?php echo $percentage; ?>%</td>
                            <td><span style="color: <?php echo $color; ?>; font-weight: bold;"><?php echo $letter; ?></span></td>
                            <td><?php echo date("F j, Y", strtotime($grade["created_at"])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty">No assignment grades available yet.</div>
        <?php endif; ?>
    </main>
</body>
</html>
