<?php
require_once 'init.php';

$quizId = intval($_GET['quiz_id'] ?? 0);

if ($quizId <= 0) {
    $result = $conn->query("SELECT id FROM quizzes ORDER BY id DESC LIMIT 1");
    $latest = $result->fetch_assoc();
    $quizId = $latest ? $latest['id'] : 0;
}

$stmt = $conn->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->bind_param("i", $quizId);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();

if (!$quiz) {
    echo '<div class="container mt-5"><div class="alert alert-warning">No hay quizzes disponibles. <a href="addquiz.php">Crear uno</a></div></div>';
    include('header.php');
    include('footer.php');
    exit;
}

$cleaned = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clean_data') {
    validateCsrfToken();
    $stmt = $conn->prepare("DELETE qr FROM quiz_responses qr INNER JOIN quiz_tokens qt ON qr.token_id = qt.id WHERE qt.quiz_id = ?");
    $stmt->bind_param("i", $quizId);
    $stmt->execute();
    $stmt = $conn->prepare("DELETE FROM quiz_tokens WHERE quiz_id = ?");
    $stmt->bind_param("i", $quizId);
    $stmt->execute();
    $cleaned = true;
}

include('header.php');

$quizzesResult = $conn->query("SELECT id, title FROM quizzes ORDER BY id DESC");
$allQuizzes = $quizzesResult->fetch_all(MYSQLI_ASSOC);
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Estadísticas del Quiz</h1>
        <div>
            <?php if (count($allQuizzes) > 1): ?>
            <select class="form-select d-inline-block w-auto" id="quizSelector" onchange="window.location.href='dashboard.php?quiz_id=' + this.value">
                <?php foreach ($allQuizzes as $q): ?>
                    <option value="<?= $q['id'] ?>" <?= $q['id'] == $quizId ? 'selected' : '' ?>><?= htmlspecialchars($q['title']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <a href="index.php" class="btn btn-outline-secondary ms-2">Volver</a>
        </div>
    </div>

    <?php if ($cleaned): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Datos del quiz limpiados correctamente. Todos los participantes y respuestas fueron eliminados.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="card-title display-4" id="totalRespondents">--</h3>
                    <p class="card-text">Participantes</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="card-title display-4 text-success" id="totalCorrect">--</h3>
                    <p class="card-text">Respuestas Correctas</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="card-title display-4 text-danger" id="totalIncorrect">--</h3>
                    <p class="card-text">Respuestas Incorrectas</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="card-title display-4 text-info" id="avgTime">--</h3>
                    <p class="card-text">Tiempo Promedio</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4" id="leaderboardCard" style="display:none;">
        <div class="card-body">
            <h5 class="card-title">Tabla de Posiciones</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="leaderboardTable">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:50px">#</th>
                            <th>Nickname</th>
                            <th style="width:100px">Aciertos</th>
                            <th style="width:110px">Tiempo</th>
                            <th style="width:60px"></th>
                        </tr>
                    </thead>
                    <tbody id="leaderboardBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Correctas vs Incorrectas (General)</h5>
                    <canvas id="overallChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Resultados por Pregunta</h5>
                    <canvas id="perQuestionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div id="questionCharts"></div>

    <div class="text-muted text-center mt-3">
        <small>Actualización automática cada 5 segundos</small>
        <br>
        <button class="btn btn-sm btn-outline-primary mt-1" onclick="loadStats()">Actualizar ahora</button>
        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar TODOS los participantes y respuestas de este quiz? Esta acción no se puede deshacer.');">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="clean_data">
            <button type="submit" class="btn btn-sm btn-outline-danger mt-1 ms-1">Limpiar datos</button>
        </form>
    </div>
</div>

<div class="modal fade" id="detailModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="detailTitle"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row mb-3" id="detailSummary"></div>
        <div class="table-responsive">
          <table class="table table-sm table-hover">
            <thead class="table-light">
              <tr><th>#</th><th>Pregunta</th><th>Eligió</th><th>Correcta</th><th>Resultado</th></tr>
            </thead>
            <tbody id="detailBody"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
let overallChart = null;
let perQuestionChart = null;
let questionChartsInstances = [];

function formatTime(seconds) {
    if (!seconds || seconds <= 0) return '--';
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return m > 0 ? m + 'm ' + s + 's' : s + 's';
}

function loadStats() {
    fetch('stats_proxy.php?quiz_id=<?= $quizId ?>')
    .then(res => res.json())
    .then(data => {
        if (data.success !== 1) {
            console.error('Error cargando estadísticas:', data);
            return;
        }

        document.getElementById('totalRespondents').textContent = data.total_respondents;

        let totalCorrect = 0;
        let totalIncorrect = 0;

        data.questions.forEach(q => {
            totalCorrect += q.correct;
            totalIncorrect += q.incorrect;
        });

        document.getElementById('totalCorrect').textContent = totalCorrect;
        document.getElementById('totalIncorrect').textContent = totalIncorrect;

        if (overallChart) overallChart.destroy();
        const overallCtx = document.getElementById('overallChart').getContext('2d');
        overallChart = new Chart(overallCtx, {
            type: 'doughnut',
            data: {
                labels: ['Correctas', 'Incorrectas'],
                datasets: [{
                    data: [totalCorrect, totalIncorrect],
                    backgroundColor: ['#27ae60', '#e74c3c'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        if (perQuestionChart) perQuestionChart.destroy();
        const perQuesCtx = document.getElementById('perQuestionChart').getContext('2d');
        perQuestionChart = new Chart(perQuesCtx, {
            type: 'bar',
            data: {
                labels: data.questions.map(q => 'P' + q.id),
                datasets: [
                    {
                        label: 'Correctas',
                        data: data.questions.map(q => q.correct),
                        backgroundColor: '#27ae60',
                    },
                    {
                        label: 'Incorrectas',
                        data: data.questions.map(q => q.incorrect),
                        backgroundColor: '#e74c3c',
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                },
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        questionChartsInstances.forEach(c => c.destroy());
        questionChartsInstances = [];
        const container = document.getElementById('questionCharts');
        container.innerHTML = '';

        data.questions.forEach(q => {
            const optionKeys = Object.keys(q.options);
            const optionData = optionKeys.map(k => q.options[k].count);
            const optionCorrect = optionKeys.map(k => q.options[k].correct);
            const col = document.createElement('div');
            col.className = 'col-md-6 mb-4';

            const card = document.createElement('div');
            card.className = 'card';

            const cardBody = document.createElement('div');
            cardBody.className = 'card-body';

            const title = document.createElement('h6');
            title.className = 'card-title';
            title.textContent = q.question_text.length > 60 ? q.question_text.substring(0, 60) + '...' : q.question_text;

            const canvas = document.createElement('canvas');
            const canvasId = 'questionChart_' + q.id;

            const row = container.appendChild(document.createElement('div'));
            row.className = 'row';
            row.appendChild(col);

            card.appendChild(cardBody);
            cardBody.appendChild(title);
            cardBody.appendChild(canvas);
            col.appendChild(card);

            canvas.setAttribute('id', canvasId);
            canvas.style.maxHeight = '250px';

            const ctx = canvas.getContext('2d');
            const chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: optionKeys,
                    datasets: [{
                        label: 'Respuestas',
                        data: optionData,
                        backgroundColor: optionKeys.map(k => q.options[k].correct ? '#27ae60' : '#3498db'),
                        borderColor: optionKeys.map(k => q.options[k].correct ? '#1e8449' : '#2980b9'),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });

            questionChartsInstances.push(chart);
        });

        if (data.respondents && data.respondents.length > 0) {
            document.getElementById('leaderboardCard').style.display = '';

            let totalTime = 0;
            const fastestTime = Math.min(...data.respondents.map(r => r.elapsed_seconds || Infinity));
            const bestScore = Math.max(...data.respondents.map(r => r.score_correct));

            data.respondents.forEach(r => { totalTime += (r.elapsed_seconds || 0); });
            const avgSecs = Math.round(totalTime / data.respondents.length);
            document.getElementById('avgTime').textContent = formatTime(avgSecs);

            const medals = ['🥇', '🥈', '🥉'];
            const tbody = document.getElementById('leaderboardBody');
            tbody.innerHTML = '';

            data.respondents.forEach((r, i) => {
                const timeStr = formatTime(r.elapsed_seconds);
                const pct = r.score_total > 0 ? Math.round(r.score_correct / r.score_total * 100) : 0;

                let rowClass = '';
                if (i === 0) rowClass = 'table-warning fw-bold';
                else if (i < 3) rowClass = 'table-light';

                let scoreBadge = pct >= 80 ? 'text-success' : (pct >= 50 ? 'text-warning' : 'text-danger');

                let scoreExtra = '';
                if (r.score_correct > 0 && r.score_correct === bestScore) scoreExtra = ' <span class="badge bg-warning text-dark">Más aciertos</span>';

                let timeBadge = '';
                if (r.elapsed_seconds > 0 && r.elapsed_seconds === fastestTime) timeBadge = ' <span class="badge bg-success">Más rápido</span>';

                tbody.innerHTML += `<tr class="${rowClass}">
                    <td>${medals[i] || (i + 1)}</td>
                    <td>${r.nickname.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</td>
                    <td><strong class="${scoreBadge}">${r.score_correct}/${r.score_total}</strong> <small class="text-muted">(${pct}%)</small>${scoreExtra}</td>
                    <td>${timeStr}${timeBadge}</td>
                    <td><button class="btn btn-outline-primary btn-sm" onclick="showDetail(${r.token_id})" title="Ver detalle"><i class="bi bi-eye"></i> Ver</button></td>
                </tr>`;
            });
        } else {
            document.getElementById('leaderboardCard').style.display = 'none';
            document.getElementById('avgTime').textContent = '--';
        }
    })
    .catch(err => console.error('Error:', err));
}

loadStats();
setInterval(loadStats, 5000);

async function showDetail(tokenId) {
    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
    document.getElementById('detailBody').innerHTML = '<tr><td colspan="5" class="text-center"><div class="spinner-border spinner-border-sm"></div> Cargando...</td></tr>';
    document.getElementById('detailTitle').textContent = '...';
    document.getElementById('detailSummary').innerHTML = '';
    modal.show();

    try {
        const r = await fetch('stats_proxy.php?action=respondent_detail&token_id=' + tokenId + '&quiz_id=<?= $quizId ?>');
        const d = await r.json();
        if (d.success !== 1) throw new Error(d.error);

        document.getElementById('detailTitle').innerHTML = d.nickname.replace(/</g, '&lt;');

        const pct = d.score_total > 0 ? Math.round(d.score_correct / d.score_total * 100) : 0;
        const timeStr = formatTime(d.elapsed_seconds);
        document.getElementById('detailSummary').innerHTML =
            '<div class="col-4"><div class="border rounded p-2 text-center"><strong>' + d.score_correct + '/' + d.score_total + '</strong><br><small>Aciertos</small></div></div>' +
            '<div class="col-4"><div class="border rounded p-2 text-center"><strong>' + pct + '%</strong><br><small>Porcentaje</small></div></div>' +
            '<div class="col-4"><div class="border rounded p-2 text-center"><strong>' + timeStr + '</strong><br><small>Tiempo</small></div></div>';

        let html = '';
        d.details.forEach((q, i) => {
            html += `<tr>
                <td>${i + 1}</td>
                <td style="max-width:300px">${q.question_text.replace(/</g,'&lt;')}</td>
                <td><small>${q.selected_label.replace(/</g,'&lt;')}</small></td>
                <td><small>${q.correct_label.replace(/</g,'&lt;')}</small></td>
                <td>${q.is_correct ? '<span class="badge bg-success">Correcto</span>' : '<span class="badge bg-danger">Incorrecto</span>'}</td>
            </tr>`;
        });
        document.getElementById('detailBody').innerHTML = html;
    } catch(e) {
        document.getElementById('detailBody').innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error: ' + e.message + '</td></tr>';
    }
}
</script>

<?php include('footer.php'); ?>
