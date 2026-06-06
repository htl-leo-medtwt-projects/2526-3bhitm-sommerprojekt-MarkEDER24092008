<?php
SESSION_START();
include("auth_check.php");
require_once("dbConfig.php");

$mode = $_GET["mode"] ?? "multiple_choice";
if (!in_array($mode, ["multiple_choice", "text"], true)) {
  $mode = "multiple_choice";
}

/**
 * Hearts refill logic (max 5):
 * user.hearts_last_refill is NULL when already at max hearts.
 * When not NULL and >= 1 hour passed, grant +1 heart and update hearts_last_refill to NOW.
 * If after refill hearts becomes 5, set hearts_last_refill to NULL.
 */
function maybe_refill_hearts($conn, $user_id) {
  $stmt = $conn->prepare("SELECT hearts, hearts_last_refill FROM user WHERE id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res->num_rows === 0) {
    $stmt->close();
    return [0, null];
  }
  $row = $res->fetch_assoc();
  $hearts = (int)$row["hearts"];
  $last_refill = $row["hearts_last_refill"]; // may be NULL

  // If already full, last_refill should be NULL by your rule.
  if ($hearts >= 5) {
    if ($last_refill !== null) {
      $upd = $conn->prepare("UPDATE user SET hearts_last_refill = NULL WHERE id = ?");
      $upd->bind_param("i", $user_id);
      $upd->execute();
      $upd->close();
    }
    $stmt->close();
    return [$hearts, $last_refill];
  }

  if ($last_refill === null) {
    // No timestamp means: we consider it not refillable only when at max.
    // But hearts < 5 + NULL would be inconsistent; keep as-is.
    $stmt->close();
    return [$hearts, $last_refill];
  }

  $last_ts = strtotime($last_refill);
  if ($last_ts === false) {
    $stmt->close();
    return [$hearts, $last_refill];
  }

  $now = time();
  if (($now - $last_ts) >= 3600) {
    $new_hearts = min(5, $hearts + 1);
    if ($new_hearts >= 5) {
      $upd = $conn->prepare("UPDATE user SET hearts = ?, hearts_last_refill = NULL WHERE id = ?");
      $upd->bind_param("ii", $new_hearts, $user_id);
      $upd->execute();
      $upd->close();
      $hearts = $new_hearts;
      $last_refill = null;
    } else {
      $upd = $conn->prepare("UPDATE user SET hearts = ?, hearts_last_refill = NOW() WHERE id = ?");
      $upd->bind_param("ii", $new_hearts, $user_id);
      $upd->execute();
      $upd->close();
      $hearts = $new_hearts;
      $last_refill = date("Y-m-d H:i:s");
    }
  }

  $stmt->close();
  // return current
  $stmt2 = $conn->prepare("SELECT hearts, hearts_last_refill FROM user WHERE id = ?");
  $stmt2->bind_param("i", $user_id);
  $stmt2->execute();
  $res2 = $stmt2->get_result();
  $row2 = $res2->fetch_assoc();
  $stmt2->close();

  return [(int)$row2["hearts"], $row2["hearts_last_refill"]];
}

/**
 * Map mode -> allowed exercise types.
 * MVP:
 * - multiple_choice => MULTIPLE_CHOICE
 * - text => FILL_IN_BLANK or WORD_BANK or TRANSLATE (we'll include TRANSLATE + FILL_IN_BLANK + WORD_BANK for better coverage)
 */
function getAllowedTypesForMode($mode) {
  if ($mode === "multiple_choice") return ["MULTIPLE_CHOICE"];
  // text mode
  return ["TRANSLATE", "FILL_IN_BLANK", "WORD_BANK", "TRUE_FALSE"];
}

$user_id = $_SESSION["user"]["id"];

[$hearts, $last_refill] = maybe_refill_hearts($conn, $user_id);

$allowed_types = getAllowedTypesForMode($mode);

