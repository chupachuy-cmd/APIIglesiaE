<?php
require_once __DIR__ . '/db.php';

function generateQuizToken(?string $nickname = null): array
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $token = bin2hex(random_bytes(32));

    $stmt = $conn->prepare("INSERT INTO quiz_tokens (token, nickname) VALUES (?, ?)");
    $stmt->bind_param("ss", $token, $nickname);
    $stmt->execute();

    return [
        'success' => 1,
        'token' => $token,
        'nickname' => $nickname
    ];
}

function validateQuizToken(string $token): ?array
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT id, token, nickname, quiz_id, started_at, elapsed_seconds, created_at FROM quiz_tokens WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $updateStmt = $conn->prepare("UPDATE quiz_tokens SET last_activity = NOW() WHERE id = ?");
        $updateStmt->bind_param("i", $row['id']);
        $updateStmt->execute();
        return $row;
    }

    return null;
}

function startQuizAttempt(int $tokenId, int $quizId): array
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT started_at FROM quiz_tokens WHERE id = ?");
    $stmt->bind_param("i", $tokenId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row && $row['started_at']) {
        return ['started' => false, 'started_at' => $row['started_at']];
    }

    $stmt = $conn->prepare("UPDATE quiz_tokens SET quiz_id = ?, started_at = UTC_TIMESTAMP() WHERE id = ?");
    $stmt->bind_param("ii", $quizId, $tokenId);
    $stmt->execute();

    $stmt = $conn->prepare("SELECT started_at FROM quiz_tokens WHERE id = ?");
    $stmt->bind_param("i", $tokenId);
    $stmt->execute();

    return ['started' => true, 'started_at' => $stmt->get_result()->fetch_assoc()['started_at']];
}

function hasUserRespondedQuiz(int $tokenId, int $quizId): bool
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM quiz_responses WHERE token_id = ? AND quiz_id = ?");
    $stmt->bind_param("ii", $tokenId, $quizId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row['count'] > 0;
}
