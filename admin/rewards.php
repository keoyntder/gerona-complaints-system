<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../config/database.php';

$pending_users      = $pdo->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
$pending_complaints = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status='pending'")->fetchColumn();
$total_points       = $pdo->query("SELECT SUM(reward_points) FROM users")->fetchColumn() ?? 0;
$total_rewarded     = $pdo->query("SELECT COUNT(*) FROM users WHERE reward_points > 0")->fetchColumn();
$total_resolved     = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status='resolved'")->fetchColumn();

$users = $pdo->query("
    SELECT u.*, COUNT(c.id) as resolved_count
    FROM users u
    LEFT JOIN complaints c ON u.id = c.user_id AND c.status = 'resolved'
    GROUP BY u.id
    ORDER BY u.reward_points DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rewards — Ragsak Ranyag Gerona</title>
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

        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
        .stat-card .stat-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 12px; }
        .stat-card .number { font-size: 28px; font-weight: 700; color: #1a1a1a; }
        .stat-card .label  { font-size: 12px; color: #888; margin-top: 2px; }

        .table-card { background: #fff; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); overflow: hidden; }
        .table-header { padding: 18px 24px; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; justify-content: space-between; }
        .table-header h3 { font-size: 15px; font-weight: 700; color: #1a1a1a; }
        .table-header p  { font-size: 13px; color: #888; margin-top: 2px; }

        .search-wrap { position: relative; }
        .search-wrap i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: 13px; }
        .search-wrap input { padding: 8px 12px 8px 32px; border: 1.5px solid #eee; border-radius: 6px; font-size: 13px; outline: none; width: 220px; font-family: 'Inter', sans-serif; }
        .search-wrap input:focus { border-color: #8B6914; }

        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; padding: 12px 16px; background: #fafafa; color: #888; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #f0f0f0; }
        td { padding: 14px 16px; border-bottom: 1px solid #f8f8f8; color: #333; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafafa; }

        .rank-badge { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; }
        .rank-1 { background: #FFD700; color: #7a5c00; }
        .rank-2 { background: #C0C0C0; color: #555; }
        .rank-3 { background: #CD7F32; color: #fff; }
        .rank-other { background: #f0f0f0; color: #888; }

        .points-chip { display: inline-flex; align-items: center; gap: 4px; background: #FFF8E7; color: #8B6914; font-size: 13px; font-weight: 700; padding: 4px 12px; border-radius: 20px; border: 1px solid #f0d080; }

        .badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge-pending  { background: #FFF3CD; color: #856404; }
        .badge-verified { background: #D1E7DD; color: #0A6640; }
        .badge-rejected { background: #F8D7DA; color: #842029; }

        .no-points { color: #bbb; font-size: 13px; }
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
    <a class="nav-item active" href="rewards.php"><i class="fa-solid fa-trophy"></i> Rewards</a>
    <a class="nav-item" href="reports.php"><i class="fa-solid fa-chart-bar"></i> Reports</a>
    <a class="nav-item" href="verify_reward.php">
        <i class="fa-solid fa-chart-bar"></i> Verify Rewards
    </a>
    <div class="sidebar-bottom">
        
        <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <h1>Rewards</h1>
        <div style="display:flex;align-items:center;gap:12px">
            <span style="font-size:14px;font-weight:600"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
        </div>
    </div>

    <div class="content">

        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon" style="background:#FFF8E7;color:#8B6914">
                    <i class="fa-solid fa-coins"></i>
                </div>
                <div class="number"><?= $total_points ?></div>
                <div class="label">Total Points Awarded</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#D1E7DD;color:#0A6640">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="number"><?= $total_resolved ?></div>
                <div class="label">Complaints Resolved</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#EEF2FF;color:#4F46E5">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="number"><?= $total_rewarded ?></div>
                <div class="label">Residents with Rewards</div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-header">
                <div>
                    <h3>Resident Leaderboard</h3>
                    <p>Ranked by reward points earned</p>
                </div>
                <div class="search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" placeholder="Search residents...">
                </div>
            </div>

            <table id="rewardsTable">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Resident</th>
                        <th>Email</th>
                        <th>Resolved Complaints</th>
                        <th>Status</th>
                        <th>Points Earned</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $i => $user): ?>
                    <tr>
                        <td>
                            <?php
                            $rank = $i + 1;
                            $rankClass = $rank <= 3 ? "rank-$rank" : "rank-other";
                            ?>
                            <div class="rank-badge <?= $rankClass ?>">
                                <?php if ($rank === 1): ?>
                                    <i class="fa-solid fa-trophy"></i>
                                <?php elseif ($rank === 2): ?>
                                    <i class="fa-solid fa-medal"></i>
                                <?php elseif ($rank === 3): ?>
                                    <i class="fa-solid fa-award"></i>
                                <?php else: ?>
                                    <?= $rank ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight:600"><?= htmlspecialchars($user['full_name']) ?></div>
                            <div style="font-size:12px;color:#888"><?= htmlspecialchars($user['address']) ?></div>
                        </td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td style="text-align:center">
                            <strong><?= $user['resolved_count'] ?></strong>
                        </td>
                        <td>
                            <span class="badge badge-<?= $user['status'] ?>">
                                <?= ucfirst($user['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user['reward_points'] > 0): ?>
                                <span class="points-chip">
                                    <i class="fa-solid fa-coins"></i>
                                    <?= $user['reward_points'] ?> pts
                                </span>
                            <?php else: ?>
                                <span class="no-points">No points yet</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script>
    document.getElementById('searchInput').addEventListener('input', function () {
        const val = this.value.toLowerCase();
        document.querySelectorAll('#rewardsTable tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
        });
    });
</script>
</body>
</html>