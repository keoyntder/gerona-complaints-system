<?php
session_start();
$cspNonce = require_once 'config/security_headers.php';
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    if ($_SESSION['role'] === 'admin') header('Location: admin/dashboard.php');
    else header('Location: resident/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ragsak, Ranyag Gerona Complaints</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha384-/o6I2CkkWC//PSjvWC/eYN7l3xM3tJm8ZzVkCOfp//W05QcE3mlGskpoHB6XqI+B" crossorigin="anonymous">
    <style nonce="<?= htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') ?>">
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; height: 100vh; overflow: hidden; background: #0a0e14; }

        .hero {
            position: relative;
            width: 100%;
            height: 100vh;
        }

        .background-photo {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }

        .background-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            transform: scale(1.04);
            animation: slowZoom 20s ease-in-out infinite alternate;
        }

        @keyframes slowZoom {
            from { transform: scale(1.04); }
            to   { transform: scale(1.12); }
        }

        .background-photo::after {
            content: '';
            position: absolute;
            inset: 0;
            background:
                linear-gradient(180deg, rgba(9,20,38,0.75) 0%, rgba(9,20,38,0.35) 38%, rgba(9,20,38,0.55) 70%, rgba(9,20,38,0.88) 100%),
                linear-gradient(100deg, rgba(9,20,38,0.55) 0%, rgba(9,20,38,0.1) 45%, rgba(9,20,38,0.1) 55%, rgba(9,20,38,0.55) 100%);
            z-index: 1;
        }

        /* Top utility strip, echoes the rest of the site */
        .top-strip {
            position: absolute;
            top: 0; left: 0; right: 0;
            z-index: 3;
            padding: 10px 32px;
            font-size: 11.5px;
            letter-spacing: 0.4px;
            color: rgba(255,255,255,0.75);
            display: flex;
            align-items: center;
            gap: 8px;
            opacity: 0;
            animation: fadeInDown 0.7s ease 0.1s forwards;
        }
        .top-strip i { font-size: 10.5px; color: #6fa8dc; }

        /* Top-left branding */
        .branding {
            position: absolute;
            top: 52px;
            left: 32px;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 16px;
            opacity: 0;
            animation: fadeInDown 0.7s ease 0.25s forwards;
        }

        .branding img {
            width: 70px;
            height: 70px;
            object-fit: contain;
            filter: drop-shadow(0 3px 10px rgba(0,0,0,0.5));
        }

        .brand-text .sub   { font-size: 11.5px; letter-spacing: 3px; color: #9dc4ee; text-transform: uppercase; font-weight: 600; }
        .brand-text .main  { font-family: 'Playfair Display', serif; font-size: 32px; color: #ffffff; font-weight: 900; line-height: 1.05; letter-spacing: 0.5px; text-shadow: 0 2px 12px rgba(0,0,0,0.35); }
        .brand-text .label { font-size: 10.5px; letter-spacing: 4px; color: #cfe0f5; text-transform: uppercase; font-weight: 500; margin-top: 3px; }

        /* Center panel */
        .center-card {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 22px;
            width: 380px;
            padding: 40px 36px 36px;
            background: rgba(15, 26, 46, 0.42);
            border: 1px solid rgba(255,255,255,0.14);
            border-radius: 14px;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            box-shadow: 0 24px 60px -12px rgba(0,0,0,0.55);
            opacity: 0;
            animation: fadeInUp 0.75s cubic-bezier(0.16, 1, 0.3, 1) 0.4s forwards;
        }

        .center-card .welcome-eyebrow {
            font-size: 11px;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: #9dc4ee;
            font-weight: 600;
        }
        .center-card .welcome-title {
            font-size: 19px;
            font-weight: 700;
            color: #fff;
            text-align: center;
            line-height: 1.4;
            margin-top: 6px;
            margin-bottom: 4px;
        }
        .center-card .welcome-sub {
            font-size: 12.5px;
            color: rgba(255,255,255,0.65);
            text-align: center;
            line-height: 1.5;
            margin-bottom: 6px;
        }

        .btn-group { width: 100%; display: flex; flex-direction: column; gap: 12px; }

        .btn-login,
        .btn-register {
            width: 100%;
            padding: 15px 16px;
            font-size: 14.5px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            border: none;
            cursor: pointer;
            letter-spacing: 0.4px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border-radius: 8px;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        .btn-login {
            background: #0E4D92;
            color: #ffffff;
            box-shadow: 0 10px 24px -6px rgba(14,77,146,0.55);
        }
        .btn-login:hover { background: #1A63B8; transform: translateY(-2px); box-shadow: 0 14px 30px -8px rgba(14,77,146,0.65); }

        .btn-register {
            background: rgba(255,255,255,0.06);
            color: #ffffff;
            border: 1.5px solid rgba(255,255,255,0.55);
        }
        .btn-register:hover { background: rgba(255,255,255,0.14); transform: translateY(-2px); border-color: #fff; }

        .divider-row { display: flex; align-items: center; gap: 10px; width: 100%; }
        .divider-row .line { flex: 1; height: 1px; background: rgba(255,255,255,0.18); }
        .divider-row span { font-size: 10.5px; color: rgba(255,255,255,0.45); letter-spacing: 1px; text-transform: uppercase; }

        /* Footer credit */
        .hero-footer {
            position: absolute;
            bottom: 18px;
            left: 0; right: 0;
            z-index: 2;
            text-align: center;
            font-size: 11px;
            color: rgba(255,255,255,0.55);
            letter-spacing: 0.3px;
            opacity: 0;
            animation: fadeInUp 0.7s ease 0.6s forwards;
        }
        .hero-footer strong { color: rgba(255,255,255,0.8); font-weight: 600; }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translate(-50%, calc(-50% + 16px)); }
            to   { opacity: 1; transform: translate(-50%, -50%); }
        }
        .hero-footer { animation-name: fadeInUpFooter; }
        @keyframes fadeInUpFooter {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 480px) {
            .branding { top: 60px; left: 20px; }
            .branding img { width: 56px; height: 56px; }
            .brand-text .main { font-size: 25px; }
            .center-card { width: 90%; padding: 32px 22px 28px; gap: 18px; }
            .top-strip { display: none; }
        }

        /* ----------------------------------------------------
           FULL DESIGN MODAL POPUP
        ---------------------------------------------------- */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(6, 12, 24, 0.75);
            backdrop-filter: blur(10px);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            padding: 20px;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-container {
            position: relative;
            width: 100%;
            max-width: 1080px;
            height: 90vh;
            max-height: 720px;
            transform: scale(0.94);
            transition: transform 0.32s cubic-bezier(0.16, 1, 0.3, 1);
            border-radius: 16px;
            box-shadow: 0 0 0 1px rgba(255,255,255,0.08), 0 30px 70px -15px rgba(0,0,0,0.65);
        }

        .modal-overlay.active .modal-container {
            transform: scale(1);
        }

        .modal-accent-bar {
            position: absolute;
            top: -1px; left: 16px; right: 16px;
            height: 4px;
            background: linear-gradient(90deg, #0E4D92, #1B6FC9, #0E4D92);
            border-radius: 4px;
            z-index: 111;
        }

        .modal-close {
            position: absolute;
            top: -16px;
            right: -16px;
            background: #ffffff;
            border: none;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0E4D92;
            font-size: 16px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.35);
            z-index: 110;
            transition: transform 0.2s, background 0.2s;
        }

        .modal-close:hover { transform: scale(1.1) rotate(90deg); background: #EFF5FC; }

        .modal-iframe {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            background: #fff;
            overflow: hidden;
        }
    </style>
</head>
<body>

<div class="hero">

    <div class="background-photo">
        <img src="assets/img/gerona-mbackground.jpg" alt="Gerona Background">
    </div>

    <!-- Top utility strip -->
    <div class="top-strip">
        <i class="fa-solid fa-landmark"></i> Republic of the Philippines &nbsp;&middot;&nbsp; Province of Tarlac &nbsp;&middot;&nbsp; Municipality of Gerona
    </div>

    <!-- Top-left branding -->
    <div class="branding">
        <img src="assets/img/gerona-seal.png" alt="Gerona Seal">
        <div class="brand-text">
            <div class="sub">Ragsak, Ranyag</div>
            <div class="main">GERONA</div>
            <div class="label">Complaints Management System</div>
        </div>
    </div>

    <!-- Center panel -->
    <div class="center-card">
        <div class="welcome-eyebrow">Resident Portal</div>
        <div class="welcome-title">Serbisyong Totoo,<br>Serbisyong May Puso!</div>
        <div class="welcome-sub">Report concerns, track their progress, and help us build a better Gerona together.</div>

        <div class="btn-group">
            <button class="btn-login" data-popup-url="login.php">
                <i class="fa-solid fa-right-to-bracket"></i> Login
            </button>
            <div class="divider-row">
                <div class="line"></div><span>or</span><div class="line"></div>
            </div>
            <button class="btn-register" data-popup-url="register.php">
                <i class="fa-solid fa-user-plus"></i> Register Account
            </button>
        </div>
    </div>

    <div class="hero-footer">
        &copy; <?= date('Y') ?> <strong>Municipality of Gerona, Tarlac</strong> &mdash; All Rights Reserved
    </div>

</div>

<!-- Pop-up Overlay Container -->
<div class="modal-overlay" id="popupModal">
    <div class="modal-container">
        <div class="modal-accent-bar"></div>
        <button class="modal-close" id="popupClose"><i class="fa-solid fa-xmark"></i></button>
        <iframe class="modal-iframe" id="popupIframe" src=""></iframe>
    </div>
</div>

<script nonce="<?= htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') ?>">
    function openPopup(pageUrl) {
        document.getElementById('popupIframe').src = pageUrl;
        document.getElementById('popupModal').classList.add('active');
    }

    function closePopup() {
        document.getElementById('popupModal').classList.remove('active');
        setTimeout(() => {
            document.getElementById('popupIframe').src = '';
        }, 300);
    }

    document.querySelectorAll('[data-popup-url]').forEach(button => {
        button.addEventListener('click', () => openPopup(button.dataset.popupUrl));
    });
    document.getElementById('popupClose').addEventListener('click', closePopup);

    // Close popup when clicking outside the box
    document.getElementById('popupModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closePopup();
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closePopup();
    });
</script>

</body>
</html>