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
        $_username = $conn->real_escape_string($_POST["username"]);
        $_password = $conn->real_escape_string($_POST["password"]);
        $_email = NULL; // Email not collected during signup, users can set it later for password reset
        
        // Validate password strength on server-side
        $passwordValidation = validatePasswordStrength($_password, $_username);
        
        if (!$passwordValidation["valid"]) {
            echo "<div style='color: #dc3545; text-align: center; padding: 20px;'>";
            echo "<h2>❌ Registration Failed</h2>";
            echo "<p><strong>" . htmlspecialchars($passwordValidation["message"]) . "</strong></p>";
            echo "<p>Please go back and try again.</p>";
            echo "<a href='../create_user_form.html' style='color: #a581e8; text-decoration: none;'>Back to Sign Up</a>";
            echo "</div>";
            include("../create_user_form.html");
            exit;
        }

        #create password hash from original password
        #VARCHAR 60 necessary, but officially PHP reccomendation: at least 255 characters
        $_passwortHash = password_hash($_password, PASSWORD_BCRYPT);

        // Handle optional avatar upload
        $avatarUrl = null;
        if (isset($_FILES["avatar"]) && $_FILES["avatar"]["error"] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES["avatar"]["tmp_name"];

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($tmpPath);
            $allowed = [
                "image/jpeg" => "jpg",
                "image/png" => "png",
                "image/gif" => "gif"
            ];

            if (isset($allowed[$mimeType])) {
                $ext = $allowed[$mimeType];

                $uploadDir = __DIR__ . "../user/avatars";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $fileName = "avatar_new_" . uniqid() . "." . $ext;
                $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

                if (move_uploaded_file($tmpPath, $targetPath)) {
                    $avatarUrl = "./user/avatars/" . $fileName;
                }
            }
        }


        //Statement for insert the values of the new user

        $insertStatement = "INSERT INTO user (username, email, password_hash, created_at, streak_count, last_login, xp, language_id, avatar_url) 
                            VALUES ('$_username', NULL, '$_passwortHash', NOW(), 0, NOW(), 0, -1, " . ($avatarUrl ? "'".$avatarUrl."'" : "NULL") . ");";


        if($_res = $conn->query($insertStatement)) {
            $_SESSION['username'] = $_username;
            header("Location: ../language_select.html");
            exit;
        }
            else {
            echo "<br> NO insertion. User could not be added. Maybe user $_username already exists.";
            include ("../create_user_form.html");
        }
    }

    #close database connection
    $conn->close();
?>