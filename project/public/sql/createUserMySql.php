<?php 
#open database connection
$conn = new mysqli("_db_host", "_db_username", "_db_passwort", "_db_datenbank");

// check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
    }

    if(!empty($_POST["submit"])) {
        $_username = $conn-> real_escape_string($_POST["username"]);
        $_password = $conn-> real_escape_string($_POST["password"]);
        if(strcmp($_passwort, $conn->real_escape_string($_POST["password2"])) != 0) {
            #password is not repeated correctly
            include("create_user_form.html");
            exit;
        }
        #create password hash from original password
        #VARCHAR 60 necessary, but officially PHP reccomendation: at least 255 characters
        $_passwortHash = password_hash($_passwort, PASSWORD_BCRYPT);

        #Statement for insert the values of the new user

        $insertStatement = "INSERT INTO login_username (username, password, user_deleted, last_login) 
                            VALUES ('$_username', '$_passwortHash', 0, NOW());";   

        if($_res = $conn->query($insertStatement)) {
            echo "<br>USER $_username has been added to the database. <br> Try to log in.";
            include("login_form.html");
        }
            else {
            echo "<br> NO insertion. User could nor be added. Maybe user $_username aleady exists.";
            include ("create_user_form.html");
        }
    }

    #close database connection
    $conn->close();
?>