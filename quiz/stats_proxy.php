<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => 0, 'error' => 'No autenticado']);
    exit;
}

$userRol = $_SESSION['user_rol'] ?? 'user';
if ($userRol !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => 0, 'error' => 'Acceso denegado']);
    exit;
}

$apiKey = getApiKey();
if (empty($apiKey)) {
    http_response_code(500);
    echo json_encode(['success' => 0, 'error' => 'API Key no configurada']);
    exit;
}

$action = $_GET['action'] ?? 'dashboard_stats';
$baseUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/../quiz_api.php?action=' . urlencode($action);

$allowedParams = ['quiz_id', 'token_id'];
foreach ($allowedParams as $param) {
    if (isset($_GET[$param])) {
        $baseUrl .= '&' . $param . '=' . urlencode($_GET[$param]);
    }
}

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $baseUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['X-API-Key: ' . $apiKey],
    CURLOPT_TIMEOUT => 30,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($httpCode);
header('Content-Type: application/json; charset=utf-8');
echo $response;
