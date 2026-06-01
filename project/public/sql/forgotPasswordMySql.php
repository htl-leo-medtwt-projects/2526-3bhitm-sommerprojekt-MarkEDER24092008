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

        // Create reset link dynamically based on current server
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $domain = $_SERVER['HTTP_HOST'];
        $basePath = '/Sommerprojekt/2526-3bhitm-sommerprojekt-MarkEDER24092008/project/public';
        $resetLink = $protocol . "://" . $domain . $basePath . "/reset_password.html?token=" . urlencode($resetToken);

        // Prepare email with dynamic reset link
        $subject = "IndiGO - Password Reset Request";
        $message = "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>IndiGO - Password Reset</title>
</head>
<body style='font-family: Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 0;'>
    <div style='background-color: #f5f5f5; padding: 20px;'>
        <div style='max-width: 600px; margin: 0 auto; background-color: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); overflow: hidden;'>
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #a581e8 0%, #8a6bc8 100%); padding: 30px 20px; text-align: center;'>
                <h1 style='margin: 0; color: white; font-size: 32px; font-weight: bold;'>IndiGO</h1>
                <p style='margin: 10px 0 0 0; color: rgba(255, 255, 255, 0.9); font-size: 14px;'>Password Reset Request</p>
            </div>
            <!-- Content -->
            <div style='padding: 40px 30px;'>
                <p style='margin: 0 0 20px 0; color: #333; font-size: 16px; line-height: 1.6;'>Hi " . htmlspecialchars($user["username"]) . ",</p>
                <p style='margin: 0 0 20px 0; color: #555; font-size: 14px; line-height: 1.6;'>We received a request to reset your IndiGO password. Click the button below to proceed:</p>
                <!-- Button -->
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='" . $resetLink . "' style='display: inline-block; padding: 14px 40px; background-color: #a581e8; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px;'>Reset Password</a>
                </div>
                <p style='margin: 30px 0 10px 0; color: #999; font-size: 12px;'>Or copy this link in your browser:</p>
                <p style='margin: 0 0 30px 0; color: #a581e8; font-size: 11px; word-break: break-all; background-color: #f9f9f9; padding: 10px; border-radius: 4px;'>" . $resetLink . "</p>
                <!-- Security Info -->
                <div style='background-color: #f0f7ff; border-left: 4px solid #a581e8; padding: 15px; margin: 30px 0; border-radius: 4px;'>
                    <p style='margin: 0; color: #333; font-size: 13px; line-height: 1.6;'><strong>Security Note:</strong> This link will expire in <strong>1 hour</strong>. For your safety, never share this link with anyone.</p>
                </div>
            </div>
            <!-- Footer -->
            <div style='background-color: #f9f9f9; padding: 20px; text-align: center; border-top: 1px solid #eee;'>
                <p style='margin: 0 0 10px 0; color: #999; font-size: 12px;'>© 2026 IndiGO. All rights reserved.</p>
                <p style='margin: 0; color: #bbb; font-size: 11px;'>This is an automated message. Please do not reply to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>";

        // Send email via Mailtrap API
        $mailtrap_api_token = "bfdbd4784bb7158edd6603434d030074";
        
        $payload = [
            "from" => ["email" => "noreply@indigo.local", "name" => "IndiGO"],
            "to" => [["email" => $_email]],
            "subject" => $subject,
            "html" => $message
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://send.mailtrap.io/api/send",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $mailtrap_api_token,
                "Content-Type: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        // Log for debugging
        error_log("Mailtrap Response Code: " . $http_code);
        error_log("Mailtrap Response: " . $response);
        if($curl_error) {
            error_log("Curl Error: " . $curl_error);
        }
        
        // Check if email was sent successfully (Mailtrap returns 200 or 201)
        if(($http_code == 200 || $http_code == 201) && !$curl_error) {
            echo "<div style='text-align: center; padding: 40px 20px;'>";
            echo "<h2 style='color: #28a745;'>✓ Email Sent!</h2>";
            echo "<p>Check your email for instructions to reset your password.</p>";
            echo "<p style='color: #666; font-size: 14px; margin-top: 20px;'>The reset link will expire in 1 hour.</p>";
            echo "<a href='../login_form.html' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #a581e8; color: white; text-decoration: none; border-radius: 5px;'>Back to Login</a>";
            echo "</div>";
        } else {
            echo "<div style='text-align: center; padding: 40px 20px;'>";
            echo "<h2 style='color: #dc3545;'>❌ Error Sending Email (Code: " . $http_code . ")</h2>";
            echo "<p>There was an issue sending the reset email. Please try again later.</p>";
            echo "<p style='color: #999; font-size: 12px;'>Debug: " . htmlspecialchars($response) . "</p>";
            echo "<a href='./forgot_password.html' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #a581e8; color: white; text-decoration: none; border-radius: 5px;'>Try Again</a>";
            echo "</div>";
        }
    } else {
        // Email doesn't exist - for security, we still show success message (prevent email enumeration)
        echo "<div style='text-align: center; padding: 40px 20px;'>";
        echo "<h2 style='color: #28a745;'>✓ Email Sent!</h2>";
        echo "<p>If an account exists with this email, you'll receive reset instructions.</p>";
        echo "<p style='color: #666; font-size: 14px; margin-top: 20px;'>Check your email and spam folder.</p>";
        echo "<a href='../login_form.html' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #a581e8; color: white; text-decoration: none; border-radius: 5px;'>Back to Login</a>";
        echo "</div>";
    }

    $stmt->close();
}

$conn->close();
?>
