<?php 
require_once "auth_check.php";

$username = htmlspecialchars($_SESSION["user"]["username"], ENT_QUOTES, "UTF-8");

?>

<h1>Secret content</h1>
<p>Welcome <?php echo $username; ?>, you are logged in and can see this secret content.</p>

<p> 
    <a href="logout.php"> Logout</a>
</p>