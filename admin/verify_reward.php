<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../config/database.php';

// Lazy-expire any pending codes whose window has passed
$pdo->exec("UPDATE reward_redemptions SET status = 'expired'
            WHERE status = 'pending' AND expires_at < NOW()");

$result       = null; // 'success' | 'error'
$message      = '';
$verifiedInfo = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['code'])) {
    $code = strtoupper(trim($_POST['code']));

    $stmt = $pdo->prepare("
        SELECT rr.*, r.name AS reward_name, r.category, u.full_name, u.reward_points
        FROM reward_redemptions rr
        JOIN rewards r ON rr.reward_id = r.id
        JOIN users u ON rr.user_id = u.id
        WHERE rr.code = ?
    ");
    $stmt->execute([$code]);
    $redemption = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$redemption) {
        $result  = 'error';
        $message = 'No redemption found for that code. Please check and try again.';
    } elseif ($redemption['status'] === 'verified') {
        $result  = 'error';
        $message = 'This code was already verified on ' . date('M d, Y \a\t h:i A', strtotime($redemption['verified_at'])) . '.';
    } elseif ($redemption['status'] === 'expired') {
        $result  = 'error';
        $message = 'This code has expired. Ask the resident to generate a new one.';
    } elseif ($redemption['status'] === 'cancelled') {
        $result  = 'error';
        $message = 'This code was cancelled and can no longer be verified.';
    } elseif ((int) $redemption['reward_points'] < (int) $redemption['points_cost']) {
        $result  = 'error';
        $message = 'This resident no longer has enough points to complete this redemption.';
    } else {
        // All good — deduct points and mark verified
        $pdo->beginTransaction();
        try {
            $deduct = $pdo->prepare("UPDATE users SET reward_points = reward_points - ? WHERE id = ?");
            $deduct->execute([$redemption['points_cost'], $redemption['user_id']]);

            $verify = $pdo->prepare("UPDATE reward_redemptions
                                      SET status = 'verified', verified_at = NOW(), verified_by = ?
                                      WHERE id = ?");
            $verify->execute([$_SESSION['user_id'] ?? null, $redemption['id']]);

            $pdo->commit();

            $result       = 'success';
            $message      = 'Redemption verified and points deducted successfully.';
            $verifiedInfo = $redemption;
            $verifiedInfo['new_balance'] = $redemption['reward_points'] - $redemption['points_cost'];
        } catch (Exception $e) {
            $pdo->rollBack();
            $result  = 'error';
            $message = 'Something went wrong while processing this redemption. Please try again.';
        }
    }
}

// Recent verification activity (for reference)
$recent = $pdo->query("
    SELECT rr.code, rr.points_cost, rr.verified_at, r.name AS reward_name, u.full_name
    FROM reward_redemptions rr
    JOIN rewards r ON rr.reward_id = r.id
    JOIN users u ON rr.user_id = u.id
    WHERE rr.status = 'verified'
    ORDER BY rr.verified_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Reward Code — Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; min-height: 100vh; }

        .admin-topbar { background: #152238; color: #cbd5e1; font-size: 12px; padding: 6px 28px; display: flex; justify-content: space-between; }
        .admin-header { background: #fff; border-bottom: 3px solid #0E4D92; padding: 16px 28px; display: flex; align-items: center; justify-content: space-between; }
        .admin-header h1 { font-size: 18px; font-weight: 700; color: #1a1a1a; }
        .admin-header a.back-link { font-size: 13px; color: #0E4D92; text-decoration: none; font-weight: 600; }

        .content { max-width: 720px; margin: 0 auto; padding: 32px 24px; }

        .verify-card { background: #fff; border: 1px solid #d0d5dd; border-radius: 6px; overflow: hidden; margin-bottom: 24px; }
        .verify-card-header { background: #0E4D92; color: #fff; padding: 18px 24px; display: flex; align-items: center; gap: 12px; }
        .verify-card-header i { font-size: 20px; }
        .verify-card-header h2 { font-size: 15px; font-weight: 700; }
        .verify-card-header p  { font-size: 12px; color: rgba(255,255,255,0.75); margin-top: 2px; }

        .verify-form { padding: 24px; }
        .code-input {
            width: 100%; padding: 16px; font-size: 24px; font-weight: 700; letter-spacing: 6px;
            text-align: center; text-transform: uppercase; border: 2px solid #c8ccd0; border-radius: 6px;
            font-family: 'Inter', sans-serif; color: #1a1a1a; outline: none; margin-bottom: 16px;
        }
        .code-input:focus { border-color: #0E4D92; }

        .btn-verify {
            width: 100%; padding: 13px; background: #0E4D92; color: #fff; font-size: 14px; font-weight: 700;
            border: none; border-radius: 6px; cursor: pointer; font-family: 'Inter', sans-serif;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .btn-verify:hover { background: #0B3D75; }

        .alert-success, .alert-error {
            padding: 16px 20px; margin: 0 24px 20px; border-radius: 6px; font-size: 13.5px; line-height: 1.6;
        }
        .alert-success { background: #f0faf4; color: #1e6b3c; border-left: 4px solid #27ae60; }
        .alert-error    { background: #fdf3f3; color: #922b21; border-left: 4px solid #c0392b; }

        .verified-details { padding: 0 24px 20px; }
        .verified-details table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .verified-details td { padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
        .verified-details td:first-child { font-weight: 600; color: #666; width: 40%; }
        .verified-details tr:last-child td { border-bottom: none; }

        .recent-card { background: #fff; border: 1px solid #d0d5dd; border-radius: 6px; overflow: hidden; }
        .recent-header { background: #f8f9fa; border-bottom: 1px solid #d0d5dd; padding: 10px 16px; display: flex; align-items: center; gap: 8px; }
        .recent-header i { color: #0E4D92; font-size: 14px; }
        .recent-header h3 { font-size: 12px; font-weight: 700; color: #333; text-transform: uppercase; letter-spacing: 0.5px; }

        table.recent-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        table.recent-table th { text-align: left; padding: 10px 14px; background: #f8f9fa; color: #666; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #d0d5dd; }
        table.recent-table td { padding: 11px 14px; border-bottom: 1px solid #f0f0f0; color: #333; }
        table.recent-table tr:last-child td { border-bottom: none; }
        .empty-recent { text-align: center; padding: 30px; color: #aaa; font-size: 13px; }
    </style>
</head>
<body>

<div class="admin-topbar">
    <span><i class="fa-solid fa-landmark"></i> Republic of the Philippines — Municipality of Gerona</span>
    <span>Admin Panel</span>
</div>
<div class="admin-header">
    <h1><i class="fa-solid fa-ticket" style="color:#0E4D92;margin-right:8px"></i> Verify Reward Code</h1>
    <a href="dashboard.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
</div>

<div class="content">

    <div class="verify-card">
        <div class="verify-card-header">
            <i class="fa-solid fa-qrcode"></i>
            <div>
                <h2>Enter Redemption Code</h2>
                <p>Ask the resident for their 6-character redemption code</p>
            </div>
        </div>

        <form method="POST" action="verify_reward.php" class="verify-form">
            <input type="text" name="code" class="code-input" placeholder="XXXXXX" maxlength="6" autofocus required
                   value="<?= isset($_POST['code']) ? htmlspecialchars(strtoupper($_POST['code'])) : '' ?>">
            <button type="submit" class="btn-verify"><i class="fa-solid fa-check"></i> Verify Code</button>
        </form>

        <?php if ($result === 'success'): ?>
            <div class="alert-success">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($message) ?>
            </div>
            <div class="verified-details">
                <table>
                    <tr><td>Resident</td><td><?= htmlspecialchars($verifiedInfo['full_name']) ?></td></tr>
                    <tr><td>Reward</td><td><?= htmlspecialchars($verifiedInfo['reward_name']) ?></td></tr>
                    <tr><td>Points Deducted</td><td><?= $verifiedInfo['points_cost'] ?> pts</td></tr>
                    <tr><td>Remaining Balance</td><td><?= $verifiedInfo['new_balance'] ?> pts</td></tr>
                    <tr><td>Verified At</td><td><?= date('M d, Y \a\t h:i A') ?></td></tr>
                </table>
            </div>
        <?php elseif ($result === 'error'): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="recent-card">
        <div class="recent-header">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <h3>Recently Verified Redemptions</h3>
        </div>
        <?php if (empty($recent)): ?>
            <div class="empty-recent">No redemptions verified yet.</div>
        <?php else: ?>
            <table class="recent-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Resident</th>
                        <th>Reward</th>
                        <th>Points</th>
                        <th>Verified At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $r): ?>
                    <tr>
                        <td style="font-weight:700;letter-spacing:1px"><?= htmlspecialchars($r['code']) ?></td>
                        <td><?= htmlspecialchars($r['full_name']) ?></td>
                        <td><?= htmlspecialchars($r['reward_name']) ?></td>
                        <td><?= $r['points_cost'] ?> pts</td>
                        <td style="color:#888;font-size:12px"><?= date('M d, Y h:i A', strtotime($r['verified_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>

</body>
</html>