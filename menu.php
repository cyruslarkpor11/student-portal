<?php
$loggedIn = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
$currentPage = basename($_SERVER["PHP_SELF"] ?? "");
$dashboardLink = "student_portal.php";
if ($loggedIn) {
    if (($_SESSION["user_type"] ?? "") === "admin" || ($_SESSION["user_type"] ?? "") === "administration") {
        $dashboardLink = "admin_portal.php";
    } elseif (($_SESSION["user_type"] ?? "") === "instructor") {
        $dashboardLink = "instructor_portal.php";
    }
}
$accountLink = "portal_account.php";
$guestContactLink = "contact.php";
$memberContactLink = "portal_contact.php";
?>
<nav style="background: rgba(0,0,0,0.4); padding: 18px 20px; text-align: center; border-bottom: 2px solid rgba(255,255,255,0.2);">
    <div style="margin-bottom: 10px; font-size: 1.2em; font-weight: bold; color: #ffd700;">African Methodist Episcopal Zion University Nimba Extension</div>

    <a href="index.php" style="color: white; text-decoration: none; margin: 0 15px; font-weight: bold; <?php echo $currentPage === 'index.php' ? 'color:#ffd700;' : ''; ?>">Home</a>

    <?php if ($loggedIn): ?>
        <a href="<?php echo $dashboardLink; ?>" style="color: white; text-decoration: none; margin: 0 15px; font-weight: bold;">Dashboard</a>
        <a href="<?php echo $accountLink; ?>" style="color: white; text-decoration: none; margin: 0 15px; font-weight: bold;">Account</a>
        <a href="<?php echo $memberContactLink; ?>" style="color: white; text-decoration: none; margin: 0 15px; font-weight: bold;">Contact</a>
        <a href="terms.php" target="_blank" rel="noopener noreferrer" style="color: white; text-decoration: none; margin: 0 15px; font-weight: bold; <?php echo $currentPage === 'terms.php' ? 'color:#ffd700;' : ''; ?>">Terms</a>
        <a href="privacy.php" target="_blank" rel="noopener noreferrer" style="color: white; text-decoration: none; margin: 0 15px; font-weight: bold; <?php echo $currentPage === 'privacy.php' ? 'color:#ffd700;' : ''; ?>">Privacy</a>
        <a href="logout.php" style="color: white; text-decoration: none; margin: 0 15px; font-weight: bold;">Logout</a>
    <?php else: ?>
        <a href="<?php echo $guestContactLink; ?>" style="color: white; text-decoration: none; margin: 0 15px; font-weight: bold; <?php echo $currentPage === 'contact.php' ? 'color:#ffd700;' : ''; ?>">Contact</a>
        <a href="terms.php" target="_blank" rel="noopener noreferrer" style="color: white; text-decoration: none; margin: 0 15px; font-weight: bold; <?php echo $currentPage === 'terms.php' ? 'color:#ffd700;' : ''; ?>">Terms</a>
        <a href="privacy.php" target="_blank" rel="noopener noreferrer" style="color: white; text-decoration: none; margin: 0 15px; font-weight: bold; <?php echo $currentPage === 'privacy.php' ? 'color:#ffd700;' : ''; ?>">Privacy</a>
    <?php endif; ?>
</nav>
