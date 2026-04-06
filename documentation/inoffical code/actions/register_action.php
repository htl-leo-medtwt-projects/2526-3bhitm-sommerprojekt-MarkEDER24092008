<?php
require_once "../config/database.php";

$username = $_POST["username"];
$email = $_POST["email"];
$password = $_POST["password"];

// Passwort sichern
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO user (username, email, password_hash) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $email, $hash);

if ($stmt->execute()) {
    header("Location: ../public/login.php?registered=1");
} else {
    header("Location: ../public/register.php?error=1");
}

exit;
?>