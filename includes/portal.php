<?php
session_start();
require "includes/db.php";

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: index.php");
    exit();
}

$user_type = $_SESSION["user_type"] ?? null;
$username = $_SESSION["username"] ?? null;

// Fetch user data
$stmt = $conn->prepare("SELECT username, email, user_type FROM users WHERE username = ? AND user_type = ?");
$stmt->execute([$username, $user_type]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: logout.php");
    exit();
}

// Get admin statistics if admin
$total_students = 0;
$total_admins = 0;
$total_contacts = 0;
if ($user_type === "admin") {
    $total_students = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'student'")->fetch()["count"];
    $total_admins = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'admin'")->fetch()["count"];
    $total_contacts = $conn->query("SELECT COUNT(*) as count FROM contacts")->fetch()["count"] ?? 0;
}

// Determine background image based on user type
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
    <title><?php echo $portal_name; ?> - African Methodist Episcopal Zion University Nimba Extension</title>
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

        /* Header */
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

        /* Navigation Tabs */
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

        /* Main Content */
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

        /* User Info Box */
        .user-info-box {
            background: rgba(255, 215, 0, 0.1);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 4px solid #ffd700;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            margin: 12px 0;
            padding: 8px 0;
        }

        .info-label {
            font-weight: bold;
            color: #ffd700;
        }

        .info-value {
            color: #e0e0e0;
        }

        /* Dashboard Grid */
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
            cursor: pointer;
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

        /* Stats Container */
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

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 12px 25px;
            margin: 10px 10px 10px 0;
            background: <?php echo $accent_color; ?>;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .btn.secondary {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid white;
        }

        .btn.secondary:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Footer */
        footer {
            background: rgba(0, 0, 0, 0.5);
            padding: 20px;
            text-align: center;
            border-top: 2px solid #ffd700;
            margin-top: 40px;
            color: #b0b0b0;
        }

        footer p {
            margin: 5px 0;
        }

        /* Responsive */
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

        .welcome-message {
            background: rgba(255, 215, 0, 0.1);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #ffd700;
            font-size: 1.2em;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="institution-name">African Methodist Episcopal Zion University Nimba Extension</div>
        <div class="portal-name"><?php echo $portal_name; ?></div>
    </header>

    <!-- Navigation Tabs -->
    <nav class="nav-tabs">
        <a href="portal.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'portal.php' ? 'active' : ''; ?>">🏠 Home</a>
        <a href="portal_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'portal_dashboard.php' ? 'active' : ''; ?>">📊 Dashboard</a>
        <a href="portal_account.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'portal_account.php' ? 'active' : ''; ?>">👤 Account</a>
        <a href="portal_contact.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'portal_contact.php' ? 'active' : ''; ?>">✉️ Contact</a>
        <a href="logout.php">🚪 Logout</a>
    </nav>

    <!-- Main Content -->
    <main>
        <h1><?php echo $portal_name; ?></h1>
        
        <div class="welcome-message">
            👋 Welcome, <strong><?php echo htmlspecialchars($user["username"]); ?></strong>! 
            You are logged in as a <strong><?php echo ucfirst($user_type); ?></strong>.
        </div>

        <div class="user-info-box">
            <div class="info-item">
                <span class="info-label">Username:</span>
                <span class="info-value"><?php echo htmlspecialchars($user["username"]); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Email:</span>
                <span class="info-value"><?php echo htmlspecialchars($user["email"]); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Account Type:</span>
                <span class="info-value"><?php echo ucfirst($user["user_type"]); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Status:</span>
                <span class="info-value">✅ Active</span>
            </div>
        </div>

        <?php if ($user_type === "student"): ?>
            <!-- STUDENT PORTAL HOME -->
            <h2>📚 Student Dashboard Overview</h2>
            <div class="stats-container">
                <div class="stat-card">
                    <span class="number">4</span>
                    <span class="label">Enrolled Courses</span>
                </div>
                <div class="stat-card">
                    <span class="number">85%</span>
                    <span class="label">Average Grade</span>
                </div>
                <div class="stat-card">
                    <span class="number">3</span>
                    <span class="label">Pending Assignments</span>
                </div>
                <div class="stat-card">
                    <span class="number">12</span>
                    <span class="label">Unread Messages</span>
                </div>
            </div>

            <h2>📖 Available Student Services</h2>
            <div class="dashboard-grid">
                <div class="card">
                    <h3>📚 My Courses</h3>
                    <p>View and manage all your enrolled courses, access lecture materials, and track progress.</p>
                </div>
                <div class="card">
                    <h3>✏️ Assignments</h3>
                    <p>Submit assignments, check deadlines, and track your assignment grades.</p>
                </div>
                <div class="card">
                    <h3>📊 My Grades</h3>
                    <p>View your grades, academic performance, and GPA information.</p>
                </div>
                <div class="card">
                    <h3>📖 Course Resources</h3>
                    <p>Access textbooks, lecture notes, and supplementary course materials.</p>
                </div>
                <div class="card">
                    <h3>💬 Messages</h3>
                    <p>Communicate with instructors and classmates through the messaging system.</p>
                </div>
                <div class="card">
                    <h3>🎓 Transcripts</h3>
                    <p>Download official academic transcripts and grade reports.</p>
                </div>
            </div>

            <div style="margin-top: 30px;">
                <a href="portal_dashboard.php" class="btn">Go to Dashboard →</a>
                <a href="portal_account.php" class="btn secondary">Manage Account →</a>
            </div>

        <?php else: ?>
            <!-- ADMIN PORTAL HOME -->
            <h2>🔐 Admin System Overview</h2>
            <div class="stats-container">
                <div class="stat-card">
                    <span class="number"><?php echo $total_students; ?></span>
                    <span class="label">Total Students</span>
                </div>
                <div class="stat-card">
                    <span class="number"><?php echo $total_admins; ?></span>
                    <span class="label">Total Admins</span>
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

            <h2>⚙️ Admin Management Tools</h2>
            <div class="dashboard-grid">
                <div class="card">
                    <h3>👥 User Management</h3>
                    <p>Add, edit, deactivate, and manage student and admin accounts.</p>
                </div>
                <div class="card">
                    <h3>📋 View Reports</h3>
                    <p>Generate and analyze system reports and user statistics.</p>
                </div>
                <div class="card">
                    <h3>🏫 Course Administration</h3>
                    <p>Create, update, and manage courses and course content.</p>
                </div>
                <div class="card">
                    <h3>🔧 System Settings</h3>
                    <p>Configure system parameters and institutional settings.</p>
                </div>
                <div class="card">
                    <h3>🔐 Security & Permissions</h3>
                    <p>Manage user roles, permissions, and security policies.</p>
                </div>
                <div class="card">
                    <h3>📧 Communication</h3>
                    <p>Send announcements and manage system-wide communications.</p>
                </div>
            </div>

            <div style="margin-top: 30px;">
                <a href="portal_dashboard.php" class="btn">Go to Dashboard →</a>
                <a href="manage_users.php" class="btn secondary">Manage Users →</a>
            </div>
        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 African Methodist Episcopal Zion University Nimba Extension</p>
        <p>Last Updated: <?php echo date("Y-m-d H:i:s"); ?></p>
    </footer>
</body>
</html>
