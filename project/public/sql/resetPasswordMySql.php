<?php
SESSION_START();

// Include database configuration
require_once("dbConfig.php");

/**
 * Validate password strength server-side
 * Matches JavaScript validation for consistency
 */
function validatePasswordStrength($password, $username) {
    // Developer exception: admin/0
    if (strtolower($username) === "admin" && $password === "0") {
        return ["valid" => true, "message" => "Developer bypass accepted", "isDeveloperMode" => true];
    }

    // Criteria checks
    $criteria = [
        "length" => strlen($password) >= 8,
        "uppercase" => preg_match("/[A-Z]/", $password),
        "lowercase" => preg_match("/[a-z]/", $password),
        "numbers" => preg_match("/[0-9]/", $password),
        "special" => preg_match("/[!@#$%^&*()_+\-=\[\]{};':\"\\|,.<>\/?]/", $password)
    ];

    // Count met criteria
    $metCriteria = array_sum($criteria);

    // Require all 5 criteria to be met
    if ($metCriteria < 5) {
        return [
            "valid" => false,
            "message" => "Password must contain: 8+ characters, uppercase, lowercase, number, and special character"
        ];
    }

    return ["valid" => true, "message" => "Password is strong"];
}

if(!empty($_POST["submit"])) {
    $_token = $conn->real_escape_string($_POST["token"]);
    $_password = $conn->real_escape_string($_POST["password"]);

    // Verify token is valid and not expired
    $stmt = $conn->prepare("SELECT id, username FROM user WHERE reset_token = ? AND reset_token_expiry > NOW() LIMIT 1");
    $stmt->bind_param("s", $_token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        echo "<div style='text-align: center; padding: 40px 20px;'>";
        echo "<h2 style='color: #dc3545;'>❌ Reset Link Expired</h2>";
        echo "<p>The reset link has expired or is invalid.</p>";
        echo "<a href='./forgot_password.html' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #a581e8; color: white; text-decoration: none; border-radius: 5px;'>Request New Link</a>";
        echo "</div>";
        $conn->close();
        exit;
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // Validate password strength
    $passwordValidation = validatePasswordStrength($_password, $user["username"]);
    
    if (!$passwordValidation["valid"]) {
        echo "<div style='color: #dc3545; text-align: center; padding: 20px;'>";
        echo "<h2>❌ Password Too Weak</h2>";
        echo "<p><strong>" . htmlspecialchars($passwordValidation["message"]) . "</strong></p>";
        echo "<p><a href='javascript:history.back()' style='color: #a581e8; text-decoration: none;'>Go Back</a></p>";
        echo "</div>";
        $conn->close();
        exit;
    }

    // Hash the new password
    $_passwordHash = password_hash($_password, PASSWORD_BCRYPT);

    // Update password and clear reset token
    $updateStmt = $conn->prepare("UPDATE user SET password_hash = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
    $updateStmt->bind_param("si", $_passwordHash, $user["id"]);
    
    if ($updateStmt->execute()) {
        echo "<div style='text-align: center; padding: 40px 20px;'>";
        echo "<h2 style='color: #28a745;'>✓ Password Reset Successful!</h2>";
        echo "<p>Your password has been updated.</p>";
        echo "<a href='./login_form.html' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #a581e8; color: white; text-decoration: none; border-radius: 5px;'>Go to Login</a>";
        echo "</div>";
    } else {
        echo "<div style='text-align: center; padding: 40px 20px;'>";
        echo "<h2 style='color: #dc3545;'>❌ Error</h2>";
        echo "<p>There was an error resetting your password. Please try again.</p>";
        echo "<a href='./forgot_password.html' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #a581e8; color: white; text-decoration: none; border-radius: 5px;'>Try Again</a>";
        echo "</div>";
    }

    $updateStmt->close();
}

$conn->close();
?>
