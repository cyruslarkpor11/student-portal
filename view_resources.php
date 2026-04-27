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

if ($isInstructor && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["upload_resource"])) {
    $course_id = $_POST["course_id"];
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $resource_type = $_POST["resource_type"] ?? "document";
    $file_path = null;

    if (isset($_FILES["resource_file"]) && $_FILES["resource_file"]["error"] == 0) {
        $upload_dir = "resources/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $file_name = time() . "_" . basename($_FILES["resource_file"]["name"]);
        $file_path = $upload_dir . $file_name;

        if (!move_uploaded_file($_FILES["resource_file"]["tmp_name"], $file_path)) {
            $file_path = null;
        }
    }

    if (!empty($title) && !empty($course_id)) {
        try {
            $stmt = $conn->prepare("INSERT INTO resources (course_id, title, description, file_path, resource_type, uploaded_date) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$course_id, $title, $description, $file_path, $resource_type]);
            $message = "Resource posted successfully.";
        } catch (PDOException $e) {
            $message = "Error posting resource: " . $e->getMessage();
        }
    } else {
        $message = "Please provide a course and title for the resource.";
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
} else {
    // Get enrolled courses
    $stmt = $conn->prepare("
        SELECT DISTINCT c.course_id, c.course_name FROM courses c
        INNER JOIN student_courses sc ON c.course_id = sc.course_id
        WHERE sc.user_id = ?
        ORDER BY c.course_name
    ");
    $stmt->execute([$user_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get selected course (if any)
$selected_course = $_GET["course"] ?? ($courses[0]["course_id"] ?? null);

// Get resources for selected course or all courses
if ($selected_course) {
    if ($isInstructor) {
        $stmt = $conn->prepare("
            SELECT r.* FROM resources r
            INNER JOIN courses c ON r.course_id = c.course_id
            WHERE c.instructor = ? AND c.course_id = ?
            ORDER BY r.uploaded_date DESC
        ");
        $stmt->execute([$instructor_name, $selected_course]);
    } else {
        $stmt = $conn->prepare("
            SELECT r.* FROM resources r
            INNER JOIN courses c ON r.course_id = c.course_id
            INNER JOIN student_courses sc ON c.course_id = sc.course_id
            WHERE sc.user_id = ? AND c.course_id = ?
            ORDER BY r.uploaded_date DESC
        ");
        $stmt->execute([$user_id, $selected_course]);
    }
} else {
    if ($isInstructor) {
        $stmt = $conn->prepare("
            SELECT r.*, c.course_name FROM resources r
            INNER JOIN courses c ON r.course_id = c.course_id
            WHERE c.instructor = ?
            ORDER BY r.uploaded_date DESC
        ");
        $stmt->execute([$instructor_name]);
    } else {
        $stmt = $conn->prepare("
            SELECT r.*, c.course_name FROM resources r
            INNER JOIN courses c ON r.course_id = c.course_id
            INNER JOIN student_courses sc ON c.course_id = sc.course_id
            WHERE sc.user_id = ?
            ORDER BY r.uploaded_date DESC
        ");
        $stmt->execute([$user_id]);
    }
}

$resources = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current course name
$current_course_name = "All Courses";
if ($selected_course) {
    foreach ($courses as $course) {
        if ($course["course_id"] == $selected_course) {
            $current_course_name = $course["course_name"];
            break;
        }
    }
}

function getResourceIcon($type) {
    $icons = [
        'document' => '📄',
        'video' => '🎥',
        'link' => '🔗',
        'other' => '📎'
    ];
    return $icons[$type] ?? '📎';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Resources - Student Portal</title>
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

        .filter-section {
            background: rgba(0, 0, 0, 0.2);
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .filter-section label {
            color: #ffd700;
            font-weight: bold;
            margin-right: 10px;
        }

        select {
            padding: 8px 15px;
            background: rgba(0, 0, 0, 0.3);
            color: white;
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        select option {
            background: #333;
            color: white;
        }

        .resource-card {
            background: rgba(0, 0, 0, 0.2);
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #FF9800;
            margin: 15px 0;
            transition: transform 0.3s;
            display: flex;
            align-items: flex-start;
            gap: 20px;
        }

        .resource-card:hover {
            transform: translateX(5px);
            background: rgba(0, 0, 0, 0.3);
        }

        .resource-icon {
            font-size: 2.5em;
            min-width: 60px;
            text-align: center;
        }

        .resource-content {
            flex: 1;
        }

        .resource-card h3 {
            color: #ffd700;
            margin: 0 0 10px 0;
        }

        .resource-info {
            margin: 10px 0;
            line-height: 1.6;
        }

        .resource-info strong {
            color: #ffd700;
        }

        .resource-type {
            display: inline-block;
            padding: 5px 10px;
            background: rgba(255, 152, 0, 0.3);
            border: 1px solid rgba(255, 152, 0, 0.5);
            border-radius: 5px;
            font-size: 0.85em;
            color: #ffb74d;
            margin-top: 10px;
        }

        .download-link {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 15px;
            background: #FF9800;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background 0.3s;
        }

        .download-link:hover {
            background: #F57C00;
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
            padding: 40px;
            color: #aaa;
            font-style: italic;
        }

        .current-section {
            color: #ffd700;
            font-size: 0.9em;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 215, 0, 0.3);
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
        <a href="view_assignments.php">📝 Assignments</a>
        <a href="view_grades.php">📊 Grades</a>
        <a href="view_resources.php" class="active">📖 Resources</a>
        <a href="view_messages.php">💬 Messages</a>
        <a href="chat_room.php">💬 Chat</a>
        <a href="view_profile.php">👤 Profile</a>
        <a href="logout.php">🚪 Logout</a>
    </nav>

    <main>
        <a href="<?php echo $dashboardLink; ?>" class="back-link">← Back to Dashboard</a>
        <h2>📖 Course Resources</h2>

        <div class="filter-section">
            <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                <label for="course">Filter by Course:</label>
                <select name="course" id="course" onchange="this.form.submit();">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course["course_id"]; ?>" <?php echo ($selected_course == $course["course_id"]) ? "selected" : ""; ?>>
                            <?php echo htmlspecialchars($course["course_name"]); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <div class="current-section">
            Currently viewing: <strong><?php echo htmlspecialchars($current_course_name); ?></strong>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($isInstructor): ?>
            <div class="resource-card" style="border-left-color: #4CAF50; flex-direction: column; gap: 15px;">
                <h3>Post a New Resource</h3>
                <form method="POST" enctype="multipart/form-data" style="width: 100%;">
                    <div class="form-group" style="margin: 10px 0;">
                        <label for="course_id">Course</label>
                        <select name="course_id" id="course_id" required>
                            <option value="">-- Select Course --</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course["course_id"]; ?>"><?php echo htmlspecialchars($course["course_name"]); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 10px 0;">
                        <label for="title">Title</label>
                        <input type="text" name="title" id="title" placeholder="Resource title" required style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid rgba(255, 215, 0, 0.3); background: rgba(0,0,0,0.3); color: white;">
                    </div>
                    <div class="form-group" style="margin: 10px 0;">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" placeholder="Short resource description" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid rgba(255, 215, 0, 0.3); background: rgba(0,0,0,0.3); color: white;"></textarea>
                    </div>
                    <div class="form-group" style="margin: 10px 0;">
                        <label for="resource_type">Type</label>
                        <select name="resource_type" id="resource_type" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid rgba(255, 215, 0, 0.3); background: rgba(0,0,0,0.3); color: white;">
                            <option value="document">Document</option>
                            <option value="video">Video</option>
                            <option value="link">Link</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 10px 0;">
                        <label for="resource_file">Upload File (Optional)</label>
                        <input type="file" name="resource_file" id="resource_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.txt,.jpg,.png" style="width: 100%;">
                    </div>
                    <button type="submit" name="upload_resource">Upload Resource</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if (count($resources) > 0): ?>
            <?php foreach ($resources as $resource): ?>
                <div class="resource-card">
                    <div class="resource-icon">
                        <?php echo getResourceIcon($resource["resource_type"]); ?>
                    </div>
                    <div class="resource-content">
                        <h3><?php echo htmlspecialchars($resource["title"]); ?></h3>
                        <div class="resource-info">
                            <?php if (!$selected_course): ?>
                                <strong>Course:</strong> <?php echo htmlspecialchars($resource["course_name"]); ?><br>
                            <?php endif; ?>
                            <strong>Uploaded:</strong> <?php echo date("F j, Y g:i A", strtotime($resource["uploaded_date"])); ?><br>
                            <strong>Description:</strong> <?php echo htmlspecialchars($resource["description"]); ?>
                        </div>
                        <div>
                            <span class="resource-type"><?php echo ucfirst($resource["resource_type"]); ?></span>
                        </div>
                        <?php if ($resource["file_path"]): ?>
                            <a href="<?php echo htmlspecialchars($resource["file_path"]); ?>" class="download-link" download>
                                ⬇️ Download Resource
                            </a>
                        <?php elseif ($resource["resource_type"] == "link"): ?>
                            <a href="<?php echo htmlspecialchars($resource["file_path"]); ?>" class="download-link" target="_blank">
                                🔗 Open Link
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty">
                No resources available for <?php echo htmlspecialchars($current_course_name); ?> yet.
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
