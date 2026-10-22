<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../config/database.php';

// Stats
$total_complaints  = $pdo->query("SELECT COUNT(*) FROM complaints")->fetchColumn();
$pending           = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status='pending'")->fetchColumn();
$in_progress       = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status='in_progress'")->fetchColumn();
$resolved          = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status='resolved'")->fetchColumn();
$total_users       = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$verified_users    = $pdo->query("SELECT COUNT(*) FROM users WHERE status='verified'")->fetchColumn();
$total_points      = $pdo->query("SELECT SUM(reward_points) FROM users")->fetchColumn() ?? 0;

// Complaints by category
$by_category = $pdo->query("
    SELECT category, COUNT(*) as total 
    FROM complaints 
    GROUP BY category 
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Complaints by barangay
$by_barangay = $pdo->query("
    SELECT barangay, COUNT(*) as total 
    FROM complaints 
    GROUP BY barangay 
    ORDER BY total DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Monthly complaints
$monthly = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%b %Y') as month, COUNT(*) as total
    FROM complaints
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY created_at DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$pending_users = $pdo->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
$pending_complaints = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status='pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports — Ragsak Ranyag Gerona</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f4f6f8; display: flex; min-height: 100vh; }

        .sidebar { width: 220px; background: #fff; border-right: 1px solid #eee; display: flex; flex-direction: column; padding: 24px 0; position: fixed; top: 0; left: 0; bottom: 0; }
        .sidebar-logo { display: flex; align-items: center; gap: 10px; padding: 0 20px 24px; border-bottom: 1px solid #f0f0f0; }
        .sidebar-logo img { width: 36px; height: 36px; object-fit: contain; }
        .sidebar-logo span { font-size: 14px; font-weight: 700; color: #1a1a1a; line-height: 1.3; }
        .nav-label { font-size: 10px; font-weight: 600; color: #aaa; text-transform: uppercase; letter-spacing: 1px; padding: 20px 20px 8px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 20px; font-size: 14px; color: #555; text-decoration: none; transition: all 0.15s; }
        .nav-item:hover { background: #f4f6f8; color: #1a1a1a; }
        .nav-item.active { background: #FFF8E7; color: #8B6914; font-weight: 600; border-right: 3px solid #8B6914; }
        .nav-item i { width: 18px; text-align: center; font-size: 15px; }
        .badge-count { margin-left: auto; background: #8B6914; color: #fff; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 20px; }
        .sidebar-bottom { margin-top: auto; padding: 16px 20px; border-top: 1px solid #f0f0f0; }
        .sidebar-bottom a { display: flex; align-items: center; gap: 10px; font-size: 14px; color: #e53935; text-decoration: none; font-weight: 500; }

        .main { margin-left: 220px; flex: 1; }
        .topbar { background: #fff; border-bottom: 1px solid #eee; padding: 14px 28px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 10; }
        .topbar h1 { font-size: 18px; font-weight: 700; color: #1a1a1a; }
        .user-avatar { width: 36px; height: 36px; border-radius: 50%; background: #8B6914; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 600; }

        .content { padding: 28px; }

        /* Stats grid */
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
        .stat-card .stat-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 12px; }
        .stat-card .number { font-size: 28px; font-weight: 700; color: #1a1a1a; }
        .stat-card .label  { font-size: 12px; color: #888; margin-top: 2px; }

        /* Two column layout */
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }

        .report-card { background: #fff; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); overflow: hidden; }
        .report-card-header { padding: 16px 20px; border-bottom: 1px solid #f0f0f0; }
        .report-card-header h3 { font-size: 14px; font-weight: 700; color: #1a1a1a; }
        .report-card-body { padding: 16px 20px; }

        /* Bar chart */
        .bar-item { margin-bottom: 14px; }
        .bar-label { display: flex; justify-content: space-between; font-size: 13px; color: #333; margin-bottom: 5px; }
        .bar-label span { color: #888; font-size: 12px; }
        .bar-track { background: #f0f0f0; border-radius: 4px; height: 8px; overflow: hidden; }
        .bar-fill { height: 100%; border-radius: 4px; background: linear-gradient(to right, #8B6914, #c9a227); transition: width 0.6s ease; }

        /* Monthly table */
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; padding: 10px 12px; background: #fafafa; color: #888; font-weight: 600; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #f0f0f0; }
        td { padding: 12px; border-bottom: 1px solid #f8f8f8; color: #333; }
        tr:last-child td { border-bottom: none; }

        .badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge-pending     { background: #FFF3CD; color: #856404; }
        .badge-in_progress { background: #CCE5FF; color: #004085; }
        .badge-resolved    { background: #D1E7DD; color: #0A6640; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo">
        <img src="../assets/img/gerona-seal.png" alt="Seal">
        <span>Ragsak Ranyag<br>Gerona</span>
    </div>
    <div class="nav-label">Main Menu</div>
    <a class="nav-item" href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
    <a class="nav-item" href="residents.php">
        <i class="fa-solid fa-users"></i> Residents
        <?php if ($pending_users > 0): ?>
            <span class="badge-count"><?= $pending_users ?></span>
        <?php endif; ?>
    </a>
    <a class="nav-item" href="complaints.php">
        <i class="fa-solid fa-triangle-exclamation"></i> Complaints
        <?php if ($pending_complaints > 0): ?>
            <span class="badge-count"><?= $pending_complaints ?></span>
        <?php endif; ?>
    </a>
    <a class="nav-item" href="rewards.php"><i class="fa-solid fa-trophy"></i> Rewards</a>
   
    <a class="nav-item active" href="reports.php"><i class="fa-solid fa-chart-bar"></i> Reports</a>
     <a class="nav-item" href="verify_reward.php">
        <i class="fa-solid fa-chart-bar"></i> Verify Rewards
    </a>
    <div class="sidebar-bottom">
        <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <h1>Reports</h1>
        <div style="display:flex;align-items:center;gap:12px">
            <span style="font-size:14px;font-weight:600"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
        </div>
    </div>

    <div class="content">

        <!-- Stats -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon" style="background:#EEF2FF;color:#4F46E5">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="number"><?= $total_complaints ?></div>
                <div class="label">Total Complaints</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#D1E7DD;color:#0A6640">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="number"><?= $resolved ?></div>
                <div class="label">Resolved</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#FFF3CD;color:#856404">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="number"><?= $verified_users ?></div>
                <div class="label">Verified Residents</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#FFF8E7;color:#8B6914">
                    <i class="fa-solid fa-coins"></i>
                </div>
                <div class="number"><?= $total_points ?></div>
                <div class="label">Total Points Given</div>
            </div>
        </div>

        <!-- Two columns -->
        <div class="two-col">

            <!-- By category -->
            <div class="report-card">
                <div class="report-card-header">
                    <h3>Complaints by Category</h3>
                </div>
                <div class="report-card-body">
                    <?php
                    $max = !empty($by_category) ? $by_category[0]['total'] : 1;
                    foreach ($by_category as $row):
                        $pct = round(($row['total'] / $max) * 100);
                    ?>
                    <div class="bar-item">
                        <div class="bar-label">
                            <?= htmlspecialchars($row['category']) ?>
                            <span><?= $row['total'] ?></span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width:<?= $pct ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($by_category)): ?>
                        <p style="color:#aaa;font-size:13px;text-align:center;padding:20px">No data yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- By barangay -->
            <div class="report-card">
                <div class="report-card-header">
                    <h3>Complaints by Barangay</h3>
                </div>
                <div class="report-card-body">
                    <?php
                    $maxB = !empty($by_barangay) ? $by_barangay[0]['total'] : 1;
                    foreach ($by_barangay as $row):
                        $pct = round(($row['total'] / $maxB) * 100);
                    ?>
                    <div class="bar-item">
                        <div class="bar-label">
                            <?= htmlspecialchars($row['barangay']) ?>
                            <span><?= $row['total'] ?></span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width:<?= $pct ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($by_barangay)): ?>
                        <p style="color:#aaa;font-size:13px;text-align:center;padding:20px">No data yet.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Monthly -->
        <div class="report-card">
            <div class="report-card-header">
                <h3>Monthly Complaint Summary</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Total Complaints</th>
                        <th>Progress</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $maxM = !empty($monthly) ? max(array_column($monthly, 'total')) : 1;
                    foreach ($monthly as $row):
                        $pct = round(($row['total'] / $maxM) * 100);
                    ?>
                    <tr>
                        <td><?= $row['month'] ?></td>
                        <td><strong><?= $row['total'] ?></strong></td>
                        <td style="width:50%">
                            <div class="bar-track">
                                <div class="bar-fill" style="width:<?= $pct ?>%"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($monthly)): ?>
                        <tr><td colspan="3" style="text-align:center;color:#aaa;padding:20px">No data yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

</body>
</html>