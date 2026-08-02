<?php
require_once __DIR__ . '/../db.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$conn->begin_transaction();
try {
    $conn->query("UPDATE quizzes SET is_active = 0");

    $title = 'Quiz Bíblico - 10 Preguntas';
    $description = 'Pon a prueba tu conocimiento bíblico con estas 10 preguntas.';
    $week_start = date('Y-m-d');
    $week_end = date('Y-m-d', strtotime('+7 days'));

    $stmt = $conn->prepare("INSERT INTO quizzes (title, description, week_start, week_end, is_active) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param("ssss", $title, $description, $week_start, $week_end);
    $stmt->execute();
    $quizId = $conn->insert_id;

    $questions = [
        [
            'text' => '¿Cuál es el libro más largo de la Biblia?',
            'type' => 'multiple_choice_4',
            'a' => 'Génesis', 'b' => 'Salmos', 'c' => 'Isaías', 'd' => 'Jeremías',
            'correct' => 'B'
        ],
        [
            'text' => '¿Cuántos discípulos eligió Jesús?',
            'type' => 'multiple_choice_4',
            'a' => '7', 'b' => '10', 'c' => '12', 'd' => '14',
            'correct' => 'C'
        ],
        [
            'text' => 'Elías fue llevado al cielo en un carro de fuego.',
            'type' => 'true_false',
            'a' => 'Verdadero', 'b' => 'Falso', 'c' => null, 'd' => null,
            'correct' => 'A'
        ],
        [
            'text' => '¿Quién construyó el arca?',
            'type' => 'multiple_choice_4',
            'a' => 'Abraham', 'b' => 'Moisés', 'c' => 'Noé', 'd' => 'David',
            'correct' => 'C'
        ],
        [
            'text' => 'Moisés escribió el libro de Apocalipsis.',
            'type' => 'true_false',
            'a' => 'Verdadero', 'b' => 'Falso', 'c' => null, 'd' => null,
            'correct' => 'B'
        ],
        [
            'text' => '¿Cuál fue el primer milagro de Jesús?',
            'type' => 'multiple_choice_4',
            'a' => 'Multiplicar panes', 'b' => 'Caminar sobre el agua', 'c' => 'Convertir agua en vino', 'd' => 'Resucitar a Lázaro',
            'correct' => 'C'
        ],
        [
            'text' => '¿Cuántos días duró el diluvio?',
            'type' => 'multiple_choice_4',
            'a' => '7', 'b' => '40', 'c' => '100', 'd' => '365',
            'correct' => 'B'
        ],
        [
            'text' => 'David mató a Goliat con una espada.',
            'type' => 'true_false',
            'a' => 'Verdadero', 'b' => 'Falso', 'c' => null, 'd' => null,
            'correct' => 'B'
        ],
        [
            'text' => '¿Qué río cruzaron los israelitas para entrar a la tierra prometida?',
            'type' => 'multiple_choice_4',
            'a' => 'Nilo', 'b' => 'Éufrates', 'c' => 'Jordán', 'd' => 'Tigris',
            'correct' => 'C'
        ],
        [
            'text' => 'Jesús nació en Nazaret.',
            'type' => 'true_false',
            'a' => 'Verdadero', 'b' => 'Falso', 'c' => null, 'd' => null,
            'correct' => 'B'
        ],
    ];

    $stmt = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option, question_type, order_index) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($questions as $i => $q) {
        $order = $i;
        $stmt->bind_param("isssssssi", $quizId, $q['text'], $q['a'], $q['b'], $q['c'], $q['d'], $q['correct'], $q['type'], $order);
        $stmt->execute();
    }

    $conn->commit();
    echo "<h2>Quiz creado exitosamente</h2>";
    echo "<p><strong>ID del quiz:</strong> $quizId</p>";
    echo "<p><strong>Preguntas insertadas:</strong> " . count($questions) . "</p>";
    echo "<hr>";
    echo "<h3>URLs para probar:</h3>";
    echo "<ul>";
    echo "<li>Admin: <a href='../quiz/index.php'>Lista de quizzes</a></li>";
    echo "<li>Dashboard: <a href='../quiz/dashboard.php?quiz_id=$quizId'>Estadísticas</a></li>";
    echo "<li>Preview: <a href='../quiz/preview.php?quiz_id=$quizId'>Probar quiz</a></li>";
    echo "</ul>";
    echo "<h3>Flujo API con nickname:</h3>";
    echo "<ol>";
    echo "<li><code>GET /api/quiz_api.php?action=token&nickname=Juan</code></li>";
    echo "<li><code>POST /api/quiz_api.php?action=start</code> — Body: <code>{\"token\":\"...\", \"quiz_id\":$quizId}</code></li>";
    echo "<li><code>POST /api/quiz_api.php?action=submit</code> — Body: <code>{\"token\":\"...\", \"quiz_id\":$quizId, \"responses\":[...]}</code></li>";
    echo "</ol>";

} catch (Exception $e) {
    $conn->rollback();
    echo "<h2>Error:</h2><pre>" . $e->getMessage() . "</pre>";
}
