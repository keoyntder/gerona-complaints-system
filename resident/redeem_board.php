<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'resident') {
    header('Location: ../login.php');
    exit;
}
require_once '../config/database.php';

$user_id = $_SESSION['user_id'];

// ---- Lazy-expire any pending codes whose 20-minute window has passed ----
$pdo->exec("UPDATE reward_redemptions SET status = 'expired'
            WHERE status = 'pending' AND expires_at < NOW()");

// ---- Handle a redeem action ----
$redeem_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reward_id'])) {
    $reward_id = (int) $_POST['reward_id'];

    // Only one active code at a time, to keep things simple and avoid abuse
    $activeCheck = $pdo->prepare("SELECT id FROM reward_redemptions WHERE user_id = ? AND status = 'pending' LIMIT 1");
    $activeCheck->execute([$user_id]);
    $alreadyActive = $activeCheck->fetch();

    $rewardStmt = $pdo->prepare("SELECT * FROM rewards WHERE id = ? AND is_active = 1");
    $rewardStmt->execute([$reward_id]);
    $reward = $rewardStmt->fetch(PDO::FETCH_ASSOC);

    $userStmt = $pdo->prepare("SELECT reward_points FROM users WHERE id = ?");
    $userStmt->execute([$user_id]);
    $currentPoints = (int) $userStmt->fetchColumn();

    if ($alreadyActive) {
        $redeem_error = 'You already have an active redemption code. Please use or wait for it to expire before redeeming another reward.';
    } elseif (!$reward) {
        $redeem_error = 'That reward is no longer available.';
    } elseif ($currentPoints < $reward['points_cost']) {
        $redeem_error = 'You don\'t have enough points to redeem this reward.';
    } else {
        // Generate a unique 6-character code
        do {
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
            $check = $pdo->prepare("SELECT id FROM reward_redemptions WHERE code = ?");
            $check->execute([$code]);
        } while ($check->fetch());

        $insert = $pdo->prepare("INSERT INTO reward_redemptions (user_id, reward_id, code, points_cost, status, expires_at)
                                  VALUES (?, ?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL 20 MINUTE))");
        $insert->execute([$user_id, $reward_id, $code, $reward['points_cost']]);

        header('Location: redeem_board.php');
        exit;
    }
}

