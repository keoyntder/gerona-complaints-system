<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../config/database.php';

$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$pending_users      = $pdo->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
$pending_complaints = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status='pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Residents — Ragsak Ranyag Gerona</title>
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

        .table-card { background: #fff; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); overflow: hidden; }
        .table-header { padding: 18px 24px; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; justify-content: space-between; }
        .table-header h3 { font-size: 15px; font-weight: 700; color: #1a1a1a; }

        .search-wrap { position: relative; }
        .search-wrap i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: 13px; }
        .search-wrap input { padding: 8px 12px 8px 32px; border: 1.5px solid #eee; border-radius: 6px; font-size: 13px; outline: none; width: 220px; font-family: 'Inter', sans-serif; }
        .search-wrap input:focus { border-color: #8B6914; }

        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; padding: 12px 16px; background: #fafafa; color: #888; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #f0f0f0; }
        td { padding: 14px 16px; border-bottom: 1px solid #f8f8f8; color: #333; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafafa; }

        .badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge-pending  { background: #FFF3CD; color: #856404; }
        .badge-verified { background: #D1E7DD; color: #0A6640; }
        .badge-rejected { background: #F8D7DA; color: #842029; }

        .action-btn { padding: 5px 12px; border: none; border-radius: 5px; font-size: 12px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; margin-right: 4px; }
        .btn-verify { background: #D1E7DD; color: #0A6640; }
        .btn-verify:hover { background: #b8ddc9; }
        .btn-reject { background: #F8D7DA; color: #842029; }
        .btn-reject:hover { background: #f0bec3; }

        .view-id { color: #8B6914; font-size: 13px; text-decoration: none; font-weight: 500; }
        .view-id:hover { text-decoration: underline; }

        .points-chip { display: inline-flex; align-items: center; gap: 4px; background: #FFF8E7; color: #8B6914; font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 20px; }

        .empty-state { text-align: center; padding: 60px 20px; color: #aaa; }
        .empty-state i { font-size: 48px; margin-bottom: 16px; display: block; }
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
    <a class="nav-item active" href="residents.php">
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
        <h1>Residents</h1>
        <div style="display:flex;align-items:center;gap:12px">
            <span style="font-size:14px;font-weight:600"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
        </div>
    </div>

    <div class="content">
        <div class="table-card">
            <div class="table-header">
                <h3>All Residents</h3>
                <div class="search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" placeholder="Search residents...">
                </div>
            </div>

            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-users"></i>
                    <p>No residents registered yet.</p>
                </div>
            <?php else: ?>
                <table id="residentsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>ID Photo</th>
                            <th>Reward Points</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $i => $user): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <div style="font-weight:600"><?= htmlspecialchars($user['full_name']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td style="max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                <?= htmlspecialchars($user['address']) ?>
                            </td>
                            <td>
                                <a class="view-id" href="../<?= $user['id_photo'] ?>" target="_blank">
                                    <i class="fa-regular fa-id-card"></i> View ID
                                </a>
                            </td>
                            <td>
                                <span class="points-chip">
                                    <i class="fa-solid fa-coins"></i>
                                    <?= $user['reward_points'] ?> pts
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $user['status'] ?>">
                                    <?= ucfirst($user['status']) ?>
                                </span>
                            </td>
                            <td style="color:#888;font-size:13px">
                                <?= date('M d, Y', strtotime($user['created_at'])) ?>
                            </td>
                            <td>
                                <?php if ($user['status'] === 'pending'): ?>
                                    <form action="verify_user.php" method="POST" style="display:inline">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="action" value="verify">
                                        <button type="submit" class="action-btn btn-verify">
                                            <i class="fa-solid fa-check"></i> Verify
                                        </button>
                                    </form>
                                    <form action="verify_user.php" method="POST" style="display:inline">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="action-btn btn-reject">
                                            <i class="fa-solid fa-xmark"></i> Reject
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="color:#bbb;font-size:13px">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.getElementById('searchInput').addEventListener('input', function () {
        const val = this.value.toLowerCase();
        document.querySelectorAll('#residentsTable tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
        });
    });
</script>
</body>
</html>