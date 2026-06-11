<?php
session_start();

require_once("dbConfig.php");

if (
    empty($_SESSION["login"]) ||
    $_SESSION["login"] !== 1 ||
    empty($_SESSION["user"]) ||
    empty($_SESSION["user"]["id"])
) {
    header("Location: ../login_form.html");
    exit;
}

if (!isset($_FILES["avatar"]) || $_FILES["avatar"]["error"] !== UPLOAD_ERR_OK) {
    header("Location: ../profile.html");
    exit;
}

$userId = (int)$_SESSION["user"]["id"];

$tmpPath = $_FILES["avatar"]["tmp_name"];
$originalName = $_FILES["avatar"]["name"] ?? "avatar";

// Basic mime/extension validation
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($tmpPath);
$allowed = [
    "image/jpeg" => "jpg",
    "image/png" => "png",
    "image/gif" => "gif"
];

if (!isset($allowed[$mimeType])) {
    // invalid type
    header("Location: ../profile.html");
    exit;
}

$ext = $allowed[$mimeType];

/*
 * Save avatars into: /sql/users/avatars
 * (this file lives in /public/sql, so "users/avatars" is sibling of this folder)
 */
$uploadDir = __DIR__ . "/users/avatars";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

// Create a deterministic filename per user
$fileName = "user_" . $userId . "_" . time() . "." . $ext;
$targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

if (!move_uploaded_file($tmpPath, $targetPath)) {
    header("Location: ../profile.html");
    exit;
}

// URL used by frontend to load avatar (relative to /public)
$relativeUrl = "./sql/users/avatars/" . $fileName;

// Update DB
$stmt = $conn->prepare("UPDATE user SET avatar_url = ? WHERE id = ?");
$stmt->bind_param("si", $relativeUrl, $userId);
$stmt->execute();

header("Location: ../profile.html");
exit;

