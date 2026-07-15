<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("DELETE FROM eventos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo "<script language='JavaScript'>alert('Evento eliminado correctamente'); location.assign('index.php');</script>";
} else {
    echo "<script language='JavaScript'>alert('Evento NO fue eliminado'); location.assign('index.php');</script>";
}
