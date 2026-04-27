<?php
session_start();
require "includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: index.php");
    exit();
}

$user_type = $_SESSION["user_type"] ?? null;
$username = $_SESSION["username"] ?? null;

$stmt = $conn->prepare("SELECT username, email, user_type FROM users WHERE username = ? AND user_type = ?");
$stmt->execute([$username, $user_type]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: logout.php");
    exit();
}

$total_students = 0;
$total_admins = 0;
$total_contacts = 0;

if ($user_type === "admin") {
    $total_students = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'student'")->fetch()["count"];
    $total_admins = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'admin'")->fetch()["count"];
    $total_contacts = $conn->query("SELECT COUNT(*) as count FROM contacts")->fetch()["count"] ?? 0;
}

$bg_image = ($user_type === "admin") ? "Images/download%202.jpg" : "Images/download%201.jpg";
$bg_color1 = ($user_type === "admin") ? "rgba(139, 0, 0, 0.8)" : "rgba(102, 126, 234, 0.8)";
$bg_color2 = ($user_type === "admin") ? "rgba(178, 34, 34, 0.8)" : "rgba(118, 75, 162, 0.8)";
$accent_color = ($user_type === "admin") ? "#ff6b6b" : "#4CAF50";
$portal_name = ($user_type === "admin") ? "ADMIN PORTAL" : "STUDENT PORTAL";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - African Methodist Episcopal Zion University Nimba Extension</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(<?php echo $bg_color1; ?>, <?php echo $bg_color2; ?>), url("<?php echo $bg_image; ?>") center/cover no-repeat fixed;
            color: white;
            min-height: 100vh;
        }

        header {
            background: rgba(0, 0, 0, 0.5);
            padding: 25px 20px;
            text-align: center;
            border-bottom: 4px solid #ffd700;
            backdrop-filter: blur(10px);
        }

        header .institution-name {
            font-size: 1.5em;
            font-weight: bold;
            color: #ffd700;
            margin-bottom: 5px;
        }

        header .portal-name {
            font-size: 2em;
            font-weight: bold;
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .nav-tabs {
            background: rgba(0, 0, 0, 0.4);
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            border-bottom: 2px solid rgba(255, 215, 0, 0.3);
            backdrop-filter: blur(10px);
        }

        .nav-tabs a {
            color: white;
            text-decoration: none;
            padding: 15px 25px;
            display: inline-block;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            font-weight: 600;
            font-size: 1em;
        }

        .nav-tabs a:hover {
            background: rgba(255, 215, 0, 0.1);
            border-bottom-color: #ffd700;
        }

        .nav-tabs a.active {
            background: rgba(255, 215, 0, 0.15);
            border-bottom-color: #ffd700;
            color: #ffd700;
        }

        main {
            background: rgba(0, 0, 0, 0.3);
            padding: 40px;
            margin: 30px 20px;
            border-radius: 12px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        h1 {
            color: #ffd700;
            margin-bottom: 30px;
            font-size: 2.5em;
            text-align: center;
        }

        h2 {
            color: #ffd700;
            margin-top: 25px;
            margin-bottom: 15px;
            font-size: 1.8em;
            border-bottom: 2px solid rgba(255, 215, 0, 0.3);
            padding-bottom: 10px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .stat-card {
            background: rgba(255, 215, 0, 0.15);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid #ffd700;
        }

        .stat-card .number {
            font-size: 2.5em;
            font-weight: bold;
            color: #ffd700;
            display: block;
        }

        .stat-card .label {
            color: #b0b0b0;
            margin-top: 10px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .card {
            background: rgba(0, 0, 0, 0.2);
            padding: 25px;
            border-radius: 10px;
            border-left: 4px solid <?php echo $accent_color; ?>;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            background: rgba(255, 215, 0, 0.1);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .card h3 {
            color: #ffd700;
            margin-top: 0;
            margin-bottom: 10px;
        }

        .card p {
            color: #b0b0b0;
            line-height: 1.6;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            overflow: hidden;
        }

        table th {
            background: rgba(255, 215, 0, 0.15);
            padding: 12px;
            text-align: left;
            color: #ffd700;
            font-weight: bold;
            border-bottom: 2px solid rgba(255, 215, 0, 0.3);
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        table tr:hover {
            background: rgba(255, 215, 0, 0.05);
        }

        footer {
            background: rgba(0, 0, 0, 0.5);
            padding: 20px;
            text-align: center;
            border-top: 2px solid #ffd700;
            margin-top: 40px;
            color: #b0b0b0;
        }

        @media (max-width: 768px) {
            main {
                margin: 20px 10px;
                padding: 20px;
            }

            h1 {
                font-size: 1.8em;
            }

            .nav-tabs a {
                padding: 12px 15px;
                font-size: 0.9em;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="institution-name">African Methodist Episcopal Zion University Nimba Extension</div>
        <div class="portal-name"><?php echo $portal_name; ?></div>
    </header>

    <nav class="nav-tabs">
        <a href="portal.php">🏠 Home</a>
        <a href="portal_dashboard.php" class="active">📊 Dashboard</a>
        <a href="portal_account.php">👤 Account</a>
        <a href="portal_contact.php">✉️ Contact</a>
        <a href="logout.php">🚪 Logout</a>
    </nav>

    <main>
        <h1>📊 Dashboard</h1>

        <?php if ($user_type === "student"): ?>
            <h2>Your Academic Dashboard</h2>
            <div class="stats-container">
                <div class="stat-card">
                    <span class="number">4</span>
                    <span class="label">Enrolled Courses</span>
                </div>
                <div class="stat-card">
                    <span class="number">85%</span>
                    <span class="label">Current GPA</span>
                </div>
                <div class="stat-card">
                    <span class="number">3</span>
                    <span class="label">Assignments Due</span>
                </div>
                <div class="stat-card">
                    <span class="number">92</span>
                    <span class="label">Total Credits</span>
                </div>
            </div>

            <h2>🎓 Recent Grades</h2>
            <table>
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Grade</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>CS 101</td>
                        <td>Introduction to Computer Science</td>
                        <td>A</td>
                        <td>✅ Completed</td>
                    </tr>
                    <tr>
                        <td>MATH 201</td>
                        <td>Calculus I</td>
                        <td>B+</td>
                        <td>✅ Completed</td>
                    </tr>
                    <tr>
                        <td>ENG 105</td>
                        <td>English Composition</td>
                        <td>A-</td>
                        <td>✅ Completed</td>
                    </tr>
                    <tr>
                        <td>PHY 102</td>
                        <td>Physics II</td>
                        <td>B</td>
                        <td>⏳ In Progress</td>
                    </tr>
                </tbody>
            </table>

            <h2>📚 Upcoming Assignments</h2>
            <table>
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Assignment</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>CS 101</td>
                        <td>Programming Project</td>
                        <td>2024-05-15</td>
                        <td>⏳ Pending</td>
                    </tr>
                    <tr>
                        <td>MATH 201</td>
                        <td>Problem Set 5</td>
                        <td>2024-05-10</td>
                        <td>⏳ Pending</td>
                    </tr>
                    <tr>
                        <td>PHY 102</td>
                        <td>Lab Report</td>
                        <td>2024-05-20</td>
                        <td>⏳ Pending</td>
                    </tr>
                </tbody>
            </table>

        <?php else: ?>
            <h2>System Administration Dashboard</h2>
            <div class="stats-container">
                <div class="stat-card">
                    <span class="number"><?php echo $total_students; ?></span>
                    <span class="label">Total Students</span>
                </div>
                <div class="stat-card">
                    <span class="number"><?php echo $total_admins; ?></span>
                    <span class="label">Total Administrators</span>
                </div>
                <div class="stat-card">
                    <span class="number"><?php echo $total_contacts; ?></span>
                    <span class="label">Contact Messages</span>
                </div>
                <div class="stat-card">
                    <span class="number">99.8%</span>
                    <span class="label">System Uptime</span>
                </div>
            </div>

            <h2>👥 Recent User Activity</h2>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>User Type</th>
                        <th>Last Login</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>john_student</td>
                        <td>Student</td>
                        <td>Today 10:30 AM</td>
                        <td>✅ Active</td>
                    </tr>
                    <tr>
                        <td>jane_student</td>
                        <td>Student</td>
                        <td>Today 09:15 AM</td>
                        <td>✅ Active</td>
                    </tr>
                    <tr>
                        <td>mike_student</td>
                        <td>Student</td>
                        <td>Yesterday 3:45 PM</td>
                        <td>⚠️ Inactive</td>
                    </tr>
                    <tr>
                        <td>admin_user</td>
                        <td>Admin</td>
                        <td>Today 8:00 AM</td>
                        <td>✅ Active</td>
                    </tr>
                </tbody>
            </table>

            <h2>📊 System Statistics</h2>
            <table>
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Value</th>
                        <th>Trend</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Registered Users</td>
                        <td><?php echo $total_students + $total_admins; ?></td>
                        <td>📈 Up 5%</td>
                    </tr>
                    <tr>
                        <td>Active Courses</td>
                        <td>18</td>
                        <td>➡️ Stable</td>
                    </tr>
                    <tr>
                        <td>Total Enrollments</td>
                        <td>72</td>
                        <td>📈 Up 12%</td>
                    </tr>
                    <tr>
                        <td>Pending Messages</td>
                        <td><?php echo $total_contacts; ?></td>
                        <td>📉 Down 8%</td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2024 African Methodist Episcopal Zion University Nimba Extension</p>
        <p>Last Updated: <?php echo date("Y-m-d H:i:s"); ?></p>
    </footer>
</body>
</html>
