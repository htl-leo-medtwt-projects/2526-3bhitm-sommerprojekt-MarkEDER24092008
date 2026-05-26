<?php
require_once("dbConfig.php");

// Get JSON input
$input = json_decode(file_get_contents("php://input"), true);
$token = isset($input["token"]) ? $input["token"] : "";

$response = ["valid" => false, "message" => "Invalid or expired reset link"];

if (!empty($token)) {
    $stmt = $conn->prepare("SELECT id FROM user WHERE reset_token = ? AND reset_token_expiry > NOW() LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $response = ["valid" => true, "message" => "Token is valid"];
    }

    $stmt->close();
}

$conn->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
