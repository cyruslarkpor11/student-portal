<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/db.php";

function adminH($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function adminTableExists(PDO $conn, string $table): bool
{
    $stmt = $conn->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$table]);
    return (bool) $stmt->fetchColumn();
}

function adminColumnExists(PDO $conn, string $table, string $column): bool
{
    if (!adminTableExists($conn, $table)) {
        return false;
    }

    $stmt = $conn->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function adminEnsureColumn(PDO $conn, string $table, string $column, string $definition): void
{
    if (!adminColumnExists($conn, $table, $column)) {
        $conn->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

function adminEnsureSupportTables(PDO $conn): void
{
    adminEnsureColumn($conn, "users", "created_at", "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

    $conn->exec("
        CREATE TABLE IF NOT EXISTS contacts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            username VARCHAR(100) DEFAULT NULL,
            email VARCHAR(100) NOT NULL,
            subject VARCHAR(200) DEFAULT NULL,
            message TEXT NOT NULL,
            status ENUM('new', 'read', 'resolved') NOT NULL DEFAULT 'new',
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    adminEnsureColumn($conn, "contacts", "username", "VARCHAR(100) DEFAULT NULL AFTER `name`");
    adminEnsureColumn($conn, "contacts", "subject", "VARCHAR(200) DEFAULT NULL AFTER `email`");
    adminEnsureColumn($conn, "contacts", "status", "ENUM('new', 'read', 'resolved') NOT NULL DEFAULT 'new' AFTER `message`");
    adminEnsureColumn($conn, "contacts", "submitted_at", "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

    $conn->exec("
        CREATE TABLE IF NOT EXISTS admission_applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL,
            phone VARCHAR(50) DEFAULT NULL,
            date_of_birth DATE DEFAULT NULL,
            gender ENUM('male','female','other') DEFAULT NULL,
            program_applied ENUM('undergraduate','master','certificate') DEFAULT 'undergraduate',
            address TEXT DEFAULT NULL,
            high_school VARCHAR(255) DEFAULT NULL,
            gpa DECIMAL(3,2) DEFAULT NULL,
            application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('pending','approved','rejected') DEFAULT 'pending',
            diploma_path VARCHAR(255) DEFAULT NULL,
            transcript_path VARCHAR(255) DEFAULT NULL,
            supporting_docs_path VARCHAR(255) DEFAULT NULL,
            INDEX (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Ensure document columns exist for existing tables
    adminEnsureColumn($conn, "admission_applications", "diploma_path", "VARCHAR(255) DEFAULT NULL");
    adminEnsureColumn($conn, "admission_applications", "transcript_path", "VARCHAR(255) DEFAULT NULL");
    adminEnsureColumn($conn, "admission_applications", "supporting_docs_path", "VARCHAR(255) DEFAULT NULL");

    $conn->exec("
        CREATE TABLE IF NOT EXISTS announcements (
            announcement_id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            body TEXT NOT NULL,
            audience ENUM('all', 'students', 'admins', 'instructors') NOT NULL DEFAULT 'all',
            status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
            published_at DATETIME DEFAULT NULL,
            created_by INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_announcements_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        )
    ");

    $conn->exec("
        CREATE TABLE IF NOT EXISTS settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT DEFAULT NULL,
            updated_by INT DEFAULT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_settings_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
}

function adminRequireUser(PDO $conn): array
{
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || ($_SESSION["user_type"] ?? "") !== "admin") {
        header("Location: index.php");
        exit();
    }

    $userId = $_SESSION["user_id"] ?? null;
    $username = $_SESSION["username"] ?? "";

    if ($userId) {
        $stmt = $conn->prepare("SELECT id, username, email, user_type FROM users WHERE id = ? AND user_type = 'admin'");
        $stmt->execute([$userId]);
    } else {
        $stmt = $conn->prepare("SELECT id, username, email, user_type FROM users WHERE username = ? AND user_type = 'admin'");
        $stmt->execute([$username]);
    }

    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$admin) {
        header("Location: logout.php");
        exit();
    }

    $_SESSION["user_id"] = $admin["id"];
    $_SESSION["username"] = $admin["username"];
    $_SESSION["user_type"] = $admin["user_type"];

    return $admin;
}

function adminCount(PDO $conn, string $sql, array $params = []): int
{
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function adminSettingDefinitions(): array
{
    return [
        "institution_name" => [
            "label" => "Institution Name",
            "type" => "text",
            "default" => "African Methodist Episcopal Zion University Nimba Extension",
        ],
        "portal_tagline" => [
            "label" => "Portal Tagline",
            "type" => "text",
            "default" => "Student and Administration Portal",
        ],
        "support_email" => [
            "label" => "Support Email",
            "type" => "email",
            "default" => "support@university.edu",
        ],
        "contact_phone" => [
            "label" => "Contact Phone",
            "type" => "text",
            "default" => "+231-000-000-000",
        ],
        "current_semester" => [
            "label" => "Current Semester",
            "type" => "text",
            "default" => "Spring 2026",
        ],
        "maintenance_mode" => [
            "label" => "Maintenance Mode",
            "type" => "select",
            "default" => "off",
            "options" => [
                "off" => "Off",
                "on" => "On",
            ],
        ],
        "allow_registration" => [
            "label" => "Allow Registration",
            "type" => "select",
            "default" => "yes",
            "options" => [
                "yes" => "Yes",
                "no" => "No",
            ],
        ],
        "homepage_notice" => [
            "label" => "Homepage Notice",
            "type" => "textarea",
            "default" => "",
        ],
    ];
}

function adminGetSettings(PDO $conn): array
{
    $definitions = adminSettingDefinitions();
    $settings = [];

    foreach ($definitions as $key => $meta) {
        $settings[$key] = $meta["default"];
    }

    $stmt = $conn->query("SELECT setting_key, setting_value FROM settings");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $settings[$row["setting_key"]] = $row["setting_value"];
    }

    return $settings;
}

function adminSaveSetting(PDO $conn, string $key, ?string $value, int $updatedBy): void
{
    $stmt = $conn->prepare("
        INSERT INTO settings (setting_key, setting_value, updated_by)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value),
            updated_by = VALUES(updated_by)
    ");
    $stmt->execute([$key, $value, $updatedBy]);
}

function adminExportCsv(string $filename, array $headerRow, array $rows): void
{
    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");

    $output = fopen("php://output", "w");
    fputcsv($output, $headerRow);
    foreach ($rows as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

function adminDeleteByColumnIfExists(PDO $conn, string $table, string $column, int $userId): void
{
    if (!adminTableExists($conn, $table) || !adminColumnExists($conn, $table, $column)) {
        return;
    }

    $stmt = $conn->prepare("DELETE FROM `$table` WHERE `$column` = ?");
    $stmt->execute([$userId]);
}

function adminNullByColumnIfExists(PDO $conn, string $table, string $column, int $userId): void
{
    if (!adminTableExists($conn, $table) || !adminColumnExists($conn, $table, $column)) {
        return;
    }

    $stmt = $conn->prepare("UPDATE `$table` SET `$column` = NULL WHERE `$column` = ?");
    $stmt->execute([$userId]);
}

function adminDeleteUserDependencies(PDO $conn, int $userId): void
{
    $singleColumnDeletes = [
        ["student_assignments", "user_id"],
        ["student_assignments", "student_id"],
        ["grades", "user_id"],
        ["grades", "student_id"],
        ["student_courses", "user_id"],
        ["student_courses", "student_id"],
        ["enrollments", "student_id"],
        ["enrollments", "user_id"],
        ["student_profile", "user_id"],
        ["student_info", "user_id"],
        ["resources", "uploaded_by"],
        ["contacts", "user_id"],
    ];

    foreach ($singleColumnDeletes as [$table, $column]) {
        adminDeleteByColumnIfExists($conn, $table, $column, $userId);
    }

    $nullableReferences = [
        ["announcements", "created_by"],
        ["settings", "updated_by"],
        ["courses", "instructor_id"],
    ];

    foreach ($nullableReferences as [$table, $column]) {
        adminNullByColumnIfExists($conn, $table, $column, $userId);
    }

    if (adminTableExists($conn, "messages")) {
        $messageColumns = [];
        foreach (["sender_id", "recipient_id", "user_id"] as $column) {
            if (adminColumnExists($conn, "messages", $column)) {
                $messageColumns[] = "`$column` = ?";
            }
        }

        if ($messageColumns) {
            $sql = "DELETE FROM `messages` WHERE " . implode(" OR ", $messageColumns);
            $params = array_fill(0, count($messageColumns), $userId);
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
        }
    }
}

function adminDeleteUser(PDO $conn, int $userId): void
{
    $conn->beginTransaction();

    try {
        adminDeleteUserDependencies($conn, $userId);

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);

        $conn->commit();
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        throw $e;
    }
}

function adminRenderHeader(string $title, string $active): void
{
    $navItems = [
        "dashboard" => ["file" => "admin_portal.php", "label" => "Dashboard"],
        "users" => ["file" => "manage_users.php", "label" => "Manage Users"],
        "messages" => ["file" => "admin_messages.php", "label" => "View Messages"],
        "reports" => ["file" => "admin_reports.php", "label" => "Reports"],
        "settings" => ["file" => "admin_settings.php", "label" => "Settings"],
        "courses" => ["file" => "admin_courses.php", "label" => "Manage Courses"],
        "announcements" => ["file" => "admin_announcements.php", "label" => "Announcements"],
        "admissions" => ["file" => "admin_admissions.php", "label" => "Admissions"],
    ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo adminH($title); ?> - Admin Portal</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(rgba(14, 48, 82, 0.9), rgba(7, 23, 46, 0.94)), url("Images/download%202.jpg") center/cover no-repeat fixed;
            color: #f5f5f5;
            min-height: 100vh;
        }

        header {
            background: rgba(0, 0, 0, 0.48);
            padding: 24px 20px;
            text-align: center;
            border-bottom: 3px solid #f0c24b;
            backdrop-filter: blur(10px);
        }

        header h1 {
            margin: 0;
            color: #f0c24b;
            font-size: 1.9rem;
        }

        .nav-tabs {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            background: rgba(0, 0, 0, 0.38);
            border-bottom: 1px solid rgba(240, 194, 75, 0.25);
            backdrop-filter: blur(8px);
        }

        .nav-tabs a {
            color: #fff;
            text-decoration: none;
            padding: 14px 18px;
            border-bottom: 3px solid transparent;
            font-weight: 600;
            transition: all 0.25s ease;
        }

        .nav-tabs a:hover,
        .nav-tabs a.active {
            color: #f0c24b;
            background: rgba(240, 194, 75, 0.08);
            border-bottom-color: #f0c24b;
        }

        main {
            max-width: 1240px;
            margin: 28px auto;
            padding: 32px;
            background: rgba(0, 0, 0, 0.34);
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.28);
            backdrop-filter: blur(8px);
        }

        h2, h3 {
            color: #f0c24b;
        }

        .page-title {
            margin-top: 0;
            margin-bottom: 10px;
            color: #f0c24b;
        }

        .page-subtitle {
            margin-top: 0;
            color: #e8d9d9;
        }

        .button,
        button,
        input[type="submit"] {
            background: #b31f1f;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            cursor: pointer;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .button:hover,
        button:hover,
        input[type="submit"]:hover {
            background: #cf2d2d;
            transform: translateY(-1px);
        }

        .button-secondary {
            background: rgba(255, 255, 255, 0.14);
        }

        .button-secondary:hover {
            background: rgba(255, 255, 255, 0.22);
        }

        .button-danger {
            background: #7a0f0f;
        }

        .button-danger:hover {
            background: #931515;
        }

        .button-link {
            color: #f0c24b;
            text-decoration: none;
            font-weight: 600;
        }

        .button-link:hover {
            text-decoration: underline;
        }

        .notice,
        .error {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 18px;
        }

        .notice {
            background: rgba(52, 168, 83, 0.18);
            border-left: 4px solid #34a853;
            color: #d7f5de;
        }

        .error {
            background: rgba(234, 67, 53, 0.18);
            border-left: 4px solid #ea4335;
            color: #ffd7d2;
        }

        .panel {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 22px;
        }

        .grid {
            display: grid;
            gap: 20px;
        }

        .grid-2 {
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        }

        .grid-3 {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .stat-card {
            background: rgba(240, 194, 75, 0.12);
            border-left: 4px solid #f0c24b;
            border-radius: 14px;
            padding: 22px;
        }

        .stat-card .number {
            font-size: 2rem;
            font-weight: 800;
            color: #f0c24b;
        }

        .stat-card .label {
            margin-top: 8px;
            color: #f7eded;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(0, 0, 0, 0.18);
            border-radius: 12px;
            overflow: hidden;
        }

        th,
        td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            vertical-align: top;
        }

        th {
            background: rgba(0, 0, 0, 0.24);
            color: #f0c24b;
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.04);
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid rgba(240, 194, 75, 0.22);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            font: inherit;
        }

        select option {
            color: #111;
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #f0c24b;
            font-weight: 700;
        }

        .muted {
            color: #d3c4c4;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: capitalize;
        }

        .badge-admin,
        .badge-published,
        .badge-resolved {
            background: rgba(52, 168, 83, 0.2);
            color: #c4f0d2;
        }

        .badge-student,
        .badge-draft,
        .badge-new {
            background: rgba(66, 133, 244, 0.2);
            color: #d5e7ff;
        }

        .badge-instructor,
        .badge-read,
        .badge-archived {
            background: rgba(240, 194, 75, 0.2);
            color: #ffe8a6;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .inline-form {
            display: inline;
        }

        .empty-state {
            padding: 24px;
            text-align: center;
            color: #e8d9d9;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
        }

        .section-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        @media (max-width: 700px) {
            main {
                margin: 16px;
                padding: 20px;
            }

            header h1 {
                font-size: 1.5rem;
            }

            .nav-tabs a {
                padding: 12px 14px;
                font-size: 0.92rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>African Methodist Episcopal Zion University Nimba Extension</h1>
    </header>

    <nav class="nav-tabs">
        <?php foreach ($navItems as $key => $item): ?>
            <a href="<?php echo adminH($item["file"]); ?>" class="<?php echo $active === $key ? "active" : ""; ?>">
                <?php echo adminH($item["label"]); ?>
            </a>
        <?php endforeach; ?>
        <a href="logout.php">Logout</a>
    </nav>

    <main>
<?php
}

function adminRenderFooter(): void
{
    ?>
    </main>
</body>
</html>
<?php
}
