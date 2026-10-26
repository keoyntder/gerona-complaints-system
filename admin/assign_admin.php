<?php
session_start();
if (!isset($_SESSION['logged_in']) || ($_SESSION['role'] ?? null) !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: residents.php');
    exit;
}

if (
    !isset($_SESSION['csrf_token'], $_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    http_response_code(403);
    exit('Invalid request token. Please reload the Residents page and try again.');
}

require_once '../config/database.php';

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
if (!$userId) {
    $_SESSION['admin_assignment_message'] = [
        'type' => 'error',
        'text' => 'Select a valid resident to assign as an administrator.',
    ];
    header('Location: residents.php');
    exit;
}

$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ? AND status = 'verified'");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['admin_assignment_message'] = [
        'type' => 'error',
        'text' => 'Only verified residents can be assigned as administrators.',
    ];
    header('Location: residents.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
$stmt->execute([$user['email']]);
if ($stmt->fetch(PDO::FETCH_ASSOC)) {
    $_SESSION['admin_assignment_message'] = [
        'type' => 'error',
        'text' => 'This account is already an administrator.',
    ];
    header('Location: residents.php');
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO admins (full_name, email, password)
    SELECT full_name, email, password
    FROM users
    WHERE id = ? AND status = 'verified'
");
$stmt->execute([$userId]);

if ($stmt->rowCount() !== 1) {
    $_SESSION['admin_assignment_message'] = [
        'type' => 'error',
        'text' => 'The account could not be assigned as an administrator. Please try again.',
    ];
} else {
    $_SESSION['admin_assignment_message'] = [
        'type' => 'success',
        'text' => 'Administrator access has been assigned successfully.',
    ];
}

header('Location: residents.php');
exit;
