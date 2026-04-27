<?php
require_once "includes/admin_helpers.php";

adminEnsureSupportTables($conn);
adminRequireUser($conn);

$export = $_GET["export"] ?? "";

if ($export !== "") {
    if ($export === "users") {
        $stmt = $conn->query("
            SELECT u.id, u.username, u.email, u.user_type, si.student_id, si.department, si.status, u.created_at
            FROM users u
            LEFT JOIN student_info si ON u.id = si.user_id
            ORDER BY u.username
        ");
        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[] = [
                $row["id"],
                $row["username"],
                $row["email"],
                $row["user_type"],
                $row["student_id"],
                $row["department"],
                $row["status"],
                $row["created_at"],
            ];
        }
        adminExportCsv("users-report.csv", ["ID", "Username", "Email", "User Type", "Student ID", "Department", "Status", "Created At"], $rows);
    }

    if ($export === "courses") {
        $stmt = $conn->query("
            SELECT
                c.course_code,
                c.course_name,
                c.instructor,
                c.credits,
                c.semester,
                COUNT(DISTINCT sc.user_id) AS enrolled_students,
                COUNT(DISTINCT a.assignment_id) AS assignment_count
            FROM courses c
            LEFT JOIN student_courses sc ON c.course_id = sc.course_id
            LEFT JOIN assignments a ON c.course_id = a.course_id
            GROUP BY c.course_id, c.course_code, c.course_name, c.instructor, c.credits, c.semester
            ORDER BY c.course_name
        ");
        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[] = [
                $row["course_code"],
                $row["course_name"],
                $row["instructor"],
                $row["credits"],
                $row["semester"],
                $row["enrolled_students"],
                $row["assignment_count"],
            ];
        }
        adminExportCsv("courses-report.csv", ["Course Code", "Course Name", "Instructor", "Credits", "Semester", "Enrolled Students", "Assignments"], $rows);
    }

    if ($export === "contacts") {
        $stmt = $conn->query("
            SELECT id, name, username, email, subject, status, submitted_at
            FROM contacts
            ORDER BY submitted_at DESC
        ");
        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[] = [
                $row["id"],
                $row["name"],
                $row["username"],
                $row["email"],
                $row["subject"],
                $row["status"],
                $row["submitted_at"],
            ];
        }
        adminExportCsv("contact-submissions.csv", ["ID", "Name", "Username", "Email", "Subject", "Status", "Submitted At"], $rows);
    }

    if ($export === "announcements") {
        $stmt = $conn->query("
            SELECT title, audience, status, published_at, created_at
            FROM announcements
            ORDER BY updated_at DESC
        ");
        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[] = [
                $row["title"],
                $row["audience"],
                $row["status"],
                $row["published_at"],
                $row["created_at"],
            ];
        }
        adminExportCsv("announcements-report.csv", ["Title", "Audience", "Status", "Published At", "Created At"], $rows);
    }
}

$summary = [
    "total_users" => adminCount($conn, "SELECT COUNT(*) FROM users"),
    "total_students" => adminCount($conn, "SELECT COUNT(*) FROM users WHERE user_type = 'student'"),
    "total_instructors" => adminCount($conn, "SELECT COUNT(*) FROM users WHERE user_type = 'instructor'"),
    "total_courses" => adminCount($conn, "SELECT COUNT(*) FROM courses"),
    "total_enrollments" => adminCount($conn, "SELECT COUNT(*) FROM student_courses"),
    "total_contacts" => adminCount($conn, "SELECT COUNT(*) FROM contacts"),
    "published_announcements" => adminCount($conn, "SELECT COUNT(*) FROM announcements WHERE status = 'published'"),
];

