<?php
require_once "includes/admin_helpers.php";

adminEnsureSupportTables($conn);
adminRequireUser($conn);

$message = "";
$error = "";
$editId = isset($_GET["edit"]) ? (int) $_GET["edit"] : 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    try {
        if ($action === "create_course") {
            $courseCode = trim($_POST["course_code"] ?? "");
            $courseName = trim($_POST["course_name"] ?? "");
            $instructor = trim($_POST["instructor"] ?? "");
            $credits = (int) ($_POST["credits"] ?? 3);
            $semester = trim($_POST["semester"] ?? "");
            $description = trim($_POST["description"] ?? "");

            if ($courseCode === "" || $courseName === "") {
                throw new RuntimeException("Course code and course name are required.");
            }

            $stmt = $conn->prepare("
                INSERT INTO courses (course_code, course_name, instructor, credits, semester, description)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$courseCode, $courseName, $instructor, $credits, $semester, $description]);
            $message = "Course created successfully.";
        } elseif ($action === "update_course") {
            $courseId = (int) ($_POST["course_id"] ?? 0);
            $courseCode = trim($_POST["course_code"] ?? "");
            $courseName = trim($_POST["course_name"] ?? "");
            $instructor = trim($_POST["instructor"] ?? "");
            $credits = (int) ($_POST["credits"] ?? 3);
            $semester = trim($_POST["semester"] ?? "");
            $description = trim($_POST["description"] ?? "");

            if ($courseId <= 0 || $courseCode === "" || $courseName === "") {
                throw new RuntimeException("Course ID, course code, and course name are required.");
            }

            $stmt = $conn->prepare("
                UPDATE courses
                SET course_code = ?, course_name = ?, instructor = ?, credits = ?, semester = ?, description = ?
                WHERE course_id = ?
            ");
            $stmt->execute([$courseCode, $courseName, $instructor, $credits, $semester, $description, $courseId]);
            $message = "Course updated successfully.";
        } elseif ($action === "delete_course") {
            $courseId = (int) ($_POST["course_id"] ?? 0);
            if ($courseId <= 0) {
                throw new RuntimeException("Invalid course selected.");
            }

            $conn->beginTransaction();

            $assignmentIdStmt = $conn->prepare("SELECT assignment_id FROM assignments WHERE course_id = ?");
            $assignmentIdStmt->execute([$courseId]);
            $assignmentIds = $assignmentIdStmt->fetchAll(PDO::FETCH_COLUMN);

            if ($assignmentIds) {
                $placeholders = implode(",", array_fill(0, count($assignmentIds), "?"));

                $deleteSubmissionStmt = $conn->prepare("DELETE FROM student_assignments WHERE assignment_id IN ($placeholders)");
                $deleteSubmissionStmt->execute($assignmentIds);

                $deleteAssignmentGradeStmt = $conn->prepare("DELETE FROM grades WHERE assignment_id IN ($placeholders)");
                $deleteAssignmentGradeStmt->execute($assignmentIds);
            }

            $stmt = $conn->prepare("DELETE FROM grades WHERE course_id = ?");
            $stmt->execute([$courseId]);

            $stmt = $conn->prepare("DELETE FROM resources WHERE course_id = ?");
            $stmt->execute([$courseId]);

            $stmt = $conn->prepare("DELETE FROM assignments WHERE course_id = ?");
            $stmt->execute([$courseId]);

            $stmt = $conn->prepare("DELETE FROM student_courses WHERE course_id = ?");
            $stmt->execute([$courseId]);

            if (adminTableExists($conn, "enrollments")) {
                $stmt = $conn->prepare("DELETE FROM enrollments WHERE course_id = ?");
                $stmt->execute([$courseId]);
            }

            $stmt = $conn->prepare("DELETE FROM courses WHERE course_id = ?");
            $stmt->execute([$courseId]);

            $conn->commit();
            $message = "Course and dependent records removed successfully.";
            if ($editId === $courseId) {
                $editId = 0;
            }
        }
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $error = $e->getMessage();
    }
}

$coursesStmt = $conn->query("
    SELECT
        c.course_id,
        c.course_code,
        c.course_name,
        c.instructor,
        c.credits,
        c.semester,
        c.description,
        COUNT(DISTINCT sc.user_id) AS enrolled_students,
        COUNT(DISTINCT a.assignment_id) AS assignment_count
    FROM courses c
    LEFT JOIN student_courses sc ON c.course_id = sc.course_id
    LEFT JOIN assignments a ON c.course_id = a.course_id
    GROUP BY c.course_id, c.course_code, c.course_name, c.instructor, c.credits, c.semester, c.description
    ORDER BY c.course_name
");
$courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);