// ---- Check for an active (pending, unexpired) redemption to show the code modal ----
$activeStmt = $pdo->prepare("
    SELECT rr.*, r.name AS reward_name, r.category
    FROM reward_redemptions rr
    JOIN rewards r ON rr.reward_id = r.id
    WHERE rr.user_id = ? AND rr.status = 'pending'
    ORDER BY rr.created_at DESC LIMIT 1
");
$activeStmt->execute([$user_id]);
$active_redemption = $activeStmt->fetch(PDO::FETCH_ASSOC);

// ---- Current points balance ----
$pointsStmt = $pdo->prepare("SELECT reward_points FROM users WHERE id = ?");
$pointsStmt->execute([$user_id]);
$current_points = (int) $pointsStmt->fetchColumn();

// ---- Filters ----
$filter_category = $_GET['category'] ?? '';
$search          = trim($_GET['search'] ?? '');
$sort            = $_GET['sort'] ?? 'name_asc';

$where  = ['is_active = 1'];
$params = [];

if ($filter_category) { $where[] = 'category = ?'; $params[] = $filter_category; }
if ($search) {
    $where[]  = '(name LIKE ? OR description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$orderMap = [
    'name_asc'    => 'name ASC',
    'points_asc'  => 'points_cost ASC',
    'points_desc' => 'points_cost DESC',
];
$orderBy = $orderMap[$sort] ?? 'name ASC';

$sql = "SELECT * FROM rewards WHERE " . implode(' AND ', $where) . " ORDER BY $orderBy";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = [
    'school_supplies' => ['label' => 'School Supplies', 'icon' => 'fa-pencil',        'color' => '#0E4D92'],
    'food'             => ['label' => 'Food',            'icon' => 'fa-bowl-food',     'color' => '#B8860B'],
    'drinks'           => ['label' => 'Drinks',          'icon' => 'fa-mug-hot',       'color' => '#1B6FC9'],
    'money'            => ['label' => 'Money',           'icon' => 'fa-money-bill-wave','color' => '#1E7A3C'],
];

$active_page = 'redeem_board';
$page_title  = 'Reward Redeem Board';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reward Redeem Board — Ragsak Ranyag Gerona</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/gov-header.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; min-height: 100vh; }

        .main { flex: 1; }
        .content { padding: 28px; max-width: 1240px; margin: 0 auto; }

        /* Balance banner */
        .balance-banner {
            background: #0E4D92;
            border-radius: 3px;
            padding: 18px 24px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .balance-banner .left { display: flex; align-items: center; gap: 14px; }
        .balance-banner i.big { font-size: 30px; color: rgba(255,255,255,0.5); }
        .balance-banner h2 { font-size: 14px; font-weight: 700; color: #fff; letter-spacing: 0.3px; }
        .balance-banner p  { font-size: 12px; color: rgba(255,255,255,0.75); margin-top: 2px; }
        .balance-banner .pts { font-size: 30px; font-weight: 700; color: #fff; text-align: right; line-height: 1; }
        .balance-banner .pts small { display: block; font-size: 11px; font-weight: 500; color: rgba(255,255,255,0.7); margin-top: 4px; }

        /* Filter bar */
        .filter-bar { background: #fff; border: 1px solid #d0d5dd; border-radius: 3px; padding: 16px 20px; margin-bottom: 20px; }
        .filter-bar form { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; gap: 4px; }
        .filter-group label { font-size: 11px; font-weight: 700; color: #666; text-transform: uppercase; letter-spacing: 0.4px; }
        .filter-group select, .filter-group input[type=text] { padding: 8px 10px; border: 1px solid #c8ccd0; border-radius: 3px; font-size: 13px; font-family: 'Inter', sans-serif; color: #1a1a1a; background: #fff; outline: none; min-width: 170px; }
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

        /* Category quick chips */
        .cat-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; }
        .cat-chip {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 600;
            text-decoration: none; border: 1px solid #d0d5dd; color: #555; background: #fff;
        }
        .cat-chip.active { background: #0E4D92; color: #fff; border-color: #0E4D92; }
        .cat-chip:hover:not(.active) { background: #f4f6f8; }

        .result-info { font-size: 13px; color: #666; margin-bottom: 12px; }
        .result-info strong { color: #1a1a1a; }

        /* Reward grid */
        .reward-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 16px; }
        .reward-card { background: #fff; border: 1px solid #d0d5dd; border-radius: 6px; overflow: hidden; display: flex; flex-direction: column; }

        /* Icon fallback box (used when a reward has no photo) */
        .reward-icon-box {
            height: 110px; display: flex; align-items: center; justify-content: center; font-size: 38px; color: #fff;
        }

        /* Photo box (used when a reward has an uploaded image) */
        .reward-photo-box {
            height: 110px; background: #f4f6f8; overflow: hidden;
        }
        .reward-photo-box img {
            width: 100%; height: 100%; object-fit: cover; display: block;
        }

        .reward-body { padding: 14px 16px; display: flex; flex-direction: column; gap: 6px; flex: 1; }
        .reward-cat-label { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .reward-name { font-size: 14.5px; font-weight: 700; color: #1a1a1a; line-height: 1.3; }
        .reward-desc { font-size: 12px; color: #888; line-height: 1.5; flex: 1; }
        .reward-footer { display: flex; align-items: center; justify-content: space-between; margin-top: 8px; }
        .reward-points { font-size: 15px; font-weight: 700; color: #0E4D92; }
        .reward-points small { font-size: 10.5px; font-weight: 600; color: #999; text-transform: uppercase; }

        .btn-redeem {
            padding: 8px 16px; background: #0E4D92; color: #fff; font-size: 12.5px; font-weight: 700;
            border: none; border-radius: 4px; cursor: pointer; font-family: 'Inter', sans-serif;
            text-transform: uppercase; letter-spacing: 0.3px;
        }
        .btn-redeem:hover { background: #0B3D75; }
        .btn-redeem:disabled { background: #d0d5dd; color: #999; cursor: not-allowed; }

        .empty-state { text-align: center; padding: 60px 20px; color: #aaa; background: #fff; border: 1px solid #d0d5dd; border-radius: 3px; }
        .empty-state i { font-size: 40px; margin-bottom: 12px; display: block; color: #ddd; }

        .redeem-error {
            padding: 12px 16px; margin-bottom: 20px; font-size: 13px;
            background: #fdf3f3; color: #922b21; border: 1px solid #f5c6c6; border-left: 4px solid #c0392b; border-radius: 3px;
        }

        /* Confirm modal */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 200; display: none; align-items: center; justify-content: center; padding: 20px; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: #fff; border-radius: 8px; width: 100%; max-width: 380px; overflow: hidden; }
        .modal-header { background: #0E4D92; color: #fff; padding: 16px 20px; font-size: 14px; font-weight: 700; }
        .modal-body { padding: 20px; font-size: 13.5px; color: #333; line-height: 1.6; }
        .modal-footer { padding: 14px 20px; border-top: 1px solid #eee; display: flex; gap: 10px; justify-content: flex-end; }
        .modal-btn { padding: 9px 18px; border-radius: 4px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; font-family: 'Inter', sans-serif; }
        .modal-btn.cancel { background: #f0f0f0; color: #555; }
        .modal-btn.confirm { background: #0E4D92; color: #fff; }

        /* Code / timer modal */
        .code-modal-box { background: #fff; border-radius: 10px; width: 100%; max-width: 400px; overflow: hidden; text-align: center; }
        .code-modal-header { background: #0E4D92; color: #fff; padding: 18px; }
        .code-modal-header i { font-size: 26px; margin-bottom: 6px; display: block; }
        .code-modal-header h3 { font-size: 15px; font-weight: 700; }
        .code-modal-body { padding: 28px 24px; }
        .code-modal-body .reward-label { font-size: 12.5px; color: #888; margin-bottom: 4px; }
        .code-modal-body .reward-title { font-size: 15px; font-weight: 700; color: #1a1a1a; margin-bottom: 20px; }
        .redemption-code {
            font-size: 38px; font-weight: 800; letter-spacing: 8px; color: #0E4D92;
            background: #EFF5FC; border: 2px dashed #BBD6F0; border-radius: 8px;
            padding: 16px 10px; margin-bottom: 18px;
        }
        .timer-label { font-size: 11.5px; color: #999; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .timer-display { font-size: 26px; font-weight: 700; color: #c0392b; margin-bottom: 18px; }
        .timer-display.expired { color: #999; }
        .code-instructions { font-size: 12.5px; color: #666; line-height: 1.6; }
        .code-modal-footer { padding: 14px; border-top: 1px solid #eee; }
        .btn-close-code { padding: 10px 24px; background: #f0f0f0; color: #555; border: none; border-radius: 4px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body>

<?php require 'includes/header.php'; ?>

<div class="main">
    <div class="content">

        <!-- Balance banner -->
        <div class="balance-banner">
            <div class="left">
                <i class="fa-solid fa-gift big"></i>
                <div>
                    <h2>Reward Redeem Board</h2>
                    <p>Exchange your reward points for school supplies, food, drinks, or cash vouchers</p>
                </div>
            </div>
            <div class="pts">
                <?= $current_points ?> pts
                <small>Available Balance</small>
            </div>
        </div>

        <?php if ($redeem_error): ?>
            <div class="redeem-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($redeem_error) ?></div>
        <?php endif; ?>

        <!-- Category quick chips -->
        <div class="cat-chips">
            <a href="redeem_board.php" class="cat-chip <?= $filter_category === '' ? 'active' : '' ?>">
                <i class="fa-solid fa-border-all"></i> All Rewards
            </a>
            <?php foreach ($categories as $key => $cat): ?>
                <a href="?category=<?= $key ?>" class="cat-chip <?= $filter_category === $key ? 'active' : '' ?>">
                    <i class="fa-solid <?= $cat['icon'] ?>"></i> <?= $cat['label'] ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Filter bar -->
        <div class="filter-bar">
            <form method="GET" action="redeem_board.php">
                <div class="filter-group">
                    <label>Category</label>
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $key => $cat): ?>
                            <option value="<?= $key ?>" <?= $filter_category === $key ? 'selected' : '' ?>><?= $cat['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Sort By</label>
                    <select name="sort">
                        <option value="name_asc"    <?= $sort === 'name_asc'    ? 'selected' : '' ?>>Name (A–Z)</option>
                        <option value="points_asc"  <?= $sort === 'points_asc'  ? 'selected' : '' ?>>Points (Low to High)</option>
                        <option value="points_desc" <?= $sort === 'points_desc' ? 'selected' : '' ?>>Points (High to Low)</option>
                    </select>
                </div>
                <div class="filter-group search-group">
                    <label>Search</label>
                    <div class="search-wrap">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="search" placeholder="Search rewards..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <button type="submit" class="btn-filter"><i class="fa-solid fa-filter"></i> Filter</button>
                <a href="redeem_board.php" class="btn-clear"><i class="fa-solid fa-xmark"></i> Clear</a>
            </form>
        </div>

        <div class="result-info">
            Showing <strong><?= count($rewards) ?></strong> reward<?= count($rewards) !== 1 ? 's' : '' ?>
        </div>

        <!-- Reward grid -->
        <?php if (empty($rewards)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-box-open"></i>
                <p>No rewards found matching your filters.</p>
            </div>
        <?php else: ?>
            <div class="reward-grid">
                <?php foreach ($rewards as $r): ?>
                    <?php
                        $cat = $categories[$r['category']];
                        $canAfford = $current_points >= $r['points_cost'];
                    ?>
                    <div class="reward-card">
                        <?php if (!empty($r['photo'])): ?>
                            <div class="reward-photo-box">
                                <img src="<?= htmlspecialchars($r['photo']) ?>"
                                     alt="<?= htmlspecialchars($r['name']) ?>" loading="lazy"
                                     onerror="this.closest('.reward-photo-box').outerHTML = '<div class=&quot;reward-icon-box&quot; style=&quot;background:<?= $cat['color'] ?>&quot;><i class=&quot;fa-solid <?= $cat['icon'] ?>&quot;></i></div>';">
                            </div>
                        <?php else: ?>
                            <div class="reward-icon-box" style="background:<?= $cat['color'] ?>">
                                <i class="fa-solid <?= $cat['icon'] ?>"></i>
                            </div>
                        <?php endif; ?>
                        <div class="reward-body">
                            <div class="reward-cat-label" style="color:<?= $cat['color'] ?>"><?= $cat['label'] ?></div>
                            <div class="reward-name"><?= htmlspecialchars($r['name']) ?></div>
                            <?php if ($r['description']): ?>
                                <div class="reward-desc"><?= htmlspecialchars($r['description']) ?></div>
                            <?php endif; ?>
                            <div class="reward-footer">
                                <div class="reward-points"><?= $r['points_cost'] ?> <small>pts</small></div>
                                <button type="button" class="btn-redeem"
                                        <?= (!$canAfford || $active_redemption) ? 'disabled' : '' ?>
                                        onclick="openConfirm(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['name'])) ?>', <?= $r['points_cost'] ?>)">
                                    <?= $canAfford ? 'Redeem' : 'Not Enough Points' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- Confirm redeem modal -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal-box">
        <div class="modal-header">Confirm Redemption</div>
        <form method="POST" action="redeem_board.php">
            <div class="modal-body">
                You're about to redeem <strong id="confirmRewardName"></strong> for
                <strong id="confirmRewardPoints"></strong> points. A one-time code will be
                generated, valid for <strong>20 minutes</strong> — show it to LGU staff to claim your reward.
            </div>
            <input type="hidden" name="reward_id" id="confirmRewardId">
            <div class="modal-footer">
                <button type="button" class="modal-btn cancel" onclick="closeConfirm()">Cancel</button>
                <button type="submit" class="modal-btn confirm">Confirm Redeem</button>
            </div>
        </form>
    </div>
</div>

<!-- Code / timer modal -->
<?php if ($active_redemption): ?>
<div class="modal-overlay active" id="codeModal">
    <div class="code-modal-box">
        <div class="code-modal-header">
            <i class="fa-solid fa-ticket"></i>
            <h3>Your Redemption Code</h3>
        </div>
        <div class="code-modal-body">
            <div class="reward-label">Reward</div>
            <div class="reward-title"><?= htmlspecialchars($active_redemption['reward_name']) ?></div>

            <div class="redemption-code"><?= htmlspecialchars($active_redemption['code']) ?></div>

            <div class="timer-label">Time remaining</div>
            <div class="timer-display" id="timerDisplay">20:00</div>

            <div class="code-instructions" id="codeInstructions">
                Show this code to LGU Gerona staff within the time shown to claim your reward.
                Your <strong><?= $active_redemption['points_cost'] ?> points</strong> will be
                deducted only once the code is verified.
            </div>
        </div>
        <div class="code-modal-footer">
            <button type="button" class="btn-close-code" onclick="document.getElementById('codeModal').classList.remove('active')">Close</button>
        </div>
    </div>
</div>
<script>
    (function () {
        var expiresAt = <?= strtotime($active_redemption['expires_at']) ?>;
        var display = document.getElementById('timerDisplay');
        var instructions = document.getElementById('codeInstructions');

        function tick() {
            var now = Math.floor(Date.now() / 1000);
            var diff = expiresAt - now;
            if (diff <= 0) {
                display.textContent = '00:00';
                display.classList.add('expired');
                instructions.innerHTML = 'This code has expired. Please redeem again if you still want this reward.';
                clearInterval(timer);
                return;
            }
            var m = String(Math.floor(diff / 60)).padStart(2, '0');
            var s = String(diff % 60).padStart(2, '0');
            display.textContent = m + ':' + s;
        }
        tick();
        var timer = setInterval(tick, 1000);
    })();
</script>
<?php endif; ?>

<script>
    function openConfirm(id, name, points) {
        document.getElementById('confirmRewardId').value = id;
        document.getElementById('confirmRewardName').textContent = name;
        document.getElementById('confirmRewardPoints').textContent = points;
        document.getElementById('confirmModal').classList.add('active');
    }
    function closeConfirm() {
        document.getElementById('confirmModal').classList.remove('active');
    }
</script>

</body>
</html>