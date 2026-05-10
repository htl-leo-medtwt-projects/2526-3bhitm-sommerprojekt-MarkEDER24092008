<?php
SESSION_START();

// Include auth check to ensure user is logged in
include("auth_check.php");

// Include database configuration
require_once("dbConfig.php");

// Get user ID from session
$user_id = $_SESSION["user"]["id"];

// Query to get the progress data from progress table
$stmt = $conn->prepare("SELECT completed, score, last_attempt FROM progress WHERE user_id = ? ORDER BY last_attempt DESC LIMIT 1");

if (!$stmt) {
    die(json_encode(array("error" => "Prepare failed: " . $conn->error)));
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $completed = $row["completed"];
    $score = $row["score"];
    $last_attempt = $row["last_attempt"];
    
    // Return the progress data as JSON
    echo json_encode(array(
        "success" => true,
        "completed" => (bool)$completed,
        "score" => (int)$score,
        "last_attempt" => $last_attempt,
        "timestamp" => strtotime($last_attempt)
    ));
} else {
    // No previous attempt found
    echo json_encode(array(
        "success" => true,
        "completed" => false,
        "score" => 0,
        "last_attempt" => null,
        "timestamp" => null
    ));
}

$stmt->close();
$conn->close();
?>
