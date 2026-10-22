<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../config/database.php';

$complaint_id = $_POST['complaint_id'] ?? null;
$status       = $_POST['status'] ?? null;

if ($complaint_id && in_array($status, ['in_progress', 'resolved'])) {
    // Update complaint status
    $stmt = $pdo->prepare("UPDATE complaints SET status = ? WHERE id = ?");
    $stmt->execute([$status, $complaint_id]);

    // If resolved, give reward points to the resident
    if ($status === 'resolved') {
        $stmt = $pdo->prepare("SELECT user_id FROM complaints WHERE id = ?");
        $stmt->execute([$complaint_id]);
        $complaint = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($complaint) {
            $stmt = $pdo->prepare("UPDATE users SET reward_points = reward_points + 10 WHERE id = ?");
            $stmt->execute([$complaint['user_id']]);
        }
    }
}

header('Location: complaints.php');
exit;