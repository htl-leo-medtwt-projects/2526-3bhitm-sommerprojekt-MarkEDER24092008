<?php
session_start();

require_once("./sql/auth_check.php");

require_once("./sql/dbConfig.php");

$user = $_SESSION["user"] ?? null;
$avatarUrl = null;
$username = "";

if (is_array($user) && !empty($user["id"])) {
    $stmt = $conn->prepare("SELECT username, avatar_url FROM user WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $user["id"]);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows === 1) {
        $row = $res->fetch_assoc();
        $username = $row["username"];
        $avatarUrl = $row["avatar_url"];
    }
}

if (empty($avatarUrl)) {
    $avatarUrl = null;
}

$avatarImg = $avatarUrl ? $avatarUrl : "";
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
</head>
<body>

    <div class="profile-screen">
        <h1 class="title">Your Profile</h1>

        <section class="card" id="profile-card">

            <!-- Profile Icon -->
            <div class="profile-icon" id="profile-avatar-wrap" style="cursor:pointer;">
                <span id="profile-avatar-placeholder" style="display:<?= $avatarImg ? 'none' : 'inline' ?>;">👤</span>
                <img
                    id="profile-avatar"
                    src="<?= htmlspecialchars($avatarImg) ?>"
                    alt="Profile"
                    style="display:<?= $avatarImg ? 'block' : 'none' ?>; width:90px; height:90px; object-fit:cover; border-radius:50%;"
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
                        <a href="./language.html"><img src="./media/images/placeholderLanguage.png" alt=""></a>
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

