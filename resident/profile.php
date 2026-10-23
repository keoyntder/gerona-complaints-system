<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'resident') {
    header('Location: ../login.php');
    exit;
}
require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$success = '';
$errors  = [];

$user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$user_id]);
$user = $user->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (empty($full_name)) $errors[] = 'Full Name is required.';
    if (empty($address))   $errors[] = 'Address is required.';

    if (!empty($password)) {
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        if (!empty($password)) {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, address = ?, password = ? WHERE id = ?");
            $stmt->execute([$full_name, $address, password_hash($password, PASSWORD_DEFAULT), $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, address = ? WHERE id = ?");
            $stmt->execute([$full_name, $address, $user_id]);
        }

        $_SESSION['user_name'] = $full_name;
        $success = 'Profile updated successfully!';

        $user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $user->execute([$user_id]);
        $user = $user->fetch(PDO::FETCH_ASSOC);
    }
}

$active_page = 'profile';
$page_title  = 'My Profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile — Ragsak Ranyag Gerona</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/gov-header.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f4f6f8; min-height: 100vh; }

        .main { flex: 1; }

        .content { padding: 0; max-width: none; margin: 0; }

        /* Profile card */
        .profile-card {
            background: #fff;
            border-top: 1px solid #d0d5dd;
            border-bottom: 1px solid #d0d5dd;
            margin-bottom: 0;
        }

        .profile-header {
            background: linear-gradient(135deg, #0E4D92, #1B6FC9);
            padding: 32px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: rgba(255,255,255,0.25);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 700;
            flex-shrink: 0;
            border: 3px solid rgba(255,255,255,0.4);
        }

        .profile-header-info h2 { font-size: 20px; font-weight: 700; color: #fff; }
        .profile-header-info p  { font-size: 13px; color: rgba(255,255,255,0.8); margin-top: 4px; }

        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(255,255,255,0.2);
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            margin-top: 8px;
        }

        .profile-body { padding: 28px; }

        .form-group { margin-bottom: 18px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 6px; }

        .input-wrap { position: relative; }
        .input-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: 14px; }
        .input-wrap input { width: 100%; padding: 11px 12px 11px 36px; border: 1.5px solid #eee; border-radius: 6px; font-size: 14px; font-family: 'Inter', sans-serif; outline: none; color: #1a1a1a; }
        .input-wrap input:focus { border-color: #0E4D92; }
        .input-wrap input::placeholder { color: #bbb; }
        .input-wrap input:disabled { background: #fafafa; color: #999; cursor: not-allowed; }

        .toggle-pw { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #aaa; font-size: 14px; }

        .section-title { font-size: 13px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid #f0f0f0; }

        .alert-danger  { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; background: #fdecea; color: #b71c1c; border-left: 3px solid #b71c1c; }
        .alert-success { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; background: #e8f5e9; color: #1b5e20; border-left: 3px solid #1b5e20; }
        .err-list { list-style: none; padding: 0; }
        .err-list li::before { content: "• "; }

        .btn-save { padding: 12px 28px; background: #0E4D92; color: #fff; font-size: 14px; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; font-family: 'Inter', sans-serif; display: inline-flex; align-items: center; gap: 8px; }
        .btn-save:hover { background: #1A63B8; }

        .id-preview { display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: #fafafa; border-radius: 6px; border: 1px solid #eee; }
        .id-preview i { font-size: 20px; color: #0E4D92; }
        .id-preview a { color: #0E4D92; font-weight: 600; font-size: 14px; text-decoration: none; }
        .id-preview a:hover { text-decoration: underline; }
        .id-preview span { font-size: 13px; color: #888; }
    </style>
</head>
<body>

<?php require 'includes/header.php'; ?>

<div class="main">
    <div class="content">

        <!-- Profile header card -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                </div>
                <div class="profile-header-info">
                    <h2><?= htmlspecialchars($user['full_name']) ?></h2>
                    <p><?= htmlspecialchars($user['email']) ?></p>
                    <div class="status-chip">
                        <i class="fa-solid fa-circle-check"></i>
                        <?= ucfirst($user['status']) ?> Account
                    </div>
                </div>
            </div>

            <div class="profile-body">

                <?php if (!empty($errors)): ?>
                    <div class="alert-danger">
                        <ul class="err-list">
                            <?php foreach ($errors as $e): ?>
                                <li><?= htmlspecialchars($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert-success"><?= $success ?></div>
                <?php endif; ?>

                <form action="profile.php" method="POST">

                    <div class="section-title">Personal Information</div>

                    <div class="form-group">
                        <label>Full Name</label>
                        <div class="input-wrap">
                            <i class="fa-regular fa-user"></i>
                            <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Address</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-location-dot"></i>
                            <input type="text" name="address" value="<?= htmlspecialchars($user['address']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <div class="input-wrap">
                            <i class="fa-regular fa-envelope"></i>
                            <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Valid ID</label>
                        <div class="id-preview">
                            <i class="fa-regular fa-id-card"></i>
                            <div>
                                <a href="/<?= htmlspecialchars(ltrim($user['id_photo'], '/'), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">View Uploaded ID</a>
                                <div><span>Uploaded during registration</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="section-title" style="margin-top:24px">Change Password <span style="font-size:11px;color:#bbb;font-weight:400;text-transform:none">(leave blank to keep current)</span></div>

                    <div class="form-row">
                        <div>
                            <label>New Password</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-lock"></i>
                                <input type="password" id="pw1" name="password" placeholder="Min. 8 characters">
                                <i class="fa-regular fa-eye toggle-pw" id="tgl1"></i>
                            </div>
                        </div>
                        <div>
                            <label>Confirm Password</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-lock"></i>
                                <input type="password" id="pw2" name="confirm_password" placeholder="Re-enter password">
                                <i class="fa-regular fa-eye toggle-pw" id="tgl2"></i>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-save">
                        <i class="fa-solid fa-floppy-disk"></i> Save Changes
                    </button>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePw(inputId, iconId) {
        document.getElementById(iconId).addEventListener('click', function () {
            const input = document.getElementById(inputId);
            if (input.type === 'password') { input.type = 'text'; this.classList.replace('fa-eye', 'fa-eye-slash'); }
            else { input.type = 'password'; this.classList.replace('fa-eye-slash', 'fa-eye'); }
        });
    }
    togglePw('pw1', 'tgl1');
    togglePw('pw2', 'tgl2');
</script>
</body>
</html>