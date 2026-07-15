<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

configureSession();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkLoginRateLimit();
    validateCsrfToken();

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Por favor ingresa email y contraseña';
    } else {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("SELECT id, email, password, nombre, rol FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_nombre'] = $user['nombre'];
                $_SESSION['user_rol'] = $user['rol'];

                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Contraseña incorrecta';
            }
        } else {
            $error = 'Usuario no encontrado';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Iglesia Eliasista</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-container { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); width: 100%; max-width: 400px; }
        .logo { text-align: center; margin-bottom: 1.5rem; }
        .logo h1 { color: #1a1a2e; font-size: 1.5rem; }
        .logo p { color: #666; font-size: 0.9rem; margin-top: 0.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #333; font-weight: bold; }
        .form-group input { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; }
        .form-group input:focus { outline: none; border-color: #16213e; }
        .btn { width: 100%; padding: 0.75rem; background: #16213e; color: white; border: none; border-radius: 5px; font-size: 1rem; cursor: pointer; transition: background 0.3s; }
        .btn:hover { background: #1a1a2e; }
        .error { background: #fee; color: #c00; padding: 0.75rem; border-radius: 5px; margin-bottom: 1rem; text-align: center; }
        .footer { text-align: center; margin-top: 1rem; color: #666; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>Iglesia Eliasista</h1>
            <p>API App - Acceso</p>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= csrfField() ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Iniciar Sesión</button>
        </form>
        <div class="footer">
            &copy; 2026 Iglesia Eliasista
        </div>
    </div>
</body>
</html>