// Determine user's language_id from user table
$stmtLang = $conn->prepare("
  SELECT language_id
  FROM user
  WHERE id = ?
");
$stmtLang->bind_param("i", $user_id);
$stmtLang->execute();
$resLang = $stmtLang->get_result();
$userRow = $resLang->fetch_assoc();
$stmtLang->close();

$language_id = (int)$userRow["language_id"];

/**
 * Find next published lesson for this language that has at least one NOT-yet-correct
 * allowed exercise (for the selected mode).
 *
 * NOTE: MySQLi bind_param cannot mix positional params after using ...unpacking.
 * We'll bind in a loop by building refs.
 */
$typesPlaceholders = implode(",", array_fill(0, count($allowed_types), "?"));
$typeBindTypes = str_repeat("s", count($allowed_types));

$sqlLessons = "
  SELECT l.id, l.title
  FROM lesson l
  JOIN unit u ON u.id = l.unit_id
  WHERE u.language_id = ?
    AND l.is_published = 1
    AND EXISTS (
      SELECT 1
      FROM exercise e
      WHERE e.lesson_id = l.id
        AND e.type IN ($typesPlaceholders)
        AND NOT EXISTS (
          SELECT 1
          FROM user_exercise_attempt ua
          WHERE ua.user_id = ?
            AND ua.exercise_id = e.id
            AND ua.is_correct = 1
        )
    )
  ORDER BY l.order_index ASC, l.id ASC
  LIMIT 1
";

$stmtLesson = $conn->prepare($sqlLessons);

// Parameters: (1) language_id (i), (2) allowed_types... (s), (last) user_id (i)
$types_param = $allowed_types;
$stmtTypes = "i" . $typeBindTypes . "i";

// Build refs for bind_param
$bindParams = [];
$bindParams[] = &$language_id;
for ($i = 0; $i < count($types_param); $i++) {
  $bindParams[] = &$types_param[$i];
}
$bindParams[] = &$user_id;

$stmtLessonArgs = array_merge([$stmtTypes], $bindParams);

// bind_param expects (types, &param1, &param2, ...)
/**
 * Avoid splat/unpacking completely: build a bind call with fixed number of
 * allowed types. This MVP currently supports only 1..4 allowed types.
 * If you add more types, extend these cases.
 */
switch (count($allowed_types)) {
  case 1:
    $stmtLesson->bind_param("iis", $language_id, $allowed_types[0], $user_id);
    break;
  case 2:
    $stmtLesson->bind_param("iiss", $language_id, $allowed_types[0], $allowed_types[1], $user_id);
    break;
  case 3:
    $stmtLesson->bind_param("iisss", $language_id, $allowed_types[0], $allowed_types[1], $allowed_types[2], $user_id);
    break;
  case 4:
  default:
    $stmtLesson->bind_param(
      "iissss",
      $language_id,
      $allowed_types[0],
      $allowed_types[1],
      $allowed_types[2],
      $allowed_types[3],
      $user_id
    );
    break;
}

$stmtLesson->execute();
$resLesson = $stmtLesson->get_result();

if ($resLesson->num_rows === 0) {
  // No lesson remaining for this mode. If there are lessons completed previously, return completion state.
  echo json_encode([
    "success" => true,
    "hearts" => $hearts,
    "completed" => true,
    "lesson" => null,
    "remainingCount" => 0
  ]);
  exit;
}

$lesson = $resLesson->fetch_assoc();
$lesson_id = (int)$lesson["id"];

// Find next exercise for this lesson + allowed types that is not answered correctly yet
$sqlEx = "
  SELECT e.*
  FROM exercise e
  WHERE e.lesson_id = ?
    AND e.type IN ($typesPlaceholders)
    AND NOT EXISTS (
      SELECT 1
      FROM user_exercise_attempt ua
      WHERE ua.user_id = ?
        AND ua.exercise_id = e.id
        AND ua.is_correct = 1
    )
  ORDER BY e.order_index ASC, e.id ASC
  LIMIT 1
";
$stmtEx = $conn->prepare($sqlEx);

// Build refs for bind_param to avoid unpacking-positional issues
$stmtExTypes = "i" . $typeBindTypes . "i";
$bindParamsEx = [];
$bindParamsEx[] = &$lesson_id;
for ($i = 0; $i < count($types_param); $i++) {
  $bindParamsEx[] = &$types_param[$i];
}
$bindParamsEx[] = &$user_id;

$stmtEx->bind_param($stmtExTypes, ...$bindParamsEx);
$stmtEx->execute();
$resEx = $stmtEx->get_result();

if ($resEx->num_rows === 0) {
  // No exercise remains; mark lesson completed for this user (for MVP mode)
  $updProg = $conn->prepare("
    INSERT INTO progress (user_id, lesson_id, completed, score, stars, attempts, last_attempt)
    VALUES (?, ?, 1, 0, 0, 0, NOW())
    ON DUPLICATE KEY UPDATE completed = 1, last_attempt = NOW()
  ");
  $updProg->bind_param("ii", $user_id, $lesson_id);
  $updProg->execute();
  $updProg->close();

  echo json_encode([
    "success" => true,
    "hearts" => $hearts,
    "completed" => true,
    "lesson" => $lesson,
    "remainingCount" => 0
  ]);
  exit;
}

$exRow = $resEx->fetch_assoc();
$exercise_id = (int)$exRow["id"];

// Compute remaining count for this lesson+mode (exercises not correctly answered)
$sqlRemain = "
  SELECT COUNT(*) AS cnt
  FROM exercise e
  WHERE e.lesson_id = ?
    AND e.type IN ($typesPlaceholders)
    AND NOT EXISTS (
      SELECT 1
      FROM user_exercise_attempt ua
      WHERE ua.user_id = ?
        AND ua.exercise_id = e.id
        AND ua.is_correct = 1
    )
";
$stmtRem = $conn->prepare($sqlRemain);

/**
 * Avoid ...unpacking in bind_param (PHP tooling error).
 * MVP supports only 1..4 allowed types.
 */
if (count($allowed_types) === 1) {
  $stmtRem->bind_param("iis", $lesson_id, $allowed_types[0], $user_id);
} elseif (count($allowed_types) === 2) {
  $stmtRem->bind_param("iiss", $lesson_id, $allowed_types[0], $allowed_types[1], $user_id);
} elseif (count($allowed_types) === 3) {
  $stmtRem->bind_param("iisss", $lesson_id, $allowed_types[0], $allowed_types[1], $allowed_types[2], $user_id);
} else {
  $stmtRem->bind_param(
    "iissss",
    $lesson_id,
    $allowed_types[0],
    $allowed_types[1],
    $allowed_types[2],
    $allowed_types[3],
    $user_id
  );
}

$stmtRem->execute();
$resRem = $stmtRem->get_result();
$remainRow = $resRem->fetch_assoc();
$stmtRem->close();

$exercise = [
  "id" => $exercise_id,
  "type" => $exRow["type"],
  "question_text" => $exRow["question_text"],
  "hint" => $exRow["hint"],
  "image_url" => $exRow["image_url"],
  "audio_url" => $exRow["audio_url"],
  "extra_data" => $exRow["extra_data"],
];

// For multiple_choice: build choices from extra_data if available; else fallback.
// We keep correct_answer server-side only.
if ($mode === "multiple_choice" && $exRow["type"] === "MULTIPLE_CHOICE") {
  $choices = [];
  $extra = $exRow["extra_data"];
  if ($extra) {
    $decoded = json_decode($extra, true);
    if (is_array($decoded) && isset($decoded["choices"]) && is_array($decoded["choices"])) {
      $choices = $decoded["choices"];
    }
  }

  if (count($choices) < 2) {
    $sqlCh = "
      SELECT e2.correct_answer
      FROM exercise e2
      WHERE e2.lesson_id = ?
        AND e2.type = 'MULTIPLE_CHOICE'
        AND e2.id <> ?
      LIMIT 30
    ";
    $stmtCh = $conn->prepare($sqlCh);
    $stmtCh->bind_param("ii", $lesson_id, $exercise_id);
    $stmtCh->execute();
    $resCh = $stmtCh->get_result();

    $pool = [];
    while ($r = $resCh->fetch_assoc()) {
      $pool[] = $r["correct_answer"];
    }
    $stmtCh->close();

    $correct = $exRow["correct_answer"];
    $choices = array_values(array_unique(array_merge([$correct], $pool)));
    shuffle($choices);
    $choices = array_slice($choices, 0, 4);
  } else {
    $choices = array_values(array_unique($choices));
    shuffle($choices);
    $choices = array_slice($choices, 0, 4);
    if (count($choices) < 2) {
      $choices[] = $exRow["correct_answer"];
    }
  }

  $exercise["choices"] = $choices;
}

echo json_encode([
  "success" => true,
  "hearts" => $hearts,
  "completed" => false,
  "lesson" => $lesson,
  "remainingCount" => (int)$remainRow["cnt"],
  "exercise" => $exercise
]);

$conn->close();
?>
