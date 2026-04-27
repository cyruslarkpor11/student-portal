<?php
session_start();
require "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars($_POST["username"]);
    $password = $_POST["password"];

    // Prepare statement to get user
    $stmt = $conn->prepare("SELECT id, password, user_type FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["loggedin"] = true;
        $_SESSION["username"] = $username;
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["user_type"] = $user["user_type"];

        if ($user["user_type"] === "admin" || $user["user_type"] === "administration") {
            header("Location: ../admin_portal.php");
        } elseif ($user["user_type"] === "instructor") {
            header("Location: ../instructor_portal.php");
        } else {
            header("Location: ../student_portal.php");
        }
        exit();
    } else {
        header("Location: ../index.php?login=failed");
        exit();
    }
}
?>
