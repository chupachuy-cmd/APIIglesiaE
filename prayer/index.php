<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

include('header.php');

$db = Database::getInstance();
$conn = $db->getConnection();
$result = $conn->query("SELECT * FROM oraciones ORDER BY id DESC");
?>

<script>
    function confirmarDelete(){
        return confirm('¿Está seguro que desea eliminar esta oración?');
    }
</script>

<div class="container mt-4">
    <h1 class="text-center">Lista de Oraciones</h1>
    <table class="table table-striped">
        <thead>
        <tr>
            <th scope="col">ID</th>
            <th scope="col">Título</th>
            <th scope="col">Descripción</th>
            <th scope="col">Asunto</th>
            <th scope="col">De quien</th>
            <th scope="col">Para quien</th>
            <th scope="col">Fecha</th>
            <th scope="col">Oración</th>
            <th scope="col">Acciones</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($mostrar = $result->fetch_assoc()): ?>
            <tr>
                <th><?= $mostrar['id'] ?></th>
                <td><?= htmlspecialchars($mostrar['title_pray']) ?></td>
                <td><?= htmlspecialchars($mostrar['description_pray']) ?></td>
                <td><?= htmlspecialchars($mostrar['subject_pray']) ?></td>
                <td><?= htmlspecialchars($mostrar['pray_for']) ?></td>
                <td><?= htmlspecialchars($mostrar['pray_to']) ?></td>
                <td><?= htmlspecialchars($mostrar['date_pray']) ?></td>
                <td><?= htmlspecialchars($mostrar['lyrics_pray']) ?></td>
                <td>
                    <a class="btn btn-info" href="editprayer.php?id=<?= $mostrar['id'] ?>">Editar</a> |
                    <a class="btn btn-danger" href="deleteprayer.php?id=<?= $mostrar['id'] ?>" onClick="return confirmarDelete()">Eliminar</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include('footer.php'); ?>
