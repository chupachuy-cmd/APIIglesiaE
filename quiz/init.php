<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userRol = $_SESSION['user_rol'] ?? 'user';
if ($userRol !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
