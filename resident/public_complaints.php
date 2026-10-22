<?php
session_start();
require_once '../config/database.php';

$categories = [
    'Road and Infrastructure', 'Garbage and Sanitation', 'Flooding and Drainage',
    'Illegal Construction', 'Noise Complaint', 'Illegal Logging', 'Water Supply',
    'Electricity', 'Public Safety', 'Others'
];

$barangays = [
    'Abagon', 'Amacalan', 'Apsayan', 'Ayson', 'Bawa', 'Buenlag',
    'Bularit', 'Calayaan', 'Carbonel', 'Cardona', 'Caturay', 'Danzo',
    'Dicolor', 'Don Basilio', 'Luna', 'Mabini', 'Magaspac', 'Malayep',
    'Matapitap', 'Matayumcab', 'New Salem', 'Oloybuaya', 'Padapada',
    'Parsolingan', 'Pinasling', 'Plastado', 'Poblacion 1', 'Poblacion 2',
    'Poblacion 3', 'Quezon', 'Rizal', 'Salapungan', 'San Agustin',
    'San Antonio', 'San Bartolome', 'San Jose', 'Santa Lucia', 'Santiago',
    'Sembrano', 'Singat', 'Sulipa', 'Tagumbao', 'Tangcaran', 'Villa Paz'
];

$filter_category = $_GET['category'] ?? '';
$filter_barangay = $_GET['barangay'] ?? '';
$filter_status   = $_GET['status'] ?? '';
$search          = $_GET['search'] ?? '';

$where  = ['1=1'];
$params = [];

