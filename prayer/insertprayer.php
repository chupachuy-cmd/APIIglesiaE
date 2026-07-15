<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

$title_pray = $_POST['title_pray'] ?? '';
$description_pray = $_POST['description_pray'] ?? '';
$subject_pray = $_POST['subject_pray'] ?? '';
$pray_for = $_POST['pray_for'] ?? '';
$pray_to = $_POST['pray_to'] ?? '';
$date_pray = $_POST['date_pray'] ?? '';
$lyrics_pray = $_POST['lyrics_pray'] ?? '';

if (empty($title_pray) || empty($description_pray)) {
    die("Error: Título y descripción son obligatorios");
}

$stmt = $conn->prepare("INSERT INTO oraciones (title_pray, description_pray, subject_pray, pray_for, pray_to, date_pray, lyrics_pray) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $title_pray, $description_pray, $subject_pray, $pray_for, $pray_to, $date_pray, $lyrics_pray);

if ($stmt->execute()) {
    header('Location: index.php');
    exit;
} else {
    echo "Error al crear el Registro";
}
