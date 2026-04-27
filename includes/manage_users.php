<?php
require_once "includes/admin_helpers.php";

adminEnsureSupportTables($conn);
$admin = adminRequireUser($conn);

$message = "";
$error = "";
$editId = isset($_GET["edit"]) ? (int) $_GET["edit"] : 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    try {
        if ($action === "create_user") {
            $username = trim($_POST["username"] ?? "");
            $email = trim($_POST["email"] ?? "");
            $password = $_POST["password"] ?? "";
            $userType = $_POST["user_type"] ?? "student";
            $studentId = trim($_POST["student_id"] ?? "");
            $firstName = trim($_POST["first_name"] ?? "");
            $lastName = trim($_POST["last_name"] ?? "");
            $department = trim($_POST["department"] ?? "");
            $status = $_POST["status"] ?? "active";

            if ($username === "" || $email === "" || $password === "") {
                throw new RuntimeException("Username, email, and password are required.");
            }

            if (strlen($password) < 6) {
                throw new RuntimeException("Password must be at least 6 characters long.");
            }

            $stmt = $conn->prepare("INSERT INTO users (username, email, password, user_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $userType]);
            $userId = (int) $conn->lastInsertId();

            if ($userType === "student" && $studentId !== "") {
                $studentStmt = $conn->prepare("
                    INSERT INTO student_info (user_id, student_id, first_name, last_name, department, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $studentStmt->execute([$userId, $studentId, $firstName, $lastName, $department, $status]);
            }

            $message = "User account created successfully.";
        } elseif ($action === "update_user") {
            $userId = (int) ($_POST["user_id"] ?? 0);
            $username = trim($_POST["username"] ?? "");
            $email = trim($_POST["email"] ?? "");
            $userType = $_POST["user_type"] ?? "student";
            $password = $_POST["password"] ?? "";
            $studentId = trim($_POST["student_id"] ?? "");
            $firstName = trim($_POST["first_name"] ?? "");
            $lastName = trim($_POST["last_name"] ?? "");
            $department = trim($_POST["department"] ?? "");
            $status = $_POST["status"] ?? "active";

            if ($userId <= 0 || $username === "" || $email === "") {
                throw new RuntimeException("User ID, username, and email are required.");
            }

            if ($password !== "" && strlen($password) < 6) {
                throw new RuntimeException("If you enter a new password, it must be at least 6 characters long.");
            }

            $conn->beginTransaction();

            if ($password !== "") {
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, user_type = ?, password = ? WHERE id = ?");
                $stmt->execute([$username, $email, $userType, password_hash($password, PASSWORD_DEFAULT), $userId]);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, user_type = ? WHERE id = ?");
                $stmt->execute([$username, $email, $userType, $userId]);
            }

            $infoExistsStmt = $conn->prepare("SELECT id, student_id FROM student_info WHERE user_id = ?");
            $infoExistsStmt->execute([$userId]);
            $studentInfo = $infoExistsStmt->fetch(PDO::FETCH_ASSOC);

            if ($studentInfo) {
                $newStudentId = $studentId !== "" ? $studentId : $studentInfo["student_id"];
                $updateInfoStmt = $conn->prepare("
                    UPDATE student_info
                    SET student_id = ?, first_name = ?, last_name = ?, department = ?, status = ?
                    WHERE user_id = ?
                ");
                $updateInfoStmt->execute([$newStudentId, $firstName, $lastName, $department, $status, $userId]);
            } elseif ($userType === "student" && $studentId !== "") {
                $insertInfoStmt = $conn->prepare("
                    INSERT INTO student_info (user_id, student_id, first_name, last_name, department, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $insertInfoStmt->execute([$userId, $studentId, $firstName, $lastName, $department, $status]);
            }

            $conn->commit();
            $message = "User account updated successfully.";

            if ($userId === (int) $admin["id"] && $userType !== "admin") {
                $error = "You changed your own role away from admin. Please log in again with an administrator account if access changes.";
            }
        } elseif ($action === "delete_user") {
            $userId = (int) ($_POST["user_id"] ?? 0);
            if ($userId <= 0) {
                throw new RuntimeException("Invalid user selected for deletion.");
            }

            if ($userId === (int) $admin["id"]) {
                throw new RuntimeException("You cannot delete the account you are currently using.");
            }

            adminDeleteUser($conn, $userId);
            $message = "User account deleted successfully.";
            if ($editId === $userId) {
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

$usersStmt = $conn->query("
    SELECT
        u.id,
        u.username,
        u.email,
        u.user_type,
        si.student_id,
        si.first_name,
        si.last_name,
        si.department,
        si.status,
        COUNT(DISTINCT sc.course_id) AS enrolled_courses
    FROM users u
    LEFT JOIN student_info si ON u.id = si.user_id
    LEFT JOIN student_courses sc ON u.id = sc.user_id
    GROUP BY u.id, u.username, u.email, u.user_type, si.student_id, si.first_name, si.last_name, si.department, si.status
    ORDER BY FIELD(u.user_type, 'admin', 'instructor', 'student'), u.username
");
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

$selectedUser = [
    "id" => 0,
    "username" => "",
    "email" => "",
    "user_type" => "student",
    "student_id" => "",
    "first_name" => "",
    "last_name" => "",
    "department" => "",
    "status" => "active",
];

if ($editId > 0) {
    $selectedStmt = $conn->prepare("
        SELECT
            u.id,
            u.username,
            u.email,
            u.user_type,
            si.student_id,
            si.first_name,
            si.last_name,
            si.department,
            si.status
        FROM users u
        LEFT JOIN student_info si ON u.id = si.user_id
        WHERE u.id = ?
    ");
    $selectedStmt->execute([$editId]);
    $selectedUser = $selectedStmt->fetch(PDO::FETCH_ASSOC) ?: $selectedUser;
}

adminRenderHeader("Manage Users", "users");
?>
<h2 class="page-title">Manage Users</h2>
<p class="page-subtitle">View, edit, and delete user accounts. Student metadata can be maintained without leaving this page.</p>

<?php if ($message !== ""): ?>
    <div class="notice"><?php echo adminH($message); ?></div>
<?php endif; ?>

<?php if ($error !== ""): ?>
    <div class="error"><?php echo adminH($error); ?></div>
<?php endif; ?>

<div class="grid grid-2">
    <div class="panel">
        <div class="section-actions">
            <h3><?php echo $selectedUser["id"] ? "Edit User" : "Create User"; ?></h3>
            <?php if ($selectedUser["id"]): ?>
                <a class="button button-secondary" href="manage_users.php">Switch to Create</a>
            <?php endif; ?>
        </div>

        <form method="post">
            <input type="hidden" name="action" value="<?php echo $selectedUser["id"] ? "update_user" : "create_user"; ?>">
            <?php if ($selectedUser["id"]): ?>
                <input type="hidden" name="user_id" value="<?php echo (int) $selectedUser["id"]; ?>">
            <?php endif; ?>

            <div class="grid grid-2">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo adminH($selectedUser["username"]); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo adminH($selectedUser["email"]); ?>" required>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label for="user_type">User Type</label>
                    <select id="user_type" name="user_type">
                        <?php foreach (["student", "instructor", "admin"] as $type): ?>
                            <option value="<?php echo adminH($type); ?>" <?php echo $selectedUser["user_type"] === $type ? "selected" : ""; ?>>
                                <?php echo adminH(ucfirst($type)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="password"><?php echo $selectedUser["id"] ? "New Password (optional)" : "Password"; ?></label>
                    <input type="password" id="password" name="password" <?php echo $selectedUser["id"] ? "" : "required"; ?>>
                </div>
            </div>

            <h3>Student Details</h3>
            <div class="grid grid-2">
                <div class="form-group">
                    <label for="student_id">Student ID</label>
                    <input type="text" id="student_id" name="student_id" value="<?php echo adminH($selectedUser["student_id"] ?? ""); ?>">
                </div>
                <div class="form-group">
                    <label for="department">Department</label>
                    <input type="text" id="department" name="department" value="<?php echo adminH($selectedUser["department"] ?? ""); ?>">
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo adminH($selectedUser["first_name"] ?? ""); ?>">
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo adminH($selectedUser["last_name"] ?? ""); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="status">Student Status</label>
                <select id="status" name="status">
                    <?php foreach (["active", "inactive", "graduated"] as $status): ?>
                        <option value="<?php echo adminH($status); ?>" <?php echo ($selectedUser["status"] ?? "active") === $status ? "selected" : ""; ?>>
                            <?php echo adminH(ucfirst($status)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit"><?php echo $selectedUser["id"] ? "Save Changes" : "Create User"; ?></button>
        </form>
    </div>

    <div class="panel">
        <div class="section-actions">
            <h3>Existing Accounts</h3>
            <span class="muted"><?php echo count($users); ?> total users</span>
        </div>

        <?php if ($users): ?>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Student Details</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <strong><?php echo adminH($user["username"]); ?></strong><br>
                                <span class="muted"><?php echo adminH($user["email"]); ?></span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo adminH($user["user_type"]); ?>">
                                    <?php echo adminH($user["user_type"]); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($user["student_id"])): ?>
                                    ID: <?php echo adminH($user["student_id"]); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($user["department"])): ?>
                                    Dept: <?php echo adminH($user["department"]); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($user["status"])): ?>
                                    Status: <?php echo adminH($user["status"]); ?><br>
                                <?php endif; ?>
                                Courses: <?php echo (int) $user["enrolled_courses"]; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <a class="button button-secondary" href="manage_users.php?edit=<?php echo (int) $user["id"]; ?>">Edit</a>
                                    <form method="post" class="inline-form" onsubmit="return confirm('Delete this user account?');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?php echo (int) $user["id"]; ?>">
                                        <button type="submit" class="button button-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">No users found.</div>
        <?php endif; ?>
    </div>
</div>
<?php adminRenderFooter(); ?>
