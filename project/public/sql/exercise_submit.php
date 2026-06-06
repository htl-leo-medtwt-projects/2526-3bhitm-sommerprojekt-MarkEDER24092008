<?php
SESSION_START();
include("auth_check.php");
require_once("dbConfig.php");

$input = json_decode(file_get_contents("php://input"), true);
if (!is_array($input)) {
  http_response_code(400);
  echo json_encode(["success" => false, "error" => "Invalid JSON body"]);
  exit;
}

$exercise_id = isset($input["exercise_id"]) ? (int)$input["exercise_id"] : 0;
$answer_given = isset($input["answer_given"]) ? (string)$input["answer_given"] : "";

if ($exercise_id <= 0 || $answer_given === "") {
  http_response_code(400);
  echo json_encode(["success" => false, "error" => "Missing exercise_id or answer_given"]);
  exit;
}

$user_id = $_SESSION["user"]["id"];

// Fetch correct answer + exercise type
$stmtEx = $conn->prepare("SELECT correct_answer, type, lesson_id FROM exercise WHERE id = ?");
$stmtEx->bind_param("i", $exercise_id);
$stmtEx->execute();
$resEx = $stmtEx->get_result();
if ($resEx->num_rows === 0) {
  http_response_code(404);
  echo json_encode(["success" => false, "error" => "Exercise not found"]);
  exit;
}
$exRow = $resEx->fetch_assoc();
$stmtEx->close();

$correct_answer = (string)$exRow["correct_answer"];
$lesson_id = (int)$exRow["lesson_id"];
$ex_type = (string)$exRow["type"];

// Determine correctness (MVP: exact match; normalize whitespace/case for text)
$norm = function($s) {
  $s = trim($s);
  // normalize multiple spaces
  $s = preg_replace('/\s+/', ' ', $s);
  return mb_strtolower($s);
};
$is_correct = ($norm($answer_given) === $norm($correct_answer)) ? 1 : 0;

// Hearts logic
// - If wrong: deduct 1 heart (min 0)
$stmtHearts = $conn->prepare("SELECT hearts FROM user WHERE id = ?");
$stmtHearts->bind_param("i", $user_id);
$stmtHearts->execute();
$resH = $stmtHearts->get_result();
$rowH = $resH->fetch_assoc();
$stmtHearts->close();

$current_hearts = (int)$rowH["hearts"];
$new_hearts = $current_hearts;

if ($is_correct === 0) {
  $new_hearts = max(0, $current_hearts - 1);
  $upd = $conn->prepare("UPDATE user SET hearts = ? WHERE id = ?");
  $upd->bind_param("ii", $new_hearts, $user_id);
  $upd->execute();
  $upd->close();
} else {
  // keep hearts unchanged on correct answers
  $new_hearts = $current_hearts;
}

// Store attempt
$time_taken_ms = 0;
$attempted_at = date("Y-m-d H:i:s");

