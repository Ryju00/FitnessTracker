<?php
session_start();
require_once '../includes/auth.php';
if (!Auth::isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

if (Auth::getRole() === 'admin') {
    header('Location: ../admin/admin.php');
    exit();
}

$today = date('Y-m-d');
$userId = Auth::getUserId();
$db = Database::getInstance();

$stmt = $db->getConnection()->prepare("
    SELECT SUM(calories) as total_cal, SUM(protein) as total_pro, 
           SUM(carbs) as total_carbs, SUM(fats) as total_fats, COUNT(*) as meals_count 
    FROM nutrition_logs WHERE user_id = ? AND date = ?
");
$stmt->execute([$userId, $today]);
$todaySummary = $stmt->fetch() ?: ['total_cal' => 0, 'total_pro' => 0, 'total_carbs' => 0, 'total_fats' => 0, 'meals_count' => 0];

$stmtUser = $db->getConnection()->prepare("SELECT data_info FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$userRow = $stmtUser->fetch();

$dataInfo = $userRow ? $userRow['data_info'] : '';
$parts = explode('|', $dataInfo);
$age = isset($parts[0]) ? (int)$parts[0] : 0;
$gender = $parts[1] ?? '';
$height = isset($parts[2]) ? (float)$parts[2] : 0;
$weight = isset($parts[3]) ? (float)$parts[3] : 0;
$activity = isset($parts[4]) ? (int)$parts[4] : 0;

$bmr = 0;
if ($gender === 'male') {
    $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) + 5;
} else {
    $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) - 161;
}

$palLevels = [1.2, 1.4, 1.6, 1.75, 1.9, 2.2];
$pal = isset($palLevels[$activity]) ? $palLevels[$activity] : 1.5;

$baseCaloriesLimit = (int)round($bmr * $pal);

$stmtWorkouts = $db->getConnection()->prepare("
    SELECT type, duration FROM workouts WHERE user_id = ? AND date = ?
");
$stmtWorkouts->execute([$userId, $today]);
$todayWorkouts = $stmtWorkouts->fetchAll();

$caloriesBurned = 0;
$activityCalories = [
    'Bieganie' => 11,
    'Rower' => 8,
    'Spacer' => 4,
    'Wioślarz' => 9,
    'Pływanie' => 10
];

foreach ($todayWorkouts as $w) {
    if (strpos($w['type'], 'Cardio:') === 0) {
        $defaultKcal = 8;
        $kcalPerMin = $defaultKcal;
        foreach ($activityCalories as $activity => $kpm) {
            if (strpos($w['type'], $activity) !== false) {
                $kcalPerMin = $kpm;
                break;
            }
        }
        $caloriesBurned += (int)round($kcalPerMin * $w['duration']);
    } elseif ($w['type'] === 'Trening Siłowy') {
        $caloriesBurned += (int)round(6 * $w['duration']);
    }
}


$caloriesLimit = $baseCaloriesLimit + $caloriesBurned;

$userEmail = $_SESSION['email'] ?? 'user@example.com';
$username = explode('@', $userEmail)[0];
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness Tracker - Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            padding-top: 76px;
        }

        .gradient-text {
            background: linear-gradient(135deg, #f77f00 0%, #fcbf49 100%);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            font-size: clamp(1.5rem, 4vw, 2rem);
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            background: white;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #f0f0f0;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: #2d3748;
            font-size: 1rem;
        }

        .stats-card {
            border-radius: 16px;
            padding: 1.75rem 1.25rem;
            margin-bottom: 0;
            height: 100%;
            min-height: 140px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            background: white;
            border: 2px solid transparent;
        }

        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .stats-card.calories { border-color: #f77f00; }
        .stats-card.protein { border-color: #fcbf49; }
        .stats-card.carbs { border-color: #d62828; }
        .stats-card.fats { border-color: #003049; }

        .stats-card h3 {
            font-size: 2.25rem;
            font-weight: 700;
            margin: 0;
            line-height: 1.2;
        }

        .stats-card.calories h3 { color: #f77f00; }
        .stats-card.protein h3 { color: #fcbf49; }
        .stats-card.carbs h3 { color: #d62828; }
        .stats-card.fats h3 { color: #003049; }

        .stats-card p {
            margin: 0.5rem 0 0 0;
            opacity: 0.8;
            font-size: 0.9rem;
            font-weight: 500;
            color: #4a5568;
        }

        .stats-card small {
            opacity: 0.7;
            font-size: 0.8rem;
            margin-top: 0.25rem;
            color: #718096;
        }

        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        .stats-card h3 {
            font-size: 2.8rem;
            font-weight: 800;
            margin: 0;
            line-height: 1.2;
        }

        .stats-card p {
            margin: 0.5rem 0 0.2rem 0;
            opacity: 0.95;
            font-size: 0.95rem;
            font-weight: 500;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .stats-card small {
            opacity: 0.85;
            font-size: 0.8rem;
            margin-top: 0.3rem;
        }

        .row.g-4.mb-5:first-of-type > [class*="col-"] {
            display: flex;
        }

        .main-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 2.5rem;
            margin-top: 2rem;
            overflow: hidden;
        }

        .mx-n3 {
            margin-left: -1rem !important;
            margin-right: -1rem !important;
        }

        .mx-n3 > [class*="col-"] {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .navbar {
            backdrop-filter: blur(10px);
            background: rgba(0, 0, 0, 0.8) !important;
        }

        .btn {
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid;
        }

        .btn-primary {
            background: linear-gradient(135deg, #f77f00 0%, #fcbf49 100%);
            border-color: #f77f00;
            color: #003049;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(247, 127, 0, 0.3);
            border-color: #f77f00;
        }

        .btn-outline-primary {
            color: #f77f00 !important;
            border-color: #f77f00 !important;
            background: white;
        }

        .btn-outline-primary:hover {
            background: #f77f00 !important;
            color: white !important;
            transform: translateY(-2px);
        }

        .btn-outline-danger {
            border-radius: 8px;
            padding: 0.5rem 1.25rem;
            color: #d62828 !important;
            border-color: #d62828 !important;
        }

        .btn-outline-danger:hover {
            background: #d62828 !important;
            color: white !important;
        }

        .btn-success {
            background: linear-gradient(135deg, #f77f00, #fcbf49) !important;
            border: none !important;
            color: #003049 !important;
            font-weight: 600;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #f77f00, #fcbf49) !important;
            color: white !important;
            transition: all 0.3s ease;
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

        .navbar {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .fw-500 { font-weight: 500; }
        .fw-600 { font-weight: 600; }

        .table { 
            margin-bottom: 0; 
        }

        .table thead th {
            border-bottom: 2px solid #e9ecef;
            color: #4a5568;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 1rem;
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            color: #2d3748;
        }

        .table tbody tr:hover {
            background-color: #f7fafc;
        }

        .btn-action:hover {
            transform: scale(1.05);
        }

        h1.page-title {
            color: #2d3748;
            font-weight: 800;
            margin-bottom: 2rem;
        }

        .table-scroll-wrapper {
            max-height: 300px;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .table-scroll-wrapper::-webkit-scrollbar {
            width: 8px;
        }

        .table-scroll-wrapper::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .table-scroll-wrapper::-webkit-scrollbar-thumb {
            background: #f77f00;
            border-radius: 10px;
        }

        .table-scroll-wrapper::-webkit-scrollbar-thumb:hover {
            background: #fcbf49;
        }

        .table-scroll-wrapper table {
            margin-bottom: 0;
        }

        .row.g-4 > [class*="col-"] {
            display: flex;
        }

        .row.g-4 > [class*="col-"] > .card {
            width: 100%;
            display: flex;
            flex-direction: column;
        }

        .row.g-4 > [class*="col-"] > .card > .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 400px;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand gradient-text fw-bold">Fitness Tracker</a>
            <div class="navbar-nav ms-auto d-flex align-items-center gap-3">
                <a href="edit_profile.php" class="nav-link" style="color: #f77f00 !important; font-weight: 600;"><?= htmlspecialchars($username) ?></a>
                <a href="../auth/logout.php" class="btn btn-sm btn-outline-danger">Wyloguj</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="text-center mb-4">
            <h1 class="h2 fw-bold text-dark mb-2">Strona główna</h1>
            <p class="text-muted">Dzisiaj: <?= date('d.m.Y') ?></p>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="stats-card text-center calories">
                    <h3><?= number_format($todaySummary['total_cal']) ?></h3>
                    <p>Kalorie</p>
                    <small>z <?= number_format($caloriesLimit) ?> kcal</small>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card text-center protein">
                    <h3><?= number_format($todaySummary['total_pro']) ?>g</h3>
                    <p>Białko</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card text-center carbs">
                    <h3><?= number_format($todaySummary['total_carbs']) ?>g</h3>
                    <p>Węglowodany</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card text-center fats">
                    <h3><?= number_format($todaySummary['total_fats']) ?>g</h3>
                    <p>Tłuszcze</p>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="m-0">Dodaj Aktywność</h5>
                    </div>
                    <div class="card-body text-center d-flex align-items-center justify-content-center">
                        <div class="w-100">
                            <div class="d-flex flex-column gap-3 px-4">
                                <a href="../activities/cardio.php" class="btn btn-lg btn-outline-primary py-3 fs-5 fw-600">
                                    Cardio
                                </a>
                                <a href="../activities/workout.php" class="btn btn-lg btn-primary py-3 fs-5 fw-600">
                                    Trening Siłowy
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="m-0">Dzisiejsze Treningi</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-scroll-wrapper">
                                <table class="table table-striped" id="workoutsTable">
                                    <thead>
                                        <tr>
                                            <th>Typ</th>
                                            <th>Czas</th>
                                            <th>Akcja</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="m-0">Dodaj Posiłek</h5>
                        </div>
                        <div class="card-body">
                            <form id="nutritionForm">
                                <div class="mb-3">
                                    <input type="text" id="nutritionName" class="form-control" placeholder="Nazwa posiłku" required>
                                </div>
                                <div class="mb-3">
                                    <input type="number" id="nutritionCalories" class="form-control" placeholder="Kalorie (kcal)" min="0" required>
                                </div>
                                <div class="mb-3">
                                    <input type="number" id="nutritionProtein" class="form-control" placeholder="Białko (g)" min="0" step="0.1" required>
                                </div>
                                <div class="mb-3">
                                    <input type="number" id="nutritionCarbs" class="form-control" placeholder="Węglowodany (g)" min="0" step="0.1" required>
                                </div>
                                <div class="mb-3">
                                    <input type="number" id="nutritionFats" class="form-control" placeholder="Tłuszcze (g)" min="0" step="0.1" required>
                                </div>
                                <button type="submit" class="btn btn-success w-100">Dodaj Posiłek</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="m-0">Dzisiejsze Posiłki</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-scroll-wrapper">
                                <table class="table table-striped" id="nutritionTable">
                                    <thead>
                                        <tr>
                                            <th>Posiłek</th>
                                            <th>Kcal</th>
                                            <th>B</th>
                                            <th>W</th>
                                            <th>T</th>
                                            <th>Akcja</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card" style="height: 180px;">
                    <div class="card-header" style="padding: 0.75rem 1rem !important; border-bottom: 1px solid #e2e8f0;">
                        <h5 class="m-0" style="font-size: 0.95rem; font-weight: 600; color: #2d3748;">Waga podniesiona (7 dni)</h5>
                    </div>
                    <div class="card-body text-center" style="padding: 1.75rem 1rem !important;">
                        <div style="margin-top: 0.5rem;">
                            <h2 id="totalWeeklyWeight" style="color: #f77f00; font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">0 kg</h2>
                            <small class="text-muted" style="font-size: 0.85rem; display: block;">Suma wagi ze wszystkich ćwiczeń</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card" style="height: 180px;">
                    <div class="card-header" style="padding: 0.75rem 1rem !important; border-bottom: 1px solid #e2e8f0;">
                        <h5 class="m-0" style="font-size: 0.95rem; font-weight: 600; color: #2d3748;">Spalone kalorie (7 dni)</h5>
                    </div>
                    <div class="card-body text-center" style="padding: 1.75rem 1rem !important;">
                        <div style="margin-top: 0.5rem;">
                            <h2 id="totalBurnedCalories" style="color: #ff6b35; font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">0 kcal</h2>
                            <small class="text-muted" style="font-size: 0.85rem; display: block;">Cardio + Treningi</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-2">
            <div class="col-md-6">
                <a href="../history/workouts_history.php" class="text-decoration-none" style="display: block; height: 100%;">
                    <div class="card" style="height: 310px; border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); background: linear-gradient(135deg, #ffb347 0%, #ffe5b4 100%); transition: all 0.3s ease; cursor: pointer;">
                        <div class="card-body text-center text-dark d-flex flex-column align-items-center justify-content-center" style="padding: 1.5rem;">
                            <div style="font-size: 2.5rem; margin-bottom: 0.75rem; animation: float 3s ease-in-out infinite;">💪</div>
                            <h5 class="card-title mb-2" style="font-size: 1.1rem; color: #000; font-weight: 600;">Historia Treningów</h5>
                            <p class="card-text mb-0" style="opacity: 0.9; font-size: 0.85rem; color: #333;">Przejdź do pełnej historii</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-6">
                <div class="card" style="height: 310px;">
                    <div class="card-header" style="padding: 0.75rem 1rem !important; border-bottom: 1px solid #e2e8f0;">
                        <h5 class="m-0" style="font-size: 0.95rem; font-weight: 600;">Spalone kcal (7 dni)</h5>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center" style="padding: 1rem !important; height: 265px;">
                        <canvas id="burnedCaloriesChart" height="150"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <a href="../history/nutrition_history.php" class="text-decoration-none" style="display: block; height: 100%;">
                    <div class="card" style="height: 310px; border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); background: linear-gradient(135deg, #ffb347 0%, #ffe5b4 100%); transition: all 0.3s ease; cursor: pointer;">
                        <div class="card-body text-center text-dark d-flex flex-column align-items-center justify-content-center" style="padding: 1.5rem;">
                            <div style="font-size: 2.5rem; margin-bottom: 0.75rem; animation: float 3s ease-in-out infinite;">🍽️</div>
                            <h5 class="card-title mb-2" style="font-size: 1.1rem; color: #000; font-weight: 600;">Historia Posiłków</h5>
                            <p class="card-text mb-0" style="opacity: 0.9; font-size: 0.85rem; color: #333;">Przejdź do pełnej historii</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-6">
                <div class="card" style="height: 310px;">
                    <div class="card-header" style="padding: 0.75rem 1rem !important; border-bottom: 1px solid #e2e8f0;">
                        <h5 class="m-0" style="font-size: 0.95rem; font-weight: 600;">Zjedzone kcal (7 dni)</h5>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center" style="padding: 1rem !important; height: 265px;">
                        <canvas id="consumedCaloriesChart" height="150"></canvas>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let workoutChart, nutritionChart, burnedCaloriesChart, consumedCaloriesChart;
        let todayDate = '<?= $today ?>';

        async function loadWorkouts() {
            console.log('Loading workouts for date:', todayDate);
            try {
                const res = await fetch('../api/workouts.php?date=' + todayDate, { credentials: 'same-origin' });
                console.log('API response status:', res.status);
                const data = await res.json();
                console.log('API response data:', data);
                const workouts = (data && data.data) ? data.data : [];
                console.log('Parsed workouts:', workouts);
                const tbody = document.querySelector('#workoutsTable tbody');
                if (!tbody) {
                    console.error('Workouts table not found!');
                    return;
                }
                if (!workouts || workouts.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Brak treningów dzisiaj</td></tr>';
                } else {
                    let html = '';
                    workouts.forEach(w => {
                        const isStrengthTraining = w.type === 'Trening Siłowy';
                        let exercises = [];
                        if (isStrengthTraining && w.notes) {
                            try {
                                exercises = JSON.parse(w.notes);
                                console.log('Exercises for workout ' + w.id + ':', exercises);
                            } catch (e) {
                                console.error('Error parsing exercises for workout ' + w.id + ':', e);
                                exercises = [];
                            }
                        }
                        
                        html += `
                            <tr class="workout-row" data-workout-id="${w.id}" ${isStrengthTraining ? `data-has-exercises="${exercises.length}"` : ''}>
                                <td>
                                    <span class="workout-toggle" style="${!isStrengthTraining ? 'cursor: default; color: transparent; width: 20px; display: inline-block;' : 'cursor: pointer; user-select: none; margin-right: 0.5rem; width: 20px; display: inline-block; font-weight: bold; color: #333;'}" data-workout-id="${w.id}">
                                        ${isStrengthTraining ? '>' : ''}
                                    </span>
                                    ${w.type || '-'}
                                </td>
                                <td>${w.duration ? w.duration + ' min' : (w.reps ? w.reps + ' reps' : '-')}</td>
                                <td><button class="btn btn-sm btn-danger" onclick="deleteWorkout(${w.id})">Usuń</button></td>
                            </tr>
                        `;
                        
                        if (exercises.length > 0) {
                            exercises.forEach(ex => {
                                html += `
                                    <tr class="workout-exercise-row" data-workout-id="${w.id}" style="display:none; background-color: #f8f9fa;">
                                        <td class="ps-5 py-2"><small><strong>${ex.name}</strong></small></td>
                                        <td class="py-2"><small>${ex.sets}x${ex.reps}${ex.weight ? ' @ ' + ex.weight + 'kg' : ''}</small></td>
                                        <td></td>
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
            } catch (err) {
                console.error('Błąd ładowania treningów:', err);
            }
        }

        async function deleteWorkout(id) {
            if (confirm('Usunąć?')) {
                await fetch('../api/workouts.php?id=' + id, {
                    method: 'DELETE',
                    credentials: 'same-origin'
                });
                loadWorkouts();
                location.reload();
            }
        }

        document.getElementById('nutritionForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                console.log('Submitting nutrition form...');
                const formData = new FormData();
                formData.append('action', 'create');
                formData.append('name', document.getElementById('nutritionName').value);
                formData.append('calories', document.getElementById('nutritionCalories').value);
                formData.append('protein', document.getElementById('nutritionProtein').value || 0);
                formData.append('carbs', document.getElementById('nutritionCarbs').value || 0);
                formData.append('fats', document.getElementById('nutritionFats').value || 0);

                console.log('Form data:', {
                    name: document.getElementById('nutritionName').value,
                    calories: document.getElementById('nutritionCalories').value,
                    protein: document.getElementById('nutritionProtein').value,
                    carbs: document.getElementById('nutritionCarbs').value,
                    fats: document.getElementById('nutritionFats').value
                });

                const res = await fetch('../api/nutrition.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                console.log('Nutrition POST response status:', res.status);
                const result = await res.json();
                console.log('Nutrition POST response:', result);
                if (result.success) {
                    console.log('Success! Reloading nutrition...');
                    loadNutrition();
                    document.getElementById('nutritionForm').reset();
                    document.querySelectorAll('[id^=nutrition]').forEach(el => el.value = '');
                } else {
                    alert('Błąd: ' + (result.message || 'Nie udało się dodać posiłku'));
                }
            } catch (err) {
                console.error('Błąd dodawania posiłku:', err);
                alert('Błąd sieci: ' + err.message);
            }
        });

        async function loadNutrition() {
            console.log('Loading nutrition for date:', todayDate);
            try {
                const res = await fetch('../api/nutrition.php?date=' + todayDate, { credentials: 'same-origin' });
                console.log('API response status:', res.status);
                const data = await res.json();
                console.log('API response data:', data);
                const nutrition = (data && data.data) ? data.data : [];
                console.log('Parsed nutrition:', nutrition);
                const tbody = document.querySelector('#nutritionTable tbody');
                if (!nutrition || nutrition.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Brak posiłków dzisiaj</td></tr>';
                } else {
                    tbody.innerHTML = nutrition.map(n => `
                        <tr>
                            <td>${n.meal_name || n.name || '-'}</td>
                            <td>${n.calories || 0}</td>
                            <td>${n.protein || 0}</td>
                            <td>${n.carbs || 0}</td>
                            <td>${n.fats || 0}</td>
                            <td><button class="btn btn-sm btn-danger" onclick="deleteNutrition(${n.id})">Usuń</button></td>
                        </tr>
                    `).join('');
                }
            } catch (err) {
                console.error('Błąd ładowania posiłków:', err);
            }
        }

        async function deleteNutrition(id) {
            if (confirm('Usunąć?')) {
                await fetch('../api/nutrition.php?id=' + id, {
                    method: 'DELETE',
                    credentials: 'same-origin'
                });
                loadNutrition();
                location.reload();
            }
        }

        async function loadWeeklyStats() {
            try {
                const res = await fetch('../api/weekly_stats.php', { credentials: 'same-origin' });
                const data = await res.json();
                if (data.success) {
                    let weightDisplay;
                    if (data.totalWeight >= 1000) {
                        const tons = Math.floor(data.totalWeight * 10 / 1000) / 10;
                        weightDisplay = tons.toFixed(1) + ' t';
                    } else {
                        weightDisplay = data.totalWeight + ' kg';
                    }
                    document.getElementById('totalWeeklyWeight').textContent = weightDisplay;
                    document.getElementById('totalBurnedCalories').textContent = data.totalCalories + ' kcal';
                }
            } catch (err) {
                console.error('Błąd ładowania statystyk tygodnia:', err);
            }
        }

        async function loadCaloriesCharts() {
            try {
                const wRes = await fetch('../api/workouts.php', { credentials: 'same-origin' });
                const wData = await wRes.json();
                const workouts = (wData && wData.data) ? wData.data : [];

                const nRes = await fetch('../api/nutrition.php', { credentials: 'same-origin' });
                const nData = await nRes.json();
                const nutrition = (nData && nData.data) ? nData.data : [];

                const today = new Date(todayDate);
                const dates = [];
                const burnedData = [];
                const consumedData = [];

                for (let i = 6; i >= 0; i--) {
                    const date = new Date(today);
                    date.setDate(date.getDate() - i);
                    const dateStr = date.toISOString().split('T')[0];
                    const dateLabel = date.toLocaleDateString('pl-PL', { weekday: 'short', month: 'short', day: 'numeric' });
                    dates.push(dateLabel);

                    let burned = 0;
                    workouts.forEach(w => {
                        if (w.date === dateStr) {
                            if (w.type.includes('Cardio:')) {
                                const kcalPerMin = {
                                    'Bieganie': 11, 'Rower': 8, 'Spacer': 4,
                                    'Wioślarz': 9, 'Pływanie': 10
                                };
                                let kpm = 8;
                                Object.entries(kcalPerMin).forEach(([activity, val]) => {
                                    if (w.type.includes(activity)) kpm = val;
                                });
                                burned += Math.round(kpm * w.duration);
                            } else if (w.type === 'Trening Siłowy') {
                                burned += Math.round(6 * w.duration);
                            }
                        }
                    });
                    burnedData.push(burned);

                    let consumed = 0;
                    nutrition.forEach(n => {
                        if (n.date === dateStr) {
                            consumed += n.calories || 0;
                        }
                    });
                    consumedData.push(consumed);
                }

                const burnedCtx = document.getElementById('burnedCaloriesChart');
                if (burnedCtx && burnedCaloriesChart) burnedCaloriesChart.destroy();
                if (burnedCtx) {
                    burnedCaloriesChart = new Chart(burnedCtx.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: dates,
                            datasets: [{
                                label: 'Spalone kcal',
                                data: burnedData,
                                borderColor: '#ff6b35',
                                backgroundColor: 'rgba(255, 107, 53, 0.1)',
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: '#ff6b35',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 5
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: { display: true, position: 'top' }
                            },
                            scales: {
                                y: { beginAtZero: true, min: 0 }
                            }
                        }
                    });
                }

                const consumedCtx = document.getElementById('consumedCaloriesChart');
                if (consumedCtx && consumedCaloriesChart) consumedCaloriesChart.destroy();
                if (consumedCtx) {
                    consumedCaloriesChart = new Chart(consumedCtx.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: dates,
                            datasets: [{
                                label: 'Zjedzone kcal',
                                data: consumedData,
                                borderColor: '#f77f00',
                                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: '#f77f00',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 5
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: { display: true, position: 'top' }
                            },
                            scales: {
                                y: { beginAtZero: true, min: 0 }
                            }
                        }
                    });
                }
            } catch (err) {
                console.error('Błąd ładowania wykresów kalorii:', err);
            }
        }

        if (document.querySelector('#workoutsTable')) loadWorkouts();
        loadNutrition();
        loadWeeklyStats();
        loadCaloriesCharts();
    </script>
</body>

</html>[file:28]