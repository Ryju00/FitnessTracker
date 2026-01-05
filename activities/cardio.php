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
    <title>Dodaj Cardio</title>
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

        .btn-light {
            background: white;
            color: #4a5568;
            border: 2px solid #e9ecef;
        }

        .btn-light:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.25rem;
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

        .btn-danger {
            background: linear-gradient(135deg, #d62828, #003049) !important;
            border: none !important;
            color: white !important;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #d62828, #003049) !important;
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
        <div class="col-md-8 col-lg-6">
            <div class="activity-card card">
                <div class="card-header">
                    <h3 class="m-0 text-center">🏃 Dodaj Cardio</h3>
                </div>
                <div class="card-body p-4 p-md-5">
                    <div class="text-center emoji-large">💨</div>
                    <form id="cardioForm">
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Typ aktywności</label>
                            <select id="cardioType" class="form-select">
                                <option value="running">🏃 Bieganie</option>
                                <option value="cycling">🚴 Rower</option>
                                <option value="walking">🚶 Spacer</option>
                                <option value="rowing">🚣 Wioślarz</option>
                                <option value="swimming">🏊 Pływanie</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Czas (minuty)</label>
                            <input type="number" id="duration" class="form-control" min="1" placeholder="np. 30" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Dystans (km) <small class="text-muted">— opcjonalnie</small></label>
                            <input type="number" id="distance" class="form-control" step="0.01" min="0" placeholder="np. 5.5">
                        </div>
                        <div class="text-center">
                            <button class="btn btn-save btn-primary btn-lg px-5" type="submit">💾 Zapisz Cardio</button>
                        </div>
                    </form>
                    <div id="msg" class="mt-4"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('cardioForm').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const form = new FormData();
    form.append('mode', 'cardio');
    form.append('cardio_type', document.getElementById('cardioType').value);
    form.append('duration', document.getElementById('duration').value);
    form.append('distance', document.getElementById('distance').value || '');

    const res = await fetch('../api/activity.php', { method: 'POST', body: form, credentials: 'same-origin' });
    const data = await res.json();
    const msg = document.getElementById('msg');
    if (data.success) {
        msg.innerHTML = `<div class="alert alert-success">Zapisano — spalono ${data.calories} kcal.</div>`;
        setTimeout(()=>{ window.location = '../user/dashboard.php'; }, 1200);
    } else {
        msg.innerHTML = `<div class="alert alert-danger">${data.message || 'Błąd'}</div>`;
    }
});
</script>
</body>
</html>
