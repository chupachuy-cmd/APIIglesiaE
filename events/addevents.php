<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
include('header.php');
?>

<h1 class="text-center">Insertar Evento</h1>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <form method="POST" action="insertevent.php">
                <div class="mb-3">
                    <label for="invitation" class="form-label">Invitación</label>
                    <input type="text" class="form-control" name="invitation" id="invitation" placeholder="Descripción del Evento" required>
                </div>
                <div class="mb-3">
                    <label for="title" class="form-label">Título</label>
                    <input type="text" class="form-control" name="title" id="title" placeholder="Título del Evento" required>
                </div>
                <div class="mb-3">
                    <label for="date_event" class="form-label">Fecha</label>
                    <input type="date" class="form-control" name="date_event" id="date_event" placeholder="Fecha del Evento" required>
                </div>
                <div class="mb-3">
                    <label for="place" class="form-label">Lugar de Evento</label>
                    <input type="text" class="form-control" name="place" id="place" placeholder="Lugar del Evento" required>
                </div>
                <div class="mb-3">
                    <label for="hour" class="form-label">Hora</label>
                    <input type="text" class="form-control" name="hour_event" id="hour" placeholder="Hora del Evento" required>
                </div>
                <div class="mb-3">
                    <label for="image_url" class="form-label">Inserta el url de la imagen</label>
                    <input type="text" class="form-control" name="image_url" id="image_url" placeholder="ej: https://iglesiaeliasista.org.mx/app-images/Eventos-3-Mesias-2024.jpeg">
                </div>
                <button type="submit" class="btn btn-primary">Agregar Evento</button>
            </form>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
