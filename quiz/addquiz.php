<?php
require_once 'init.php';

$VALID_TYPES = ['multiple_choice_4', 'true_false'];
$VALID_OPTIONS_BY_TYPE = [
    'multiple_choice_4' => ['A', 'B', 'C', 'D'],
    'true_false' => ['A', 'B'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $week_start = $_POST['week_start'] ?? '';
    $week_end = $_POST['week_end'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $questions = $_POST['questions'] ?? [];
    $questionTypes = $_POST['question_type'] ?? [];
    $options_a = $_POST['option_a'] ?? [];
    $options_b = $_POST['option_b'] ?? [];
    $options_c = $_POST['option_c'] ?? [];
    $options_d = $_POST['option_d'] ?? [];
    $correct_options = $_POST['correct_option'] ?? [];
    $order_indexes = $_POST['order_index'] ?? [];

    if (empty($title) || empty($week_start) || empty($week_end)) {
        $error = "Título y fechas son obligatorios.";
    } elseif (empty($questions)) {
        $error = "Debes agregar al menos una pregunta.";
    } else {
        $conn->begin_transaction();
        try {
            if ($is_active) {
                $conn->query("UPDATE quizzes SET is_active = 0");
            }

            $stmt = $conn->prepare("INSERT INTO quizzes (title, description, week_start, week_end, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $title, $description, $week_start, $week_end, $is_active);
            $stmt->execute();
            $quizId = $conn->insert_id;

            $qStmt = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option, question_type, order_index) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            foreach ($questions as $i => $questionText) {
                if (empty(trim($questionText))) continue;

                $qType = $questionTypes[$i] ?? 'multiple_choice_4';
                if (!in_array($qType, $VALID_TYPES)) {
                    throw new Exception("Tipo de pregunta inválido en pregunta " . ($i + 1));
                }

                $validOptions = $VALID_OPTIONS_BY_TYPE[$qType];
                $optA = trim($options_a[$i] ?? '');
                $optB = trim($options_b[$i] ?? '');
                $optC = in_array('C', $validOptions) ? trim($options_c[$i] ?? '') : null;
                $optD = in_array('D', $validOptions) ? trim($options_d[$i] ?? '') : null;
                $correct = strtoupper(trim($correct_options[$i] ?? ''));
                $order = intval($order_indexes[$i] ?? $i);

                if (!in_array($correct, $validOptions)) {
                    throw new Exception("Opción correcta inválida para el tipo '$qType' en pregunta " . ($i + 1));
                }

                if (empty($optA) || empty($optB)) {
                    throw new Exception("Las opciones A y B son obligatorias en pregunta " . ($i + 1));
                }

                if (in_array('C', $validOptions) && empty(trim($options_c[$i] ?? ''))) {
                    throw new Exception("La opción C es obligatoria para el tipo '$qType' en pregunta " . ($i + 1));
                }

                if (in_array('D', $validOptions) && empty(trim($options_d[$i] ?? ''))) {
                    throw new Exception("La opción D es obligatoria para el tipo '$qType' en pregunta " . ($i + 1));
                }

                $qStmt->bind_param("isssssssi", $quizId, $questionText, $optA, $optB, $optC, $optD, $correct, $qType, $order);
                $qStmt->execute();
            }

            $conn->commit();
            echo "<script>alert('Quiz creado correctamente'); location.assign('index.php');</script>";
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error al crear el quiz: " . $e->getMessage();
        }
    }
}

include('header.php');
?>

<div class="container mt-4">
    <h1 class="text-center">Agregar Nuevo Quiz</h1>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" id="quizForm">
        <?= csrfField() ?>
        <div class="mb-3">
            <label for="title" class="form-label">Título del Quiz</label>
            <input type="text" class="form-control" id="title" name="title" required>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Descripción</label>
            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="week_start" class="form-label">Fecha de inicio</label>
                <input type="date" class="form-control" id="week_start" name="week_start" required>
            </div>
            <div class="col-md-6">
                <label for="week_end" class="form-label">Fecha de fin</label>
                <input type="date" class="form-control" id="week_end" name="week_end" required>
            </div>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1">
            <label class="form-check-label" for="is_active">Activar este quiz (desactivará los demás)</label>
        </div>

        <hr>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Preguntas</h3>
            <button type="button" class="btn btn-secondary" id="addQuestionBtn" data-bs-toggle="modal" data-bs-target="#questionTypeModal">+ Agregar Pregunta</button>
        </div>
        <div id="questionsContainer"></div>

        <div id="emptyState" class="text-center py-5 text-muted">
            <p>No hay preguntas aún. Haz clic en <strong>"+ Agregar Pregunta"</strong> para empezar.</p>
        </div>

        <br>
        <button type="submit" class="btn btn-primary">Guardar Quiz</button>
        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
    </form>
</div>

<div class="modal fade" id="questionTypeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Selecciona el tipo de pregunta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <button type="button" class="btn btn-outline-primary btn-lg w-100 mb-3" data-question-type="multiple_choice_4" data-bs-dismiss="modal">
                    <strong>Opción múltiple</strong><br>
                    <small class="text-muted">4 opciones con una sola respuesta correcta</small>
                </button>
                <button type="button" class="btn btn-outline-success btn-lg w-100" data-question-type="true_false" data-bs-dismiss="modal">
                    <strong>Cierto o Falso</strong><br>
                    <small class="text-muted">2 opciones: Verdadero o Falso</small>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let questionCount = 0;

function createMultipleChoiceCard(num) {
    return `
        <div class="card mb-3 question-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="card-title">Pregunta <span class="question-number">${num}</span> <span class="badge bg-primary ms-1">Opción múltiple</span></h5>
                    <button type="button" class="btn btn-danger btn-sm remove-question">Eliminar</button>
                </div>
                <input type="hidden" name="question_type[]" value="multiple_choice_4">
                <input type="hidden" name="order_index[]" value="${num - 1}">
                <div class="mb-3">
                    <label class="form-label">Texto de la pregunta</label>
                    <input type="text" class="form-control" name="questions[]" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Opción A</label>
                        <input type="text" class="form-control" name="option_a[]" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Opción B</label>
                        <input type="text" class="form-control" name="option_b[]" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Opción C</label>
                        <input type="text" class="form-control" name="option_c[]" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Opción D</label>
                        <input type="text" class="form-control" name="option_d[]" required>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label">Respuesta correcta</label>
                    <select class="form-select" name="correct_option[]" required>
                        <option value="">Selecciona...</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                    </select>
                </div>
            </div>
        </div>`;
}

function createTrueFalseCard(num) {
    return `
        <div class="card mb-3 question-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="card-title">Pregunta <span class="question-number">${num}</span> <span class="badge bg-success ms-1">Cierto o Falso</span></h5>
                    <button type="button" class="btn btn-danger btn-sm remove-question">Eliminar</button>
                </div>
                <input type="hidden" name="question_type[]" value="true_false">
                <input type="hidden" name="order_index[]" value="${num - 1}">
                <div class="mb-3">
                    <label class="form-label">Texto de la pregunta</label>
                    <input type="text" class="form-control" name="questions[]" required>
                </div>
                <input type="hidden" name="option_a[]" value="Verdadero">
                <input type="hidden" name="option_b[]" value="Falso">
                <input type="hidden" name="option_c[]" value="">
                <input type="hidden" name="option_d[]" value="">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3 bg-light">
                            <strong>A) Verdadero</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 bg-light">
                            <strong>B) Falso</strong>
                        </div>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label">Respuesta correcta</label>
                    <select class="form-select" name="correct_option[]" required>
                        <option value="">Selecciona...</option>
                        <option value="A">A - Verdadero</option>
                        <option value="B">B - Falso</option>
                    </select>
                </div>
            </div>
        </div>`;
}

function addQuestionCard(type) {
    questionCount++;
    const container = document.getElementById('questionsContainer');
    const html = type === 'true_false' ? createTrueFalseCard(questionCount) : createMultipleChoiceCard(questionCount);
    container.insertAdjacentHTML('beforeend', html);
    updateUIState();
}

function updateUIState() {
    const container = document.getElementById('questionsContainer');
    const emptyState = document.getElementById('emptyState');
    const cards = container.querySelectorAll('.question-card');

    emptyState.style.display = cards.length === 0 ? '' : 'none';

    cards.forEach((card) => {
        card.querySelector('.remove-question').style.display = cards.length > 1 ? 'inline-block' : 'none';
    });
}

function updateQuestionNumbers() {
    const cards = document.querySelectorAll('.question-card');
    cards.forEach((card, i) => {
        const numSpan = card.querySelector('.question-number');
        numSpan.textContent = i + 1;
        card.querySelector('input[name="order_index[]"]').value = i;
    });
    questionCount = cards.length;
}

document.getElementById('questionsContainer').addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-question')) {
        e.target.closest('.question-card').remove();
        updateQuestionNumbers();
        updateUIState();
    }
});

document.querySelectorAll('#questionTypeModal [data-question-type]').forEach(btn => {
    btn.addEventListener('click', function() {
        addQuestionCard(this.dataset.questionType);
    });
});

document.getElementById('quizForm').addEventListener('submit', function(e) {
    updateQuestionNumbers();
});
</script>

<?php include('footer.php'); ?>
