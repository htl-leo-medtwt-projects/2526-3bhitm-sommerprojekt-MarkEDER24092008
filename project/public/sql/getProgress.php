<?php
SESSION_START();

// Include auth check to ensure user is logged in
include("auth_check.php");

// Include database configuration
require_once("dbConfig.php");

// Get user ID from session
$user_id = $_SESSION["user"]["id"];

// Query to get the last attempt time from progress table
$stmt = $conn->prepare("SELECT lastattempt FROM progress WHERE user_id = ? ORDER BY lastattempt DESC LIMIT 1");

if (!$stmt) {
    die(json_encode(array("error" => "Prepare failed: " . $conn->error)));
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $last_attempt = $row["lastattempt"];
    
    // Return the last attempt time as JSON
    echo json_encode(array(
        "success" => true,
        "lastattempt" => $last_attempt,
        "timestamp" => strtotime($last_attempt)
    ));
} else {
    // No previous attempt found
    echo json_encode(array(
        "success" => true,
        "lastattempt" => null,
        "timestamp" => null
    ));
}

$stmt->close();
$conn->close();
?>