$insAttempt = $conn->prepare("
  INSERT INTO user_exercise_attempt (user_id, exercise_id, answer_given, is_correct, time_taken_ms, attempted_at)
  VALUES (?, ?, ?, ?, ?, ?)
");
$insAttempt->bind_param("iisiss", $user_id, $exercise_id, $answer_given, $is_correct, $time_taken_ms, $attempted_at);
$insAttempt->execute();
$insAttempt->close();

// For MVP progression: if correct, mark progress.completed=1 per lesson (simple)
if ($is_correct === 1) {
  // update progress row for this lesson
  $updProg = $conn->prepare("
    INSERT INTO progress (user_id, lesson_id, completed, score, stars, attempts, last_attempt)
    VALUES (?, ?, 0, 0, 0, 1, NOW())
    ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()
  ");
  $updProg->bind_param("ii", $user_id, $lesson_id);
  $updProg->execute();
  $updProg->close();
}

// Decide next exercise / completion:
// We'll check whether there exists any not-yet-correct exercise in this lesson
// for the same mode by using type mapping (MVP: choose type set based on current exercise type).
$mode = null;
if ($ex_type === 'MULTIPLE_CHOICE') $mode = 'multiple_choice';
else $mode = 'text';

$allowed_types = ($mode === 'multiple_choice') ? ['MULTIPLE_CHOICE'] : ['TRANSLATE', 'FILL_IN_BLANK', 'WORD_BANK', 'TRUE_FALSE'];

// Build SQL to see if any remaining correct==0 exists
$placeholders = implode(",", array_fill(0, count($allowed_types), "?"));
$typeStr = str_repeat("s", count($allowed_types));

$sqlRemaining = "
  SELECT COUNT(*) AS cnt
  FROM exercise e
  WHERE e.lesson_id = ?
    AND e.type IN ($placeholders)
    AND NOT EXISTS (
      SELECT 1 FROM user_exercise_attempt ua
      WHERE ua.user_id = ?
        AND ua.exercise_id = e.id
        AND ua.is_correct = 1
    )
";
$stmtRem = $conn->prepare($sqlRemaining);

if (count($allowed_types) === 1) {
  $stmtRem->bind_param("isi", $lesson_id, $allowed_types[0], $user_id);
} elseif (count($allowed_types) === 4) {
  $stmtRem->bind_param("isssssssi", $lesson_id, $allowed_types[0], $allowed_types[1], $allowed_types[2], $allowed_types[3], $user_id);
} else {
  http_response_code(500);
  echo json_encode(["success" => false, "error" => "Unsupported allowed_types size"]);
  exit;
}

$stmtRem->execute();
$resRem = $stmtRem->get_result();
$remainRow = $resRem->fetch_assoc();
$stmtRem->close();

$completed = ((int)$remainRow["cnt"] === 0);

// If completed, set progress.completed=1
if ($completed) {
  $updComp = $conn->prepare("UPDATE progress SET completed = 1, last_attempt = NOW() WHERE user_id = ? AND lesson_id = ?");
  $updComp->bind_param("ii", $user_id, $lesson_id);
  $updComp->execute();
  $updComp->close();
}

// If not completed, load next exercise using exercise_get.php logic (inline simplified)
$nextExercise = null;
if (!$completed) {
  $sqlNext = "
    SELECT e.*
    FROM exercise e
    WHERE e.lesson_id = ?
      AND e.type IN ($placeholders)
      AND NOT EXISTS (
        SELECT 1 FROM user_exercise_attempt ua
        WHERE ua.user_id = ?
          AND ua.exercise_id = e.id
          AND ua.is_correct = 1
      )
    ORDER BY e.order_index ASC, e.id ASC
    LIMIT 1
  ";
  $stmtNext = $conn->prepare($sqlNext);

  if (count($allowed_types) === 1) {
    $stmtNext->bind_param("i" . "s" . "i", $lesson_id, $allowed_types[0], $user_id);
  } else {
    $stmtNext->bind_param("i" . "ssss" . "i", $lesson_id, $allowed_types[0], $allowed_types[1], $allowed_types[2], $allowed_types[3], $user_id);
  }

  $stmtNext->execute();
  $resNext = $stmtNext->get_result();
  if ($resNext->num_rows > 0) {
    $r = $resNext->fetch_assoc();
    $nextExercise = [
      "id" => (int)$r["id"],
      "type" => $r["type"],
      "question_text" => $r["question_text"],
      "hint" => $r["hint"],
      "image_url" => $r["image_url"],
      "audio_url" => $r["audio_url"],
      "extra_data" => $r["extra_data"],
    ];
  }
  $stmtNext->close();
}

echo json_encode([
  "success" => true,
  "is_correct" => (bool)$is_correct,
  "hearts" => $new_hearts,
  "completed" => $completed,
  "next_exercise" => $nextExercise
]);

$conn->close();
?>
