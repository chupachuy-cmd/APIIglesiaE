<?php

require_once 'db.php';
require_once 'helpers.php';

setHeaders();
checkApiRateLimit();

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';

function authenticateApiRequest(): void
{
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
    $validApiKey = getApiKey();

    if (empty($validApiKey)) {
        return;
    }

    if (!hash_equals($validApiKey, $apiKey)) {
        http_response_code(401);
        echo json_encode(["success" => 0, "message" => "API Key inválida"]);
        exit;
    }
}

$db = Database::getInstance();
$conn = $db->getConnection();

$allowedTables = [
    'coros'          => 'coros',
    'devocionarios'  => 'devocionarios',
    'dulia'          => 'dulia',
    'gacetas'        => 'gacetas',
    'hiperdulia'     => 'hiperdulia',
    'latria'         => 'latria',
    'predicas'       => 'predicas',
    'eventos'        => 'eventos',
    'oraciones'      => 'oraciones',
    'quizzes'        => 'quizzes',
    'quiz_questions' => 'quiz_questions'
];

$tableSchemas = [
    'coros'          => ['title', 'lyrics', 'url'],
    'devocionarios'  => ['title', 'description', 'url'],
    'dulia'          => ['title', 'descripcion', 'url'],
    'gacetas'        => ['name', 'date', 'url'],
    'hiperdulia'     => ['title', 'description', 'url'],
    'latria'         => ['title', 'description', 'url'],
    'predicas'       => ['title', 'description', 'url'],
    'eventos'        => ['invitation', 'title', 'date_event', 'hour_event', 'place', 'image_url'],
    'oraciones'      => ['title_pray', 'description_pray', 'subject_pray', 'pray_for', 'pray_to', 'date_pray', 'lyrics_pray'],
    'quizzes'        => ['title', 'description', 'week_start', 'week_end', 'is_active'],
    'quiz_questions' => ['quiz_id', 'question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_option', 'question_type', 'order_index'],
];

$endpoint = strtolower($endpoint);

if (!isset($allowedTables[$endpoint])) {
    echo json_encode([
        "success" => 0,
        "message" => "Endpoint no válido. Endpoints disponibles: " . implode(', ', array_keys($allowedTables))
    ]);
    exit;
}

$table = $allowedTables[$endpoint];
$validColumns = $tableSchemas[$endpoint];

switch ($method) {
    case 'GET':
        $data = $db->fetchAll($table, $id);

        if (empty($data)) {
            echo json_encode([
                "success" => 0,
                "message" => $id ? "No se encontró registro con ID $id" : "No hay datos disponibles"
            ]);
        } else {
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'POST':
        authenticateApiRequest();
        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input) {
            echo json_encode(["success" => 0, "error" => "Datos inválidos"]);
            break;
        }

        $filtered = [];
        foreach ($validColumns as $col) {
            $val = $input[$col] ?? '';
            $filtered[$col] = is_string($val) ? trim($val) : $val;
        }
        $columns = implode(', ', array_keys($filtered));
        $placeholders = implode(', ', array_fill(0, count($filtered), '?'));
        $types = str_repeat('s', count($filtered));

        $sql = "INSERT INTO `$table` ($columns) VALUES ($placeholders)";

        if ($endpoint === 'quizzes' && !empty($filtered['is_active'])) {
            $conn->query("UPDATE quizzes SET is_active = 0");
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...array_values($filtered));

        if ($stmt->execute()) {
            echo json_encode(["success" => 1, "id" => $conn->insert_id]);
        } else {
            echo json_encode(["success" => 0, "error" => "Error al crear registro"]);
        }
        break;

    case 'PUT':
        authenticateApiRequest();
        if (!$id) {
            echo json_encode(["success" => 0, "error" => "ID requerido"]);
            break;
        }

        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input) {
            echo json_encode(["success" => 0, "error" => "Datos inválidos"]);
            break;
        }

        $filtered = [];
        foreach ($validColumns as $col) {
            if (array_key_exists($col, $input)) {
                $val = $input[$col];
                $filtered[$col] = is_string($val) ? trim($val) : $val;
            }
        }

        if (empty($filtered)) {
            echo json_encode(["success" => 0, "error" => "No hay columnas válidas para actualizar"]);
            break;
        }

        $sets = [];
        foreach ($filtered as $col => $val) {
            $sets[] = "$col = ?";
        }
        $types = str_repeat('s', count($filtered)) . 'i';
        $params = array_values($filtered);
        $params[] = $id;

        $sql = "UPDATE `$table` SET " . implode(', ', $sets) . " WHERE id = ?";

        if ($endpoint === 'quizzes' && isset($filtered['is_active']) && $filtered['is_active']) {
            $conn->query("UPDATE quizzes SET is_active = 0");
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            echo json_encode(["success" => 1, "message" => "Registro actualizado"]);
        } else {
            echo json_encode(["success" => 0, "error" => "Error al actualizar"]);
        }
        break;

    case 'DELETE':
        authenticateApiRequest();
        if (!$id) {
            echo json_encode(["success" => 0, "error" => "ID requerido"]);
            break;
        }

        $sql = "DELETE FROM `$table` WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(["success" => 1, "message" => "Registro eliminado"]);
        } else {
            echo json_encode(["success" => 0, "error" => "Error al eliminar"]);
        }
        break;

    default:
        echo json_encode(["success" => 0, "error" => "Método no permitido"]);
}
