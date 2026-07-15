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
$result = $conn->query("SELECT * FROM eventos ORDER BY id DESC");
?>

<script>
    function confirmarDelete(){
        return confirm('¿Estás seguro que desea eliminar este evento?');
    }
</script>

<div class="container mt-4">
    <h1 class="text-center">Lista de Eventos</h1>
    <table class="table table-striped">
        <thead>
        <tr>
            <th scope="col">ID</th>
            <th scope="col">Invitación</th>
            <th scope="col">Título</th>
            <th scope="col">Fecha</th>
            <th scope="col">Lugar</th>
            <th scope="col">Hora</th>
            <th scope="col">Imagen</th>
            <th scope="col">Acciones</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($mostrar = $result->fetch_assoc()): ?>
            <tr>
                <th><?= $mostrar['id'] ?></th>
                <td><?= htmlspecialchars($mostrar['invitation']) ?></td>
                <td><?= htmlspecialchars($mostrar['title']) ?></td>
                <td><?= htmlspecialchars($mostrar['date_event']) ?></td>
                <td><?= htmlspecialchars($mostrar['place']) ?></td>
                <td><?= htmlspecialchars($mostrar['hour_event']) ?></td>
                <td>
                    <img class="img-table" src="<?= htmlspecialchars($mostrar['image_url']) ?>" alt="" style="max-width:100px;">
                </td>
                <td>
                    <a class="btn btn-info" href="editevent.php?id=<?= $mostrar['id'] ?>">Editar</a> |
                    <a class="btn btn-danger" href="deleteevent.php?id=<?= $mostrar['id'] ?>" onClick="return confirmarDelete()">Eliminar</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include('footer.php'); ?>
