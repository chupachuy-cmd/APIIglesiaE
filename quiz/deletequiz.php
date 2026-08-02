<?php
require_once 'init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

validateCsrfToken();

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id > 0) {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("DELETE FROM quiz_responses WHERE quiz_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM quiz_questions WHERE quiz_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM quizzes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $conn->commit();
        echo "<script>alert('Quiz eliminado correctamente'); location.assign('index.php');</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error al eliminar el quiz'); location.assign('index.php');</script>";
    }
} else {
    echo "<script>alert('Quiz NO fue eliminado'); location.assign('index.php');</script>";
}
