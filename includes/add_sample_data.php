<?php
session_start();
require "includes/db.php";

try {
    echo "Adding sample data to the database...<br>";

    // Add sample courses
    $courses_data = [
        ["COMP101", "Introduction to Computer Science", "Dr. John Smith", "Learn the basics of programming and computer science", 3, "Fall 2024"],
        ["COMP201", "Data Structures", "Dr. Sarah Johnson", "Study fundamental data structures and algorithms", 3, "Fall 2024"],
        ["MATH101", "Calculus I", "Prof. Michael Brown", "Introduction to differential and integral calculus", 4, "Fall 2024"],
        ["ENG101", "English Composition", "Dr. Emily Davis", "Develop academic writing skills", 3, "Fall 2024"],
        ["PHYS101", "Physics I", "Prof. Robert Wilson", "Mechanics, waves, and thermodynamics", 4, "Fall 2024"],
    ];

    foreach ($courses_data as $course) {
        try {
            $stmt = $conn->prepare("INSERT IGNORE INTO courses (course_code, course_name, instructor, description, credits, semester) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute($course);
        } catch (Exception $e) {
            // Course might already exist
        }
    }
    echo "✓ Sample courses added<br>";

    // Get all users and courses to create enrollments and grades
    $stmt = $conn->prepare("SELECT id FROM users WHERE user_type = 'student' LIMIT 5");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT course_id, course_code FROM courses LIMIT 5");
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Enroll students in courses
    if (!empty($students) && !empty($courses)) {
        foreach ($students as $student) {
            $student_id = $student["id"];
            foreach ($courses as $course) {
                try {
                    $stmt = $conn->prepare("INSERT IGNORE INTO student_courses (user_id, course_id) VALUES (?, ?)");
                    $stmt->execute([$student_id, $course["course_id"]]);
                } catch (Exception $e) {
                    // Enrollment might already exist
                }
            }
        }
        echo "✓ Student course enrollments added<br>";
    }

    // Add sample assignments
    $assignments_data = [
        [1, "First Program", "Create a simple calculator program", "2024-11-15 23:59:00", 100],
        [1, "Data Types Assignment", "Practice using different data types", "2024-11-22 23:59:00", 100],
        [2, "Algorithm Analysis", "Analyze the time complexity of algorithms", "2024-11-20 23:59:00", 100],
        [3, "Integration Problems", "Solve 20 integration problems", "2024-11-18 23:59:00", 100],
    ];

    foreach ($assignments_data as $assign) {
        try {
            $stmt = $conn->prepare("INSERT INTO assignments (course_id, title, description, due_date, total_points) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute($assign);
        } catch (Exception $e) {
            // Assignment might already exist
        }
    }
    echo "✓ Sample assignments added<br>";

    // Add sample grades
    $stmt = $conn->prepare("SELECT id FROM users WHERE user_type = 'student' LIMIT 3");
    $stmt->execute();
    $sample_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT course_id FROM courses LIMIT 3");
    $stmt->execute();
    $sample_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($sample_students) && !empty($sample_courses)) {
        foreach ($sample_students as $student) {
            $student_id = $student["id"];
            foreach ($sample_courses as $course) {
                $course_id = $course["course_id"];
                $points = rand(70, 100);
                try {
                    $stmt = $conn->prepare("INSERT INTO grades (user_id, course_id, points_earned, total_points, grade_letter) VALUES (?, ?, ?, 100, ?)");
                    if ($points >= 90) $letter = "A";
                    elseif ($points >= 80) $letter = "B";
                    elseif ($points >= 70) $letter = "C";
                    elseif ($points >= 60) $letter = "D";
                    else $letter = "F";
                    $stmt->execute([$student_id, $course_id, $points, $letter]);
                } catch (Exception $e) {
                    // Grade might already exist
                }
            }
        }
        echo "✓ Sample grades added<br>";
    }

    // Add sample resources
    $resources_data = [
        [1, "Lecture Notes Week 1", "Introduction to programming concepts", "resources/comp101_week1.pdf", "document"],
        [1, "Programming Tutorial", "Video tutorial on loops and functions", "resources/comp101_tutorial.mp4", "video"],
        [2, "Data Structures Book", "Reference material on data structures", "resources/ds_book.pdf", "document"],
        [3, "Calculus Reference", "Common formulas and rules", "resources/calculus_ref.pdf", "document"],
    ];

    foreach ($resources_data as $res) {
        try {
            $stmt = $conn->prepare("INSERT INTO resources (course_id, title, description, file_path, resource_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute($res);
        } catch (Exception $e) {
            // Resource might already exist
        }
    }
    echo "✓ Sample resources added<br>";

    echo "<br><strong>✓ All sample data added successfully!</strong><br>";
    echo "<a href='student_portal.php'>Go to Student Portal</a>";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
