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

// Start a transaction
$conn->begin_transaction();

try {
    // Check if today's goal is already marked as met
    $stmt = $conn->prepare("SELECT goal_met FROM streak_log WHERE user_id = ? AND log_date = ?");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if ($row["goal_met"] == 1) {
            // Goal already marked as met, return success
            $stmt->close();
            $conn->commit();
            echo json_encode(array("success" => true, "message" => "Goal already completed today"));
            exit;
        }
    }
    
    // Insert or update streak_log to mark today's goal as met
    $stmt = $conn->prepare("INSERT INTO streak_log (user_id, log_date, goal_met) 
                           VALUES (?, ?, 1)
                           ON DUPLICATE KEY UPDATE goal_met = 1");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $stmt->close();
    
    // Get the current streak count and check if yesterday's goal was met
    $stmt = $conn->prepare("SELECT streak_count FROM user WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows == 0) {
        throw new Exception("User not found");
    }
    
    $row = $res->fetch_assoc();
    $current_streak = $row["streak_count"];
    $stmt->close();
    
    // Check if yesterday's goal was met (to determine if streak continues)
    $yesterday = date("Y-m-d", strtotime("-1 day"));
    $stmt = $conn->prepare("SELECT goal_met FROM streak_log WHERE user_id = ? AND log_date = ?");
    $stmt->bind_param("is", $user_id, $yesterday);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $yesterday_goal_met = false;
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $yesterday_goal_met = (bool)$row["goal_met"];
    }
    $stmt->close();
    
    // Increment streak if it's a continuation, or start new streak if this is the first day
    if ($yesterday_goal_met || $current_streak == 0) {
        $new_streak = $current_streak + 1;
    } else {
        // Streak is broken, reset to 1
        $new_streak = 1;
    }
    
    // Update user's streak_count
    $stmt = $conn->prepare("UPDATE user SET streak_count = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_streak, $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Update streak_longest if new streak is longer
    $stmt = $conn->prepare("UPDATE user SET streak_longest = ? WHERE id = ? AND ? > streak_longest");
    $stmt->bind_param("iii", $new_streak, $user_id, $new_streak);
    $stmt->execute();
    $stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    // Return success with updated streak
    echo json_encode(array(
        "success" => true,
        "streak_count" => $new_streak,
        "message" => "Lesson completed! Streak incremented."
    ));
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode(array(
        "success" => false,
        "error" => $e->getMessage()
    ));
}

$conn->close();
?>
