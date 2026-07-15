<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

$invitation = $_POST['invitation'] ?? '';
$title = $_POST['title'] ?? '';
$date_event = $_POST['date_event'] ?? '';
$place = $_POST['place'] ?? '';
$hour_event = $_POST['hour_event'] ?? '';
$image_url = $_POST['image_url'] ?? '';

if (empty($title) || empty($invitation)) {
    die("Error: Título e invitación son obligatorios");
}

$stmt = $conn->prepare("INSERT INTO eventos (invitation, title, date_event, place, hour_event, image_url) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $invitation, $title, $date_event, $place, $hour_event, $image_url);

if ($stmt->execute()) {
    header('Location: index.php');
    exit;
} else {
    echo "Error al crear el evento";
}
