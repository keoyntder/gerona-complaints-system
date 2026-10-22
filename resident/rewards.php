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

$total = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE user_id = ?");
$total->execute([$user_id]);
$total = $total->fetchColumn();

$resolved = $pdo->prepare("SELECT * FROM complaints WHERE user_id = ? AND status = 'resolved' ORDER BY created_at DESC");
$resolved->execute([$user_id]);
$resolved = $resolved->fetchAll(PDO::FETCH_ASSOC);

$active_page = 'rewards';
$page_title  = 'My Rewards';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Rewards — Ragsak Ranyag Gerona</title>
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
            border-radius: 3px;
            padding: 20px 24px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-banner .left { display: flex; align-items: center; gap: 16px; }
        .header-banner i.big { font-size: 40px; color: rgba(255,255,255,0.25); }
        .header-banner h2 { font-size: 15px; font-weight: 700; color: #fff; }
        .header-banner p  { font-size: 12px; color: rgba(255,255,255,0.75); margin-top: 3px; }
        .header-banner .pts-display { text-align: right; }
        .header-banner .pts-display .pts { font-size: 40px; font-weight: 700; color: #fff; line-height: 1; }
        .header-banner .pts-display small { font-size: 12px; color: rgba(255,255,255,0.7); }

        /* Stats */
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0; border: 1px solid #d0d5dd; background: #fff; margin-bottom: 20px; border-radius: 3px; overflow: hidden; }
        .stat-item { padding: 18px 22px; border-right: 1px solid #d0d5dd; }
        .stat-item:last-child { border-right: none; }
        .stat-item .stat-label { font-size: 11px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
        .stat-item .stat-number { font-size: 30px; font-weight: 700; color: #1a1a1a; line-height: 1; }
        .stat-item .stat-sub { font-size: 11px; color: #aaa; margin-top: 4px; }

        /* Table card */
        .table-card { background: #fff; border: 1px solid #d0d5dd; border-radius: 3px; overflow: hidden; }
        .card-header { background: #f8f9fa; border-bottom: 1px solid #d0d5dd; padding: 10px 16px; display: flex; align-items: center; gap: 8px; }
        .card-header i { color: #0E4D92; font-size: 14px; }
        .card-header h3 { font-size: 12px; font-weight: 700; color: #333; text-transform: uppercase; letter-spacing: 0.5px; }

        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { text-align: left; padding: 10px 14px; background: #f8f9fa; color: #666; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #d0d5dd; }
        td { padding: 12px 14px; border-bottom: 1px solid #f0f0f0; color: #333; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafcfe; }

        .points-chip { display: inline-flex; align-items: center; gap: 5px; background: #EFF5FC; color: #0E4D92; font-size: 12px; font-weight: 700; padding: 4px 12px; border-radius: 2px; border: 1px solid #BBD6F0; }

        .empty-row td { text-align: center; color: #aaa; padding: 40px; }
        .empty-row i { font-size: 32px; display: block; margin-bottom: 10px; color: #ddd; }

        /* How it works */
        .how-card { background: #fff; border: 1px solid #d0d5dd; border-radius: 3px; overflow: hidden; margin-bottom: 20px; }
        .steps { display: grid; grid-template-columns: repeat(3, 1fr); }
        .step { padding: 20px; border-right: 1px solid #eee; text-align: center; }
        .step:last-child { border-right: none; }
        .step-num { width: 32px; height: 32px; border-radius: 50%; background: #0E4D92; color: #fff; font-size: 14px; font-weight: 700; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; }
        .step h4 { font-size: 13px; font-weight: 700; color: #1a1a1a; margin-bottom: 4px; }
        .step p  { font-size: 12px; color: #888; line-height: 1.5; }
    </style>
</head>
<body>

<?php require 'includes/header.php'; ?>

<div class="main">
    <div class="content">

        <!-- Header banner -->
        <div class="header-banner">
            <div class="left">
                <i class="fa-solid fa-trophy big"></i>
                <div>
                    <h2>REWARD POINTS SUMMARY</h2>
                    <p>Municipality of Gerona, Tarlac — Resident Reward Program</p>
                    <p style="margin-top:6px;font-size:11px;color:rgba(255,255,255,0.6)">Earn 10 points for every complaint resolved by LGU Gerona</p>
                </div>
            </div>
            <div class="pts-display">
                <div class="pts"><?= $user['reward_points'] ?></div>
                <small>Total Points Earned</small>
            </div>
        </div>

        <div style="margin-bottom:20px">
            <a href="redeem_board.php" style="display:inline-flex;align-items:center;gap:8px;padding:11px 22px;background:#0E4D92;color:#fff;font-size:13px;font-weight:700;border-radius:4px;text-decoration:none;text-transform:uppercase;letter-spacing:0.4px">
                <i class="fa-solid fa-gift"></i> Redeem My Points
            </a>
        </div>

        <!-- Stats -->
        <div class="stats">
            <div class="stat-item">
                <div class="stat-label">Total Complaints Filed</div>
                <div class="stat-number"><?= $total ?></div>
                <div class="stat-sub">All complaints submitted</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Complaints Resolved</div>
                <div class="stat-number"><?= count($resolved) ?></div>
                <div class="stat-sub">Successfully resolved</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Points Earned</div>
                <div class="stat-number"><?= $user['reward_points'] ?></div>
                <div class="stat-sub"><?= count($resolved) ?> × 10 pts each</div>
            </div>
        </div>

        <!-- How it works -->
        <div class="how-card">
            <div class="card-header">
                <i class="fa-solid fa-circle-info"></i>
                <h3>How the Reward System Works</h3>
            </div>
            <div class="steps">
                <div class="step">
                    <div class="step-num">1</div>
                    <h4>File a Complaint</h4>
                    <p>Report a problem in your barangay through the complaint form</p>
                </div>
                <div class="step">
                    <div class="step-num">2</div>
                    <h4>LGU Reviews & Resolves</h4>
                    <p>LGU Gerona verifies and resolves the reported issue</p>
                </div>
                <div class="step">
                    <div class="step-num">3</div>
                    <h4>Earn 10 Points</h4>
                    <p>You automatically receive 10 reward points upon resolution</p>
                </div>
            </div>
        </div>

        <!-- Reward history table -->
        <div class="table-card">
            <div class="card-header">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <h3>Reward History</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Category</th>
                        <th>Barangay</th>
                        <th>Description</th>
                        <th>Date Filed</th>
                        <th>Points Earned</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($resolved)): ?>
                        <tr class="empty-row">
                            <td colspan="6">
                                <i class="fa-solid fa-trophy"></i>
                                No rewards yet. File a complaint and earn points when it gets resolved!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($resolved as $i => $c): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($c['category']) ?></td>
                            <td><?= htmlspecialchars($c['barangay']) ?></td>
                            <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                <?= htmlspecialchars($c['description']) ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
                            <td>
                                <span class="points-chip">
                                    <i class="fa-solid fa-coins"></i> +10 pts
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

</body>
</html>