<?php
require_once 'init.php';

$quizId = intval($_GET['quiz_id'] ?? 0);

if ($quizId <= 0) {
    echo "<script>alert('ID inválido'); location.assign('index.php');</script>";
    exit;
}

$stmt = $conn->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->bind_param("i", $quizId);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();

if (!$quiz) {
    echo "<script>alert('Quiz no encontrado'); location.assign('index.php');</script>";
    exit;
}

$stmt = $conn->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY order_index ASC");
$stmt->bind_param("i", $quizId);
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function getOptionClass(bool $submitted, array $question, array $results, string $letter): string
{
    if (!$submitted) return '';
    $qId = $question['id'];
    $selected = $results[$qId]['selected'] ?? '';
    $correct = $question['correct_option'];

    if ($letter === $correct) return 'bg-success bg-opacity-10 border-success';
    if ($letter === $selected && $selected !== $correct) return 'bg-danger bg-opacity-10 border-danger';
    return '';
}

$submitted = ($_SERVER['REQUEST_METHOD'] === 'POST');
$results = [];
$score = ['total' => 0, 'correct' => 0, 'incorrect' => 0];

if ($submitted) {
    $answers = $_POST['answers'] ?? [];

    foreach ($questions as $q) {
        $qId = $q['id'];
        $selected = strtoupper(trim($answers[$qId] ?? ''));
        $correct = $q['correct_option'];
        $isCorrect = ($selected === $correct);

        if ($isCorrect) {
            $score['correct']++;
        } else {
            $score['incorrect']++;
        }

        $results[$qId] = [
            'question' => $q,
            'selected' => $selected,
            'is_correct' => $isCorrect,
        ];
    }

    $score['total'] = $score['correct'] + $score['incorrect'];
}

