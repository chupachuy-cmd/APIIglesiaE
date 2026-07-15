<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

configureSession();
requireAuth();

$endpoint = $_GET['endpoint'] ?? 'coros';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

$db = Database::getInstance();
$conn = $db->getConnection();

$tables = [
    'coros' => ['title' => 'Coros', 'columns' => ['id', 'title', 'lyrics', 'url']],
    'devocionarios' => ['title' => 'Devocionarios', 'columns' => ['id', 'title', 'description', 'url']],
    'dulia' => ['title' => 'Dulia', 'columns' => ['id', 'title', 'descripcion', 'url']],
    'gacetas' => ['title' => 'Gacetas', 'columns' => ['id', 'name', 'date', 'url']],
    'predicas' => ['title' => 'Prédicas', 'columns' => ['id', 'title', 'description', 'url']],
    'eventos' => ['title' => 'Eventos', 'columns' => ['id', 'invitation', 'title', 'date_event', 'hour_event', 'place', 'image_url']],
    'oraciones' => ['title' => 'Oraciones', 'columns' => ['id', 'title_pray', 'description_pray', 'subject_pray', 'pray_for', 'pray_to', 'date_pray', 'lyrics_pray']],
    'hiperdulia' => ['title' => 'Hiperdulia', 'columns' => ['id', 'title', 'description', 'url']],
    'latria' => ['title' => 'Latria', 'columns' => ['id', 'title', 'description', 'url']]
];

if (!isset($tables[$endpoint]) || !$id) {
    header('Location: tabla.php?endpoint=' . $endpoint);
    exit;
}

$tableInfo = $tables[$endpoint];
$tableName = $endpoint;

$stmt = $conn->prepare("SELECT * FROM $tableName WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    header('Location: tabla.php?endpoint=' . $endpoint);
    exit;
}

$rol = $_SESSION['user_rol'] ?? 'user';
$tablesCanEditByRole = [
    'admin' => ['coros', 'devocionarios', 'dulia', 'gacetas', 'predicas', 'eventos', 'oraciones', 'hiperdulia', 'latria'],
    'editor' => ['oraciones', 'eventos']
];
$canEdit = in_array($rol, ['admin', 'editor']) && in_array($endpoint, $tablesCanEditByRole[$rol] ?? []);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver <?= $tableInfo['title'] ?> - Iglesia Eliasista</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f5f5f5; }
        .header { background: #16213e; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.2rem; }
        .header a { color: white; text-decoration: none; padding: 0.5rem 1rem; background: #6c757d; border-radius: 5px; }
        .container { padding: 2rem; max-width: 800px; margin: 0 auto; }
        .card { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); }
        h2 { color: #16213e; margin-bottom: 1.5rem; }
        .detail-row { display: flex; border-bottom: 1px solid #eee; padding: 0.75rem 0; }
        .detail-label { font-weight: bold; width: 200px; color: #16213e; }
        .detail-value { flex: 1; color: #333; word-break: break-all; }
        .buttons { display: flex; gap: 1rem; margin-top: 1.5rem; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 1rem; }
        .btn-primary { background: #16213e; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .event-layout { display: flex; flex-direction: row; gap: 2rem; align-items: flex-start; }
        .event-info { width: 30%; flex-shrink: 0; display: flex; flex-direction: column; gap: 1rem; }
        .event-calendar-widget { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); border-radius: 10px; padding: 1rem; color: white; text-align: center; box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3); }
        .calendar-month { font-size: 0.85rem; font-weight: 600; text-transform: uppercase; opacity: 0.9; margin-bottom: 0.3rem; }
        .calendar-day { font-size: 2.5rem; font-weight: 700; line-height: 1; margin-bottom: 0.3rem; }
        .calendar-weekday { font-size: 0.9rem; font-weight: 500; opacity: 0.95; }
        .event-info-card { background: white; border-radius: 10px; padding: 1rem; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); }
        .event-info-card .event-detail-row { padding: 0.8rem 0; border-bottom: 1px solid #f0f0f0; }
        .event-info-card .event-detail-row:last-child { border-bottom: none; }
        .event-detail-label { font-weight: 600; color: #16213e; margin-bottom: 0.25rem; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.7; }
        .event-detail-value { color: #333; word-break: break-word; font-size: 0.95rem; }
        .event-image-container { width: 70%; display: flex; justify-content: center; align-items: center; }
        .event-image-container img { max-width: 100%; height: auto; border-radius: 12px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15); }
        @media (max-width: 1024px) {
            .event-layout { flex-direction: column; }
            .event-info { width: 100%; }
            .event-image-container { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Ver <?= $tableInfo['title'] ?></h1>
        <a href="tabla.php?endpoint=<?= $endpoint ?>">Volver</a>
    </div>

    <div class="container">
        <div class="card">
            <h2>Detalle del Registro</h2>

            <?php if ($endpoint === 'eventos'): ?>
                <div class="event-layout">
                    <div class="event-info">
                        <?php
                            $dateStr = $row['date_event'] ?? '';
                            $monthName = formatMonthAbbrSpanish($dateStr);
                            $dayNum = $dateStr ? date('d', strtotime($dateStr)) : '';
                            $dayName = formatDayNameSpanish($dateStr);
                        ?>
                        <div class="event-calendar-widget">
                            <div class="calendar-month"><?= strtoupper($monthName) ?></div>
                            <div class="calendar-day"><?= $dayNum ?></div>
                            <div class="calendar-weekday"><?= ucfirst($dayName) ?></div>
                        </div>
                        <div class="event-info-card">
                            <?php foreach ($row as $key => $value):
                                if ($key === 'image_url' || $key === 'id' || $key === 'date_event') continue;
                            ?>
                                <div class="event-detail-row">
                                    <div class="event-detail-label"><?= ucfirst(str_replace('_', ' ', $key)) ?></div>
                                    <div class="event-detail-value"><?= nl2br(htmlspecialchars($value ?? '')) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="event-image-container">
                        <?php if (!empty($row['image_url'])): ?>
                            <img src="<?= htmlspecialchars($row['image_url']) ?>" alt="Imagen del evento">
                        <?php else: ?>
                            <div style="width: 100%; height: 400px; background: linear-gradient(135deg, #f0f0f0 0%, #e8e8e8 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #999;">
                                <span>Sin imagen</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($row as $key => $value):
                    $displayValue = $value;
                    if (in_array($key, ['date', 'date_event', 'date_pray'])) {
                        $displayValue = formatDateSpanish($value);
                    }
                ?>
                    <div class="detail-row">
                        <div class="detail-label"><?= ucfirst(str_replace('_', ' ', $key)) ?></div>
                        <div class="detail-value"><?= nl2br(htmlspecialchars($displayValue ?? '')) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="buttons">
                <?php if ($canEdit): ?>
                    <a href="form.php?endpoint=<?= $endpoint ?>&action=edit&id=<?= $id ?>" class="btn btn-primary">Editar</a>
                <?php endif; ?>
                <a href="tabla.php?endpoint=<?= $endpoint ?>" class="btn btn-secondary">Volver</a>
            </div>
        </div>
    </div>
</body>
</html>
