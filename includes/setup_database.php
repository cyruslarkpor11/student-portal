<?php
session_start();
require "includes/db.php";

try {
    // Create student_profile table
    $conn->exec("CREATE TABLE IF NOT EXISTS student_profile (
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
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Create courses table
    $conn->exec("CREATE TABLE IF NOT EXISTS courses (
        course_id INT AUTO_INCREMENT PRIMARY KEY,
        course_name VARCHAR(100) NOT NULL,
        course_code VARCHAR(20) NOT NULL UNIQUE,
        instructor VARCHAR(100),
        description TEXT,
        credits INT,
        semester VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create student_courses table (enrollment)
    $conn->exec("CREATE TABLE IF NOT EXISTS student_courses (
        enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        course_id INT NOT NULL,
        enrolled_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
        UNIQUE KEY unique_enrollment (user_id, course_id)
    )");

    // Create assignments table
    $conn->exec("CREATE TABLE IF NOT EXISTS assignments (
        assignment_id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NOT NULL,
        title VARCHAR(150) NOT NULL,
        description TEXT,
        due_date DATETIME,
        total_points INT DEFAULT 100,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES courses(course_id)
    )");

    // Create student_assignments table (submissions)
    $conn->exec("CREATE TABLE IF NOT EXISTS student_assignments (
        submission_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        assignment_id INT NOT NULL,
        submission_file VARCHAR(255),
        submission_text TEXT,
        points_earned INT,
        feedback TEXT,
        submitted_date DATETIME,
        status ENUM('not_started', 'in_progress', 'submitted', 'graded') DEFAULT 'not_started',
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (assignment_id) REFERENCES assignments(assignment_id) ON DELETE CASCADE,
        UNIQUE KEY unique_submission (user_id, assignment_id)
    )");

    // Create grades table
    $conn->exec("CREATE TABLE IF NOT EXISTS grades (
        grade_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        course_id INT NOT NULL,
        assignment_id INT,
        points_earned INT,
        total_points INT DEFAULT 100,
        grade_letter VARCHAR(2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
        FOREIGN KEY (assignment_id) REFERENCES assignments(assignment_id) ON DELETE SET NULL
    )");

    // Create resources table
    $conn->exec("CREATE TABLE IF NOT EXISTS resources (
        resource_id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NOT NULL,
        title VARCHAR(150) NOT NULL,
        description TEXT,
        file_path VARCHAR(255),
        resource_type ENUM('document', 'video', 'link', 'other') DEFAULT 'document',
        uploaded_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
    )");

    // Create messages table
    $conn->exec("CREATE TABLE IF NOT EXISTS messages (
        message_id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        recipient_id INT NOT NULL,
        subject VARCHAR(200),
        body TEXT NOT NULL,
        sent_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_date DATETIME,
        is_read BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Create contacts table for admin review
    $conn->exec("CREATE TABLE IF NOT EXISTS contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        username VARCHAR(100) DEFAULT NULL,
        email VARCHAR(100) NOT NULL,
        subject VARCHAR(200) DEFAULT NULL,
        message TEXT NOT NULL,
        status ENUM('new', 'read', 'resolved') NOT NULL DEFAULT 'new',
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create announcements table
    $conn->exec("CREATE TABLE IF NOT EXISTS announcements (
        announcement_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        body TEXT NOT NULL,
        audience ENUM('all', 'students', 'admins', 'instructors') NOT NULL DEFAULT 'all',
        status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
        published_at DATETIME DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )");

    // Create settings table
    $conn->exec("CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT DEFAULT NULL,
        updated_by INT DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (updated_by) REFERENCES users(id)
    )");

    echo "✓ All tables created successfully!<br>";
    echo "<a href='student_portal.php'>Go to Student Portal</a>";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
