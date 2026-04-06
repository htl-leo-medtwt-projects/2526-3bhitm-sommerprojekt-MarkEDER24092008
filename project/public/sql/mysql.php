<?php

$host = "db_server";
$user = "admin";       // ggf. anpassen
$pass = "123";           // ggf. anpassen
$db   = "IndiGo";

// 2. Verbindung erstellen (mysqli Objekt)
$conn = new mysqli($host, $user, $pass, $db);

// 3. Verbindung prüfen
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// jetzt kannst du $conn überall nutzen
?>