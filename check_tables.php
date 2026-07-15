<?php
require_once 'db.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$tables = ['coros', 'devocionarios', 'dulia', 'gacetas', 'predicas', 'eventos', 'oraciones', 'hiperdulia', 'latria'];

foreach ($tables as $table) {
    echo "=== $table ===\n";
    $result = $conn->query("DESCRIBE $table");
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    echo "\n";
}
