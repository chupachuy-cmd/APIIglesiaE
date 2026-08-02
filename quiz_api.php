<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/quiz_token.php';

setHeaders();
checkApiRateLimit();

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getInstance();
$conn = $db->getConnection();

function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($action) {
    case 'token':
        if ($method !== 'GET') {
            jsonResponse(['success' => 0, 'error' => 'Método no permitido'], 405);
        }

        $nickname = trim($_GET['nickname'] ?? '');

        if ($nickname !== '' && (mb_strlen($nickname) < 2 || mb_strlen($nickname) > 50)) {
            jsonResponse(['success' => 0, 'error' => 'El nickname debe tener entre 2 y 50 caracteres'], 400);
        }

        $result = generateQuizToken($nickname !== '' ? $nickname : null);
        jsonResponse($result);
        break;

    case 'start':
        if ($method !== 'POST') {
            jsonResponse(['success' => 0, 'error' => 'Método no permitido'], 405);
        }

        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input) {
            jsonResponse(['success' => 0, 'error' => 'Datos inválidos'], 400);
        }

        $token = $input['token'] ?? '';
        $quizId = intval($input['quiz_id'] ?? 0);

        $tokenData = validateQuizToken($token);

        if (!$tokenData) {
            jsonResponse(['success' => 0, 'error' => 'Token inválido'], 401);
        }

        if ($quizId <= 0) {
            jsonResponse(['success' => 0, 'error' => 'Quiz ID requerido'], 400);
        }

        $stmt = $conn->prepare("SELECT id, title, description, week_start, week_end, is_active FROM quizzes WHERE id = ?");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        $quiz = $stmt->get_result()->fetch_assoc();

        if (!$quiz) {
            jsonResponse(['success' => 0, 'error' => 'Quiz no encontrado'], 404);
        }

        $today = date('Y-m-d');
        if (!$quiz['is_active'] || $quiz['week_start'] > $today || $quiz['week_end'] < $today) {
            jsonResponse(['success' => 0, 'error' => 'El quiz no está activo']);
        }

        $startResult = startQuizAttempt($tokenData['id'], $quizId);

        $stmt = $conn->prepare("SELECT id, question_text, option_a, option_b, option_c, option_d, question_type, order_index FROM quiz_questions WHERE quiz_id = ? ORDER BY order_index ASC");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $quiz['questions'] = $questions;

        jsonResponse([
            'success' => 1,
            'quiz' => $quiz,
            'started_at' => $startResult['started_at'],
            'nickname' => $tokenData['nickname']
        ]);
        break;

    case 'active':
        if ($method !== 'GET') {
            jsonResponse(['success' => 0, 'error' => 'Método no permitido'], 405);
        }

        $today = date('Y-m-d');
        $stmt = $conn->prepare("SELECT id, title, description, week_start, week_end FROM quizzes WHERE is_active = 1 AND week_start <= ? AND week_end >= ? LIMIT 1");
        $stmt->bind_param("ss", $today, $today);
        $stmt->execute();
        $quiz = $stmt->get_result()->fetch_assoc();

        if (!$quiz) {
            jsonResponse(['success' => 2, 'message' => 'No hay quiz activo esta semana']);
        }

        $stmt = $conn->prepare("SELECT id, question_text, option_a, option_b, option_c, option_d, question_type, order_index FROM quiz_questions WHERE quiz_id = ? ORDER BY order_index ASC");
        $stmt->bind_param("i", $quiz['id']);
        $stmt->execute();
        $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $quiz['questions'] = $questions;

        jsonResponse(['success' => 1, 'quiz' => $quiz]);
        break;

    case 'status':
        if ($method !== 'GET') {
            jsonResponse(['success' => 0, 'error' => 'Método no permitido'], 405);
        }

        $token = $_SERVER['HTTP_X_QUIZ_TOKEN'] ?? $_GET['token'] ?? '';
        $tokenData = validateQuizToken($token);

        if (!$tokenData) {
            jsonResponse(['success' => 0, 'error' => 'Token inválido'], 401);
        }

        $today = date('Y-m-d');
        $stmt = $conn->prepare("SELECT id FROM quizzes WHERE is_active = 1 AND week_start <= ? AND week_end >= ? LIMIT 1");
        $stmt->bind_param("ss", $today, $today);
        $stmt->execute();
        $quiz = $stmt->get_result()->fetch_assoc();

        if (!$quiz) {
            jsonResponse(['success' => 2, 'message' => 'No hay quiz activo']);
        }

        $responded = hasUserRespondedQuiz($tokenData['id'], $quiz['id']);

        jsonResponse([
            'success' => 1,
            'responded' => $responded,
            'quiz_id' => $quiz['id'],
            'nickname' => $tokenData['nickname']
        ]);
        break;

    case 'submit':
        if ($method !== 'POST') {
            jsonResponse(['success' => 0, 'error' => 'Método no permitido'], 405);
        }

        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input) {
            jsonResponse(['success' => 0, 'error' => 'Datos inválidos'], 400);
        }

        $token = $input['token'] ?? '';
        $quizId = intval($input['quiz_id'] ?? 0);
        $responses = $input['responses'] ?? [];

        $tokenData = validateQuizToken($token);

        if (!$tokenData) {
            jsonResponse(['success' => 0, 'error' => 'Token inválido'], 401);
        }

        if (hasUserRespondedQuiz($tokenData['id'], $quizId)) {
            jsonResponse(['success' => 0, 'error' => 'Ya respondiste este quiz']);
        }

        $stmt = $conn->prepare("SELECT id, is_active, week_start, week_end FROM quizzes WHERE id = ?");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        $quiz = $stmt->get_result()->fetch_assoc();

        if (!$quiz) {
            jsonResponse(['success' => 0, 'error' => 'Quiz no encontrado'], 404);
        }

        $today = date('Y-m-d');
        if (!$quiz['is_active'] || $quiz['week_start'] > $today || $quiz['week_end'] < $today) {
            jsonResponse(['success' => 0, 'error' => 'El quiz no está activo']);
        }

        $totalCorrect = 0;
        $totalQuestions = count($responses);

        $conn->begin_transaction();
        try {
            $insertStmt = $conn->prepare("INSERT INTO quiz_responses (token_id, quiz_id, question_id, selected_option, is_correct) VALUES (?, ?, ?, ?, ?)");

            foreach ($responses as $response) {
                $questionId = intval($response['question_id'] ?? 0);
                $selected = strtoupper(trim($response['selected_option'] ?? ''));

                $qStmt = $conn->prepare("SELECT correct_option FROM quiz_questions WHERE id = ? AND quiz_id = ?");
                $qStmt->bind_param("ii", $questionId, $quizId);
                $qStmt->execute();
                $qResult = $qStmt->get_result()->fetch_assoc();

                if (!$qResult) {
                    continue;
                }

                $isCorrect = ($selected === $qResult['correct_option']) ? 1 : 0;
                if ($isCorrect) {
                    $totalCorrect++;
                }

                $insertStmt->bind_param("iiisi", $tokenData['id'], $quizId, $questionId, $selected, $isCorrect);
                $insertStmt->execute();
            }

            $updateStmt = $conn->prepare("UPDATE quiz_tokens SET elapsed_seconds = TIMESTAMPDIFF(SECOND, started_at, UTC_TIMESTAMP()) WHERE id = ?");
            $updateStmt->bind_param("i", $tokenData['id']);
            $updateStmt->execute();

            $stmt = $conn->prepare("SELECT elapsed_seconds FROM quiz_tokens WHERE id = ?");
            $stmt->bind_param("i", $tokenData['id']);
            $stmt->execute();
            $elapsedSeconds = (int) $stmt->get_result()->fetch_assoc()['elapsed_seconds'];

            $conn->commit();

            jsonResponse([
                'success' => 1,
                'score' => [
                    'total' => $totalQuestions,
                    'correct' => $totalCorrect,
                    'incorrect' => $totalQuestions - $totalCorrect
                ],
                'elapsed_seconds' => $elapsedSeconds,
                'nickname' => $tokenData['nickname']
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            jsonResponse(['success' => 0, 'error' => 'Error al guardar respuestas'], 500);
        }
        break;

    case 'dashboard_stats':
        if ($method !== 'GET') {
            jsonResponse(['success' => 0, 'error' => 'Método no permitido'], 405);
        }

        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
        $validApiKey = getApiKey();

        if (empty($validApiKey) || !hash_equals($validApiKey, $apiKey)) {
            jsonResponse(['success' => 0, 'error' => 'API Key requerida'], 401);
        }

        $quizId = intval($_GET['quiz_id'] ?? 0);

        if ($quizId <= 0) {
            $stmt = $conn->query("SELECT id FROM quizzes ORDER BY id DESC LIMIT 1");
            $latest = $stmt->fetch_assoc();
            $quizId = $latest ? $latest['id'] : 0;
        }

        if ($quizId <= 0) {
            jsonResponse(['success' => 0, 'error' => 'No hay quizzes disponibles']);
        }

        $stmt = $conn->prepare("SELECT id, title FROM quizzes WHERE id = ?");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        $quiz = $stmt->get_result()->fetch_assoc();

        $stmt = $conn->prepare("SELECT COUNT(DISTINCT token_id) as total FROM quiz_responses WHERE quiz_id = ?");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        $totalRespondents = $stmt->get_result()->fetch_assoc()['total'];

        $stmt = $conn->prepare("
            SELECT 
                qq.id, 
                qq.question_text, 
                qq.correct_option,
                qq.question_type,
                COUNT(qr.id) as total_answers,
                SUM(qr.is_correct) as correct_answers,
                SUM(CASE WHEN qr.selected_option = 'A' THEN 1 ELSE 0 END) as count_a,
                SUM(CASE WHEN qr.selected_option = 'B' THEN 1 ELSE 0 END) as count_b,
                SUM(CASE WHEN qr.selected_option = 'C' THEN 1 ELSE 0 END) as count_c,
                SUM(CASE WHEN qr.selected_option = 'D' THEN 1 ELSE 0 END) as count_d
            FROM quiz_questions qq
            LEFT JOIN quiz_responses qr ON qq.id = qr.question_id AND qr.quiz_id = ?
            WHERE qq.quiz_id = ?
            GROUP BY qq.id
            ORDER BY qq.order_index ASC
        ");
        $stmt->bind_param("ii", $quizId, $quizId);
        $stmt->execute();
        $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $result = [];
        foreach ($questions as $q) {
            $qType = $q['question_type'] ?? 'multiple_choice_4';
            $options = [
                'A' => ['count' => (int)$q['count_a'], 'correct' => $q['correct_option'] === 'A'],
                'B' => ['count' => (int)$q['count_b'], 'correct' => $q['correct_option'] === 'B'],
            ];
            if ($qType === 'multiple_choice_4') {
                $options['C'] = ['count' => (int)$q['count_c'], 'correct' => $q['correct_option'] === 'C'];
                $options['D'] = ['count' => (int)$q['count_d'], 'correct' => $q['correct_option'] === 'D'];
            }
            $result[] = [
                'id' => $q['id'],
                'question_text' => $q['question_text'],
                'question_type' => $qType,
                'total_answers' => (int)$q['total_answers'],
                'correct' => (int)$q['correct_answers'],
                'incorrect' => (int)($q['total_answers'] - $q['correct_answers']),
                'options' => $options,
                'correct_option' => $q['correct_option'],
            ];
        }

        $stmt = $conn->prepare("
            SELECT 
                qt.id as token_id,
                qt.nickname,
                qt.elapsed_seconds,
                COUNT(qr.id) as total_answers,
                SUM(qr.is_correct) as correct_answers
            FROM quiz_tokens qt
            LEFT JOIN quiz_responses qr ON qt.id = qr.token_id AND qr.quiz_id = ?
            WHERE qt.quiz_id = ? AND qt.nickname IS NOT NULL AND qt.started_at IS NOT NULL
            GROUP BY qt.id, qt.nickname, qt.elapsed_seconds
            ORDER BY correct_answers DESC, qt.elapsed_seconds ASC
        ");
        $stmt->bind_param("ii", $quizId, $quizId);
        $stmt->execute();
        $rawRespondents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $respondents = [];
        foreach ($rawRespondents as $r) {
            $respondents[] = [
                'token_id' => (int)$r['token_id'],
                'nickname' => $r['nickname'],
                'score_correct' => (int)$r['correct_answers'],
                'score_total' => (int)$r['total_answers'],
                'elapsed_seconds' => (int)$r['elapsed_seconds'],
            ];
        }

        jsonResponse([
            'success' => 1,
            'quiz' => $quiz,
            'total_respondents' => (int)$totalRespondents,
            'questions' => $result,
            'respondents' => $respondents
        ]);
        break;

    case 'respondent_detail':
        if ($method !== 'GET') {
            jsonResponse(['success' => 0, 'error' => 'Método no permitido'], 405);
        }

        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
        $validApiKey = getApiKey();

        if (empty($validApiKey) || !hash_equals($validApiKey, $apiKey)) {
            jsonResponse(['success' => 0, 'error' => 'API Key requerida'], 401);
        }

        $tokenId = intval($_GET['token_id'] ?? 0);
        $quizId = intval($_GET['quiz_id'] ?? 0);

        if ($tokenId <= 0 || $quizId <= 0) {
            jsonResponse(['success' => 0, 'error' => 'token_id y quiz_id requeridos'], 400);
        }

        $stmt = $conn->prepare("SELECT nickname, elapsed_seconds FROM quiz_tokens WHERE id = ?");
        $stmt->bind_param("i", $tokenId);
        $stmt->execute();
        $tokenRow = $stmt->get_result()->fetch_assoc();

        if (!$tokenRow) {
            jsonResponse(['success' => 0, 'error' => 'Token no encontrado'], 404);
        }

        $stmt = $conn->prepare("
            SELECT 
                qq.question_text,
                qq.correct_option,
                qq.question_type,
                qq.option_a,
                qq.option_b,
                qq.option_c,
                qq.option_d,
                qr.selected_option,
                qr.is_correct
            FROM quiz_responses qr
            JOIN quiz_questions qq ON qr.question_id = qq.id
            WHERE qr.token_id = ? AND qr.quiz_id = ?
            ORDER BY qq.order_index ASC
        ");
        $stmt->bind_param("ii", $tokenId, $quizId);
        $stmt->execute();
        $details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $correctCount = 0;
        $responseList = [];
        foreach ($details as $d) {
            $isCorrect = (int)$d['is_correct'];
            if ($isCorrect) $correctCount++;

            $labelMap = ['A' => 'option_a', 'B' => 'option_b', 'C' => 'option_c', 'D' => 'option_d'];
            $selectedLabel = $d['question_type'] === 'true_false'
                ? ($d['selected_option'] === 'A' ? 'Verdadero' : 'Falso')
                : ($d['selected_option'] . ') ' . ($d[$labelMap[$d['selected_option']] ?? ''] ?? ''));

            $correctLabel = $d['question_type'] === 'true_false'
                ? ($d['correct_option'] === 'A' ? 'Verdadero' : 'Falso')
                : ($d['correct_option'] . ') ' . ($d[$labelMap[$d['correct_option']] ?? ''] ?? ''));

            $responseList[] = [
                'question_text' => $d['question_text'],
                'question_type' => $d['question_type'],
                'selected_option' => $d['selected_option'],
                'selected_label' => $selectedLabel,
                'correct_option' => $d['correct_option'],
                'correct_label' => $correctLabel,
                'is_correct' => (bool)$isCorrect,
            ];
        }

        jsonResponse([
            'success' => 1,
            'nickname' => $tokenRow['nickname'],
            'elapsed_seconds' => (int)$tokenRow['elapsed_seconds'],
            'score_correct' => $correctCount,
            'score_total' => count($responseList),
            'details' => $responseList
        ]);
        break;

    default:
        jsonResponse(['success' => 0, 'error' => 'Acción no válida. Disponibles: token, start, active, status, submit, dashboard_stats'], 400);
}