include('header.php');
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Probar Quiz</h1>
        <div>
            <span class="badge bg-info fs-6">Modo prueba</span>
            <a href="editquiz.php?id=<?= $quizId ?>" class="btn btn-outline-secondary ms-2">Editar Quiz</a>
            <a href="index.php" class="btn btn-outline-secondary ms-1">Volver</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h4 class="card-title"><?= htmlspecialchars($quiz['title']) ?></h4>
            <?php if (!empty($quiz['description'])): ?>
                <p class="card-text text-muted"><?= htmlspecialchars($quiz['description']) ?></p>
            <?php endif; ?>
            <p class="card-text">
                <small class="text-muted">
                    <?= htmlspecialchars($quiz['week_start']) ?> al <?= htmlspecialchars($quiz['week_end']) ?>
                    <?php if ($quiz['is_active']): ?>
                        <span class="badge bg-success">Activo</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Inactivo</span>
                    <?php endif; ?>
                </small>
            </p>
        </div>
    </div>

    <?php if ($submitted): ?>
        <div class="card mb-4 border-success">
            <div class="card-body text-center">
                <h3>Resultado</h3>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="display-5 fw-bold"><?= $score['correct'] ?>/<?= $score['total'] ?></div>
                        <small class="text-muted">Correctas</small>
                    </div>
                    <div class="col-md-4">
                        <div class="display-5 fw-bold text-success"><?= $score['total'] > 0 ? round(($score['correct'] / $score['total']) * 100) : 0 ?>%</div>
                        <small class="text-muted">Porcentaje</small>
                    </div>
                    <div class="col-md-4">
                        <div class="display-5 fw-bold text-danger"><?= $score['incorrect'] ?></div>
                        <small class="text-muted">Incorrectas</small>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" id="previewForm">
        <?php foreach ($questions as $i => $q): ?>
            <?php $qType = $q['question_type'] ?? 'multiple_choice_4'; ?>
            <div class="card mb-3 <?= $submitted ? ($results[$q['id']]['is_correct'] ? 'border-success' : 'border-danger') : '' ?>">
                <div class="card-body">
                    <h5 class="card-title">
                        Pregunta <?= $i + 1 ?>
                        <span class="badge <?= $qType === 'true_false' ? 'bg-success' : 'bg-primary' ?> ms-1">
                            <?= $qType === 'true_false' ? 'Cierto o Falso' : 'Opción múltiple' ?>
                        </span>
                        <?php if ($submitted): ?>
                            <?php if ($results[$q['id']]['is_correct']): ?>
                                <span class="badge bg-success ms-1">Correcto</span>
                            <?php else: ?>
                                <span class="badge bg-danger ms-1">Incorrecto</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </h5>
                    <p class="card-text fw-medium mb-3"><?= htmlspecialchars($q['question_text']) ?></p>

                    <?php if ($qType === 'true_false'): ?>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <div class="border rounded p-3 <?= getOptionClass($submitted, $q, $results, 'A') ?>">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" value="A" id="q<?= $q['id'] ?>_a"
                                            <?= !$submitted ? 'required' : '' ?>
                                            <?= $submitted && $results[$q['id']]['selected'] === 'A' ? 'checked' : '' ?>
                                            <?= $submitted ? 'disabled' : '' ?>>
                                        <label class="form-check-label fw-bold" for="q<?= $q['id'] ?>_a">A) Verdadero</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <div class="border rounded p-3 <?= getOptionClass($submitted, $q, $results, 'B') ?>">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" value="B" id="q<?= $q['id'] ?>_b"
                                            <?= !$submitted ? 'required' : '' ?>
                                            <?= $submitted && $results[$q['id']]['selected'] === 'B' ? 'checked' : '' ?>
                                            <?= $submitted ? 'disabled' : '' ?>>
                                        <label class="form-check-label fw-bold" for="q<?= $q['id'] ?>_b">B) Falso</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php
                        $options = [
                            'A' => $q['option_a'],
                            'B' => $q['option_b'],
                            'C' => $q['option_c'],
                            'D' => $q['option_d'],
                        ];
                        ?>
                        <div class="row">
                            <?php foreach ($options as $letter => $text): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="border rounded p-3 <?= getOptionClass($submitted, $q, $results, $letter) ?>">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" value="<?= $letter ?>" id="q<?= $q['id'] ?>_<?= strtolower($letter) ?>"
                                                <?= !$submitted ? 'required' : '' ?>
                                                <?= $submitted && $results[$q['id']]['selected'] === $letter ? 'checked' : '' ?>
                                                <?= $submitted ? 'disabled' : '' ?>>
                                            <label class="form-check-label" for="q<?= $q['id'] ?>_<?= strtolower($letter) ?>">
                                                <strong><?= $letter ?>)</strong> <?= htmlspecialchars($text) ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($submitted && !$results[$q['id']]['is_correct']): ?>
                        <div class="alert alert-warning mt-2 mb-0">
                            <strong>Respuesta correcta:</strong>
                            <?php $correctLetter = $q['correct_option']; ?>
                            <?php if ($qType === 'true_false'): ?>
                                <?= $correctLetter === 'A' ? 'A) Verdadero' : 'B) Falso' ?>
                            <?php else: ?>
                                <?= $correctLetter ?>) <?= htmlspecialchars($q['option_' . strtolower($correctLetter)]) ?>
                            <?php endif; ?>
                            <br>
                            <small>Seleccionaste: <?= $results[$q['id']]['selected'] ?: 'Ninguna' ?></small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($questions)): ?>
            <div class="alert alert-warning">Este quiz no tiene preguntas. <a href="editquiz.php?id=<?= $quizId ?>">Agregar preguntas</a></div>
        <?php elseif (!$submitted): ?>
            <div class="text-center mb-4">
                <button type="submit" class="btn btn-success btn-lg">Revisar respuestas</button>
            </div>
        <?php else: ?>
            <div class="text-center mb-4">
                <a href="preview.php?quiz_id=<?= $quizId ?>" class="btn btn-primary btn-lg">Intentar de nuevo</a>
                <a href="editquiz.php?id=<?= $quizId ?>" class="btn btn-outline-secondary btn-lg ms-2">Editar Quiz</a>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php include('footer.php'); ?>
