<?php
session_start();
require_once 'helpers.php';

configureSession();
requireAuth();

$nombre = $_SESSION['user_nombre'];
$email = $_SESSION['user_email'];
$rol = $_SESSION['user_rol'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Iglesia Eliasista</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f5f5f5;
        }

        .header {
            background: #16213e;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 1.2rem;
        }

        .header a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            background: #e74c3c;
            border-radius: 5px;
        }

        .container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .welcome {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .welcome h2 {
            color: #16213e;
            margin-bottom: 0.5rem;
        }

        .welcome p {
            color: #666;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card h3 {
            color: #16213e;
            margin-bottom: 0.5rem;
        }

        .card p {
            color: #666;
            font-size: 0.9rem;
        }

        .card a {
            display: inline-block;
            margin-top: 1rem;
            color: #16213e;
            text-decoration: none;
            font-weight: bold;
        }

        .card .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: #27ae60;
            color: white;
            border-radius: 15px;
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Iglesia Eliasista - API</h1>
        <div>
            <span style="margin-right: 1rem;"><?= htmlspecialchars($nombre) ?> (<?= htmlspecialchars($rol) ?>)</span>
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </div>

    <div class="container">
        <div class="welcome">
            <h2>Bienvenido, <?= htmlspecialchars($nombre) ?>!</h2>
            <p>Accede a los módulos de la aplicación</p>
        </div>

        <?php if ($rol === 'admin'): ?>
            <div class="grid">
                <div class="card">
                    <h3>🎵 Coros</h3>
                    <p>Administra los coros y cantos litúrgicos</p>
                    <a href="tabla.php?endpoint=coros">Ver Coros →</a>
                </div>
                <div class="card">
                    <h3>📖 Devocionarios</h3>
                    <p>Gestiona los devocionarios diarios</p>
                    <a href="tabla.php?endpoint=devocionarios">Ver Devocionarios →</a>
                </div>
                <div class="card">
                    <h3>🕎 Dulia</h3>
                    <p>Contenido sobre dulia</p>
                    <a href="tabla.php?endpoint=dulia">Ver Dulia →</a>
                </div>
                <div class="card">
                    <h3>📰 Gacetas</h3>
                    <p>Publicaciones y gacetas</p>
                    <a href="tabla.php?endpoint=gacetas">Ver Gacetas →</a>
                </div>
                <div class="card">
                    <h3>🎤 Prédicas</h3>
                    <p>Registro de prédicas</p>
                    <a href="tabla.php?endpoint=predicas">Ver Prédicas →</a>
                </div>
                <div class="card">
                    <h3>📅 Eventos</h3>
                    <p>Calendario de eventos</p>
                    <a href="tabla.php?endpoint=eventos">Ver Eventos →</a>
                </div>
                <div class="card">
                    <h3>🙏 Oraciones</h3>
                    <p>Gestión de oraciones</p>
                    <a href="tabla.php?endpoint=oraciones">Ver Oraciones →</a>
                </div>
                <div class="card">
                    <h3>🕯️ Hiperdulia</h3>
                    <p>Contenido sobre hiperdulia</p>
                    <a href="tabla.php?endpoint=hiperdulia">Ver Hiperdulia →</a>
                </div>
                <div class="card">
                    <h3>⭐ Latria</h3>
                    <p>Contenido sobre latria</p>
<a href="tabla.php?endpoint=latria">Ver Latria →</a>
                </div>
                <div class="card">
                    <h3>❓ Quizzes</h3>
                    <p>Cuestionarios semanales para la comunidad</p>
                    <a href="quiz/index.php">Ver Quizzes →</a>
                </div>
                <div class="card">
                    <h3>⚙️ Administración</h3>
                    <p>Panel de administración</p>
                    <a href="usuarios.php">Gestionar Usuarios →</a>
                    <span class="badge">Admin</span>
                </div>
            </div>
        <?php elseif ($rol === 'editor'): ?>
            <div class="grid">
                <div class="card">
                    <h3>🙏 Oraciones</h3>
                    <p>Gestión de oraciones</p>
                    <a href="tabla.php?endpoint=oraciones">Ver Oraciones →</a>
                </div>
                <div class="card">
                    <h3>📅 Eventos</h3>
                    <p>Calendario de eventos</p>
                    <a href="tabla.php?endpoint=eventos">Ver Eventos →</a>
                </div>
            </div>
        <?php else: ?>
            <div class="grid">
                <div class="card">
                    <h3>🙏 Oraciones</h3>
                    <p>Gestión de oraciones</p>
                    <a href="tabla.php?endpoint=oraciones">Ver Oraciones →</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>