<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'resident') {
    header('Location: ../login.php');
    exit;
}
require_once '../config/database.php';

$stmt = $pdo->prepare("SELECT * FROM complaints WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

$active_page = 'my_complaints';
$page_title  = 'My Complaints';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Complaints — Ragsak Ranyag Gerona</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/gov-header.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f4f6f8; min-height: 100vh; }

        .main { flex: 1; }

        .content { padding: 28px; max-width: 1240px; margin: 0 auto; }

        .table-card { background: #fff; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); overflow: hidden; }
        .table-header { padding: 18px 24px; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; justify-content: space-between; }
        .table-header h3 { font-size: 15px; font-weight: 700; color: #1a1a1a; }

        .btn-new { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #0E4D92; color: #fff; font-size: 13px; font-weight: 600; border-radius: 6px; text-decoration: none; }
        .btn-new:hover { background: #1A63B8; }
        .btn-pdf { display: inline-flex; align-items: center; gap: 6px; padding: 7px 10px; background: #eef4fb; color: #0E4D92; font-size: 12px; font-weight: 600; border-radius: 5px; text-decoration: none; white-space: nowrap; }
        .btn-pdf:hover { background: #dceafb; }

        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; padding: 12px 16px; background: #fafafa; color: #888; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #f0f0f0; }
        td { padding: 14px 16px; border-bottom: 1px solid #f8f8f8; color: #333; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafafa; }

        .badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge-pending     { background: #FFF3CD; color: #856404; }
        .badge-in_progress { background: #CCE5FF; color: #004085; }
        .badge-resolved    { background: #D1E7DD; color: #0A6640; }

        .empty-state { text-align: center; padding: 60px 20px; color: #aaa; }
        .empty-state i { font-size: 48px; margin-bottom: 16px; display: block; }
        .empty-state p { font-size: 14px; margin-bottom: 16px; }
    </style>
</head>
<body>

<?php require 'includes/header.php'; ?>

<div class="main">
    <div class="content">
        <div class="table-card">
            <div class="table-header">
                <h3>Complaint History</h3>
                <a href="complaint.php" class="btn-new">
                    <i class="fa-solid fa-plus"></i> New Complaint
                </a>
            </div>

            <?php if (empty($complaints)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-folder-open"></i>
                    <p>You haven't filed any complaints yet.</p>
                    <a href="complaint.php" class="btn-new">
                        <i class="fa-solid fa-plus"></i> File your first complaint
                    </a>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Category</th>
                            <th>Barangay</th>
                            <th>Location</th>
                            <th>Description</th>
                            <th>Photo</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($complaints as $i => $c): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($c['category']) ?></td>
                            <td><?= htmlspecialchars($c['barangay']) ?></td>
                            <td><?= htmlspecialchars($c['location']) ?></td>
                            <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                <?= htmlspecialchars($c['description']) ?>
                            </td>
                            <td>
                                <?php if ($c['photo']): ?>
                                    <a href="../<?= $c['photo'] ?>" target="_blank" style="color:#0E4D92;font-size:13px;font-weight:500">
                                        <i class="fa-solid fa-image"></i> View
                                    </a>
                                <?php else: ?>
                                    <span style="color:#bbb;font-size:13px">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= $c['status'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $c['status'])) ?>
                                </span>
                            </td>
                            <td style="color:#888;font-size:13px">
                                <?= date('M d, Y \a\t h:i A', strtotime($c['created_at'])) ?>
                            </td>
                            <td>
                                <a class="btn-pdf" href="complaint_pdf.php?id=<?= (int) $c['id'] ?>" target="_blank" rel="noopener noreferrer">
                                    <i class="fa-solid fa-file-pdf"></i> PDF
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>