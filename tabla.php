<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

configureSession();
requireAuth();

$endpoint = strtolower($_GET['endpoint'] ?? 'coros');
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

$rol = $_SESSION['user_rol'] ?? 'user';

$canEdit = in_array($rol, ['admin', 'editor']);
$canDelete = in_array($rol, ['admin', 'editor']);
$tablesCanEditByRole = [
    'admin' => ['coros', 'devocionarios', 'dulia', 'gacetas', 'predicas', 'eventos', 'oraciones', 'hiperdulia', 'latria'],
    'editor' => ['oraciones', 'eventos']
];
$canEditThisTable = in_array($endpoint, $tablesCanEditByRole[$rol] ?? []);
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;

$db = Database::getInstance();
$conn = $db->getConnection();

$tables = [
    'coros' => ['title' => 'Coros', 'columns' => ['id', 'title', 'lyrics', 'url'], 'searchable' => ['title', 'lyrics', 'url'], 'headers' => ['id' => 'ID', 'title' => 'Título', 'lyrics' => 'Letra', 'url' => 'Audio']],
    'devocionarios' => ['title' => 'Devocionarios', 'columns' => ['id', 'title', 'description', 'url'], 'searchable' => ['title', 'description', 'url'], 'headers' => ['id' => 'ID', 'title' => 'Título', 'description' => 'Descripción', 'url' => 'Enlace']],
    'dulia' => ['title' => 'Dulia', 'columns' => ['id', 'title', 'descripcion', 'url'], 'searchable' => ['title', 'descripcion', 'url'], 'headers' => ['id' => 'ID', 'title' => 'Título', 'descripcion' => 'Descripción', 'url' => 'Enlace']],
    'gacetas' => ['title' => 'Gacetas', 'columns' => ['id', 'name', 'date', 'url'], 'searchable' => ['name', 'date', 'url'], 'headers' => ['id' => 'ID', 'name' => 'Nombre', 'date' => 'Fecha', 'url' => 'Archivo']],
    'predicas' => ['title' => 'Prédicas', 'columns' => ['id', 'title', 'description', 'url'], 'searchable' => ['title', 'description', 'url'], 'headers' => ['id' => 'ID', 'title' => 'Título', 'description' => 'Descripción', 'url' => 'Video']],
    'eventos' => ['title' => 'Eventos', 'columns' => ['id', 'invitation', 'title', 'date_event', 'hour_event', 'place', 'image_url'], 'searchable' => ['title', 'invitation', 'place'], 'headers' => ['id' => 'ID', 'invitation' => 'Invitación', 'title' => 'Título', 'date_event' => 'Fecha', 'hour_event' => 'Hora', 'place' => 'Lugar', 'image_url' => 'Imagen']],
    'oraciones' => ['title' => 'Oraciones', 'columns' => ['id', 'title_pray', 'description_pray', 'subject_pray', 'pray_for', 'pray_to', 'date_pray', 'lyrics_pray'], 'searchable' => ['title_pray', 'description_pray', 'subject_pray'], 'headers' => ['id' => 'ID', 'title_pray' => 'Título', 'description_pray' => 'Descripción', 'subject_pray' => 'Tema', 'pray_for' => 'Por', 'pray_to' => 'A', 'date_pray' => 'Fecha', 'lyrics_pray' => 'Letra']],
    'hiperdulia' => ['title' => 'Hiperdulia', 'columns' => ['id', 'title', 'description', 'url'], 'searchable' => ['title', 'description', 'url'], 'headers' => ['id' => 'ID', 'title' => 'Título', 'description' => 'Descripción', 'url' => 'Audio']],
    'latria' => ['title' => 'Latria', 'columns' => ['id', 'title', 'description', 'url'], 'searchable' => ['title', 'description', 'url'], 'headers' => ['id' => 'ID', 'title' => 'Título', 'description' => 'Descripción', 'url' => 'Audio']]
];

if (!isset($tables[$endpoint])) {
    die("Endpoint no válido");
}

$tableInfo = $tables[$endpoint];
$tableName = $endpoint;

