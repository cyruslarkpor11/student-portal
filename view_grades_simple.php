<?php
session_start();
require "includes/db.php";

// Check authentication
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: index.php");
    exit();
}

if ($_SESSION["user_type"] !== "student") {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// Get student info
$stmt = $conn->prepare("SELECT student_id, first_name, last_name, gender, major, program, department FROM student_info WHERE user_id = ?");
$stmt->execute([$user_id]);
$student_info = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all grades for this student
$stmt = $conn->prepare("
    SELECT 
        c.course_code,
        c.course_name,
        c.credits,
        c.instructor,
        g.points_earned,
        g.total_points,
        g.grade_letter
    FROM grades g
    INNER JOIN courses c ON g.course_id = c.course_id
    WHERE g.user_id = ?
    ORDER BY c.course_name
");
$stmt->execute([$user_id]);
$grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_credits = 0;
$total_points_earned = 0;
$total_points_possible = 0;
$gpa_sum = 0;

foreach ($grades as $grade) {
    $total_credits += $grade['credits'];
    $total_points_earned += $grade['points_earned'];
    $total_points_possible += $grade['total_points'];
    
    // Calculate GPA (4.0 scale)
    $percentage = ($grade['points_earned'] / $grade['total_points']) * 100;
    if ($percentage >= 90) $gpa_points = 4.0;
    elseif ($percentage >= 80) $gpa_points = 3.0;
    elseif ($percentage >= 70) $gpa_points = 2.0;
    elseif ($percentage >= 60) $gpa_points = 1.0;
    else $gpa_points = 0.0;
    
    $gpa_sum += ($gpa_points * $grade['credits']);
}

$gpa = $total_credits > 0 ? round($gpa_sum / $total_credits, 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Sheet - Student Portal</title>
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
        .nav-tabs a:hover, .nav-tabs a.active {
            background: rgba(255, 215, 0, 0.1);
            border-bottom-color: #ffd700;
            color: #ffd700;
        }
        main {
            background: rgba(0, 0, 0, 0.3);
            max-width: 1100px;
            margin: 30px auto;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        }
        h2 {
            color: #ffd700;
            margin-bottom: 30px;
            font-size: 2em;
        }
        .student-header {
            background: rgba(255, 215, 0, 0.1);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 215, 0, 0.3);
        }
        .header-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 15px;
        }
        .header-item {
            flex: 1;
            min-width: 200px;
        }
        .header-item strong {
            color: #ffd700;
            display: block;
            margin-bottom: 5px;
        }
        .header-item span {
            color: #ddd;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        th {
            background: rgba(255, 215, 0, 0.8);
            color: #000;
            padding: 15px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(255, 215, 0, 0.1);
            color: #ddd;
        }
        tr:hover {
            background: rgba(255, 215, 0, 0.05);
        }
        tfoot tr {
            background: rgba(255, 215, 0, 0.1);
            font-weight: bold;
            color: #ffd700;
        }
        .no-grades {
            text-align: center;
            padding: 40px;
            color: #aaa;
            font-style: italic;
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
        <a href="view_grades_simple.php" class="active">📈 Grades</a>
        <a href="view_resources.php">📖 Resources</a>
        <a href="view_messages.php">💬 Messages</a>
        <a href="view_profile.php">👤 Profile</a>
        <a href="logout.php">🚪 Logout</a>
    </nav>

    <main>
        <a href="student_portal.php" class="back-link">← Back to Dashboard</a>
        <h2>📋 Academic Grade Sheet</h2>

        <?php if ($student_info): ?>
        <div class="student-header">
            <div class="header-row">
                <div class="header-item">
                    <strong>Student Name:</strong>
                    <span><?php echo htmlspecialchars(($student_info['first_name'] ?? 'N/A') . ' ' . ($student_info['last_name'] ?? '')); ?></span>
                </div>
                <div class="header-item">
                    <strong>Student ID:</strong>
                    <span><?php echo htmlspecialchars($student_info['student_id'] ?? 'N/A'); ?></span>
                </div>
                <div class="header-item">
                    <strong>Sex:</strong>
                    <span><?php echo htmlspecialchars(ucfirst($student_info['gender'] ?? 'N/A')); ?></span>
                </div>
            </div>
            <div class="header-row">
                <div class="header-item">
                    <strong>Major:</strong>
                    <span><?php echo htmlspecialchars($student_info['major'] ?? 'N/A'); ?></span>
                </div>
                <div class="header-item">
                    <strong>Program:</strong>
                    <span><?php echo htmlspecialchars(ucfirst($student_info['program'] ?? 'Undergraduate')); ?></span>
                </div>
                <div class="header-item">
                    <strong>Department:</strong>
                    <span><?php echo htmlspecialchars($student_info['department'] ?? 'N/A'); ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (count($grades) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Course Code</th>
                    <th>Course Title</th>
                    <th>Credit</th>
                    <th>Points</th>
                    <th>Grade</th>
                    <th>Instructor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grades as $grade): ?>
                <tr>
                    <td><?php echo htmlspecialchars($grade['course_code']); ?></td>
                    <td><?php echo htmlspecialchars($grade['course_name']); ?></td>
                    <td><?php echo htmlspecialchars($grade['credits']); ?></td>
                    <td><?php echo htmlspecialchars($grade['points_earned'] . '/' . $grade['total_points']); ?></td>
                    <td><strong><?php echo htmlspecialchars($grade['grade_letter']); ?></strong></td>
                    <td><?php echo htmlspecialchars($grade['instructor']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">TOTALS</td>
                    <td><?php echo $total_credits; ?></td>
                    <td><?php echo $total_points_earned . '/' . $total_points_possible; ?></td>
                    <td colspan="2">GPA: <strong><?php echo $gpa; ?></strong></td>
                </tr>
            </tfoot>
        </table>
        <?php else: ?>
        <div class="no-grades">
            <p>No grades available yet. Check back later!</p>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>