$selectedCourse = [
    "course_id" => 0,
    "course_code" => "",
    "course_name" => "",
    "instructor" => "",
    "credits" => 3,
    "semester" => "",
    "description" => "",
];

if ($editId > 0) {
    $selectedStmt = $conn->prepare("
        SELECT course_id, course_code, course_name, instructor, credits, semester, description
        FROM courses
        WHERE course_id = ?
    ");
    $selectedStmt->execute([$editId]);
    $selectedCourse = $selectedStmt->fetch(PDO::FETCH_ASSOC) ?: $selectedCourse;
}

adminRenderHeader("Manage Courses", "courses");
?>
<h2 class="page-title">Manage Courses</h2>
<p class="page-subtitle">Add, edit, and remove courses while keeping enrollments, assignments, and related records in sync.</p>

<?php if ($message !== ""): ?>
    <div class="notice"><?php echo adminH($message); ?></div>
<?php endif; ?>

<?php if ($error !== ""): ?>
    <div class="error"><?php echo adminH($error); ?></div>
<?php endif; ?>

<div class="grid grid-2">
    <div class="panel">
        <div class="section-actions">
            <h3><?php echo $selectedCourse["course_id"] ? "Edit Course" : "Create Course"; ?></h3>
            <?php if ($selectedCourse["course_id"]): ?>
                <a class="button button-secondary" href="admin_courses.php">Switch to Create</a>
            <?php endif; ?>
        </div>

        <form method="post">
            <input type="hidden" name="action" value="<?php echo $selectedCourse["course_id"] ? "update_course" : "create_course"; ?>">
            <?php if ($selectedCourse["course_id"]): ?>
                <input type="hidden" name="course_id" value="<?php echo (int) $selectedCourse["course_id"]; ?>">
            <?php endif; ?>

            <div class="grid grid-2">
                <div class="form-group">
                    <label for="course_code">Course Code</label>
                    <input type="text" id="course_code" name="course_code" value="<?php echo adminH($selectedCourse["course_code"]); ?>" required>
                </div>
                <div class="form-group">
                    <label for="course_name">Course Name</label>
                    <input type="text" id="course_name" name="course_name" value="<?php echo adminH($selectedCourse["course_name"]); ?>" required>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label for="instructor">Instructor</label>
                    <input type="text" id="instructor" name="instructor" value="<?php echo adminH($selectedCourse["instructor"]); ?>">
                </div>
                <div class="form-group">
                    <label for="credits">Credits</label>
                    <input type="number" id="credits" name="credits" min="1" max="10" value="<?php echo (int) $selectedCourse["credits"]; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="semester">Semester</label>
                <input type="text" id="semester" name="semester" value="<?php echo adminH($selectedCourse["semester"]); ?>">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?php echo adminH($selectedCourse["description"]); ?></textarea>
            </div>

            <button type="submit"><?php echo $selectedCourse["course_id"] ? "Save Course" : "Create Course"; ?></button>
        </form>
    </div>

    <div class="panel">
        <div class="section-actions">
            <h3>Course Catalog</h3>
            <span class="muted"><?php echo count($courses); ?> courses</span>
        </div>

        <?php if ($courses): ?>
            <table>
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Instructor</th>
                        <th>Load</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td>
                                <strong><?php echo adminH($course["course_code"]); ?></strong><br>
                                <?php echo adminH($course["course_name"]); ?><br>
                                <span class="muted"><?php echo adminH($course["semester"] ?: "No semester set"); ?></span>
                            </td>
                            <td>
                                <?php echo adminH($course["instructor"] ?: "Not assigned"); ?><br>
                                <span class="muted"><?php echo (int) $course["credits"]; ?> credits</span>
                            </td>
                            <td>
                                Students: <?php echo (int) $course["enrolled_students"]; ?><br>
                                Assignments: <?php echo (int) $course["assignment_count"]; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <a class="button button-secondary" href="admin_courses.php?edit=<?php echo (int) $course["course_id"]; ?>">Edit</a>
                                    <form method="post" class="inline-form" onsubmit="return confirm('Delete this course and all related records?');">
                                        <input type="hidden" name="action" value="delete_course">
                                        <input type="hidden" name="course_id" value="<?php echo (int) $course["course_id"]; ?>">
                                        <button type="submit" class="button button-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">No courses have been created yet.</div>
        <?php endif; ?>
    </div>
</div>
<?php adminRenderFooter(); ?>
