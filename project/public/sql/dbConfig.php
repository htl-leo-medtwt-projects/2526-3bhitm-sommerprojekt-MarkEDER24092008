<?php
// Database connection configuration

$_db_host = "db_server";
$_db_datenbank = "IndiGo";
$_db_username = "super-root";
$_db_passwort = "000";

// Open database connection
$conn = new mysqli($_db_host, $_db_username, $_db_passwort, $_db_datenbank);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
