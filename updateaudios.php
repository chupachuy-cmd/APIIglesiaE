<?php
require_once 'db.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$sql = "UPDATE predicas SET url = REPLACE(url, 'predicas/', 'audios/') WHERE url LIKE '%predicas/%'";

if ($conn->query($sql)) {
    $affected_rows = $conn->affected_rows;
    echo "Actualización exitosa!<br>";
    echo "Filas actualizadas: " . $affected_rows . "<br>";
} else {
    echo "Error al actualizar: " . $conn->error;
}
