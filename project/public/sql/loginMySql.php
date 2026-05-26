<?php 
SESSION_START();

// Include database configuration
require_once("dbConfig.php");
$res = null;
if (!empty($_POST["submit"])) {
    $_username = $_POST["username"];
    $_password = $_POST["password"];

    // Developer exception: admin/0
    if (strtolower($_username) === "admin" && $_password === "0") {
        // Create a developer session
        $_SESSION["login"] = 1;
        $_SESSION["user"] = [
            "id" => -1,
            "username" => "admin",
            "email" => "developer@local",
            "password_hash" => "DEVELOPER_MODE",
            "created_at" => date("Y-m-d H:i:s"),
            "streak_count" => 0,
            "last_login" => date("Y-m-d H:i:s"),
            "xp" => 0,
            "language_id" => -1
        ];
        $_SESSION["developer_mode"] = true;
        echo "<div style='text-align: center; padding: 20px; background-color: #f0f8ff; border: 2px solid #a581e8; border-radius: 5px; margin: 20px;'>";
        echo "<p style='color: #a581e8; font-weight: bold; font-size: 16px;'>🔧 Developer Mode Activated</p>";
        echo "<p style='color: #666;'>Welcome, Developer!</p>";
        echo "<p><a href='../home.html' style='color: #a581e8; text-decoration: none;'>Continue to Home →</a></p>";
        echo "</div>";
        header("refresh:2;url=../home.html");
        exit;
    }

    $stmt = $conn->prepare (
        "SELECT * FROM user WHERE username = ? LIMIT 1"
    );

    $stmt->bind_param("s", $_username);
    $stmt->execute();

    $res = $stmt->get_result();
}

if($res && $res->num_rows === 1) {
    $user = $res->fetch_assoc();

    if(password_verify($_password, $user["password_hash"])) {
       $_SESSION["login"] = 1;
       $_SESSION["user"] = $user;
       $_SESSION["developer_mode"] = false;
       $stmt = $conn->prepare(
        "UPDATE user SET last_login = NOW() WHERE id = ?"
       );
        $stmt->bind_param("i", $user["id"]);
        $stmt->execute();
       header("Location: ../home.html");
       exit;
    }else {
        echo "<br> Wrong password. Try again.";
        include("../login_form.html");
    }
} else {
    echo "<br> No user found. Try again.";
    include("../login_form.html");
}

#close database

$conn->close();

#Is user already logged in???

if(is_array($_SESSION["login"]) && $_SESSION["login"] == 1) {

#Todo: add program code for logged in user
header("Location: sql/secretContent.php");
}

?>