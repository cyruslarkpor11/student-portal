<?php
require "db.php";

function tableExists(PDO $conn, string $table): bool
{
    $stmt = $conn->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$table]);
    return (bool) $stmt->fetchColumn();
}

function columnExists(PDO $conn, string $table, string $column): bool
{
    if (!tableExists($conn, $table)) {
        return false;
    }

    $stmt = $conn->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function ensureColumn(PDO $conn, string $table, string $column, string $definition): void
{
    if (!columnExists($conn, $table, $column)) {
        $conn->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

function getPrimaryLikeColumn(PDO $conn, string $table, array $candidates): ?string
{
    foreach ($candidates as $column) {
        if (columnExists($conn, $table, $column)) {
            return $column;
        }
    }

    return null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background: #f5f5f5;
            color: #222;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 720px;
            margin: 0 auto;
        }

        h2 {
            margin-top: 0;
        }

        .success {
            color: #155724;
            background: #d4edda;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #c3e6cb;
        }

        .error {
            color: #721c24;
            background: #f8d7da;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #f5c6cb;
        }

        ul {
            padding-left: 20px;
        }

        li {
            margin: 8px 0;
        }

        code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
<div class="container">
<?php
try {
    $createdTables = [];

    $conn->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            user_type ENUM('student', 'admin', 'instructor') NOT NULL DEFAULT 'student',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $userTypeColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'user_type'")->fetch(PDO::FETCH_ASSOC);
    if ($userTypeColumn && strpos($userTypeColumn["Type"], "'instructor'") === false) {
        $conn->exec("ALTER TABLE users MODIFY user_type ENUM('student', 'admin', 'instructor') NOT NULL DEFAULT 'student'");
    }
    ensureColumn($conn, "users", "created_at", "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    $createdTables[] = "users";

    $conn->exec("
        CREATE TABLE IF NOT EXISTS student_info (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            student_id VARCHAR(20) NOT NULL UNIQUE,
            first_name VARCHAR(50) DEFAULT NULL,
            last_name VARCHAR(50) DEFAULT NULL,
            department VARCHAR(100) DEFAULT NULL,
            status ENUM('active', 'inactive', 'graduated') DEFAULT 'active',
            profile_picture VARCHAR(255) DEFAULT NULL,
            phone VARCHAR(20) DEFAULT NULL,
            address TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_student_info_user (user_id),
            CONSTRAINT fk_student_info_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    ensureColumn($conn, "student_info", "department", "VARCHAR(100) DEFAULT NULL");
    ensureColumn($conn, "student_info", "status", "ENUM('active', 'inactive', 'graduated') DEFAULT 'active'");
    ensureColumn($conn, "student_info", "profile_picture", "VARCHAR(255) DEFAULT NULL");
    ensureColumn($conn, "student_info", "phone", "VARCHAR(20) DEFAULT NULL");
    ensureColumn($conn, "student_info", "address", "TEXT DEFAULT NULL");
    ensureColumn($conn, "student_info", "major", "VARCHAR(100) DEFAULT NULL");
    ensureColumn($conn, "student_info", "minor", "VARCHAR(100) DEFAULT NULL");
    ensureColumn($conn, "student_info", "bio", "TEXT DEFAULT NULL");
    ensureColumn($conn, "student_info", "gender", "ENUM('male', 'female', 'other') DEFAULT NULL");
    ensureColumn($conn, "student_info", "program", "ENUM('undergraduate', 'master', 'phd') DEFAULT 'undergraduate'");
    $createdTables[] = "student_info";

    $conn->exec("
        CREATE TABLE IF NOT EXISTS student_profile (
            user_id INT PRIMARY KEY,
            student_id VARCHAR(50) UNIQUE,
            phone VARCHAR(20),
            address VARCHAR(255),
            date_of_birth DATE,
            major VARCHAR(100),
            department VARCHAR(100),
            year_level VARCHAR(50),
            status VARCHAR(50) DEFAULT 'active',
            gpa DECIMAL(3, 2),
            profile_picture VARCHAR(255),
            bio TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_student_profile_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    ensureColumn($conn, "student_profile", "student_id", "VARCHAR(50) DEFAULT NULL");
    ensureColumn($conn, "student_profile", "department", "VARCHAR(100) DEFAULT NULL");
    ensureColumn($conn, "student_profile", "status", "VARCHAR(50) DEFAULT 'active'");
    ensureColumn($conn, "student_profile", "profile_picture", "VARCHAR(255) DEFAULT NULL");
    ensureColumn($conn, "student_profile", "bio", "TEXT DEFAULT NULL");
    $createdTables[] = "student_profile";

    $conn->exec("
        CREATE TABLE IF NOT EXISTS courses (
            course_id INT AUTO_INCREMENT PRIMARY KEY,
            course_name VARCHAR(100) NOT NULL,
            course_code VARCHAR(20) NOT NULL UNIQUE,
            instructor VARCHAR(100) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            credits INT DEFAULT 3,
            semester VARCHAR(20) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    ensureColumn($conn, "courses", "instructor", "VARCHAR(100) DEFAULT NULL");
    ensureColumn($conn, "courses", "description", "TEXT DEFAULT NULL");
    ensureColumn($conn, "courses", "credits", "INT DEFAULT 3");
    ensureColumn($conn, "courses", "semester", "VARCHAR(20) DEFAULT NULL");
    $createdTables[] = "courses";

    $courseKey = getPrimaryLikeColumn($conn, "courses", ["course_id", "id"]);
    if ($courseKey === null) {
        throw new RuntimeException("The courses table exists but does not contain `course_id` or `id`, so enrollment foreign keys cannot be created.");
    }

    $conn->exec("
        CREATE TABLE IF NOT EXISTS student_courses (
            enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            course_id INT NOT NULL,
            enrolled_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_student_course (user_id, course_id),
            CONSTRAINT fk_student_courses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_student_courses_course FOREIGN KEY (course_id) REFERENCES courses($courseKey) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $createdTables[] = "student_courses";

    $conn->exec("
        CREATE TABLE IF NOT EXISTS enrollments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            course_id INT NOT NULL,
            enrolled_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_enrollment (student_id, course_id),
            CONSTRAINT fk_enrollments_user FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_enrollments_course FOREIGN KEY (course_id) REFERENCES courses($courseKey) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $createdTables[] = "enrollments";

    $conn->exec("
        CREATE TABLE IF NOT EXISTS assignments (
            assignment_id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT NOT NULL,
            title VARCHAR(150) NOT NULL,
            description TEXT DEFAULT NULL,
            due_date DATETIME DEFAULT NULL,
            total_points INT DEFAULT 100,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_assignments_course FOREIGN KEY (course_id) REFERENCES courses($courseKey) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $createdTables[] = "assignments";

    $assignmentKey = getPrimaryLikeColumn($conn, "assignments", ["assignment_id", "id"]);
    if ($assignmentKey === null) {
        throw new RuntimeException("The assignments table exists but does not contain `assignment_id` or `id`, so grade foreign keys cannot be created.");
    }

    $conn->exec("
        CREATE TABLE IF NOT EXISTS student_assignments (
            submission_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            assignment_id INT NOT NULL,
            submission_file VARCHAR(255) DEFAULT NULL,
            submission_text TEXT DEFAULT NULL,
            points_earned INT DEFAULT NULL,
            feedback TEXT DEFAULT NULL,
            submitted_date DATETIME DEFAULT NULL,
            status ENUM('not_started', 'in_progress', 'submitted', 'graded') DEFAULT 'not_started',
            UNIQUE KEY unique_submission (user_id, assignment_id),
            CONSTRAINT fk_student_assignments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_student_assignments_assignment FOREIGN KEY (assignment_id) REFERENCES assignments($assignmentKey) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $createdTables[] = "student_assignments";

    $conn->exec("
        CREATE TABLE IF NOT EXISTS grades (
            grade_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            course_id INT NOT NULL,
            assignment_id INT DEFAULT NULL,
            points_earned INT DEFAULT NULL,
            total_points INT DEFAULT 100,
            grade_letter VARCHAR(2) DEFAULT NULL,
            feedback TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_grades_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_grades_course FOREIGN KEY (course_id) REFERENCES courses($courseKey) ON DELETE CASCADE,
            CONSTRAINT fk_grades_assignment FOREIGN KEY (assignment_id) REFERENCES assignments($assignmentKey) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    ensureColumn($conn, "grades", "feedback", "TEXT DEFAULT NULL");
    $createdTables[] = "grades";

    $conn->exec("
        CREATE TABLE IF NOT EXISTS resources (
            resource_id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT NOT NULL,
            title VARCHAR(150) NOT NULL,
            description TEXT DEFAULT NULL,
            file_path VARCHAR(255) DEFAULT NULL,
            resource_type ENUM('document', 'video', 'link', 'other') DEFAULT 'document',
            uploaded_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_resources_course FOREIGN KEY (course_id) REFERENCES courses($courseKey) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $createdTables[] = "resources";

    $conn->exec("
        CREATE TABLE IF NOT EXISTS messages (
            message_id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            recipient_id INT NOT NULL,
            subject VARCHAR(200) DEFAULT NULL,
            body TEXT NOT NULL,
            sent_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            read_date DATETIME DEFAULT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_messages_recipient FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $createdTables[] = "messages";

    $conn->exec("
        CREATE TABLE IF NOT EXISTS chat_messages (
            message_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_chat_messages_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $createdTables[] = "chat_messages";

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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    ensureColumn($conn, "contacts", "username", "VARCHAR(100) DEFAULT NULL");
    ensureColumn($conn, "contacts", "subject", "VARCHAR(200) DEFAULT NULL");
    ensureColumn($conn, "contacts", "status", "ENUM('new', 'read', 'resolved') NOT NULL DEFAULT 'new'");
    $createdTables[] = "contacts";

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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $createdTables[] = "announcements";

    $conn->exec("
        CREATE TABLE IF NOT EXISTS settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT DEFAULT NULL,
            updated_by INT DEFAULT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_settings_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $createdTables[] = "settings";

        // Admission applications table for public-facing admission portal
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
                INDEX (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $createdTables[] = "admission_applications";

    echo "<div class='success'>";
    echo "<h2>All required tables are ready.</h2>";
    echo "<p><strong>Database:</strong> " . htmlspecialchars($dbname) . "</p>";
    echo "<p>This setup now matches the current student portal schema and also creates a compatible <code>enrollments</code> table so older setup steps do not fail.</p>";
    echo "<ul>";
    foreach ($createdTables as $tableName) {
        echo "<li>" . htmlspecialchars($tableName) . "</li>";
    }
    echo "</ul>";
    echo "<p><strong>Next step:</strong> Run <a href='../insert_sample_data.php'><code>insert_sample_data.php</code></a> to seed demo users, courses, enrollments, grades, resources, and messages.</p>";
    echo "</div>";
} catch (Throwable $e) {
    echo "<div class='error'>";
    echo "<h2>Error creating tables</h2>";
    echo "<p><strong>Error Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>What this usually means:</strong> the database already contains an older table shape, and a new foreign key is pointing at a column name that does not exist in that version.</p>";
    echo "<p><strong>Checklist:</strong></p>";
    echo "<ul>";
    echo "<li>Make sure MySQL/MariaDB is running in XAMPP.</li>";
    echo "<li>Make sure Apache is running.</li>";
    echo "<li>Verify <code>includes/db.php</code> points to <code>localhost</code>, database <code>myproject</code>, user <code>root</code>, and your correct password.</li>";
    echo "<li>If you previously ran another setup script, rerun this patched page so it can align the schema.</li>";
    echo "</ul>";
    echo "</div>";
}
?>
</div>
</body>
</html>
