<?php
session_start();
require_once 'config/database.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name        = trim($_POST['full_name'] ?? '');
    $address          = trim($_POST['address'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($full_name))        $errors[] = 'Full Name is required.';
    if (empty($address))          $errors[] = 'Address is required.';
    if (empty($email))            $errors[] = 'Email is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
    if (empty($password))         $errors[] = 'Password is required.';
    if (strlen($password) < 8)    $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm_password) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = 'This email is already registered.';
    }

    $idPhotoPath = null;
    if (!isset($_FILES['id_photo']) || $_FILES['id_photo']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Please upload your valid ID.';
    } else {
        $upload = $_FILES['id_photo'];

        if ($upload['error'] !== UPLOAD_ERR_OK) {
            if (in_array($upload['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                $errors[] = 'ID file must be 2MB or less.';
            } elseif ($upload['error'] === UPLOAD_ERR_PARTIAL) {
                $errors[] = 'The ID upload was incomplete. Please try again.';
            } else {
                $errors[] = 'The ID could not be uploaded. Please try again.';
            }
        } elseif ($upload['size'] > 2 * 1024 * 1024) {
            $errors[] = 'ID file must be 2MB or less.';
        } elseif (!is_uploaded_file($upload['tmp_name'])) {
            $errors[] = 'The uploaded ID could not be verified. Please try again.';
        } else {
            $extension = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));
            $mimeType  = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
            $validTypes = [
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png'  => 'image/png',
                'pdf'  => 'application/pdf',
            ];

            if (!isset($validTypes[$extension]) || $validTypes[$extension] !== $mimeType) {
                $errors[] = 'ID must be a valid JPG, PNG, or PDF file.';
            } else {
                $uploadDirectory = __DIR__ . '/uploads/ids';

                if (!is_dir($uploadDirectory) || !is_writable($uploadDirectory)) {
                    $errors[] = 'IDs cannot be saved right now. Please contact the site administrator.';
                } else {
                    $newName = bin2hex(random_bytes(16)) . '.' . $extension;
                    $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $newName;

                    if (move_uploaded_file($upload['tmp_name'], $destination)) {
                        $idPhotoPath = 'uploads/ids/' . $newName;
                    } else {
                        $errors[] = 'The ID could not be saved. Please try again or contact the site administrator.';
                    }
                }
            }
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, address, email, password, id_photo, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$full_name, $address, $email, password_hash($password, PASSWORD_DEFAULT), $idPhotoPath]);
        $success = 'Registration successful! Please wait for LGU verification.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Ragsak Ranyag Gerona</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            min-height: 100vh; 
            background: #111418;
            background-image: radial-gradient(circle at 20% 20%, rgba(139, 105, 20, 0.15), transparent 40%),
                              radial-gradient(circle at 80% 80%, rgba(20, 24, 33, 0.8), transparent 50%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
        }

        /* Container Card */
        .page-container {
            width: 100%;
            max-width: 1080px;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            min-height: 680px;
        }

        /* ----------------------------------------------------
           LEFT SIDE - HERO PHOTO & BRANDING
        ---------------------------------------------------- */
        .hero-side {
            flex: 1.1;
            position: relative;
            background: #1a1d24;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 40px;
            overflow: hidden;
        }

        .hero-bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            opacity: 0.45;
            filter: scale(1.02);
            transition: transform 0.5s ease;
        }

        .hero-side:hover .hero-bg {
            transform: scale(1.05);
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(15, 17, 21, 0.7) 0%, rgba(15, 17, 21, 0.95) 100%);
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .hero-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .hero-header img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
        }

        .hero-brand-title {
            color: #ffffff;
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .hero-brand-sub {
            color: #e8d9a0;
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .hero-body {
            margin-top: auto;
            margin-bottom: 40px;
        }

        .hero-body h1 {
            color: #ffffff;
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            line-height: 1.2;
            margin-bottom: 12px;
        }

        .hero-body p {
            color: #b0b7c3;
            font-size: 14px;
            line-height: 1.6;
        }

        .hero-footer {
            display: flex;
            gap: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
        }

        .badge-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #d1d5db;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-item i {
            color: #c8b87a;
        }

        /* ----------------------------------------------------
           RIGHT SIDE - FORM
        ---------------------------------------------------- */
        .form-side {
            flex: 1;
            padding: 44px 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #ffffff;
        }

        .form-title-area {
            margin-bottom: 24px;
        }

        .form-title-area h2 {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            letter-spacing: -0.3px;
        }

        .form-title-area p {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap i.icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 14px;
            transition: color 0.2s;
        }

        .input-wrap input {
            width: 100%;
            padding: 10px 14px 10px 40px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 13.5px;
            font-family: 'Inter', sans-serif;
            color: #1f2937;
            background: #f9fafb;
            outline: none;
            transition: all 0.2s;
        }

        .input-wrap input:focus {
            border-color: #8B6914;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(139, 105, 20, 0.15);
        }

        .input-wrap input:focus + i.icon {
            color: #8B6914;
        }

        .toggle-pw {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #9ca3af;
            font-size: 14px;
        }

        .toggle-pw:hover { color: #4b5563; }

        /* Custom ID Upload Area */
        .upload-container {
            border: 1.5px dashed #d1d5db;
            background: #f9fafb;
            border-radius: 8px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 14px;
        }

        .upload-container:hover {
            border-color: #8B6914;
            background: #fffdfa;
        }

        .upload-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .upload-icon-box {
            width: 34px;
            height: 34px;
            background: rgba(139, 105, 20, 0.1);
            color: #8B6914;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .upload-text-main {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
        }

        .upload-text-sub {
            font-size: 11px;
            color: #6b7280;
        }

        #id_photo { display: none; }

        /* Alerts */
        .alert-danger {
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 12.5px;
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 12.5px;
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .err-list { list-style: none; padding: 0; }
        .err-list li::before { content: "• "; color: #dc2626; }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 12px;
            background: #8B6914;
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 8px;
            box-shadow: 0 4px 12px rgba(139, 105, 20, 0.25);
            transition: all 0.2s;
        }

        .btn-submit:hover {
            background: #73560f;
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(139, 105, 20, 0.35);
        }

        .login-link {
            text-align: center;
            margin-top: 16px;
            font-size: 13px;
            color: #6b7280;
        }

        .login-link a {
            color: #8B6914;
            font-weight: 600;
            text-decoration: none;
        }

        .login-link a:hover { text-decoration: underline; }

        /* Responsive Breakpoints */
        @media (max-width: 900px) {
            .page-container {
                flex-direction: column;
                max-width: 500px;
            }
            .hero-side {
                padding: 30px;
                min-height: 220px;
            }
            .hero-body { margin-bottom: 10px; }
            .hero-body h1 { font-size: 24px; }
            .hero-body p, .hero-footer { display: none; }
            .form-side { padding: 30px 24px; }
        }

                /* Custom Scrollbar for Chrome, Safari, Edge, and Opera */
        ::-webkit-scrollbar {
            width: 6px;              /* Thin and non-intrusive */
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05); /* Subtle background track */
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: #8B6914;      /* Matches your Gerona Gold theme */
            border-radius: 10px;
            transition: background 0.2s ease;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a07a1a;      /* Darker gold on hover */
        }

        /* Custom Scrollbar for Firefox */
        * {
            scrollbar-width: thin;
            scrollbar-color: #8B6914 rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>

<div class="page-container">

    <!-- LEFT SIDE: Hero Visual & Branding -->
    <div class="hero-side">
        <img class="hero-bg" src="assets/img/gerona-rbackground.jpg" alt="Gerona Town">
        <div class="hero-overlay"></div>

        <div class="hero-content">
            <div class="hero-header">
                <img src="assets/img/gerona-seal.png" alt="Gerona Seal">
                <div>
                    <div class="hero-brand-sub">Ragsak, Ranyag</div>
                    <div class="hero-brand-title">GERONA</div>
                </div>
            </div>

            <div class="hero-body">
                <h1>Citizen Complaint Portal</h1>
                <p>Register your account to securely submit complaints, track progress, and help build a better community.</p>
            </div>

            <div class="hero-footer">
                <div class="badge-item">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Official Portal</span>
                </div>
                <div class="badge-item">
                    <i class="fa-solid fa-user-check"></i>
                    <span>LGU Verified</span>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT SIDE: Registration Form -->
    <div class="form-side">

        <div class="form-title-area">
            <h2>Create an Account</h2>
            <p>Fill in your details below to register</p>
        </div>

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
            <div class="alert-success"><?= $success ?> <a href="login.php">Login here</a></div>
        <?php endif; ?>

        <form action="register.php" method="POST" enctype="multipart/form-data">

            <div class="form-group">
                <label>Full Name</label>
                <div class="input-wrap">
                    <input type="text" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" placeholder="e.g. Juan Dela Cruz" required>
                    <i class="fa-regular fa-user icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label>Address</label>
                <div class="input-wrap">
                    <input type="text" name="address" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" placeholder="Barangay, Street Address" required>
                    <i class="fa-solid fa-location-dot icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <div class="input-wrap">
                    <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="name@example.com" required>
                    <i class="fa-regular fa-envelope icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="input-wrap">
                    <input type="password" id="password" name="password" placeholder="At least 8 characters" required>
                    <i class="fa-solid fa-lock icon"></i>
                    <i class="fa-regular fa-eye toggle-pw" id="togglePw"></i>
                </div>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <div class="input-wrap">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                    <i class="fa-solid fa-lock icon"></i>
                    <i class="fa-regular fa-eye toggle-pw" id="togglePw2"></i>
                </div>
            </div>

            <!-- Upload Field Dropzone -->
            <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; text-transform: uppercase;">Government ID Verification</label>
            <label for="id_photo" class="upload-container">
                <div class="upload-info">
                    <div class="upload-icon-box">
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                    <div>
                        <div class="upload-text-main" id="id-filename">Upload Valid ID</div>
                        <div class="upload-text-sub">JPG, PNG, or PDF (Max 2MB)</div>
                    </div>
                </div>
                <i class="fa-solid fa-cloud-arrow-up" style="color: #8B6914; font-size: 16px;"></i>
            </label>
            <input type="file" id="id_photo" name="id_photo" accept=".jpg,.jpeg,.png,.pdf">

            <button type="submit" class="btn-submit">Register Account</button>
        </form>

        <div class="login-link">
            Already registered? <a href="login.php">Login here</a>
        </div>

    </div>

</div>

<script>
    document.getElementById('id_photo').addEventListener('change', function () {
        const filename = this.files[0] ? this.files[0].name : 'Upload Valid ID';
        document.getElementById('id-filename').textContent = filename;
    });

    function togglePassword(inputId, iconId) {
        document.getElementById(iconId).addEventListener('click', function () {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                this.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                this.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    }

    togglePassword('password', 'togglePw');
    togglePassword('confirm_password', 'togglePw2');
</script>

</body>
</html>