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

if (isset($_POST['enviar'])) {
    $id = intval($_POST['id'] ?? 0);
    $invitation = $_POST['invitation'] ?? '';
    $title = $_POST['title'] ?? '';
    $date_event = $_POST['date_event'] ?? '';
    $place = $_POST['place'] ?? '';
    $hour_event = $_POST['hour_event'] ?? '';
    $image_url = $_POST['image_url'] ?? '';

    $stmt = $conn->prepare("UPDATE eventos SET invitation=?, title=?, date_event=?, place=?, hour_event=?, image_url=? WHERE id=?");
    $stmt->bind_param("ssssssi", $invitation, $title, $date_event, $place, $hour_event, $image_url, $id);

    if ($stmt->execute()) {
        echo "<script language='JavaScript'>alert('Los datos fueron Actualizados correctamente'); location.assign('index.php');</script>";
    } else {
        echo "<script language='JavaScript'>alert('Los datos NO fueron actualizados correctamente'); location.assign('index.php');</script>";
    }

    $stmt->close();

} else {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    $stmt = $conn->prepare("SELECT * FROM eventos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $mostrar = $result->fetch_assoc();

    if (!$mostrar) {
        die("Registro no encontrado");
    }

    $invitation = $mostrar['invitation'];
    $title = $mostrar['title'];
    $date_event = $mostrar['date_event'];
    $place = $mostrar['place'];
    $hour_event = $mostrar['hour_event'];
    $image_url = $mostrar['image_url'];
    $stmt->close();
?>
    <div class="conatiner">
        <h1 class="text-center"><?php echo htmlspecialchars($title); ?><br /><small>Editar Evento</small></h1>
        <hr>
        <div class="row justify-content-center">
        <div class="col-md-8">
            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                <div class="mb-3">
                    <label for="invitation" class="form-label">Invitación</label>
                    <input value="<?php echo htmlspecialchars($invitation); ?>" type="text" class="form-control" name="invitation" id="invitation" placeholder="Descripción del Evento" required>
                </div>
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <div class="mb-3">
                    <label for="title" class="form-label">Título</label>
                    <input value="<?php echo htmlspecialchars($title); ?>" type="text" class="form-control" name="title" id="title" placeholder="Título del Evento" required>
                </div>
                <div class="mb-3">
                    <label for="date_event" class="form-label">Fecha</label>
                    <input value="<?php echo htmlspecialchars($date_event); ?>" type="date" class="form-control" name="date_event" id="date_event" placeholder="Fecha del Evento" required>
                </div>
                <div class="mb-3">
                    <label for="place" class="form-label">Lugar de Evento</label>
                    <input value="<?php echo htmlspecialchars($place); ?>" type="text" class="form-control" name="place" id="place" placeholder="Lugar del Evento" required>
                </div>
                <div class="mb-3">
                    <label for="hour" class="form-label">Hora</label>
                    <input value="<?php echo htmlspecialchars($hour_event); ?>" type="text" class="form-control" name="hour_event" id="hour" placeholder="Hora del Evento" required>
                </div>
                <div class="mb-3">
                    <label for="image_url" class="form-label">Inserta el url de la imagen</label>
                    <input value="<?php echo htmlspecialchars($image_url); ?>" type="text" class="form-control" name="image_url" id="image_url" placeholder="ej: https://iglesiaeliasista.org.mx/app-images/Eventos-3-Mesias-2024.jpeg">
                </div>
                <button type="submit" name="enviar" class="btn btn-primary">Editar Evento</button>
            </form>
        </div>
    </div>
<?php } ?>
</div>
<?php include('footer.php'); ?>
