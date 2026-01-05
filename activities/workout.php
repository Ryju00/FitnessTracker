<?php
session_start();
require_once '../includes/auth.php';
if (!Auth::isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dodaj Trening</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
            padding-top: 2rem;
        }

        .activity-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            background: white;
        }

        .card-header {
            background: white;
            border-bottom: 2px solid #f77f00;
            border-radius: 16px 16px 0 0;
            padding: 1.5rem;
            font-weight: 600;
            color: #2d3748;
            font-size: 1.1rem;
        }

        .card-body {
            padding: 2rem;
        }

        .form-control, .form-select {
            border-radius: 12px;
            padding: 0.875rem 1.25rem;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: #f77f00;
            box-shadow: 0 0 0 4px rgba(247, 127, 0, 0.1);
            outline: none;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .btn {
            border-radius: 12px;
            padding: 0.875rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #f77f00 0%, #fcbf49 100%);
            color: #003049;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(247, 127, 0, 0.3);
            color: #003049;
        }

        .btn-secondary {
            background: #e9ecef;
            color: #495057;
        }

        .btn-secondary:hover {
            background: #dee2e6;
            transform: translateY(-2px);
            color: #495057;
        }

        .exercise-row {
            background: #f8f9fa;
            padding: 1.25rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            border: 2px solid #e9ecef;
        }

        .section-divider {
            border-top: 2px solid #e9ecef;
            margin: 2rem 0;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.25rem;
        }

        .btn-outline-primary {
            color: #f77f00 !important;
            border-color: #f77f00 !important;
        }

        .btn-outline-primary:hover {
            background: #f77f00 !important;
            color: white !important;
        }

        .btn-light {
            background: white !important;
            color: #f77f00 !important;
            border: none !important;
        }

        .btn-light:hover {
            background: #f77f00 !important;
            color: white !important;
        }
    </style>
</head>
<body class="py-5">
<div class="container">
    <a href="../user/dashboard.php" class="btn btn-light mb-4 shadow-sm" style="border-radius: 10px;">
        ← Powrót do Dashboard
    </a>
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-9">
            <div class="activity-card card">
                <div class="card-header">
                    <h3 class="m-0 text-center">💪 Dodaj Trening</h3>
                </div>
                <div class="card-body p-4 p-md-5">
                    <div class="text-center emoji-large">🏋️</div>
                    <form id="exForm">
                        <h5 class="mb-3">Ćwiczenia</h5>
                        <div id="exercisesList"></div>
                        <button type="button" id="addEx" class="btn btn-add btn-outline-primary mb-4">➕ Dodaj ćwiczenie</button>

                        <div class="section-divider"></div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Całkowity czas treningu (minuty)</label>
                            <input type="number" id="duration" class="form-control" min="1" placeholder="np. 60" required>
                        </div>

                        <div class="text-center">
                            <button class="btn btn-save btn-primary btn-lg px-5" type="submit">💾 Zapisz Trening</button>
                        </div>
                    </form>
                    <div id="msg" class="mt-4"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function addExerciseRow(ex = {}){
    const id = Date.now() + Math.random();
    const div = document.createElement('div');
    div.className = 'exercise-row';
    div.innerHTML = `
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label fw-semibold">Nazwa ćwiczenia</label>
                <input class="form-control ex-name" value="${ex.name||''}" placeholder="np. Wyciskanie" required>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Powtórzenia</label>
                <input type="number" class="form-control ex-reps" min="1" value="${ex.reps||""}" placeholder="12">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Serie</label>
                <input type="number" class="form-control ex-sets" min="1" value="${ex.sets||""}" placeholder="3">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Waga (kg)</label>
                <input type="number" step="0.5" class="form-control ex-weight" value="${ex.weight||""}" placeholder="50">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-danger btn-sm w-100 remove-ex" style="border-radius: 8px; display: flex; align-items: center; justify-content: center;">🗑️</button>
            </div>
        </div>
    `;
    div.querySelector('.remove-ex').addEventListener('click', ()=> div.remove());
    document.getElementById('exercisesList').appendChild(div);
}

document.getElementById('addEx').addEventListener('click', ()=> addExerciseRow());

document.getElementById('exForm').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const duration = document.getElementById('duration').value;
    const rows = Array.from(document.querySelectorAll('#exercisesList .row'));
    const exercises = rows.map(r => ({
        name: r.querySelector('.ex-name').value,
        reps: r.querySelector('.ex-reps').value || null,
        sets: r.querySelector('.ex-sets').value || null,
        weight: r.querySelector('.ex-weight').value || null
    })).filter(x=>x.name && x.name.trim().length>0);

    const form = new FormData();
    form.append('mode','workout');
    form.append('duration', duration);
    form.append('exercises', JSON.stringify(exercises));

    const res = await fetch('../api/activity.php', { method: 'POST', body: form, credentials: 'same-origin' });
    const data = await res.json();
    const msg = document.getElementById('msg');
    if (data.success) {
        msg.innerHTML = `<div class="alert alert-success">Trening zapisany — spalono ${data.calories} kcal.</div>`;
        setTimeout(()=> window.location='../user/dashboard.php', 1200);
    } else {
        msg.innerHTML = `<div class="alert alert-danger">${data.message||'Błąd'}</div>`;
    }
});

addExerciseRow();
</script>
</body>
</html>
