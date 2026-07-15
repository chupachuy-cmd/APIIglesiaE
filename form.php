<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

configureSession();
requireAuth();

$rol = $_SESSION['user_rol'] ?? 'user';
$endpoint = $_GET['endpoint'] ?? 'coros';
$action = $_GET['action'] ?? 'new';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

$tablesCanEditByRole = [
    'admin' => ['coros', 'devocionarios', 'dulia', 'gacetas', 'predicas', 'eventos', 'oraciones', 'hiperdulia', 'latria'],
    'editor' => ['oraciones', 'eventos']
];
$canEditThisTable = in_array($endpoint, $tablesCanEditByRole[$rol] ?? []);

if (!$canEditThisTable) {
    header('Location: dashboard.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

$tables = [
    'coros' => ['title' => 'Coros', 'fields' => ['title', 'lyrics', 'url'], 'upload' => ['field' => 'url', 'dir' => 'audios', 'ext' => ['mp3'], 'accept' => '.mp3', 'maxSize' => 20]],
    'devocionarios' => ['title' => 'Devocionarios', 'fields' => ['title', 'description', 'url'], 'upload' => ['field' => 'url', 'dir' => 'audios', 'ext' => ['mp3'], 'accept' => '.mp3', 'maxSize' => 20]],
    'dulia' => ['title' => 'Dulia', 'fields' => ['title', 'descripcion', 'url'], 'upload' => ['field' => 'url', 'dir' => 'audios', 'ext' => ['mp3'], 'accept' => '.mp3', 'maxSize' => 20]],
    'gacetas' => ['title' => 'Gacetas', 'fields' => ['name', 'date', 'url'], 'upload' => ['field' => 'url', 'dir' => 'gacetas', 'ext' => ['pdf'], 'accept' => '.pdf', 'maxSize' => 20]],
    'predicas' => ['title' => 'Prédicas', 'fields' => ['title', 'description', 'url']],
    'eventos' => ['title' => 'Eventos', 'fields' => ['invitation', 'title', 'date_event', 'hour_event', 'place', 'image_url'], 'upload' => ['field' => 'image_url', 'dir' => 'images', 'ext' => ['jpg', 'jpeg', 'png'], 'accept' => '.jpg,.jpeg,.png', 'maxSize' => 10]],
    'oraciones' => ['title' => 'Oraciones', 'fields' => ['title_pray', 'description_pray', 'subject_pray', 'pray_for', 'pray_to', 'date_pray', 'lyrics_pray']],
    'hiperdulia' => ['title' => 'Hiperdulia', 'fields' => ['title', 'description', 'url'], 'upload' => ['field' => 'url', 'dir' => 'audios', 'ext' => ['mp3'], 'accept' => '.mp3', 'maxSize' => 20]],
    'latria' => ['title' => 'Latria', 'fields' => ['title', 'description', 'url'], 'upload' => ['field' => 'url', 'dir' => 'audios', 'ext' => ['mp3'], 'accept' => '.mp3', 'maxSize' => 20]]
];

if (!isset($tables[$endpoint])) {
    die("Endpoint no válido");
}

$tableInfo = $tables[$endpoint];
$tableName = $endpoint;
$row = [];
$uploadConfig = $tableInfo['upload'] ?? null;

if ($action === 'edit' && $id) {
    $stmt = $conn->prepare("SELECT * FROM $tableName WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if (!$row) die("Registro no encontrado");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();

    $fields = $tableInfo['fields'];
    $data = [];

    foreach ($fields as $field) {
        if ($uploadConfig && $uploadConfig['field'] === $field && $action === 'edit' && $id) {
            $data[$field] = $row[$field] ?? '';
        } else {
            $data[$field] = $_POST[$field] ?? '';
        }
    }

    if ($uploadConfig && isset($_FILES[$uploadConfig['field']]) && $_FILES[$uploadConfig['field']]['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES[$uploadConfig['field']];
        $allowedExt = $uploadConfig['ext'];
        $maxSize = $uploadConfig['maxSize'] ?? 10;

        $uploadResult = uploadFile($file, __DIR__ . '/' . $uploadConfig['dir'], $allowedExt, $maxSize);

        if ($uploadResult['success']) {
            $data[$uploadConfig['field']] = $uploadResult['path'];
        } else {
            echo "<script>alert('" . addslashes($uploadResult['error']) . "'); history.back();</script>";
            exit;
        }
    }

    if ($action === 'edit' && $id) {
        $sets = [];
        foreach ($fields as $field) {
            $sets[] = "$field = ?";
        }
        $sql = "UPDATE $tableName SET " . implode(', ', $sets) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $params = array_values($data);
        $params[] = $id;
        $types = str_repeat('s', count($fields)) . 'i';
        $stmt->bind_param($types, ...$params);
    } else {
        $columns = implode(', ', $fields);
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        $sql = "INSERT INTO $tableName ($columns) VALUES ($placeholders)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(str_repeat('s', count($fields)), ...array_values($data));
    }

    $stmt->execute();
    header("Location: tabla.php?endpoint=$endpoint");
    exit;
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $action === 'edit' ? 'Editar' : 'Nuevo' ?> <?= $tableInfo['title'] ?> - Iglesia Eliasista</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f5f5f5; }
        .header { background: #16213e; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.2rem; }
        .header a { color: white; text-decoration: none; padding: 0.5rem 1rem; background: #6c757d; border-radius: 5px; }
        .container { padding: 2rem; max-width: 800px; margin: 0 auto; }
        .card { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); }
        h2 { color: #16213e; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #333; font-weight: bold; }
        .form-group input,
        .form-group textarea,
        .form-group select { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; }
        .form-group textarea { min-height: 150px; resize: vertical; }
        .editor-toolbar { display: flex; gap: 0.25rem; margin-bottom: 0.25rem; }
        .editor-toolbar button { padding: 0.25rem 0.5rem; border: 1px solid #ddd; background: #f8f9fa; cursor: pointer; border-radius: 3px; font-size: 0.9rem; }
        .editor-toolbar button:hover { background: #e9ecef; }
        .editor-toolbar button.active { background: #16213e; color: white; border-color: #16213e; }
        .form-group input:focus,
        .form-group textarea:focus { outline: none; border-color: #16213e; }
        .form-group .file-info { font-size: 0.85rem; color: #666; margin-top: 0.25rem; }
        .alert-info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 0.75rem; border-radius: 5px; margin-top: 0.5rem; font-size: 0.9rem; }
        .alert-info strong { display: block; margin-bottom: 0.25rem; }
        .alert-info a { color: #0c5460; text-decoration: underline; }
        .buttons { display: flex; gap: 1rem; margin-top: 1.5rem; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; text-decoration: none; }
        .btn-primary { background: #16213e; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-primary:hover { background: #1a1a2e; }
    </style>
</head>
<body>
    <div class="header">
        <h1><?= $action === 'edit' ? 'Editar' : 'Nuevo' ?> <?= $tableInfo['title'] ?></h1>
        <a href="tabla.php?endpoint=<?= $endpoint ?>">Cancelar</a>
    </div>

    <div class="container">
        <div class="card">
            <h2><?= $action === 'edit' ? 'Editar' : 'Nuevo' ?> <?= $tableInfo['title'] ?></h2>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <?php 
                $labels = [
                    'title' => 'Título', 'lyrics' => 'Letra', 'url' => 'Archivo',
                    'description' => 'Descripción', 'descripcion' => 'Descripción',
                    'name' => 'Nombre', 'date' => 'Fecha', 'date_event' => 'Fecha del evento',
                    'hour_event' => 'Hora del evento', 'place' => 'Lugar',
                    'image_url' => 'Imagen', 'invitation' => 'Invitación',
                    'title_pray' => 'Título de oración', 'description_pray' => 'Descripción',
                    'subject_pray' => 'Tema', 'pray_for' => 'Orar por',
                    'pray_to' => 'Orar a', 'date_pray' => 'Fecha', 'lyrics_pray' => 'Letra'
                ];
                ?>
                <?php foreach ($tableInfo['fields'] as $field): ?>
                    <div class="form-group">
                        <label for="<?= $field ?>"><?= $labels[$field] ?? ucfirst(str_replace('_', ' ', $field)) ?></label>
                        <?php
                        $isTextArea = in_array($field, ['description', 'descripcion', 'lyrics_pray', 'lyrics']);
                        $isUpload = ($uploadConfig && $uploadConfig['field'] === $field);
                        $value = $row[$field] ?? '';
                        $accept = $uploadConfig['accept'] ?? '';
                        $extText = strtoupper(str_replace('.', '', $accept));
                        $maxSize = $uploadConfig['maxSize'] ?? 10;
                        ?>
                        <?php if ($isUpload): ?>
                            <input type="file" id="<?= $field ?>" name="<?= $field ?>" accept="<?= $accept ?>">
                            <div class="file-info">
                                Solo archivos <?= $extText ?>. Máximo <?= $maxSize ?>MB.
                            </div>
                            <?php if ($value && $action === 'edit'): ?>
                                <div class="alert-info">
                                    <strong><i class="fas fa-info-circle"></i> Archivo actual:</strong>
                                    <a href="<?= htmlspecialchars($value) ?>" target="_blank"><?= htmlspecialchars($value) ?></a>
                                </div>
                            <?php endif; ?>
                        <?php elseif ($isTextArea): ?>
                            <?php if ($field === 'lyrics'): ?>
                                <div class="editor-toolbar">
                                    <button type="button" onclick="formatText('<?= $field ?>', 'bold')"><b>N</b></button>
                                    <button type="button" onclick="formatText('<?= $field ?>', 'italic')"><i>I</i></button>
                                    <button type="button" onclick="formatText('<?= $field ?>', 'center')">≡</button>
                                </div>
                            <?php endif; ?>
                            <textarea id="<?= $field ?>" name="<?= $field ?>" rows="6"><?= htmlspecialchars($value) ?></textarea>
                        <?php elseif (in_array($field, ['date', 'date_event'])): ?>
                            <input type="date" id="<?= $field ?>" name="<?= $field ?>" value="<?= htmlspecialchars($value) ?>">
                        <?php else: ?>
                            <input type="text" id="<?= $field ?>" name="<?= $field ?>" value="<?= htmlspecialchars($value) ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div class="buttons">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="tabla.php?endpoint=<?= $endpoint ?>" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        function formatText(fieldId, format) {
            const textarea = document.getElementById(fieldId);
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            const selected = text.substring(start, end);
            let wrapped = '';
            if (format === 'bold') wrapped = '<b>' + selected + '</b>';
            else if (format === 'italic') wrapped = '<i>' + selected + '</i>';
            else if (format === 'center') wrapped = '<center>' + selected + '</center>';
            textarea.value = text.substring(0, start) + wrapped + text.substring(end);
        }
    </script>
</body>
</html>
