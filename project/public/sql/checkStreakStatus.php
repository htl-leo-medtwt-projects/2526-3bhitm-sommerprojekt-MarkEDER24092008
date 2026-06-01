<?php
SESSION_START();

// Include auth check to ensure user is logged in
include("auth_check.php");

// Include database configuration
require_once("dbConfig.php");

// Get user ID from session
$user_id = $_SESSION["user"]["id"];

// Get today's date
$today = date("Y-m-d");

// Check if yesterday's goal was met (if streak should be broken)
$yesterday = date("Y-m-d", strtotime("-1 day"));
$stmt = $conn->prepare("SELECT goal_met FROM streak_log WHERE user_id = ? AND log_date = ?");
if (!$stmt) {
    die(json_encode(array("error" => "Prepare failed: " . $conn->error)));
}

$stmt->bind_param("is", $user_id, $yesterday);
$stmt->execute();
$res = $stmt->get_result();

$streak_active = true; // Default: streak is active

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $streak_active = (bool)$row["goal_met"];
} else {
    // No entry for yesterday means new user or first day, consider streak active
    $streak_active = true;
}

// If streak is broken, reset streak_count to 0
if (!$streak_active) {
    $stmt = $conn->prepare("UPDATE user SET streak_count = 0 WHERE id = ?");
    if (!$stmt) {
        die(json_encode(array("error" => "Prepare failed: " . $conn->error)));
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

// Check today's goal status
$stmt = $conn->prepare("SELECT goal_met FROM streak_log WHERE user_id = ? AND log_date = ?");
if (!$stmt) {
    die(json_encode(array("error" => "Prepare failed: " . $conn->error)));
}

$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$res = $stmt->get_result();

$goal_met_today = false;
if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $goal_met_today = (bool)$row["goal_met"];
}

// Return the streak status
echo json_encode(array(
    "success" => true,
    "streak_active" => $streak_active,
    "goal_met_today" => $goal_met_today,
    "today" => $today
));

$stmt->close();
$conn->close();
?>
