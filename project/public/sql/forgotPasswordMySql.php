<?php
SESSION_START();

// Include database configuration
require_once("dbConfig.php");

if(!empty($_POST["submit"])) {
    $_email = $conn->real_escape_string($_POST["email"]);

    // Check if email exists in database
    $stmt = $conn->prepare("SELECT id, username FROM user WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Generate a secure reset token
        $resetToken = bin2hex(random_bytes(32));
        $tokenExpiry = date("Y-m-d H:i:s", strtotime("+1 hour")); // Token valid for 1 hour

        // Store reset token in database
        $updateStmt = $conn->prepare("UPDATE user SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
        $updateStmt->bind_param("ssi", $resetToken, $tokenExpiry, $user["id"]);
        $updateStmt->execute();
        $updateStmt->close();

        // Create reset link
        $resetLink = "http://localhost/Sommerprojekt/2526-3bhitm-sommerprojekt-MarkEDER24092008/project/public/reset_password.html?token=" . urlencode($resetToken);

        // Prepare email
        $subject = "IndiGO - Password Reset Request";
        $message = "
        <html>
            <head>
                <title>IndiGO Password Reset</title>
            </head>
            <body style='font-family: Arial, sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #a581e8;'>Password Reset Request</h2>
                    <p>Hi " . htmlspecialchars($user["username"]) . ",</p>
                    <p>We received a request to reset your IndiGO password. Click the button below to proceed:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . $resetLink . "' style='display: inline-block; padding: 12px 30px; background-color: #a581e8; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>Reset Password</a>
                    </div>
                    <p style='color: #666; font-size: 12px;'>This link will expire in 1 hour. If you didn't request this, please ignore this email.</p>
                    <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                    <p style='color: #999; font-size: 12px;'>© 2026 IndiGO. All rights reserved.</p>
                </div>
            </body>
        </html>
        ";

        // Set email headers for HTML
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
        $headers .= "From: noreply@indigo.local" . "\r\n";

        // Send email
        if(mail($_email, $subject, $message, $headers)) {
            echo "<div style='text-align: center; padding: 40px 20px;'>";
            echo "<h2 style='color: #28a745;'>✓ Email Sent!</h2>";
            echo "<p>Check your email for instructions to reset your password.</p>";
            echo "<p style='color: #666; font-size: 14px; margin-top: 20px;'>The reset link will expire in 1 hour.</p>";
            echo "<a href='./login_form.html' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #a581e8; color: white; text-decoration: none; border-radius: 5px;'>Back to Login</a>";
            echo "</div>";
        } else {
            echo "<div style='text-align: center; padding: 40px 20px;'>";
            echo "<h2 style='color: #dc3545;'>❌ Error Sending Email</h2>";
            echo "<p>There was an issue sending the reset email. Please try again later.</p>";
            echo "<a href='./forgot_password.html' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #a581e8; color: white; text-decoration: none; border-radius: 5px;'>Try Again</a>";
            echo "</div>";
        }
    } else {
        // Email doesn't exist - for security, we still show success message (prevent email enumeration)
        echo "<div style='text-align: center; padding: 40px 20px;'>";
        echo "<h2 style='color: #28a745;'>✓ Email Sent!</h2>";
        echo "<p>If an account exists with this email, you'll receive reset instructions.</p>";
        echo "<p style='color: #666; font-size: 14px; margin-top: 20px;'>Check your email and spam folder.</p>";
        echo "<a href='./login_form.html' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #a581e8; color: white; text-decoration: none; border-radius: 5px;'>Back to Login</a>";
        echo "</div>";
    }

    $stmt->close();
}

$conn->close();
?>
