<?php
require_once "includes/admin_helpers.php";

adminEnsureSupportTables($conn);
$admin = adminRequireUser($conn);

$stats = [
    "students" => adminCount($conn, "SELECT COUNT(*) FROM users WHERE user_type = 'student'"),
    "admins" => adminCount($conn, "SELECT COUNT(*) FROM users WHERE user_type = 'admin'"),
    "instructors" => adminCount($conn, "SELECT COUNT(*) FROM users WHERE user_type = 'instructor'"),
    "courses" => adminCount($conn, "SELECT COUNT(*) FROM courses"),
    "contacts" => adminCount($conn, "SELECT COUNT(*) FROM contacts WHERE status != 'resolved'"),
    "announcements" => adminCount($conn, "SELECT COUNT(*) FROM announcements WHERE status = 'published'"),
];

$recentContactsStmt = $conn->query("
    SELECT id, name, email, subject, status, submitted_at
    FROM contacts
    ORDER BY submitted_at DESC
    LIMIT 5
");
$recentContacts = $recentContactsStmt->fetchAll(PDO::FETCH_ASSOC);

$recentAnnouncementsStmt = $conn->query("
    SELECT announcement_id, title, status, audience, updated_at
    FROM announcements
    ORDER BY updated_at DESC
    LIMIT 5
");
$recentAnnouncements = $recentAnnouncementsStmt->fetchAll(PDO::FETCH_ASSOC);

adminRenderHeader("Admin Portal", "dashboard");
?>
<h2 class="page-title">Admin Portal</h2>
<p class="page-subtitle">Welcome back, <?php echo adminH($admin["username"]); ?>. The controls below are now connected to working admin pages.</p>
<div style="margin-bottom: 20px;">
    <button type="button" class="button" onclick="window.history.back();">← Back</button>
</div>

<div class="panel">
    <div class="grid grid-3">
        <div class="stat-card">
            <div class="number"><?php echo $stats["students"]; ?></div>
            <div class="label">Students</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $stats["admins"]; ?></div>
            <div class="label">Admins</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $stats["instructors"]; ?></div>
            <div class="label">Instructors</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $stats["courses"]; ?></div>
            <div class="label">Courses</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $stats["contacts"]; ?></div>
            <div class="label">Open Contact Messages</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $stats["announcements"]; ?></div>
            <div class="label">Published Announcements</div>
        </div>
    </div>
</div>

<div class="grid grid-3">
    <div class="panel">
        <h3>Manage Users</h3>
        <p class="muted">View, edit, and delete user accounts. Student profile details are editable from the same screen.</p>
        <a class="button" href="manage_users.php">Open User Manager</a>
        <a class="button" href="register.php" style="margin-top: 10px; display: inline-block;">Register New User</a>
    </div>

    <div class="panel">
        <h3>View Messages</h3>
        <p class="muted">Review contact form submissions, open message details, and mark items as resolved.</p>
        <a class="button" href="admin_messages.php">Review Contact Messages</a>
    </div>

    <div class="panel">
        <h3>Reports</h3>
        <p class="muted">Generate live analytics and export users, courses, and contact reports as CSV files.</p>
        <a class="button" href="admin_reports.php">Generate Reports</a>
    </div>

    <div class="panel">
        <h3>Settings</h3>
        <p class="muted">Configure portal name, support details, semester labels, registration state, and notices.</p>
        <a class="button" href="admin_settings.php">Open Settings</a>
    </div>

    <div class="panel">
        <h3>Manage Courses</h3>
        <p class="muted">Create new courses, edit existing ones, and remove courses cleanly with dependent data.</p>
        <a class="button" href="admin_courses.php">Manage Courses</a>
    </div>

    <div class="panel">
        <h3>Announcements</h3>
        <p class="muted">Create drafts, publish campus-wide announcements, and archive older notices.</p>
        <a class="button" href="admin_announcements.php">Create Announcement</a>
    </div>
</div>

<div class="grid grid-2">
    <div class="panel">
        <div class="section-actions">
            <h3>Recent Contact Messages</h3>
            <a class="button-link" href="admin_messages.php">View all</a>
        </div>
        <?php if ($recentContacts): ?>
            <table>
                <thead>
                    <tr>
                        <th>Sender</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentContacts as $contact): ?>
                        <tr>
                            <td><?php echo adminH($contact["name"]); ?><br><span class="muted"><?php echo adminH($contact["email"]); ?></span></td>
                            <td><?php echo adminH($contact["subject"] ?: "No subject"); ?></td>
                            <td><span class="badge badge-<?php echo adminH($contact["status"]); ?>"><?php echo adminH($contact["status"]); ?></span></td>
                            <td><?php echo adminH(date("M j, Y g:i A", strtotime($contact["submitted_at"]))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">No contact submissions yet.</div>
        <?php endif; ?>
    </div>

    <div class="panel">
        <div class="section-actions">
            <h3>Recent Announcements</h3>
            <a class="button-link" href="admin_announcements.php">Manage announcements</a>
        </div>
        <?php if ($recentAnnouncements): ?>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Audience</th>
                        <th>Status</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentAnnouncements as $announcement): ?>
                        <tr>
                            <td><?php echo adminH($announcement["title"]); ?></td>
                            <td><?php echo adminH($announcement["audience"]); ?></td>
                            <td><span class="badge badge-<?php echo adminH($announcement["status"]); ?>"><?php echo adminH($announcement["status"]); ?></span></td>
                            <td><?php echo adminH(date("M j, Y g:i A", strtotime($announcement["updated_at"]))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">No announcements have been created yet.</div>
        <?php endif; ?>
    </div>
</div>
<?php adminRenderFooter(); ?>
