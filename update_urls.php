<?php
require_once 'db.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$tables = ['coros', 'devocionarios', 'dulia', 'gacetas', 'predicas', 'hiperdulia', 'latria'];

$oldDomain = 'https://iglesiaeliasista.org.mx/';
$count = 0;

foreach ($tables as $table) {
    $result = $conn->query("DESCRIBE $table");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        if (strpos($row['Field'], 'url') !== false || strpos($row['Field'], 'image') !== false) {
            $columns[] = $row['Field'];
        }
    }
    
    foreach ($columns as $col) {
        $sql = "UPDATE $table SET $col = REPLACE($col, '$oldDomain', '') WHERE $col LIKE '$oldDomain%'";
        if ($conn->query($sql)) {
            $affected = $conn->affected_rows;
            if ($affected > 0) {
                echo "Tabla '$table', columna '$col': $affected registros actualizados<br>";
                $count += $affected;
            }
        }
    }
}

$eventResult = $conn->query("DESCRIBE eventos");
while ($row = $eventResult->fetch_assoc()) {
    if (strpos($row['Field'], 'image_url') !== false) {
        $sql = "UPDATE eventos SET image_url = REPLACE(image_url, '$oldDomain', '') WHERE image_url LIKE '$oldDomain%'";
        if ($conn->query($sql)) {
            $affected = $conn->affected_rows;
            if ($affected > 0) {
                echo "Tabla 'eventos', columna 'image_url': $affected registros actualizados<br>";
                $count += $affected;
            }
        }
    }
}

echo "<br>Total: $count registros actualizados";
