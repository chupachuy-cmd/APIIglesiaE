<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resolver Quiz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:700px">

    <div id="stepNickname" class="card shadow">
        <div class="card-body text-center">
            <h3 class="mb-3">Quiz Bíblico</h3>
            <p class="text-muted">Ingresa tu nombre para comenzar</p>
            <input type="text" id="nicknameInput" class="form-control form-control-lg text-center mb-3" placeholder="Tu nickname" maxlength="50">
            <button class="btn btn-primary btn-lg w-100" onclick="startQuiz()">Comenzar Quiz</button>
            <div id="nicknameError" class="text-danger mt-2" style="display:none;"></div>
        </div>
    </div>

    <div id="stepLoading" class="text-center py-5" style="display:none;">
        <div class="spinner-border text-primary mb-3" role="status"></div>
        <h5>Cargando preguntas...</h5>
    </div>

    <div id="stepQuiz" style="display:none;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4><span id="userNickname"></span></h4>
            <div class="badge bg-dark fs-5" id="timer">00:00</div>
        </div>
        <div id="questionsContainer"></div>
        <button class="btn btn-success btn-lg w-100 mt-3" onclick="submitQuiz()">Enviar respuestas</button>
    </div>

    <div id="stepResult" class="card shadow" style="display:none;">
        <div class="card-body text-center">
            <h3 class="mb-3">Resultado</h3>
            <div class="display-1 fw-bold text-primary" id="resultScore">0/0</div>
            <p class="text-muted">Tiempo: <strong id="resultTime"></strong></p>
            <div class="row mt-3">
                <div class="col-6"><div class="border rounded p-3 bg-success bg-opacity-10"><strong id="resultCorrect"></strong><br><small>Correctas</small></div></div>
                <div class="col-6"><div class="border rounded p-3 bg-danger bg-opacity-10"><strong id="resultIncorrect"></strong><br><small>Incorrectas</small></div></div>
            </div>
            <button class="btn btn-outline-primary mt-4" onclick="location.reload()">Intentar de nuevo</button>
            <a href="dashboard.php?quiz_id=2" class="btn btn-outline-secondary mt-4 ms-2">Ver Leaderboard</a>
        </div>
    </div>

</div>
<script>
let token = '', quizId = 0, startedAt = '', nickname = '', timerInterval = null, questionIds = [];

function show(id) {
    ['stepNickname','stepLoading','stepQuiz','stepResult'].forEach(s => document.getElementById(s).style.display = 'none');
    document.getElementById(id).style.display = '';
}

async function startQuiz() {
    nickname = document.getElementById('nicknameInput').value.trim();
    if (nickname.length < 2) {
        document.getElementById('nicknameError').style.display = '';
        document.getElementById('nicknameError').textContent = 'Mínimo 2 caracteres';
        return;
    }
    show('stepLoading');

    try {
        let r = await fetch('../quiz_api.php?action=token&nickname=' + encodeURIComponent(nickname));
        let d = await r.json();
        if (d.success !== 1) throw new Error(d.error);
        token = d.token;

        r = await fetch('../quiz_api.php?action=active');
        d = await r.json();
        if (d.success !== 1) throw new Error(d.message || 'No hay quiz activo');
        quizId = d.quiz.id;

        r = await fetch('../quiz_api.php?action=start', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({token, quiz_id: quizId})
        });
        d = await r.json();
        if (d.success !== 1) throw new Error(d.error);
        startedAt = d.started_at;

        document.getElementById('userNickname').textContent = nickname;
        renderQuestions(d.quiz.questions);
        show('stepQuiz');
        startTimer();
    } catch(e) {
        alert('Error: ' + e.message);
        show('stepNickname');
    }
}

function renderQuestions(questions) {
    questionIds = questions.map(q => q.id);
    let html = '';
    questions.forEach((q, i) => {
        html += `<div class="card mb-3"><div class="card-body">
            <h6>${i+1}. ${q.question_text.replace(/</g,'&lt;')}</h6>`;
        if (q.question_type === 'true_false') {
            html += `<div class="form-check"><input class="form-check-input" type="radio" name="q_${q.id}" value="A" id="q${q.id}_a"><label class="form-check-label" for="q${q.id}_a">Verdadero</label></div>
                     <div class="form-check"><input class="form-check-input" type="radio" name="q_${q.id}" value="B" id="q${q.id}_b"><label class="form-check-label" for="q${q.id}_b">Falso</label></div>`;
        } else {
            ['A','B','C','D'].forEach(l => {
                if (q['option_'+l.toLowerCase()]) html += `<div class="form-check"><input class="form-check-input" type="radio" name="q_${q.id}" value="${l}" id="q${q.id}_${l.toLowerCase()}"><label class="form-check-label" for="q${q.id}_${l.toLowerCase()}">${l}) ${q['option_'+l.toLowerCase()].replace(/</g,'&lt;')}</label></div>`;
            });
        }
        html += '</div></div>';
    });
    document.getElementById('questionsContainer').innerHTML = html;
}

function startTimer() {
    const start = new Date().getTime();
    timerInterval = setInterval(() => {
        let secs = Math.floor((new Date().getTime() - start) / 1000);
        document.getElementById('timer').textContent = Math.floor(secs/60).toString().padStart(2,'0') + ':' + (secs%60).toString().padStart(2,'0');
    }, 200);
}

function stopTimer() {
    clearInterval(timerInterval);
}

async function submitQuiz() {
    if (!confirm('¿Enviar respuestas?')) return;
    stopTimer();

    let responses = [];
    let seenIds = new Set();
    let unansweredIds = [];

    document.querySelectorAll('input[name^="q_"]:checked').forEach(input => {
        let id = parseInt(input.name.replace('q_',''));
        if (!seenIds.has(id)) {
            seenIds.add(id);
            responses.push({question_id: id, selected_option: input.value});
        }
    });

    questionIds.forEach(id => {
        if (!seenIds.has(id)) unansweredIds.push(id);
    });

    if (unansweredIds.length > 0) {
        if (!confirm('Hay ' + unansweredIds.length + ' pregunta(s) sin responder. ¿Enviar de todas formas?')) { startTimer(); return; }
    }

    try {
        let r = await fetch('../quiz_api.php?action=submit', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({token, quiz_id: quizId, responses})
        });
        let d = await r.json();
        if (d.success !== 1) throw new Error(d.error);

        let secs = d.elapsed_seconds;
        document.getElementById('resultScore').textContent = d.score.correct + '/' + d.score.total;
        document.getElementById('resultTime').textContent = Math.floor(secs/60)+'m '+(secs%60)+'s';
        document.getElementById('resultCorrect').textContent = d.score.correct;
        document.getElementById('resultIncorrect').textContent = d.score.incorrect;
        show('stepResult');
    } catch(e) {
        alert('Error: ' + e.message);
        startTimer();
    }
}
</script>
</body>
</html>
