<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

$installKey = $_GET['key'] ?? '';
$validKey = getenv('INSTALL_KEY') ?: 'setup-iglesia-2024';

if (!hash_equals($validKey, $installKey)) {
    http_response_code(403);
    die('Acceso denegado. Use ?key=SETUP_KEY');
}

$db = Database::getInstance();
$conn = $db->getConnection();

$sql = "CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(100),
    rol ENUM('admin','editor','user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo "OK: Tabla 'usuarios' verificada.<br>";
} else {
    echo "ERROR: " . $conn->error . "<br>";
}

$email = "jesuslv2412@hotmail.com";
$password = password_hash("123", PASSWORD_DEFAULT);
$nombre = "Jesus";

$stmt = $conn->prepare("INSERT IGNORE INTO usuarios (email, password, nombre, rol) VALUES (?, ?, ?, 'admin')");
$stmt->bind_param("sss", $email, $password, $nombre);
$stmt->execute();

$email2 = "fa_arenita@hotmail.com";
$password2 = password_hash("aremintaIglesiaeditor", PASSWORD_DEFAULT);
$nombre2 = "Arenita";

$stmt2 = $conn->prepare("INSERT IGNORE INTO usuarios (email, password, nombre) VALUES (?, ?, ?)");
$stmt2->bind_param("sss", $email2, $password2, $nombre2);
$stmt2->execute();

echo "OK: Usuarios verificados.<br>";
echo "NOTA: Cambie las contraseñas por defecto inmediatamente después del primer login.";
