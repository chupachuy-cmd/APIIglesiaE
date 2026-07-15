<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
include('header.php');
?>

<h1 class="text-center">Insertar Pedimento de Oración</h1>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <form method="POST" action="insertprayer.php">
                <div class="mb-3">
                    <label for="title_pray" class="form-label">Título</label>
                    <input type="text" class="form-control" name="title_pray" id="title_pray" required>
                </div>
                <div class="mb-3">
                    <label for="description_pray" class="form-label">Descripción de la Oración</label>
                    <input type="text" class="form-control" name="description_pray" id="description_pray" required>
                </div>
                <div class="mb-3">
                    <label for="subject_pray" class="form-label">Asunto</label>
                    <input type="text" class="form-control" name="subject_pray" id="subject_pray" required>
                </div>
                <div class="mb-3">
                    <label for="pray_for" class="form-label">Quien pide la Oración</label>
                    <input type="text" class="form-control" name="pray_for" id="pray_for" required>
                </div>
                <div class="mb-3">
                    <label for="pray_to" class="form-label">Para quien es la Oración</label>
                    <input type="text" class="form-control" name="pray_to" id="pray_to" required>
                </div>
                <div class="mb-3">
                    <label for="date_pray" class="form-label">Fecha de la Oración</label>
                    <input type="date" class="form-control" name="date_pray" id="date_pray" required>
                </div>
                <div class="mb-3">
                    <label for="lyrics_pray" class="form-label">Oración</label>
                    <textarea rows="4" cols="50" class="form-control" name="lyrics_pray" id="lyrics_pray" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Agregar Oración</button>
            </form>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
