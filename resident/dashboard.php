<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'resident') {
    header('Location: ../login.php');
    exit;
}
require_once '../config/database.php';

$user_id = $_SESSION['user_id'];

$user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$user_id]);
$user = $user->fetch(PDO::FETCH_ASSOC);

$total_complaints    = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE user_id = ?");
$total_complaints->execute([$user_id]);
$total_complaints    = $total_complaints->fetchColumn();

$resolved_complaints = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE user_id = ? AND status = 'resolved'");
$resolved_complaints->execute([$user_id]);
$resolved_complaints = $resolved_complaints->fetchColumn();

$pending_complaints  = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE user_id = ? AND status = 'pending'");
$pending_complaints->execute([$user_id]);
$pending_complaints  = $pending_complaints->fetchColumn();

$recent = $pdo->prepare("SELECT * FROM complaints WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$recent->execute([$user_id]);
$recent = $recent->fetchAll(PDO::FETCH_ASSOC);

$active_page = 'dashboard';
$page_title  = 'Resident Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Ragsak Ranyag Gerona</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/gov-header.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; min-height: 100vh; }

        .main { flex: 1; }

        .content { padding: 28px; max-width: 1240px; margin: 0 auto; }

        /* Header banner */
        .header-banner {
            background: #0E4D92;
            border: 1px solid #0B3D75;
            border-radius: 3px;
            padding: 20px 24px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-banner .left { display: flex; align-items: center; gap: 16px; }
        .header-banner img { width: 52px; height: 52px; object-fit: contain; filter: brightness(1.1); }
        .header-banner h2 { font-size: 16px; font-weight: 700; color: #fff; letter-spacing: 0.3px; }
        .header-banner p  { font-size: 12px; color: rgba(255,255,255,0.8); margin-top: 3px; }
        .header-banner .date { font-size: 12px; color: rgba(255,255,255,0.75); text-align: right; line-height: 1.8; }

        /* Stats */
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0; border: 1px solid #d0d5dd; background: #fff; margin-bottom: 20px; border-radius: 3px; overflow: hidden; }

        .stat-item { padding: 20px 24px; border-right: 1px solid #d0d5dd; }
        .stat-item:last-child { border-right: none; }

        .stat-item .stat-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .stat-item .stat-label { font-size: 11px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-item .stat-icon { width: 32px; height: 32px; border-radius: 3px; display: flex; align-items: center; justify-content: center; font-size: 14px; }
        .stat-item .stat-number { font-size: 32px; font-weight: 700; color: #1a1a1a; line-height: 1; }
        .stat-item .stat-sub { font-size: 11px; color: #aaa; margin-top: 4px; }

        /* Two col layout */
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        @media (max-width: 640px) {
            .two-col { grid-template-columns: 1fr; }
        }

        /* Municipal Officials — full-width leadership spotlight */
        .officials-spotlight { background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 20px; max-width: 360px; }

        .official-track { display: flex; transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .official-slide { min-width: 100%; display: flex; min-height: 96px; }

        .official-photo-panel { width: 34%; flex-shrink: 0; background: #eef1f4; position: relative; }
        .official-photo { width: 100%; height: 100%; object-fit: cover; object-position: top; display: block; }
        .official-photo-placeholder {
            width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
            background: #eef1f4; color: #b7c0c9; font-size: 32px;
        }

        .official-info-panel {
            flex: 1;
            background: linear-gradient(135deg, #123f74, #0E4D92 55%, #0b3d75);
            color: #fff;
            padding: 14px 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .official-eyebrow {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 9.5px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase;
            color: rgba(255,255,255,0.6); margin-bottom: 6px;
        }
        .official-name-full { font-size: 16px; font-weight: 800; line-height: 1.2; margin-bottom: 2px; letter-spacing: 0.2px; }
        .official-title-full { font-size: 12px; font-weight: 500; color: rgba(255,255,255,0.78); }

        .official-controls-bar {
            background: #0b3d75;
            border-top: 1px solid rgba(255,255,255,0.08);
            padding: 6px 20px;
            display: flex; align-items: center; justify-content: center; gap: 12px;
        }
        .official-arrow {
            width: 22px; height: 22px; border-radius: 50%;
            background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.28);
            color: #fff; display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 10px; transition: background 0.2s;
        }
        .official-arrow:hover { background: rgba(255,255,255,0.24); }
        .official-dots { display: flex; align-items: center; gap: 5px; }
        .official-dot {
            width: 5px; height: 5px; border-radius: 50%; background: rgba(255,255,255,0.32);
            border: none; cursor: pointer; padding: 0; transition: all 0.25s;
        }
        .official-dot.active { width: 14px; border-radius: 3px; background: #fff; }

        @media (max-width: 640px) {
            .official-slide { flex-direction: column; min-height: auto; }
            .official-photo-panel { width: 100%; height: 120px; }
            .official-info-panel { padding: 14px 18px; }
        }

        /* Info card */
        .info-card { background: #fff; border: 1px solid #d0d5dd; border-radius: 3px; overflow: hidden; }

        .card-header { background: #f8f9fa; border-bottom: 1px solid #d0d5dd; padding: 10px 16px; display: flex; align-items: center; gap: 8px; }
        .card-header i { color: #0E4D92; font-size: 14px; }
        .card-header h3 { font-size: 12px; font-weight: 700; color: #333; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Profile info table */
        .info-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .info-table td { padding: 10px 16px; border-bottom: 1px solid #f0f0f0; vertical-align: top; }
        .info-table td:first-child { font-weight: 600; color: #666; width: 40%; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; background: #fafafa; border-right: 1px solid #f0f0f0; }
        .info-table td:last-child { color: #1a1a1a; }
        .info-table tr:last-child td { border-bottom: none; }

        /* Recent complaints */
        .complaints-card { background: #fff; border: 1px solid #d0d5dd; border-radius: 3px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { text-align: left; padding: 10px 14px; background: #f8f9fa; color: #666; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #d0d5dd; }
        td { padding: 11px 14px; border-bottom: 1px solid #f0f0f0; color: #333; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafcfe; }

        .badge { padding: 3px 10px; border-radius: 2px; font-size: 11px; font-weight: 700; display: inline-block; text-transform: uppercase; letter-spacing: 0.3px; }
        .badge-pending     { background: #FFF3CD; color: #856404; border: 1px solid #ffc107; }
        .badge-in_progress { background: #CCE5FF; color: #004085; border: 1px solid #80bdff; }
        .badge-resolved    { background: #D4EDDA; color: #155724; border: 1px solid #28a745; }

        .points-banner {
            background: #EFF5FC;
            border: 1px solid #BBD6F0;
            border-radius: 3px;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .points-banner .left { display: flex; align-items: center; gap: 12px; }
        .points-banner i { font-size: 22px; color: #0E4D92; }
        .points-banner h4 { font-size: 13px; font-weight: 700; color: #5a3e0a; }
        .points-banner p  { font-size: 11px; color: #888; margin-top: 1px; }
        .points-banner .pts { font-size: 24px; font-weight: 700; color: #0E4D92; }

        .btn-file {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 20px;
            background: #0E4D92;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            border-radius: 3px;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-file:hover { background: #0B3D75; }

        .empty-row td { text-align: center; color: #aaa; padding: 30px; font-size: 13px; }
    </style>
</head>
<body>

<?php require 'includes/header.php'; ?>

<div class="main">
    <div class="content">

        <!-- Welcome banner -->
        <div class="header-banner">
            <div class="left">
                <i class="fa-solid fa-hand-wave" style="font-size:28px;color:rgba(255,255,255,0.6)"></i>
                <div>
                    <h2>Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?></h2>
                    <p>Here's an overview of your complaints and rewards</p>
                </div>
            </div>
            <div class="date">
                <div style="font-weight:700;color:#fff"><?= date('l') ?></div>
                <div><?= date('F d, Y') ?></div>
                <div><?= date('h:i A') ?></div>
            </div>
        </div>

        <!-- Points banner -->
        <div class="points-banner">
            <div class="left">
                <i class="fa-solid fa-coins"></i>
                <div>
                    <h4>Reward Points Balance</h4>
                    <p>Earn 10 points for every resolved complaint</p>
                </div>
            </div>
            <div class="pts"><?= $user['reward_points'] ?> pts</div>
        </div>

        <!-- Stats -->
        <div class="stats">
            <div class="stat-item">
                <div class="stat-top">
                    <div class="stat-label">Total Complaints</div>
                    <div class="stat-icon" style="background:#eef2ff;color:#4f46e5">
                        <i class="fa-solid fa-file-lines"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $total_complaints ?></div>
                <div class="stat-sub">Complaints filed</div>
            </div>
            <div class="stat-item">
                <div class="stat-top">
                    <div class="stat-label">Pending</div>
                    <div class="stat-icon" style="background:#fff3cd;color:#856404">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $pending_complaints ?></div>
                <div class="stat-sub">Awaiting action</div>
            </div>
            <div class="stat-item">
                <div class="stat-top">
                    <div class="stat-label">Resolved</div>
                    <div class="stat-icon" style="background:#d4edda;color:#155724">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $resolved_complaints ?></div>
                <div class="stat-sub">Successfully resolved</div>
            </div>
        </div>

        <!-- Municipal Officials — leadership spotlight -->
        <div class="officials-spotlight">
            <div class="official-track" id="officialTrack">

                <?php
                // Add/edit officials here. Set 'photo' to a real image path
                // (e.g. '../assets/img/officials/mayor.jpg') once you have one on hand.
                $officials = [
                    ['name' => 'Hon. May B. Eclar',  'title' => 'Municipal Mayor',      'photo' => '../assets/img/MAYOR-MAY-ECLAR.jpg'],
                    ['name' => 'Hon. Eloy C. Eclar', 'title' => 'Municipal Vice Mayor', 'photo' => '../assets/img/VICE-MAYOR-ELOY-ECLAR.jpg'],
                ];
                foreach ($officials as $o): ?>
                    <div class="official-slide">
                        <div class="official-photo-panel">
                            <?php if ($o['photo']): ?>
                                <img class="official-photo" src="<?= htmlspecialchars($o['photo']) ?>" alt="<?= htmlspecialchars($o['name']) ?>">
                            <?php else: ?>
                                <div class="official-photo-placeholder"><i class="fa-solid fa-user"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="official-info-panel">
                            <div class="official-eyebrow"><i class="fa-solid fa-landmark-dome"></i> Elected Official of Gerona</div>
                            <div class="official-name-full"><?= htmlspecialchars($o['name']) ?></div>
                            <div class="official-title-full"><?= htmlspecialchars($o['title']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
            <div class="official-controls-bar">
                <button type="button" class="official-arrow" onclick="officialMove(-1)"><i class="fa-solid fa-chevron-left"></i></button>
                <div class="official-dots" id="officialDots"></div>
                <button type="button" class="official-arrow" onclick="officialMove(1)"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </div>

        <!-- Two col -->
        <div class="two-col">

            <!-- Resident info -->
            <div class="info-card">
                <div class="card-header">
                    <i class="fa-solid fa-user"></i>
                    <h3>Resident Information</h3>
                </div>
                <table class="info-table">
                    <tr>
                        <td>Full Name</td>
                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                    </tr>
                    <tr>
                        <td>Address</td>
                        <td><?= htmlspecialchars($user['address']) ?></td>
                    </tr>
                    <tr>
                        <td>Email</td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                    </tr>
                    <tr>
                        <td>Account Status</td>
                        <td>
                            <span class="badge badge-<?= $user['status'] === 'verified' ? 'resolved' : ($user['status'] === 'pending' ? 'pending' : 'in_progress') ?>">
                                <?= strtoupper($user['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>Registered</td>
                        <td><?= date('F d, Y', strtotime($user['created_at'])) ?></td>
                    </tr>
                </table>
            </div>

            <!-- Quick actions -->
            <div class="info-card">
                <div class="card-header">
                    <i class="fa-solid fa-bolt"></i>
                    <h3>Quick Actions</h3>
                </div>
                <div style="padding:20px;display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <a href="complaint.php" class="btn-file" style="justify-content:center">
                        <i class="fa-solid fa-plus"></i> File a New Complaint
                    </a>
                    <a href="my_complaints.php" class="btn-file" style="justify-content:center;background:#555">
                        <i class="fa-solid fa-list"></i> View My Complaints
                    </a>
                    <a href="rewards.php" class="btn-file" style="justify-content:center;background:#1a6b3c">
                        <i class="fa-solid fa-trophy"></i> View My Rewards
                    </a>
                    <a href="profile.php" class="btn-file" style="justify-content:center;background:#2c5282">
                        <i class="fa-solid fa-user-pen"></i> Edit My Profile
                    </a>
                </div>
            </div>

        </div>

        <!-- Recent complaints -->
        <div class="complaints-card">
            <div class="card-header" style="justify-content:space-between">
                <div style="display:flex;align-items:center;gap:8px">
                    <i class="fa-solid fa-clock-rotate-left" style="color:#0E4D92;font-size:14px"></i>
                    <h3 style="font-size:12px;font-weight:700;color:#333;text-transform:uppercase;letter-spacing:0.5px">Recent Complaints</h3>
                </div>
                <a href="my_complaints.php" style="font-size:11px;color:#0E4D92;text-decoration:none;font-weight:600;text-transform:uppercase;letter-spacing:0.3px">View All →</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Category</th>
                        <th>Barangay</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Date Filed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent)): ?>
                        <tr class="empty-row">
                            <td colspan="6">No complaints filed yet. <a href="complaint.php" style="color:#0E4D92;font-weight:600">File your first complaint →</a></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent as $i => $c): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($c['category']) ?></td>
                            <td><?= htmlspecialchars($c['barangay']) ?></td>
                            <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                <?= htmlspecialchars($c['description']) ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= $c['status'] ?>">
                                    <?= strtoupper(str_replace('_', ' ', $c['status'])) ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script>
    (function () {
        const track = document.getElementById('officialTrack');
        const dotsWrap = document.getElementById('officialDots');
        if (!track) return;

        const slides = track.children;
        let index = 0;

        for (let i = 0; i < slides.length; i++) {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'official-dot' + (i === 0 ? ' active' : '');
            dot.addEventListener('click', () => officialGoTo(i));
            dotsWrap.appendChild(dot);
        }

        function render() {
            track.style.transform = 'translateX(-' + (index * 100) + '%)';
            const dots = dotsWrap.children;
            for (let i = 0; i < dots.length; i++) {
                dots[i].classList.toggle('active', i === index);
            }
        }

        window.officialMove = function (dir) {
            index = (index + dir + slides.length) % slides.length;
            render();
        };
        window.officialGoTo = function (i) {
            index = i;
            render();
        };
    })();
</script>
</body>
</html>