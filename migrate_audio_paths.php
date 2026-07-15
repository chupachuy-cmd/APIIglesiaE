<?php

/**
 * Script de migración: Actualiza las rutas de audio de 'audioscoros/' a 'audios/'
 * Ejecutar desde el navegador: http://localhost/api/migrate_audio_paths.php
 */

require_once 'db.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Tablas que pueden contener referencias a audios
$audioTables = ['coros', 'devocionarios', 'dulia', 'hiperdulia', 'latria'];

$totalUpdated = 0;

echo "<h1>Migración de Rutas de Audio</h1>";
echo "<hr>";

foreach ($audioTables as $table) {
    // Encontrar columnas que contienen URLs
    $result = $conn->query("DESCRIBE $table");

    while ($row = $result->fetch_assoc()) {
        if (strpos($row['Field'], 'url') !== false) {
            $column = $row['Field'];

            // Actualizar referencias de 'audioscoros/' a 'audios/'
            $sql = "UPDATE $table SET $column = REPLACE($column, 'audioscoros/', 'audios/') WHERE $column LIKE '%audioscoros/%'";

            if ($conn->query($sql)) {
                $affected = $conn->affected_rows;
                if ($affected > 0) {
                    echo "<p><strong>✓ Tabla '$table', columna '$column':</strong> $affected registros actualizados</p>";
                    $totalUpdated += $affected;
                }
            } else {
                echo "<p><strong>✗ Error en tabla '$table':</strong> " . $conn->error . "</p>";
            }
        }
    }
}

echo "<hr>";
echo "<h3>Total de registros actualizados: <span style='color:green;'>" . $totalUpdated . "</span></h3>";

if ($totalUpdated === 0) {
    echo "<p style='color:blue;'>No se encontraron registros con rutas antiguas. ✓</p>";
}

echo "<p><a href='tabla.php?endpoint=coros'>Ir a Coros</a> | <a href='dashboard.php'>Ir al Dashboard</a></p>";
