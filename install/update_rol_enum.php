<?php
require_once 'db.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "<h3>Actualizando ENUM de rol...</h3>";

$sql = "ALTER TABLE usuarios MODIFY rol ENUM('admin','editor','user') DEFAULT 'user'";

if ($conn->query($sql)) {
    echo "<p style='color:green;'>✓ ENUM actualizado correctamente a: admin, editor, user</p>";
} else {
    echo "<p style='color:red;'>✗ Error: " . $conn->error . "</p>";
}

$stmt = $conn->query("DESCRIBE usuarios");
echo "<h4>Estructura actual:</h4><ul>";
while ($row = $stmt->fetch_assoc()) {
    if ($row['Field'] === 'rol') {
        echo "<li><strong>rol</strong>: " . $row['Type'] . "</li>";
    }
}
echo "</ul>";

echo "<p><a href='dashboard.php'>← Volver al Dashboard</a></p>";