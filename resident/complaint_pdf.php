<?php
session_start();

require_once '../config/database.php';
$cspNonce = require_once '../config/security_headers.php';
$role = !empty($_SESSION['logged_in']) ? ($_SESSION['role'] ?? null) : null;

$complaintId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$complaintId || $complaintId < 1) {
    http_response_code(404);
    exit('Complaint not found.');
}

if ($role === 'resident') {
    $stmt = $pdo->prepare(
        'SELECT c.*, u.full_name, u.email, u.address
         FROM complaints c
         JOIN users u ON c.user_id = u.id
         WHERE c.id = ? AND c.user_id = ?
         LIMIT 1'
    );
    $stmt->execute([$complaintId, $_SESSION['user_id']]);
} elseif ($role === 'admin') {
    $stmt = $pdo->prepare(
        'SELECT c.*, u.full_name, u.email, u.address
         FROM complaints c
         JOIN users u ON c.user_id = u.id
         WHERE c.id = ?
         LIMIT 1'
    );
    $stmt->execute([$complaintId]);
} else {
    $stmt = $pdo->prepare(
        'SELECT c.*, u.full_name
         FROM complaints c
         JOIN users u ON c.user_id = u.id
         WHERE c.id = ?
         LIMIT 1'
    );
    $stmt->execute([$complaintId]);
}

$complaint = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$complaint) {
    http_response_code(404);
    exit('Complaint not found.');
}

$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$submittedAt = date('F j, Y \a\t g:i A', strtotime($complaint['created_at']));
$status = ucfirst(str_replace('_', ' ', $complaint['status']));

$photoUrl = null;
$photoPath = $complaint['photo'] ?? '';
if (
    is_string($photoPath)
    && preg_match('~\Auploads/complaints/[A-Za-z0-9._-]+\z~', $photoPath)
) {
    $uploadDirectory = realpath(__DIR__ . '/../uploads/complaints');
    $filePath = realpath(__DIR__ . '/../' . $photoPath);

    if ($uploadDirectory && $filePath && str_starts_with($filePath, $uploadDirectory . DIRECTORY_SEPARATOR) && is_file($filePath)) {
        $photoUrl = '../' . $photoPath;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint #<?= (int) $complaint['id'] ?> — Complaint Report</title>
    <style nonce="<?= htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') ?>">
        * { box-sizing: border-box; }
        body { margin: 0; padding: 32px; color: #1f2933; font: 15px/1.5 Arial, sans-serif; background: #f2f4f7; }
        .report { max-width: 850px; margin: 0 auto; padding: 40px; background: #fff; border: 1px solid #d9dee5; }
        .report-header { display: flex; justify-content: space-between; gap: 24px; align-items: flex-start; padding-bottom: 20px; border-bottom: 2px solid #0E4D92; }
        .report-header h1 { margin: 0 0 4px; color: #0E4D92; font-size: 24px; }
        .report-header p { margin: 0; color: #667085; }
        .report-number { color: #475467; font-weight: 700; text-align: right; }
        h2 { margin: 26px 0 12px; padding-bottom: 7px; border-bottom: 1px solid #d9dee5; color: #344054; font-size: 15px; text-transform: uppercase; letter-spacing: .04em; }
        .details { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 28px; }
        .field-label { display: block; margin-bottom: 3px; color: #667085; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .field-value { overflow-wrap: anywhere; }
        .description { white-space: pre-wrap; overflow-wrap: anywhere; }
        .photo { display: block; max-width: 100%; max-height: 560px; margin-top: 12px; object-fit: contain; object-position: left top; }
        .toolbar { max-width: 850px; margin: 0 auto 16px; text-align: right; }
        .toolbar button { padding: 10px 16px; border: 0; border-radius: 5px; color: #fff; background: #0E4D92; font-size: 14px; font-weight: 700; cursor: pointer; }
        @media (max-width: 600px) { body { padding: 12px; } .report { padding: 22px; } .details { grid-template-columns: 1fr; } }
        @media print {
            @page { size: A4; margin: 16mm; }
            body { padding: 0; background: #fff; }
            .report { max-width: none; padding: 0; border: 0; }
            .toolbar { display: none; }
            .field, .report-header, .photo-section { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" id="print-report">Print / Save as PDF</button>
    </div>
    <main class="report">
        <header class="report-header">
            <div>
                <h1>Complaint Report</h1>
                <p>Municipality of Gerona, Tarlac — Complaints Management System</p>
            </div>
            <div class="report-number">Complaint #<?= (int) $complaint['id'] ?></div>
        </header>

        <h2>Complaint details</h2>
        <section class="details">
            <div class="field">
                <span class="field-label">Date and time submitted</span>
                <div class="field-value"><?= $escape($submittedAt) ?></div>
            </div>
            <div class="field">
                <span class="field-label">Status</span>
                <div class="field-value"><?= $escape($status) ?></div>
            </div>
            <div class="field">
                <span class="field-label">Category</span>
                <div class="field-value"><?= $escape($complaint['category']) ?></div>
            </div>
            <div class="field">
                <span class="field-label">Barangay</span>
                <div class="field-value"><?= $escape($complaint['barangay']) ?></div>
            </div>
            <div class="field">
                <span class="field-label">Specific location / landmark</span>
                <div class="field-value"><?= $escape($complaint['location']) ?></div>
            </div>
        </section>

        <h2>Description</h2>
        <div class="description"><?= $escape($complaint['description']) ?></div>

        <?php if ($role === 'admin' || $role === 'resident'): ?>
            <h2>Submitted by</h2>
            <section class="details">
                <div class="field">
                    <span class="field-label">Name</span>
                    <div class="field-value"><?= $escape($complaint['full_name']) ?></div>
                </div>
                <div class="field">
                    <span class="field-label">Email</span>
                    <div class="field-value"><?= $escape($complaint['email']) ?></div>
                </div>
                <div class="field">
                    <span class="field-label">Address</span>
                    <div class="field-value"><?= $escape($complaint['address']) ?></div>
                </div>
            </section>
        <?php else: ?>
            <h2>Reported by</h2>
            <div class="field-value"><?= $escape($complaint['full_name']) ?></div>
        <?php endif; ?>

        <?php if ($photoUrl): ?>
            <section class="photo-section">
                <h2>Supporting photo</h2>
                <img class="photo" src="<?= $escape($photoUrl) ?>" alt="Photo attached to complaint">
            </section>
        <?php endif; ?>
    </main>
    <script nonce="<?= htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') ?>">
        document.getElementById('print-report').addEventListener('click', () => window.print());
        window.addEventListener('load', () => window.print());
    </script>
</body>
</html>
