<?php 
SESSION_START();

// Include database configuration
require_once("dbConfig.php");

if(!empty($_POST["submit"])) {
        $_username = $conn-> real_escape_string($_POST["username"]);
        $_password = $conn-> real_escape_string($_POST["password"]);
        $_email = htmlspecialchars($conn-> real_escape_string($_POST["email"]));
        // if(strcmp($_password, $conn->real_escape_string($_POST["password2"])) != 0) {
        //     #password is not repeated correctly
        //     include("create_user_form.html");
        //     exit;
        // }
        #create password hash from original password
        #VARCHAR 60 necessary, but officially PHP reccomendation: at least 255 characters
        $_passwortHash = password_hash($_password, PASSWORD_BCRYPT);

        #Statement for insert the values of the new user

        $insertStatement = "INSERT INTO user (username, email, password, created_at, streak_count, last_login, xp, language_id) 
                            VALUES ('$_username','$_email', '$_passwortHash', NOW(), 0, NOW(), 0, 0);";   

        if($_res = $conn->query($insertStatement)) {
            echo "<br>USER $_username has been added to the database. <br> Try to log in.";
            include("../login_form.html");
        }
            else {
            echo "<br> NO insertion. User could nor be added. Maybe user $_username aleady exists.";
            include ("../create_user_form.html");
        }
    }

    #close database connection
    $conn->close();
?>