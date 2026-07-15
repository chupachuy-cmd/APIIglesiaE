<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

configureSession();
requireRole('admin');

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

$db = Database::getInstance();
$conn = $db->getConnection();

if ($action === 'update_rol' && $id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();

    $newRol = trim($_POST['rol'] ?? 'user');
    if (in_array($newRol, ['admin', 'editor', 'user'], true)) {
        $stmt = $conn->prepare("UPDATE usuarios SET rol = ? WHERE id = ?");
        $stmt->bind_param("si", $newRol, $id);
        $stmt->execute();
    }
    header('Location: usuarios.php');
    exit;
}

if ($action === 'delete' && $id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();

    if ($id !== (int)$_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
    header('Location: usuarios.php');
    exit;
}

$result = $conn->query("SELECT id, email, nombre, rol, created_at FROM usuarios ORDER BY id");
$usuarios = $result->fetch_all(MYSQLI_ASSOC);

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Iglesia Eliasista</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f5f5f5; }
        .header { background: #16213e; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.2rem; }
        .header a { color: white; text-decoration: none; padding: 0.5rem 1rem; background: #e74c3c; border-radius: 5px; }
        .container { padding: 2rem; max-width: 1200px; margin: 0 auto; }
        h2 { color: #16213e; margin-bottom: 1rem; }
        table { width: 100%; background: white; border-collapse: collapse; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        th { background: #16213e; color: white; padding: 1rem; text-align: left; }
        td { padding: 0.75rem 1rem; border-bottom: 1px solid #eee; }
        tr:hover { background: #f8f9fa; }
        .rol-select { padding: 0.3rem; border-radius: 5px; border: 1px solid #ddd; }
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 5px; cursor: pointer; font-size: 0.9rem; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-sm { padding: 0.3rem 0.6rem; font-size: 0.8rem; }
        .badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 15px; font-size: 0.75rem; }
        .badge-admin { background: #e74c3c; color: white; }
        .badge-editor { background: #f39c12; color: white; }
        .badge-user { background: #3498db; color: white; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Gestión de Usuarios - Iglesia Eliasista</h1>
        <a href="dashboard.php">← Volver</a>
    </div>

    <div class="container">
        <h2>Usuarios</h2>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['nombre'] ?? '') ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <span class="badge badge-<?= $u['rol'] ?>"><?= $u['rol'] ?></span>
                    </td>
                    <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                        <form method="POST" action="usuarios.php?action=update_rol&id=<?= $u['id'] ?>" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <select name="rol" class="rol-select" onchange="this.form.submit()">
                                <option value="user" <?= $u['rol'] === 'user' ? 'selected' : '' ?>>User</option>
                                <option value="editor" <?= $u['rol'] === 'editor' ? 'selected' : '' ?>>Editor</option>
                                <option value="admin" <?= $u['rol'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </form>
                        <form method="POST" action="usuarios.php?action=delete&id=<?= $u['id'] ?>" style="display:inline;" onsubmit="return confirm('¿Eliminar usuario?')">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                        <?php else: ?>
                        <span style="color:#999;">(tú)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
