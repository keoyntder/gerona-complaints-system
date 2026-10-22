<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

$user_id = $_POST['user_id'] ?? null;
$action  = $_POST['action'] ?? null;

if ($user_id && in_array($action, ['verify', 'reject'])) {
    $status = $action === 'verify' ? 'verified' : 'rejected';
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$status, $user_id]);
}

header('Location: dashboard.php');
exit;