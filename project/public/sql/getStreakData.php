<?php
SESSION_START();

// Include auth check to ensure user is logged in
include("auth_check.php");

// Include database configuration
require_once("dbConfig.php");

// Get user ID from session
$user_id = $_SESSION["user"]["id"];

error_log("[getStreakData] User ID: " . $user_id);

// Get current streak count and longest streak from user table
$stmt = $conn->prepare("SELECT streak_count, streak_longest FROM user WHERE id = ?");
if (!$stmt) {
    error_log("[getStreakData] Prepare failed: " . $conn->error);
    die(json_encode(array("error" => "Prepare failed: " . $conn->error)));
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $user_data = $res->fetch_assoc();
    $streak_count = $user_data["streak_count"];
    $streak_longest = $user_data["streak_longest"];
} else {
    error_log("[getStreakData] User not found");
    $streak_count = 0;
    $streak_longest = 0;
}

error_log("[getStreakData] Streak count: " . $streak_count . ", Longest: " . $streak_longest);

// Get today's goal status from streak_log
$today = date("Y-m-d");
$stmt = $conn->prepare("SELECT goal_met FROM streak_log WHERE user_id = ? AND log_date = ?");
if (!$stmt) {
    error_log("[getStreakData] Prepare failed: " . $conn->error);
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

error_log("[getStreakData] Today: " . $today . ", Goal met: " . ($goal_met_today ? "true" : "false"));

// Get the most recent lesson attempt timestamp
$stmt = $conn->prepare("SELECT MAX(last_attempt) as last_attempt FROM progress WHERE user_id = ? AND completed = 1");
if (!$stmt) {
    error_log("[getStreakData] Prepare failed: " . $conn->error);
    die(json_encode(array("error" => "Prepare failed: " . $conn->error)));
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$last_attempt_timestamp = null;
if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    if ($row["last_attempt"]) {
        $last_attempt_timestamp = strtotime($row["last_attempt"]);
    }
}

// Return the streak data as JSON
$response = array(
    "success" => true,
    "streak_count" => (int)$streak_count,
    "streak_longest" => (int)$streak_longest,
    "goal_met_today" => $goal_met_today,
    "last_attempt_timestamp" => $last_attempt_timestamp,
    "today" => $today
);

error_log("[getStreakData] Response: " . json_encode($response));

echo json_encode($response);

$stmt->close();
$conn->close();
?>
