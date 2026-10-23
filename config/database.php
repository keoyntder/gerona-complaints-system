<?php

date_default_timezone_set('Asia/Manila');

$host     = 'localhost';
$dbname   = 'ragsak_ranyag';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '+08:00'");
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}