$userBreakdownStmt = $conn->query("
    SELECT user_type, COUNT(*) AS total
    FROM users
    GROUP BY user_type
    ORDER BY total DESC
");
$userBreakdown = $userBreakdownStmt->fetchAll(PDO::FETCH_ASSOC);

$courseDemandStmt = $conn->query("
    SELECT c.course_code, c.course_name, COUNT(sc.user_id) AS enrolled_students
    FROM courses c
    LEFT JOIN student_courses sc ON c.course_id = sc.course_id
    GROUP BY c.course_id, c.course_code, c.course_name
    ORDER BY enrolled_students DESC, c.course_name
    LIMIT 8
");
$courseDemand = $courseDemandStmt->fetchAll(PDO::FETCH_ASSOC);

$contactStatusStmt = $conn->query("
    SELECT status, COUNT(*) AS total
    FROM contacts
    GROUP BY status
    ORDER BY total DESC
");
$contactStatus = $contactStatusStmt->fetchAll(PDO::FETCH_ASSOC);

$recentUsersStmt = $conn->query("
    SELECT username, email, user_type, created_at
    FROM users
    ORDER BY id DESC
    LIMIT 8
");
$recentUsers = $recentUsersStmt->fetchAll(PDO::FETCH_ASSOC);

adminRenderHeader("Reports", "reports");
?>
<h2 class="page-title">Reports</h2>
<p class="page-subtitle">Generate system reports and analytics from the live portal data.</p>

<div class="panel">
    <div class="section-actions">
        <h3>Quick Exports</h3>
        <div class="actions">
            <a class="button" href="admin_reports.php?export=users">Users CSV</a>
            <a class="button" href="admin_reports.php?export=courses">Courses CSV</a>
            <a class="button" href="admin_reports.php?export=contacts">Contacts CSV</a>
            <a class="button" href="admin_reports.php?export=announcements">Announcements CSV</a>
        </div>
    </div>
</div>

<div class="grid grid-3">
    <div class="stat-card">
        <div class="number"><?php echo $summary["total_users"]; ?></div>
        <div class="label">Total Users</div>
    </div>
    <div class="stat-card">
        <div class="number"><?php echo $summary["total_students"]; ?></div>
        <div class="label">Students</div>
    </div>
    <div class="stat-card">
        <div class="number"><?php echo $summary["total_instructors"]; ?></div>
        <div class="label">Instructors</div>
    </div>
    <div class="stat-card">
        <div class="number"><?php echo $summary["total_courses"]; ?></div>
        <div class="label">Courses</div>
    </div>
    <div class="stat-card">
        <div class="number"><?php echo $summary["total_enrollments"]; ?></div>
        <div class="label">Enrollments</div>
    </div>
    <div class="stat-card">
        <div class="number"><?php echo $summary["total_contacts"]; ?></div>
        <div class="label">Contact Submissions</div>
    </div>
</div>

<div class="grid grid-2">
    <div class="panel">
        <h3>User Breakdown</h3>
        <?php if ($userBreakdown): ?>
            <table>
                <thead>
                    <tr>
                        <th>User Type</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userBreakdown as $row): ?>
                        <tr>
                            <td><?php echo adminH(ucfirst($row["user_type"])); ?></td>
                            <td><?php echo (int) $row["total"]; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">No user data available yet.</div>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h3>Contact Status Overview</h3>
        <?php if ($contactStatus): ?>
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contactStatus as $row): ?>
                        <tr>
                            <td><span class="badge badge-<?php echo adminH($row["status"]); ?>"><?php echo adminH($row["status"]); ?></span></td>
                            <td><?php echo (int) $row["total"]; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">No contact submissions available yet.</div>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-2">
    <div class="panel">
        <h3>Most Enrolled Courses</h3>
        <?php if ($courseDemand): ?>
            <table>
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Enrolled Students</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courseDemand as $course): ?>
                        <tr>
                            <td><?php echo adminH($course["course_code"] . " - " . $course["course_name"]); ?></td>
                            <td><?php echo (int) $course["enrolled_students"]; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">No course analytics available yet.</div>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h3>Recent Accounts</h3>
        <?php if ($recentUsers): ?>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentUsers as $user): ?>
                        <tr>
                            <td><?php echo adminH($user["username"]); ?><br><span class="muted"><?php echo adminH($user["email"]); ?></span></td>
                            <td><span class="badge badge-<?php echo adminH($user["user_type"]); ?>"><?php echo adminH($user["user_type"]); ?></span></td>
                            <td><?php echo adminH($user["created_at"] ? date("M j, Y g:i A", strtotime($user["created_at"])) : "Unknown"); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">No recent account activity found.</div>
        <?php endif; ?>
    </div>
</div>

<div class="panel">
    <h3>Announcement Reach</h3>
    <p class="muted">
        Published announcements: <?php echo $summary["published_announcements"]; ?>.
        Use the Announcements page to create drafts, publish updates, and archive older notices.
    </p>
</div>
<?php adminRenderFooter(); ?>
