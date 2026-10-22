<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint_id'])) {
    $complaint_id = $_POST['complaint_id'];
    

    $stmt = $pdo->prepare("SELECT photo FROM complaints WHERE id = ?");
    $stmt->execute([$complaint_id]);
    $complaint = $stmt->fetch();
    
    if ($complaint && !empty($complaint['photo']) && file_exists('../' . $complaint['photo'])) {
        unlink('../' . $complaint['photo']);
    }


    $deleteStmt = $pdo->prepare("DELETE FROM complaints WHERE id = ?");
    $deleteStmt->execute([$complaint_id]);
}

header('Location: complaints.php');
exit;