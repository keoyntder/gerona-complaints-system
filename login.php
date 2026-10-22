<?php
session_start();
require_once 'config/database.php';

$error   = '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['user_id']   = $admin['id'];
            $_SESSION['user_name'] = $admin['full_name'];
            $_SESSION['role']      = 'admin';
            $_SESSION['logged_in'] = true;
            echo "<script>top.location.href = 'admin/dashboard.php';</script>";
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'pending') {
                $error = 'Your account is still pending verification by LGU Gerona.';
            } elseif ($user['status'] === 'rejected') {
                $error = 'Your account was rejected. Please contact LGU Gerona.';
            } else {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['role']      = 'resident';
                $_SESSION['logged_in'] = true;
                echo "<script>top.location.href = 'resident/dashboard.php';</script>";
                exit;
            }
        } else {
            if (!$admin) $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Ragsak Ranyag Gerona</title>
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
            max-width: 1000px;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            min-height: 580px;
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
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
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
            padding: 11px 14px 11px 40px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
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

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
            font-size: 13px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #4b5563;
            cursor: pointer;
            user-select: none;
        }

        .remember-me input {
            accent-color: #8B6914;
            width: 15px;
            height: 15px;
            cursor: pointer;
        }

        .forgot-link {
            color: #8B6914;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }

        .forgot-link:hover { text-decoration: underline; color: #73560f; }

        /* Alerts */
        .alert-danger {
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        /* Submit Button Action Group */
        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-login {
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
            box-shadow: 0 4px 12px rgba(139, 105, 20, 0.25);
            transition: all 0.2s;
        }

        .btn-login:hover {
            background: #73560f;
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(139, 105, 20, 0.35);
        }

        .btn-register-outline {
            width: 100%;
            padding: 11px;
            background: transparent;
            color: #374151;
            font-size: 14px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            display: inline-block;
            transition: all 0.2s;
        }

        .btn-register-outline:hover {
            background: #f9fafb;
            border-color: #9ca3af;
            color: #111827;
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

        /* Responsive Breakpoints */
        @media (max-width: 900px) {
            .page-container {
                flex-direction: column;
                max-width: 480px;
            }
            .hero-side {
                padding: 30px;
                min-height: 200px;
            }
            .hero-body { margin-bottom: 10px; }
            .hero-body h1 { font-size: 24px; }
            .hero-body p, .hero-footer { display: none; }
            .form-side { padding: 32px 24px; }
        }
    </style>
</head>
<body>

<div class="page-container">

    <!-- LEFT SIDE: Hero Visual & Branding -->
    <div class="hero-side">
        <img class="hero-bg" src="assets/img/gerona-mbackground.jpg" alt="Gerona Town Hall">
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
                <p>Welcome back! Sign in to view your filed complaints, track resolution updates, or access the admin portal.</p>
            </div>

            <div class="hero-footer">
                <div class="badge-item">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Official Portal</span>
                </div>
                <div class="badge-item">
                    <i class="fa-solid fa-lock"></i>
                    <span>Secure Sign In</span>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT SIDE: Login Form -->
    <div class="form-side">

        <div class="form-title-area">
            <h2>Welcome back</h2>
            <p>Please enter your credentials to access your account</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">

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
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <i class="fa-solid fa-lock icon"></i>
                    <i class="fa-regular fa-eye toggle-pw" id="togglePw"></i>
                </div>
            </div>

            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember"> Remember me
                </label>
                <a href="#" class="forgot-link">Forgot password?</a>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn-login">Sign In</button>
                <a href="register.php" class="btn-register-outline">Create new account</a>
            </div>

        </form>

    </div>

</div>

<script>
    document.getElementById('togglePw').addEventListener('click', function () {
        const pw = document.getElementById('password');
        if (pw.type === 'password') {
            pw.type = 'text';
            this.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            pw.type = 'password';
            this.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
</script>

</body>
</html>