if ($filter_category) { $where[] = 'c.category = ?'; $params[] = $filter_category; }
if ($filter_barangay) { $where[] = 'c.barangay = ?'; $params[] = $filter_barangay; }
if ($filter_status)   { $where[] = 'c.status = ?';   $params[] = $filter_status; }
if ($search) {
    $where[]  = '(c.description LIKE ? OR c.location LIKE ? OR c.category LIKE ? OR c.barangay LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql = "
    SELECT c.*, u.full_name
    FROM complaints c
    JOIN users u ON c.user_id = u.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY c.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total    = $pdo->query("SELECT COUNT(*) FROM complaints")->fetchColumn();
$pending  = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status='pending'")->fetchColumn();
$progress = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status='in_progress'")->fetchColumn();
$resolved = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status='resolved'")->fetchColumn();

// Helper: relative "time ago" label, Facebook-feed style
function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M d, Y', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Complaints Board — Ragsak Ranyag Gerona</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/gov-header.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; min-height: 100vh; }

        .content { max-width: 1240px; margin: 0 auto; padding: 24px 28px; }

        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0; border: 1px solid #d0d5dd; background: #fff; margin-bottom: 20px; border-radius: 3px; overflow: hidden; }
        .stat-item { padding: 16px 20px; border-right: 1px solid #d0d5dd; }
        .stat-item:last-child { border-right: none; }
        .stat-item .stat-label { font-size: 11px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
        .stat-item .stat-number { font-size: 28px; font-weight: 700; color: #1a1a1a; line-height: 1; }
        .stat-item .stat-sub { font-size: 11px; color: #aaa; margin-top: 3px; }

        .filter-bar { background: #fff; border: 1px solid #d0d5dd; border-radius: 3px; padding: 16px 20px; margin-bottom: 20px; }
        .filter-bar form { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; gap: 4px; }
        .filter-group label { font-size: 11px; font-weight: 700; color: #666; text-transform: uppercase; letter-spacing: 0.4px; }
        .filter-group select, .filter-group input[type=text] { padding: 8px 10px; border: 1px solid #c8ccd0; border-radius: 3px; font-size: 13px; font-family: 'Inter', sans-serif; color: #1a1a1a; background: #fff; outline: none; min-width: 160px; }
        .filter-group select:focus, .filter-group input[type=text]:focus { border-color: #0E4D92; }
        .search-group { flex: 1; min-width: 200px; }
        .search-wrap { position: relative; }
        .search-wrap i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: 13px; }
        .search-wrap input { width: 100%; padding: 8px 10px 8px 32px; border: 1px solid #c8ccd0; border-radius: 3px; font-size: 13px; font-family: 'Inter', sans-serif; outline: none; }
        .search-wrap input:focus { border-color: #0E4D92; }
        .btn-filter { padding: 8px 20px; background: #0E4D92; color: #fff; font-size: 13px; font-weight: 600; border: none; border-radius: 3px; cursor: pointer; font-family: 'Inter', sans-serif; display: inline-flex; align-items: center; gap: 6px; }
        .btn-filter:hover { background: #0B3D75; }
        .btn-clear { padding: 8px 16px; background: #fff; color: #555; font-size: 13px; font-weight: 600; border: 1px solid #c8ccd0; border-radius: 3px; cursor: pointer; font-family: 'Inter', sans-serif; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-clear:hover { background: #f4f6f8; }

        .result-info { font-size: 13px; color: #666; margin-bottom: 12px; }
        .result-info strong { color: #1a1a1a; }

        .badge { padding: 3px 10px; border-radius: 2px; font-size: 11px; font-weight: 700; display: inline-block; text-transform: uppercase; letter-spacing: 0.3px; }
        .badge-pending     { background: #FFF3CD; color: #856404; border: 1px solid #ffc107; }
        .badge-in_progress { background: #CCE5FF; color: #004085; border: 1px solid #80bdff; }
        .badge-resolved    { background: #D4EDDA; color: #155724; border: 1px solid #28a745; }

        .category-chip { display: inline-flex; align-items: center; gap: 4px; background: #EFF5FC; color: #0E4D92; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 2px; }

        .empty-state { text-align: center; padding: 60px 20px; color: #aaa; background: #fff; border: 1px solid #d0d5dd; border-radius: 3px; }
        .empty-state i { font-size: 40px; margin-bottom: 12px; display: block; color: #ddd; }
        .empty-state p { font-size: 14px; }

        .active-filters { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
        .filter-chip { display: inline-flex; align-items: center; gap: 5px; background: #EFF5FC; color: #0E4D92; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; border: 1px solid #BBD6F0; }

        /* ---- Feed (Facebook-post style) ---- */
        .feed { columns: 3 340px; column-gap: 16px; }

        .post-card { background: #fff; border: 1px solid #d0d5dd; border-radius: 8px; overflow: hidden; break-inside: avoid; -webkit-column-break-inside: avoid; margin-bottom: 16px; }

        .post-header { padding: 14px 16px 10px; display: flex; align-items: center; gap: 12px; }
        .post-avatar {
            width: 42px; height: 42px; border-radius: 50%; background: #0E4D92; color: #fff;
            display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 700; flex-shrink: 0;
        }
        .post-meta { flex: 1; min-width: 0; }
        .post-name { font-size: 14px; font-weight: 700; color: #1a1a1a; }
        .post-sub { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; font-size: 12px; color: #888; margin-top: 2px; }
        .post-sub .dot { width: 3px; height: 3px; border-radius: 50%; background: #ccc; }

        .post-body { padding: 2px 16px 12px; font-size: 14px; color: #222; line-height: 1.55; }
        .post-location { font-size: 12.5px; color: #666; margin-top: 6px; display: flex; align-items: center; gap: 6px; }
        .post-location i { color: #0E4D92; }

        .post-photo-link { display: block; }
        .post-photo-wrap { position: relative; }
        .post-photo { width: 100%; max-height: 520px; object-fit: cover; display: block; background: #eef1f4; }
        .post-photo-fallback {
            display: none;
            width: 100%; height: 200px;
            background: #eef1f4; color: #b7c0c9;
            flex-direction: column; align-items: center; justify-content: center;
            gap: 6px; font-size: 32px;
        }
        .post-photo-fallback span { font-size: 12px; color: #9aa5af; font-weight: 600; }
        .post-photo-wrap.img-error .post-photo { display: none; }
        .post-photo-wrap.img-error .post-photo-fallback { display: flex; }

        .post-footer { padding: 12px 16px; border-top: 1px solid #f0f0f0; display: flex; align-items: center; justify-content: space-between; }
        .post-footer .status-wrap { display: flex; align-items: center; gap: 8px; }
        .post-footer .cat-wrap { display: flex; flex-wrap: wrap; gap: 6px; }

        @media (max-width: 640px) {
            .feed { columns: 1; }
            .post-photo { max-height: 360px; }
        }
    </style>
</head>
<body>

<?php
$active_page = 'public_complaints';
$page_title  = 'Public Complaints Board';
require 'includes/header.php';
?>

<div class="content">

    <!-- Stats -->
    <div class="stats">
        <div class="stat-item">
            <div class="stat-label">Total Complaints</div>
            <div class="stat-number"><?= $total ?></div>
            <div class="stat-sub">All filed complaints</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Pending</div>
            <div class="stat-number"><?= $pending ?></div>
            <div class="stat-sub">Awaiting action</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">In Progress</div>
            <div class="stat-number"><?= $progress ?></div>
            <div class="stat-sub">Being addressed</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Resolved</div>
            <div class="stat-number"><?= $resolved ?></div>
            <div class="stat-sub">Successfully resolved</div>
        </div>
    </div>

    <!-- Filter bar -->
    <div class="filter-bar">
        <form method="GET" action="public_complaints.php">
            <div class="filter-group">
                <label>Category</label>
                <select name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat ?>" <?= $filter_category === $cat ? 'selected' : '' ?>>
                            <?= $cat ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Barangay</label>
                <select name="barangay">
                    <option value="">All Barangays</option>
                    <?php foreach ($barangays as $brgy): ?>
                        <option value="<?= $brgy ?>" <?= $filter_barangay === $brgy ? 'selected' : '' ?>>
                            <?= $brgy ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Status</label>
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="pending"     <?= $filter_status === 'pending'     ? 'selected' : '' ?>>Pending</option>
                    <option value="in_progress" <?= $filter_status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="resolved"    <?= $filter_status === 'resolved'    ? 'selected' : '' ?>>Resolved</option>
                </select>
            </div>
            <div class="filter-group search-group">
                <label>Search</label>
                <div class="search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="search" placeholder="Search by keyword, location, barangay..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <button type="submit" class="btn-filter">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
            <a href="public_complaints.php" class="btn-clear">
                <i class="fa-solid fa-xmark"></i> Clear
            </a>
        </form>
    </div>

    <!-- Active filters -->
    <?php if ($filter_category || $filter_barangay || $filter_status || $search): ?>
    <div class="active-filters">
        <?php if ($filter_category): ?>
            <span class="filter-chip"><i class="fa-solid fa-tag"></i> <?= htmlspecialchars($filter_category) ?></span>
        <?php endif; ?>
        <?php if ($filter_barangay): ?>
            <span class="filter-chip"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($filter_barangay) ?></span>
        <?php endif; ?>
        <?php if ($filter_status): ?>
            <span class="filter-chip"><i class="fa-solid fa-circle-half-stroke"></i> <?= ucfirst(str_replace('_', ' ', $filter_status)) ?></span>
        <?php endif; ?>
        <?php if ($search): ?>
            <span class="filter-chip"><i class="fa-solid fa-magnifying-glass"></i> "<?= htmlspecialchars($search) ?>"</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Result count -->
    <div class="result-info">
        Showing <strong><?= count($complaints) ?></strong> complaint<?= count($complaints) !== 1 ? 's' : '' ?>
        <?= ($filter_category || $filter_barangay || $filter_status || $search) ? 'matching your filters' : 'in total' ?>
    </div>

    <!-- Feed -->
    <?php if (empty($complaints)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-folder-open"></i>
            <p>No complaints found matching your filters.</p>
        </div>
    <?php else: ?>
        <div class="feed">
            <?php foreach ($complaints as $c): ?>
                <div class="post-card">

                    <!-- Header: reporter + time -->
                    <div class="post-header">
                        <div class="post-avatar"><?= strtoupper(substr($c['full_name'], 0, 1)) ?></div>
                        <div class="post-meta">
                            <div class="post-name"><?= htmlspecialchars($c['full_name']) ?></div>
                            <div class="post-sub">
                                <span><?= htmlspecialchars($c['barangay']) ?></span>
                                <span class="dot"></span>
                                <span><?= time_ago($c['created_at']) ?></span>
                            </div>
                        </div>
                        <span class="badge badge-<?= $c['status'] ?>">
                            <?= ucfirst(str_replace('_', ' ', $c['status'])) ?>
                        </span>
                    </div>

                    <!-- Body: description + location -->
                    <div class="post-body">
                        <?= nl2br(htmlspecialchars($c['description'])) ?>
                        <div class="post-location">
                            <i class="fa-solid fa-location-dot"></i>
                            <?= htmlspecialchars($c['location']) ?>, Brgy. <?= htmlspecialchars($c['barangay']) ?>
                        </div>
                    </div>

                    <!-- Photo, if any -->
                    <?php if ($c['photo']): ?>
                        <div class="post-photo-wrap">
                            <a href="../<?= $c['photo'] ?>" target="_blank" class="post-photo-link">
                                <img class="post-photo" src="../<?= $c['photo'] ?>" alt="Complaint photo"
                                     onerror="this.closest('.post-photo-wrap').classList.add('img-error')">
                            </a>
                            <div class="post-photo-fallback">
                                <i class="fa-solid fa-image-slash"></i>
                                <span>Photo unavailable</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Footer: category -->
                    <div class="post-footer">
                        <div class="cat-wrap">
                            <span class="category-chip"><i class="fa-solid fa-tag"></i> <?= htmlspecialchars($c['category']) ?></span>
                        </div>
                        <span style="font-size:12px;color:#aaa"><?= date('M d, Y \a\t h:i A', strtotime($c['created_at'])) ?></span>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>