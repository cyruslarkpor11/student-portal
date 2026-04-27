<?php
require "includes/db.php";

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

function fetchSingleValue(PDO $conn, string $sql, array $params = [])
{
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

try {
    $userTypeColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'user_type'")->fetch(PDO::FETCH_ASSOC);
    if ($userTypeColumn && strpos($userTypeColumn["Type"], "'instructor'") === false) {
        $conn->exec("ALTER TABLE users MODIFY user_type ENUM('student', 'admin', 'instructor') NOT NULL DEFAULT 'student'");
    }

    $adminPassword = password_hash("admin123", PASSWORD_DEFAULT);
    $studentPassword = password_hash("student123", PASSWORD_DEFAULT);
    $instructorPassword = password_hash("instructor123", PASSWORD_DEFAULT);

    $userInsert = $conn->prepare("
        INSERT INTO users (username, password, email, user_type)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            password = VALUES(password),
            email = VALUES(email),
            user_type = VALUES(user_type)
    ");
    $userInsert->execute(["admin", $adminPassword, "admin@university.edu", "admin"]);
    $userInsert->execute(["instructor1", $instructorPassword, "john.smith@university.edu", "instructor"]);
    $userInsert->execute(["instructor2", $instructorPassword, "jane.doe@university.edu", "instructor"]);
    $userInsert->execute(["student1", $studentPassword, "student1@university.edu", "student"]);
    $userInsert->execute(["student2", $studentPassword, "student2@university.edu", "student"]);

    $student1Id = fetchSingleValue($conn, "SELECT id FROM users WHERE username = 'student1'");
    $student2Id = fetchSingleValue($conn, "SELECT id FROM users WHERE username = 'student2'");

    $studentInfoInsert = $conn->prepare("
        INSERT IGNORE INTO student_info (user_id, student_id, first_name, last_name, department, status, phone, address, major, gender, program)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $studentInfoInsert->execute([$student1Id, "CS001", "John", "Doe", "Computer Science", "active", "+231-770-000-001", "Ganta, Nimba County", "Computer Science", "male", "undergraduate"]);
    $studentInfoInsert->execute([$student2Id, "CS002", "Jane", "Smith", "Computer Science", "active", "+231-770-000-002", "Sanniquellie, Nimba County", "Computer Science", "female", "undergraduate"]);

    if (tableExists($conn, "student_profile")) {
        if (columnExists($conn, "student_profile", "student_id") && columnExists($conn, "student_profile", "department") && columnExists($conn, "student_profile", "status")) {
            $profileInsert = $conn->prepare("
                INSERT INTO student_profile (user_id, student_id, department, status, major, year_level, phone, address)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    student_id = VALUES(student_id),
                    department = VALUES(department),
                    status = VALUES(status),
                    major = VALUES(major),
                    year_level = VALUES(year_level),
                    phone = VALUES(phone),
                    address = VALUES(address)
            ");
            $profileInsert->execute([$student1Id, "CS001", "Computer Science", "active", "Computer Science", "Freshman", "+231-770-000-001", "Ganta, Nimba County"]);
            $profileInsert->execute([$student2Id, "CS002", "Computer Science", "active", "Computer Science", "Sophomore", "+231-770-000-002", "Sanniquellie, Nimba County"]);
        }
    }

    $courseInsert = $conn->prepare("
        INSERT IGNORE INTO courses (course_code, course_name, instructor, credits, semester, description)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $courseInsert->execute(["CS101", "Introduction to Programming", "John Smith", 3, "Spring 2026", "Learn the basics of programming with practical exercises."]);
    $courseInsert->execute(["CS102", "Data Structures", "Jane Doe", 4, "Spring 2026", "Study core data structures and introductory algorithms."]);
    $courseInsert->execute(["CS201", "Web Development", "John Smith", 3, "Spring 2026", "Build modern web applications with HTML, CSS, JavaScript, and PHP."]);

    $course1Id = fetchSingleValue($conn, "SELECT course_id FROM courses WHERE course_code = 'CS101'");
    $course2Id = fetchSingleValue($conn, "SELECT course_id FROM courses WHERE course_code = 'CS102'");
    $course3Id = fetchSingleValue($conn, "SELECT course_id FROM courses WHERE course_code = 'CS201'");

    $studentCoursesInsert = $conn->prepare("INSERT IGNORE INTO student_courses (user_id, course_id) VALUES (?, ?)");
    $studentCoursesInsert->execute([$student1Id, $course1Id]);
    $studentCoursesInsert->execute([$student1Id, $course2Id]);
    $studentCoursesInsert->execute([$student2Id, $course2Id]);
    $studentCoursesInsert->execute([$student2Id, $course3Id]);

    if (tableExists($conn, "enrollments")) {
        $enrollmentInsert = $conn->prepare("INSERT IGNORE INTO enrollments (student_id, course_id) VALUES (?, ?)");
        $enrollmentInsert->execute([$student1Id, $course1Id]);
        $enrollmentInsert->execute([$student1Id, $course2Id]);
        $enrollmentInsert->execute([$student2Id, $course2Id]);
        $enrollmentInsert->execute([$student2Id, $course3Id]);
    }

    $assignmentRows = [
        [$course1Id, "Assignment 1", "Write a simple calculator program in PHP.", "2026-05-15 23:59:59", 100],
        [$course1Id, "Assignment 2", "Practice loops, conditions, and arrays.", "2026-05-22 23:59:59", 100],
        [$course2Id, "Linked List Exercise", "Implement and explain a linked list.", "2026-05-20 23:59:59", 100],
        [$course3Id, "Portal Wireframe", "Design a wireframe for the student portal dashboard.", "2026-05-28 23:59:59", 100],
    ];

    $assignmentSelect = $conn->prepare("SELECT assignment_id FROM assignments WHERE course_id = ? AND title = ?");
    $assignmentInsert = $conn->prepare("
        INSERT INTO assignments (course_id, title, description, due_date, total_points)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($assignmentRows as $assignmentRow) {
        $assignmentSelect->execute([$assignmentRow[0], $assignmentRow[1]]);
        if (!$assignmentSelect->fetchColumn()) {
            $assignmentInsert->execute($assignmentRow);
        }
    }

    $assignment1Id = fetchSingleValue($conn, "SELECT assignment_id FROM assignments WHERE course_id = ? AND title = ?", [$course1Id, "Assignment 1"]);
    $assignment2Id = fetchSingleValue($conn, "SELECT assignment_id FROM assignments WHERE course_id = ? AND title = ?", [$course2Id, "Linked List Exercise"]);
    $assignment3Id = fetchSingleValue($conn, "SELECT assignment_id FROM assignments WHERE course_id = ? AND title = ?", [$course3Id, "Portal Wireframe"]);

    $gradeInsert = $conn->prepare("
        INSERT INTO grades (user_id, course_id, assignment_id, points_earned, total_points, grade_letter, feedback)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $gradeRecords = [
        [$student1Id, $course1Id, $assignment1Id, 92, 100, "A", "Strong logic and clean structure."],
        [$student1Id, $course2Id, $assignment2Id, 88, 100, "B", "Good implementation with clear comments."],
        [$student2Id, $course2Id, $assignment2Id, 85, 100, "B", "Good work. Add more explanation around node traversal."],
        [$student2Id, $course3Id, $assignment3Id, 78, 100, "C", "Solid design, but needs better organization and polish."]
    ];

    foreach ($gradeRecords as $record) {
        list($uid, $cid, $aid, $points, $total, $letter, $feedback) = $record;
        $gradeExists = fetchSingleValue(
            $conn,
            "SELECT grade_id FROM grades WHERE user_id = ? AND course_id = ? AND assignment_id = ?",
            [$uid, $cid, $aid]
        );
        if (!$gradeExists && $aid) {
            $gradeInsert->execute([$uid, $cid, $aid, $points, $total, $letter, $feedback]);
        }
    }

    $resourceRows = [
        [$course1Id, "Programming Basics Notes", "Lecture notes for the introduction to programming module.", null, "document"],
        [$course2Id, "Data Structures Reading List", "Recommended reading materials for linked lists, stacks, and queues.", null, "document"],
        [$course3Id, "Frontend Inspiration", "Useful reference link for portal UI ideas.", "https://developer.mozilla.org/", "link"],
    ];

    $resourceSelect = $conn->prepare("SELECT resource_id FROM resources WHERE course_id = ? AND title = ?");
    $resourceInsert = $conn->prepare("
        INSERT INTO resources (course_id, title, description, file_path, resource_type)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($resourceRows as $resourceRow) {
        $resourceSelect->execute([$resourceRow[0], $resourceRow[1]]);
        if (!$resourceSelect->fetchColumn()) {
            $resourceInsert->execute($resourceRow);
        }
    }

    $instructor1Id = fetchSingleValue($conn, "SELECT id FROM users WHERE username = 'instructor1'");
    $messageExists = fetchSingleValue(
        $conn,
        "SELECT message_id FROM messages WHERE sender_id = ? AND recipient_id = ? AND subject = ?",
        [$instructor1Id, $student1Id, "Course Update"]
    );

    if (!$messageExists) {
        $messageInsert = $conn->prepare("
            INSERT INTO messages (sender_id, recipient_id, subject, body, sent_date)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $messageInsert->execute([$instructor1Id, $student1Id, "Course Update", "Hello, the next programming class will be on Friday at 10:00 AM."]);
    }

    echo "Sample data inserted successfully!<br>";
    echo "Login credentials:<br>";
    echo "Admin - username: admin, password: admin123<br>";
    echo "Instructor - username: instructor1, password: instructor123<br>";
    echo "Student - username: student1, password: student123<br>";
} catch (Throwable $e) {
    echo "Error: " . htmlspecialchars($e->getMessage());
}
?>
