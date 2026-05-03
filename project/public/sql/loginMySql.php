<?php 
SESSION_START();

// Include database configuration
require_once("dbConfig.php");

if (!empty($_POST["submit"])) {
    $_username = $_POST["username"];
    $_password = $_POST["password"];

    $stmt = $conn->prepare (
        "SELECT * FROM user WHERE username = ? LIMIT 1"
    );

    $stmt->bind_param("s", $_username);
    $stmt->execute();

    $res = $stmt->get_result();
}

if($res->num_rows === 1) {
    $user = $res->fetch_assoc();

    if(password_verify($_password, $user["password"])) {
       $_SESSION["login"] = 1;
       $_SESSION["user"] = $user;
       $stmt = $conn->prepare(
        "UPDATE user SET last_login = NOW() WHERE id = ?"
       );
        $stmt->bind_param("i", $user["id"]);
        $stmt->execute();
       header("Location: sql/secretContent.php");
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