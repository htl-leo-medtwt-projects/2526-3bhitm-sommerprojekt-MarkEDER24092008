<?php
SESSION_START();

// Include database configuration
require_once("dbConfig.php");

if(!empty($_POST["language_id"]) && !empty($_SESSION["username"])) {
    $_username = $_SESSION["username"];
    $_language_id = intval($_POST["language_id"]);

    // Update the user's language_id
    $updateStatement = "UPDATE user SET language_id = ? WHERE username = ?";
    
    $stmt = $conn->prepare($updateStatement);
    $stmt->bind_param("is", $_language_id, $_username);
    
    if($stmt->execute()) {
        // Clear the session username and redirect to login
        unset($_SESSION["username"]);
        header("Location: ../login_form.html");
        exit;
    } else {
        echo "<br> Error updating language preference. Please try again.";
        include("../language_select.html");
    }
} else {
    // Missing data, redirect back to language selection
    header("Location: ../language_select.html");
    exit;
}

$conn->close();
?>
