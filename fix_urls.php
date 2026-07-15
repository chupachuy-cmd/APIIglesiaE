<?php
require_once 'db.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$tables = ['coros', 'devocionarios', 'dulia', 'gacetas', 'predicas', 'hiperdulia', 'latria'];

foreach ($tables as $table) {
    $result = $conn->query("DESCRIBE $table");
    while ($row = $result->fetch_assoc()) {
        if (strpos($row['Field'], 'url') !== false || strpos($row['Field'], 'image') !== false) {
            $col = $row['Field'];
            $conn->query("UPDATE $table SET $col = REPLACE($col, 'api/', '') WHERE $col LIKE 'api/%'");
            $affected = $conn->affected_rows;
            if ($affected > 0) {
                echo "Tabla '$table', columna '$col': $affected registros actualizados<br>";
            }
        }
    }
}

$conn->query("UPDATE eventos SET image_url = REPLACE(image_url, 'api/', '') WHERE image_url LIKE 'api/%'");
echo "Listo";
