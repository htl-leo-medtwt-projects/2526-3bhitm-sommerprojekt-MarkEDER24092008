<?php
session_start();

require_once("./sql/auth_check.php");

require_once("./sql/dbConfig.php");

$user = $_SESSION["user"] ?? null;
$avatarUrl = null;
$username = "";
$languageFlagUrl = null;

if (is_array($user) && !empty($user["id"])) {
    $userId = (int)$user["id"];

    // Load basic user data
    $stmt = $conn->prepare("SELECT username, avatar_url, language_id FROM user WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();

    $activeLanguageIdFromUserRow = null;

    if ($res && $res->num_rows === 1) {
        $row = $res->fetch_assoc();
        $username = $row["username"];
        $avatarUrl = $row["avatar_url"];
        $activeLanguageIdFromUserRow = $row["language_id"];
    }

    // Prefer active language from user_language (is_active = 1)
    $stmt2 = $conn->prepare("
        SELECT l.flag_url
        FROM user_language ul
        INNER JOIN language l ON l.id = ul.language_id
        WHERE ul.user_id = ? AND ul.is_active = 1
        LIMIT 1
    ");
    $stmt2->bind_param("i", $userId);
    $stmt2->execute();
    $res2 = $stmt2->get_result();

    if ($res2 && $res2->num_rows === 1) {
        $row2 = $res2->fetch_assoc();
        $languageFlagUrl = $row2["flag_url"] ?? null;
    }

    // Fallback: use user.language_id if user_language didn't return anything
    if (empty($languageFlagUrl) && !empty($activeLanguageIdFromUserRow)) {
        $stmt3 = $conn->prepare("SELECT flag_url FROM language WHERE id = ? LIMIT 1");
        $stmt3->bind_param("i", $activeLanguageIdFromUserRow);
        $stmt3->execute();
        $res3 = $stmt3->get_result();

        if ($res3 && $res3->num_rows === 1) {
            $row3 = $res3->fetch_assoc();
            $languageFlagUrl = $row3["flag_url"] ?? null;
        }
    }
}

if (empty($avatarUrl)) {
    $avatarUrl = null;
}

if (empty($languageFlagUrl)) {
    $languageFlagUrl = "./media/images/placeholderLanguage.png";
}

$avatarImg = !empty($avatarUrl) ? $avatarUrl : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Profile</title>
    <link rel="stylesheet" href="./css/generalStyle.css">
    <link rel="stylesheet" href="./css/profileStyle.css">
    <link rel="icon" type="image/png" href="./media/images/favicons/profile_favicon.png">
    <script src="./js/theme.js"></script>
    <script src="./js/audio.js" defer></script>
</head>
<body>


    <div class="profile-screen">
        <h1 class="title">Your Profile</h1>

        <section class="card" id="profile-card">

            <!-- Profile Icon -->
            <div class="profile-icon" id="profile-avatar-wrap" style="cursor:pointer;">
                <span
                    id="profile-avatar-placeholder"
                    style="display:<?php echo $avatarImg ? 'none' : 'inline'; ?>;"
                >👤</span>
                <img
                    id="profile-avatar"
                    <?php if ($avatarImg): ?>
                        src="<?= htmlspecialchars($avatarImg) ?>"
                    <?php endif; ?>
                    alt="Profile"
                    style="display:<?php echo $avatarImg ? 'block' : 'none'; ?>; width:90px; height:90px; object-fit:cover; border-radius:50%;"
                >
            </div>

            <!-- User Info -->
            <div class="info-section">

                <div class="info-row">
                    <span>Username:</span>
                    <span><?= htmlspecialchars($username) ?></span>
                </div>

                <div class="info-row">
                    <span>Password:</span>
                    <span>(click to preview)</span>
                </div>

            </div>

            <!-- Stats -->
            <div class="stats-section">

                <div class="info-row">
                    <span>Date Created:</span>
                    <span>dd.MM.yyyy</span>
                </div>

                <div class="info-row">
                    <span>Highest Streak:</span>
                    <span>99</span>
                </div>

            </div>

            <!-- Current Language -->
            <div class="study-section">
                <div class="info-row">
                    <span>Currently Studying:</span>

                    <div class="language-info">
                        <a href="./language.html">
                            <img
                                src="<?= htmlspecialchars($languageFlagUrl) ?>"
                                alt="Current Language"
                            >
                        </a>
                    </div>
                </div>
            </div>

            <form id="avatar-upload-form" method="POST" action="./sql/updateAvatarMySql.php" enctype="multipart/form-data" style="display:none;">
                <input type="file" name="avatar" accept="image/*" id="avatar-file-input" />
            </form>
        </section>

    </div>

    <nav class="bottomNav">
        <a href="./home.html">
            <div class="bottomNavButton"><img src="./media/images/home.png" alt="Home"></div>
        </a>

        <a href="./settings.html">
            <div class="bottomNavButton"><img src="./media/images/settings.png" alt="Settings"></div>
        </a>

        <a href="./progress.html">
        <div class="bottomNavButton"><img src="./media/images/progress.png" alt="Progress"></div>
      </a>

        <a href="./profile.php">
            <div class="bottomNavButton"><img src="./media/images/profile.png" alt="Profile"></div>
        </a>

        <a href="./streak.html">
            <div class="bottomNavButton"><img src="./media/images/fire.gif" alt="Streak"></div>
        </a>
    </nav>

    <script>
        const wrap = document.getElementById('profile-avatar-wrap');
        const input = document.getElementById('avatar-file-input');
        const form = document.getElementById('avatar-upload-form');

        wrap.addEventListener('click', async () => {
            const ok = window.confirm('Do you want to change your profile image?');
            if (!ok) return;
            input.value = '';
            input.click();
        });

        input.addEventListener('change', () => {
            if (!input.files || input.files.length === 0) return;
            form.submit();
        });
    </script>

</body>
</html>

