<?php
session_start();

function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('CSRF token inválido');
    }
}

function csrfField(): string
{
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function checkRateLimit(string $key, int $maxAttempts = 10, int $windowSeconds = 60): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $cacheKey = 'ratelimit_' . $key . '_' . md5($ip);
    $now = time();

    if (!isset($_SESSION[$cacheKey])) {
        $_SESSION[$cacheKey] = ['count' => 0, 'reset' => $now + $windowSeconds];
    }

    $data = &$_SESSION[$cacheKey];

    if ($now > $data['reset']) {
        $data = ['count' => 0, 'reset' => $now + $windowSeconds];
    }

    $data['count']++;

    if ($data['count'] > $maxAttempts) {
        http_response_code(429);
        die(json_encode(["success" => 0, "message" => "Demasiadas solicitudes. Intente de nuevo en " . ($data['reset'] - $now) . " segundos."]));
    }

    return true;
}

function checkLoginRateLimit(): void
{
    checkRateLimit('login', 5, 300);
}

function checkApiRateLimit(): void
{
    checkRateLimit('api', 60, 60);
}

function requireAuth(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function requireRole(string $role): void
{
    requireAuth();
    $userRol = $_SESSION['user_rol'] ?? 'user';
    if ($userRol !== $role) {
        header('Location: dashboard.php');
        exit;
    }
}

function configureSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.use_strict_mode', '1');
    }
}

function formatDateSpanish(string $date): string
{
    if (empty($date)) return '';
    $timestamp = strtotime($date);
    if ($timestamp === false) return $date;
    $months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $days = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    return date('d', $timestamp) . ' de ' . $months[date('n', $timestamp) - 1] . ' de ' . date('Y', $timestamp);
}

function formatDayNameSpanish(string $date): string
{
    if (empty($date)) return '';
    $timestamp = strtotime($date);
    if ($timestamp === false) return '';
    $days = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    return $days[date('w', $timestamp)];
}

function formatMonthAbbrSpanish(string $date): string
{
    if (empty($date)) return '';
    $timestamp = strtotime($date);
    if ($timestamp === false) return '';
    $months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    return $months[date('n', $timestamp) - 1];
}

function sanitizeFilename(string $filename): string
{
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $name = preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
    $name = preg_replace('/-+/', '-', $name);
    $name = trim($name, '-');
    if (empty($name)) $name = 'archivo';
    return $name . '.' . $ext;
}

function validateFileUpload(array $file, array $allowedExtensions, int $maxSizeMb = 10): ?string
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return 'Error al subir el archivo (código: ' . ($file['error'] ?? 'desconocido') . ')';
    }

    $maxSize = $maxSizeMb * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return 'El archivo excede el tamaño máximo de ' . $maxSizeMb . 'MB';
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions)) {
        return 'Tipo de archivo no permitido. Solo: ' . implode(', ', $allowedExtensions);
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = [
        'mp3'  => ['audio/mpeg', 'audio/mp3', 'audio/x-mpeg'],
        'pdf'  => ['application/pdf'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
    ];

    if (isset($allowedMimes[$ext]) && !in_array($mime, $allowedMimes[$ext])) {
        return 'El archivo no coincide con la extensión declarada (MIME: ' . $mime . ')';
    }

    return null;
}

function uploadFile(array $file, string $targetDir, array $allowedExtensions, int $maxSizeMb = 10): array
{
    $error = validateFileUpload($file, $allowedExtensions, $maxSizeMb);
    if ($error !== null) {
        return ['success' => false, 'error' => $error];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = sanitizeFilename($file['name']);

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0750, true);
    }

    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;
    $counter = 1;
    while (file_exists($targetPath)) {
        $nameOnly = pathinfo($filename, PATHINFO_FILENAME);
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $nameOnly . '_' . $counter . '.' . $ext;
        $counter++;
    }

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'path' => basename(dirname($targetPath)) . '/' . basename($targetPath)];
    }

    return ['success' => false, 'error' => 'Error al mover el archivo'];
}
