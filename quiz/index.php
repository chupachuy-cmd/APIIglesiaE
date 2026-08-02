<?php
require_once 'init.php';
include('header.php');

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$countStmt = $conn->query("SELECT COUNT(*) as total FROM quizzes");
$totalQuizzes = $countStmt->fetch_assoc()['total'];
$totalPages = max(1, ceil($totalQuizzes / $perPage));

$stmt = $conn->prepare("SELECT * FROM quizzes ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $perPage, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container mt-4">
    <h1 class="text-center">Lista de Quizzes</h1>
    <table class="table table-striped">
        <thead>
        <tr>
            <th scope="col">ID</th>
            <th scope="col">Título</th>
            <th scope="col">Semana</th>
            <th scope="col">Estado</th>
            <th scope="col">Creado</th>
            <th scope="col">Acciones</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($quiz = $result->fetch_assoc()): ?>
            <tr>
                <th><?= $quiz['id'] ?></th>
                <td><?= htmlspecialchars($quiz['title']) ?></td>
                <td><?= htmlspecialchars($quiz['week_start']) ?> al <?= htmlspecialchars($quiz['week_end']) ?></td>
                <td>
                    <?php if ($quiz['is_active']): ?>
                        <span class="badge bg-success">Activo</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Inactivo</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($quiz['created_at']) ?></td>
                <td>
                    <a class="btn btn-info btn-sm" href="editquiz.php?id=<?= $quiz['id'] ?>">Editar</a>
                    <a class="btn btn-primary btn-sm" href="preview.php?quiz_id=<?= $quiz['id'] ?>">Probar</a>
                    <a class="btn btn-primary btn-sm" href="dashboard.php?quiz_id=<?= $quiz['id'] ?>">Estadísticas</a>
                    <form method="POST" action="deletequiz.php" style="display:inline;" onsubmit="return confirm('¿Estás seguro que deseas eliminar este quiz? Se borrarán todas sus preguntas y respuestas.');">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $quiz['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <nav aria-label="Paginación de quizzes">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>">Anterior</a>
            </li>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>">Siguiente</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php include('footer.php'); ?>