if ($action === 'delete' && $id) {
    if (!$canEditThisTable) {
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM $tableName WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
}

$isAjax = isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

if ($action === 'list') {
    $where = '';
    $params = [];
    if ($search && !empty($tableInfo['searchable'])) {
        $conditions = [];
        foreach ($tableInfo['searchable'] as $col) {
            $conditions[] = "$col LIKE ?";
            $params[] = "%$search%";
        }
        $where = 'WHERE ' . implode(' OR ', $conditions);
    }

    $countSql = "SELECT COUNT(*) as total FROM $tableName $where";
    $stmtCount = $conn->prepare($countSql);
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $totalRecords = $stmtCount->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $perPage);

    $offset = ($page - 1) * $perPage;
    $sql = "SELECT * FROM $tableName $where ORDER BY id DESC LIMIT $perPage OFFSET $offset";

    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }
    $rows = $result->fetch_all(MYSQLI_ASSOC);

    if ($isAjax) {
        echo json_encode([
            'success' => true,
            'rows' => $rows,
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages,
            'page' => $page,
            'columns' => $tableInfo['columns'],
            'headers' => $tableInfo['headers'] ?? [],
            'searchable' => $tableInfo['searchable'],
            'title' => $tableInfo['title'],
            'endpoint' => $endpoint,
            'canEdit' => $canEdit && $canEditThisTable,
            'canDelete' => $canDelete && $canEditThisTable
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $tableInfo['title'] ?> - Iglesia Eliasista</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f5f5f5; }
        .header { background: #16213e; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.2rem; }
        .header a { color: white; text-decoration: none; padding: 0.5rem 1rem; background: #e74c3c; border-radius: 5px; }
        .container { padding: 2rem; max-width: 1400px; margin: 0 auto; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; gap: 1rem; flex-wrap: wrap; }
        .toolbar h2 { color: #16213e; }
        .search-box { display: flex; gap: 0.5rem; }
        .search-box input { padding: 0.5rem 1rem; border: 1px solid #ddd; border-radius: 5px; font-size: 0.9rem; min-width: 250px; }
        .search-box button { padding: 0.5rem 1rem; background: #16213e; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 0.9rem; display: inline-block; }
        .btn-primary { background: #17a2b8; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-sm { padding: 0.3rem 0.6rem; font-size: 0.8rem; }
        table { width: 100%; background: white; border-collapse: collapse; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        th { background: #16213e; color: white; padding: 1rem; text-align: left; }
        td { padding: 0.75rem 1rem; border-bottom: 1px solid #eee; }
        tr:hover { background: #f8f9fa; }
        .actions { display: flex; gap: 0.5rem; }
        .card { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .empty { text-align: center; padding: 3rem; color: #666; }
        .pagination { display: flex; justify-content: center; align-items: center; gap: 0.5rem; margin-top: 1.5rem; }
        .pagination a, .pagination span { padding: 0.5rem 0.75rem; background: white; border: 1px solid #ddd; border-radius: 5px; text-decoration: none; color: #16213e; cursor: pointer; }
        .pagination a:hover { background: #16213e; color: white; }
        .pagination .active { background: #16213e; color: white; }
        .pagination .disabled { color: #ccc; pointer-events: none; }
        .results-info { color: #666; font-size: 0.9rem; margin-bottom: 1rem; }
        .loading { text-align: center; padding: 2rem; color: #666; }
        .loading::after { content: '...'; animation: dots 1s infinite; }
        @keyframes dots { 0%, 20% { content: '.'; } 40% { content: '..'; } 60%, 100% { content: '...'; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>Iglesia Eliasista - <span id="tableTitle"><?= $tableInfo['title'] ?></span></h1>
        <a href="dashboard.php">Volver</a>
    </div>

    <div class="container">
        <div class="toolbar">
            <h2><?= $tableInfo['title'] ?></h2>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Buscar..." value="<?= htmlspecialchars($search) ?>">
                <button onclick="loadData(1)" class="btn btn-primary">Buscar</button>
                <button onclick="clearSearch()" class="btn btn-secondary">Limpiar</button>
            </div>
            <?php if ($canEditThisTable): ?>
                <button onclick="showForm()" class="btn btn-primary">+ Nuevo</button>
            <?php endif; ?>
        </div>

        <div id="resultsInfo" class="results-info"></div>
        <div id="tableContainer"></div>
        <div id="pagination" class="pagination"></div>
    </div>

    <script>
        let currentEndpoint = '<?= $endpoint ?>';
        let canEdit = <?= json_encode($canEdit && $canEditThisTable) ?>;
        let canDelete = <?= json_encode($canDelete && $canEditThisTable) ?>;
        let currentSearch = '<?= $search ?>';
        let currentPage = 1;
        let totalPages = 1;

        function loadData(page = 1) {
            currentPage = page;
            const search = document.getElementById('searchInput').value;
            currentSearch = search;

            document.getElementById('tableContainer').innerHTML = '<div class="loading">Cargando</div>';

            let url = `tabla.php?endpoint=${currentEndpoint}&action=list&ajax=1&page=${page}`;
            if (search.trim()) {
                url += `&search=${encodeURIComponent(search)}`;
            }

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Error en la respuesta');
                    return response.json();
                })
                .then(data => {
                    renderTable(data);
                    renderPagination(data);
                    document.getElementById('resultsInfo').textContent = `Mostrando ${data.rows.length} de ${data.totalRecords} registros`;
                    if (search) {
                        document.getElementById('resultsInfo').textContent += ` (filtrado: "${search}")`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('tableContainer').innerHTML = '<div class="card"><div class="empty">Error al cargar datos</div></div>';
                });
        }

        function renderTable(data) {
            if (data.rows.length === 0) {
                document.getElementById('tableContainer').innerHTML = '<div class="card"><div class="empty">No hay registros</div></div>';
                return;
            }

            const dateFields = ['date', 'date_event', 'date_pray'];
            const months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

            function formatDateSpanish(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                if (isNaN(d.getTime())) return dateStr;
                return d.getDate() + ' de ' + months[d.getMonth()] + ' de ' + d.getFullYear();
            }

            let html = '<table><thead><tr>';
            data.columns.forEach(col => {
                const header = data.headers && data.headers[col] ? data.headers[col] : col.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                html += `<th>${header}</th>`;
            });
            html += '<th>Acciones</th></tr></thead><tbody>';

            data.rows.forEach(row => {
                html += '<tr>';
                data.columns.forEach(col => {
                    let value = row[col] || '';
                    let displayValue;

                    if (dateFields.includes(col) && value) {
                        displayValue = formatDateSpanish(value);
                    } else {
                        displayValue = escapeHtml(String(value).substring(0, 50));
                    }

                    const colLower = col.toLowerCase();

                    if ((colLower.includes('url') || colLower.includes('image')) && value) {
                        const isPdf = value.toLowerCase().endsWith('.pdf');
                        const isAudio = value.toLowerCase().endsWith('.mp3') || value.toLowerCase().endsWith('.wav') || value.toLowerCase().endsWith('.m4a');
                        let icon = 'fa-external-link-alt';
                        let label = '';
                        let color = '#3498db';

                        if (isPdf) {
                            icon = 'fa-file-pdf';
                            color = '#e74c3c';
                            label = 'Ver PDF';
                        } else if (isAudio || data.endpoint === 'coros') {
                            icon = 'fa-music';
                            color = '#9b59b6';
                            label = 'Reproducir';
                        } else if (colLower.includes('image')) {
                            icon = 'fa-image';
                        }

                        displayValue = `<a href="${value}" target="_blank" style="color:${color};text-decoration:none;"><i class="fas ${icon}"></i> ${label}</a>`;
                    }

                    html += `<td>${displayValue}</td>`;
                });
                html += `<td>
                    <div class="actions">
                        <button onclick="viewItem(${row.id})" class="btn btn-info btn-sm">Ver</button>
                        ${canEdit ? `<button onclick="editItem(${row.id})" class="btn btn-success btn-sm">Editar</button>` : ''}
                        ${canDelete ? `<button onclick="deleteItem(${row.id})" class="btn btn-danger btn-sm">Eliminar</button>` : ''}
                    </div>
                </td></tr>`;
            });
            html += '</tbody></table>';

            document.getElementById('tableContainer').innerHTML = html;
        }

        function renderPagination(data) {
            totalPages = data.totalPages;
            currentPage = data.page;

            if (totalPages <= 1) {
                document.getElementById('pagination').innerHTML = '';
                return;
            }

            let html = '';
            html += currentPage > 1
                ? `<a onclick="loadData(${currentPage - 1})">← Anterior</a>`
                : `<span class="disabled">← Anterior</span>`;

            for (let i = 1; i <= totalPages; i++) {
                if (i === currentPage) {
                    html += `<span class="active">${i}</span>`;
                } else if (i <= 3 || i > totalPages - 3 || Math.abs(i - currentPage) < 2) {
                    html += `<a onclick="loadData(${i})">${i}</a>`;
                } else if (i === 4 || i === totalPages - 3) {
                    html += `<span>...</span>`;
                }
            }

            html += currentPage < totalPages
                ? `<a onclick="loadData(${currentPage + 1})">Siguiente →</a>`
                : `<span class="disabled">Siguiente →</span>`;

            document.getElementById('pagination').innerHTML = html;
        }

        function viewItem(id) {
            window.location.href = `view.php?endpoint=${currentEndpoint}&id=${id}`;
        }

        function editItem(id) {
            window.location.href = `form.php?endpoint=${currentEndpoint}&action=edit&id=${id}`;
        }

        function deleteItem(id) {
            if (confirm('¿Eliminar este registro?')) {
                fetch(`tabla.php?endpoint=${currentEndpoint}&action=delete&ajax=1&id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadData(currentPage);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al eliminar');
                    });
            }
        }

        function showForm() {
            window.location.href = `form.php?endpoint=${currentEndpoint}&action=new`;
        }

        function clearSearch() {
            document.getElementById('searchInput').value = '';
            loadData(1);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') loadData(1);
        });

        loadData();
    </script>
</body>
</html>
