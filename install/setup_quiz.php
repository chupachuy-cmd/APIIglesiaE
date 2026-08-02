<?php
require_once __DIR__ . '/../db.php';

$installKey = $_GET['key'] ?? '';
$validKey = getenv('INSTALL_KEY') ?: 'setup-iglesia-2024';

if (!hash_equals($validKey, $installKey)) {
    http_response_code(403);
    die('Acceso denegado. Use ?key=SETUP_KEY');
}

$db = Database::getInstance();
$conn = $db->getConnection();

$sql = "CREATE TABLE IF NOT EXISTS quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    week_start DATE NOT NULL,
    week_end DATE NOT NULL,
    is_active TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo "OK: Tabla 'quizzes' verificada.<br>";
} else {
    echo "ERROR (quizzes): " . $conn->error . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    option_a VARCHAR(500) NOT NULL,
    option_b VARCHAR(500) NOT NULL,
    option_c VARCHAR(500) NULL,
    option_d VARCHAR(500) NULL,
    correct_option CHAR(1) NOT NULL,
    question_type VARCHAR(50) NOT NULL DEFAULT 'multiple_choice_4',
    order_index INT DEFAULT 0,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
)";

if ($conn->query($sql)) {
    echo "OK: Tabla 'quiz_questions' verificada.<br>";
} else {
    echo "ERROR (quiz_questions): " . $conn->error . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS quiz_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo "OK: Tabla 'quiz_tokens' verificada.<br>";
} else {
    echo "ERROR (quiz_tokens): " . $conn->error . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS quiz_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token_id INT NOT NULL,
    quiz_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_option CHAR(1) NOT NULL,
    is_correct TINYINT(1) NOT NULL,
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (token_id) REFERENCES quiz_tokens(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_response (token_id, quiz_id, question_id)
)";

if ($conn->query($sql)) {
    echo "OK: Tabla 'quiz_responses' verificada.<br>";
} else {
    echo "ERROR (quiz_responses): " . $conn->error . "<br>";
}

echo "<br><strong>Migraciones:</strong><br>";

$result = $conn->query("SHOW COLUMNS FROM quiz_questions LIKE 'question_type'");
if ($result && $result->num_rows === 0) {
    if ($conn->query("ALTER TABLE quiz_questions ADD COLUMN question_type VARCHAR(50) NOT NULL DEFAULT 'multiple_choice_4' AFTER correct_option")) {
        echo "OK: Columna 'question_type' agregada.<br>";
    } else {
        echo "ERROR (question_type): " . $conn->error . "<br>";
    }
} else {
    echo "OK: Columna 'question_type' ya existe.<br>";
}

if ($conn->query("ALTER TABLE quiz_questions MODIFY option_c VARCHAR(500) NULL")) {
    echo "OK: Columna 'option_c' modificada a NULL.<br>";
} else {
    echo "INFO: " . $conn->error . "<br>";
}

if ($conn->query("ALTER TABLE quiz_questions MODIFY option_d VARCHAR(500) NULL")) {
    echo "OK: Columna 'option_d' modificada a NULL.<br>";
} else {
    echo "INFO: " . $conn->error . "<br>";
}

echo "<br><strong>Migraciones quiz_tokens (nickname + temporizador):</strong><br>";

$newColumns = [
    ['nickname', "VARCHAR(100) NULL"],
    ['quiz_id', "INT NULL"],
    ['started_at', "DATETIME NULL"],
    ['elapsed_seconds', "INT NULL"],
];

foreach ($newColumns as $col) {
    $result = $conn->query("SHOW COLUMNS FROM quiz_tokens LIKE '{$col[0]}'");
    if ($result && $result->num_rows === 0) {
        if ($conn->query("ALTER TABLE quiz_tokens ADD COLUMN {$col[0]} {$col[1]}")) {
            echo "OK: Columna '{$col[0]}' agregada a quiz_tokens.<br>";
        } else {
            echo "ERROR ({$col[0]}): " . $conn->error . "<br>";
        }
    } else {
        echo "OK: Columna '{$col[0]}' ya existe en quiz_tokens.<br>";
    }
}

echo "<br>Instalación del módulo Quiz completada.";
