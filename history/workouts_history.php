<?php
session_start();
require_once '../includes/auth.php';
if (!Auth::isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}
$userId = Auth::getUserId();
$db = Database::getInstance();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia Treningów</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: white;
            min-height: 100vh;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            background: white;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background: linear-gradient(135deg, #ffb347 0%, #ffe5b4 100%) !important;
            border: none !important;
            border-radius: 16px 16px 0 0 !important;
            padding: 1.5rem !important;
        }

        .card-header h3 {
            color: #000 !important;
            font-weight: 600 !important;
            margin: 0 !important;
        }

        .table-scroll-wrapper {
            max-height: 600px;
            overflow-y: auto;
            overflow-x: hidden;
            border-radius: 10px;
        }

        .table {
            margin-bottom: 0;
            table-layout: fixed;
            width: 100%;
        }

        .table td, .table th {
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
        }

        .table thead th {
            border-bottom: 2px solid #f77f00;
            color: #f77f00;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover:not(.workout-exercise-row) {
            background-color: rgba(247, 127, 0, 0.1) !important;
            transform: scale(1.01);
        }

        .workout-row {
            cursor: pointer;
        }

        .workout-exercise-row {
            background-color: #f8f9fa !important;
        }

        .workout-exercise-row:hover {
            background-color: #e9ecef !important;
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
            transform: translateY(-2px);
        }

        .btn-danger {
            background: linear-gradient(135deg, #d62828, #003049);
            border: none;
            font-weight: 500;
            transition: all 0.3s ease;
            color: white;
        }

        .btn-danger:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(214, 40, 40, 0.4);
        }
    </style>
</head>
<body class="py-5">
    <div class="container mt-5">
        <a href="../user/dashboard.php" class="btn btn-light mb-4 shadow-sm" style="border-radius: 10px;">
            ← Powrót do Dashboard
        </a>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header">
                        <h3>💪 Historia Treningów</h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4 g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Szukaj po nazwie:</label>
                                <input type="text" id="searchName" class="form-control" placeholder="Wpisz typ treningu..." style="border-radius: 10px;">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Data od:</label>
                                <input type="date" id="dateFrom" class="form-control" style="border-radius: 10px;">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Data do:</label>
                                <input type="date" id="dateTo" class="form-control" style="border-radius: 10px;">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-success w-100" onclick="clearSearch()" style="border-radius: 10px; background: linear-gradient(135deg, #f77f00, #fcbf49) !important; border: none !important; color: #003049 !important;">Wyczyść</button>
                            </div>
                        </div>
                        <div class="table-scroll-wrapper">
                            <table class="table table-striped" id="workoutsTable">
                                <thead class="sticky-top bg-light">
                                    <tr>
                                        <th>Data</th>
                                        <th>Typ</th>
                                        <th>Czas/Info</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let allWorkoutsData = [];

        async function loadAllWorkouts() {
            try {
                const res = await fetch('../api/workouts.php', { credentials: 'same-origin' });
                const data = await res.json();
                allWorkoutsData = (data && data.data) ? data.data : [];
                console.log('All workouts:', allWorkoutsData);
                renderWorkouts(allWorkoutsData);
            } catch (err) {
                console.error('Błąd ładowania treningów:', err);
            }
        }

        function renderWorkouts(workouts) {
            const tbody = document.querySelector('#workoutsTable tbody');
            if (!workouts || workouts.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Brak treningów</td></tr>';
            } else {
                let html = '';
                workouts.forEach(w => {
                    const isStrengthTraining = w.type === 'Trening Siłowy';
                    let exercises = [];
                    if (isStrengthTraining && w.notes) {
                        try {
                            exercises = JSON.parse(w.notes);
                        } catch (e) {
                            console.error('Error parsing exercises:', e);
                            exercises = [];
                        }
                    }

                    const date = new Date(w.date).toLocaleDateString('pl-PL');
                    
                    html += `
                        <tr class="workout-row" data-workout-id="${w.id}" ${isStrengthTraining ? `data-has-exercises="${exercises.length}"` : ''}>
                            <td><strong>${date}</strong></td>
                            <td>
                                <span class="workout-toggle" style="${!isStrengthTraining ? 'cursor: default; color: transparent; width: 20px; display: inline-block;' : 'cursor: pointer; user-select: none; margin-right: 0.5rem; width: 20px; display: inline-block; font-weight: bold; color: #333;'}" data-workout-id="${w.id}">
                                    ${isStrengthTraining ? '>' : ''}
                                </span>
                                ${w.type || '-'}
                            </td>
                            <td>${w.duration ? w.duration + ' min' : (w.reps ? w.reps + ' reps' : '-')}</td>
                        </tr>
                    `;

                    if (exercises.length > 0) {
                        exercises.forEach(ex => {
                            html += `
                                <tr class="workout-exercise-row" data-workout-id="${w.id}" style="display:none;">
                                    <td></td>
                                    <td class="ps-5 py-2"><small><strong>${ex.name}</strong></small></td>
                                    <td class="py-2"><small>${ex.sets}x${ex.reps}${ex.weight ? ' @ ' + ex.weight + 'kg' : ''}</small></td>
                                </tr>
                            `;
                        });
                    }
                });
                tbody.innerHTML = html;

                document.querySelectorAll('.workout-toggle').forEach(toggle => {
                    toggle.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const workoutId = this.dataset.workoutId;
                        const isExpanded = this.textContent.trim() === 'v';
                        const rows = document.querySelectorAll(`.workout-exercise-row[data-workout-id="${workoutId}"]`);

                        if (isExpanded) {
                            this.textContent = '>';
                            rows.forEach(r => r.style.display = 'none');
                        } else {
                            this.textContent = 'v';
                            rows.forEach(r => r.style.display = '');
                        }
                    });
                });
            }
        }

        function filterWorkouts() {
            const searchName = document.getElementById('searchName').value.toLowerCase();
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;

            const filtered = allWorkoutsData.filter(w => {
                const matchName = !searchName || (w.type && w.type.toLowerCase().includes(searchName));
                const workoutDate = new Date(w.date).toISOString().split('T')[0];
                const matchDateFrom = !dateFrom || workoutDate >= dateFrom;
                const matchDateTo = !dateTo || workoutDate <= dateTo;

                return matchName && matchDateFrom && matchDateTo;
            });

            renderWorkouts(filtered);
        }

        function clearSearch() {
            document.getElementById('searchName').value = '';
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            renderWorkouts(allWorkoutsData);
        }

        document.getElementById('searchName').addEventListener('input', filterWorkouts);
        document.getElementById('dateFrom').addEventListener('change', filterWorkouts);
        document.getElementById('dateTo').addEventListener('change', filterWorkouts);

        loadAllWorkouts();
    </script>
</body>
</html>
