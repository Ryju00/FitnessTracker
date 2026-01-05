<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!Auth::isLoggedIn() || Auth::getRole() !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$userEmail = $_SESSION['email'] ?? 'admin@example.com';
$username = explode('@', $userEmail)[0];

$db = Database::getInstance();

$totalUsers = $db->getConnection()->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$totalWorkouts = $db->getConnection()->query("SELECT COUNT(*) FROM workouts")->fetchColumn();
$totalMeals = $db->getConnection()->query("SELECT COUNT(*) FROM nutrition_logs")->fetchColumn();
$totalCaloriesConsumed = $db->getConnection()->query("SELECT SUM(calories) FROM nutrition_logs")->fetchColumn() ?? 0;

$workouts = $db->getConnection()->query("SELECT type, duration FROM workouts")->fetchAll();
$totalCaloriesBurned = 0;
foreach ($workouts as $w) {
    if ($w['type'] === 'Trening Siłowy') {
        $totalCaloriesBurned += (int)$w['duration'] * 6;
    } else {
        $multiplier = 0;
        if (strpos($w['type'], 'Bieganie') !== false) $multiplier = 11;
        elseif (strpos($w['type'], 'Rower') !== false) $multiplier = 8;
        elseif (strpos($w['type'], 'Marsz') !== false) $multiplier = 4;
        elseif (strpos($w['type'], 'Wiosłowanie') !== false) $multiplier = 9;
        elseif (strpos($w['type'], 'Pływanie') !== false) $multiplier = 10;
        $totalCaloriesBurned += (int)$w['duration'] * $multiplier;
    }
}

$activeUsersToday = $db->getConnection()->query("SELECT COUNT(DISTINCT user_id) FROM workouts WHERE DATE(date) = CURDATE()")->fetchColumn();

$recentUsers = $db->getConnection()->query("SELECT id, email, created_at, role FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

$recentWorkouts = $db->getConnection()->query("
    SELECT w.*, u.email 
    FROM workouts w 
    JOIN users u ON w.user_id = u.id 
    ORDER BY w.date DESC, w.created_at DESC 
    LIMIT 10
")->fetchAll();

$activityStats = $db->getConnection()->query("
    SELECT DATE(date) as workout_date, COUNT(*) as workout_count
    FROM workouts 
    WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(date)
    ORDER BY workout_date ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administratora - Fitness Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: #f5f7fa;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            display: none;
        }

        body::after {
            content: '';
            display: none;
        }

        @keyframes subtleFloat {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(30px, 30px) scale(1.1); }
        }

        .gradient-text {
            background: linear-gradient(to right, #ffd700 0%, #ff8c00 50%, #ff4500 100%);
            background-size: 400% 400%;
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            font-size: clamp(1.5rem, 5vw, 2.5rem);
            font-weight: bold;
            animation: gradient 3s ease infinite;
        }

        @keyframes gradient {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: white;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }

        .stat-card {
            border-radius: 15px;
            padding: 2rem 1.5rem;
            margin-bottom: 0;
            height: 100%;
            min-height: 160px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        .stat-card h3 {
            font-size: 2.8rem;
            font-weight: 800;
            margin: 0;
            line-height: 1.2;
        }

        .stat-card p {
            margin: 0.5rem 0 0.2rem 0;
            opacity: 0.95;
            font-size: 0.95rem;
            font-weight: 500;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .stat-card small {
            opacity: 0.85;
            font-size: 0.8rem;
            margin-top: 0.3rem;
        }

        .row.g-4.mb-5:first-of-type > [class*="col-"] {
            display: flex;
        }

        .navbar {
            backdrop-filter: blur(10px);
            background: rgba(0, 0, 0, 0.8) !important;
        }

        .navbar-brand {
            color: #f77f00 !important;
            font-weight: 700;
            font-size: 1.4rem;
        }

        .page-title {
            color: #2d3748;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
        }

        .main-container {
            background: transparent;
            border-radius: 20px;
            padding: 2.5rem;
            margin-top: 2rem;
            overflow: hidden;
            min-height: calc(100vh - 100px);
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
        }

        .badge-admin {
            background: linear-gradient(135deg, #f77f00 0%, #fcbf49 100%);
            color: #003049 !important;
        }

        .badge-user {
            background: #d62828;
            color: white !important;
        }

        .card-header {
            background: white !important;
            border: none !important;
            border-radius: 15px 15px 0 0 !important;
        }

        .card-header h5 {
            color: #2d3748 !important;
            font-weight: 600;
            margin: 0 !important;
        }

        .btn-outline-danger {
            color: #d62828 !important;
            border-color: #d62828 !important;
            background: transparent;
        }

        .btn-outline-danger:hover {
            background: #d62828 !important;
            color: white !important;
        }

        .btn-success {
            background: linear-gradient(135deg, #f77f00, #fcbf49) !important;
            border: none !important;
            color: #003049 !important;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #f77f00, #fcbf49) !important;
            color: white !important;
            text-decoration: none;
        }

        .btn-danger {
            background: #d62828 !important;
            border: none !important;
            color: white !important;
        }

        .btn-danger:hover {
            background: #c41c1c !important;
            color: white !important;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #f77f00, #fcbf49) !important;
            border: none !important;
            color: #003049 !important;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #f77f00, #fcbf49) !important;
            color: white !important;
            text-decoration: none;
        }
    </style>
</head>
<body class="min-vh-100 py-4">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand gradient-text">Admin Panel</a>
            <div class="navbar-nav ms-auto">
                <span class="nav-link" style="color: #f77f00 !important; font-weight: 600;"><?= htmlspecialchars($username) ?></span>
                <a href="../auth/logout.php" class="nav-link">Wyloguj</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5 pt-4">
        <div class="main-container">
            <h1 class="page-title text-center mb-4">Panel Zarządzania Systemem</h1>

        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card stat-card">
                    <h2 class="text-primary"><?= $totalUsers ?></h2>
                    <p>👥 Użytkowników</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <h2 class="text-success"><?= $totalWorkouts ?></h2>
                    <p>💪 Treningów</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <h2 class="text-info"><?= $totalMeals ?></h2>
                    <p>🍽️ Posiłków</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <h2 class="text-warning"><?= $activeUsersToday ?></h2>
                    <p>✅ Aktywnych dziś</p>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="m-0">Aktywność użytkowników (7 dni)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="activityChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="m-0">Ostatnio zarejestrowani</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-scroll-wrapper">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Email</th>
                                        <th>Rola</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentUsers as $user): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td><span class="badge badge-<?= $user['role'] ?>"><?= $user['role'] ?></span></td>
                                            <td><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="m-0">Ostatnie treningi</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-scroll-wrapper">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Użytkownik</th>
                                        <th>Typ</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentWorkouts as $workout): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($workout['email']) ?></td>
                                            <td><?= htmlspecialchars($workout['type']) ?></td>
                                            <td><?= date('d.m.Y', strtotime($workout['date'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="m-0">Zarządzanie</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex gap-3">
                            <a href="manage_users.php" class="btn btn-primary btn-lg flex-grow-1">Zarządzaj użytkownikami</a>
                            <button class="btn btn-success btn-lg flex-grow-1" onclick="exportData()">Eksportuj dane (CSV)</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const activityData = <?= json_encode($activityStats) ?>;
        const activityLabels = activityData.map(d => new Date(d.workout_date).toLocaleDateString('pl-PL', {day: '2-digit', month: '2-digit'}));
        const activityCounts = activityData.map(d => d.workout_count);

        new Chart(document.getElementById('activityChart'), {
            type: 'line',
            data: {
                labels: activityLabels,
                datasets: [{
                    label: 'Treningi',
                    data: activityCounts,
                    borderColor: '#f77f00',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grace: '10%',
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                if (Number.isInteger(value)) {
                                    return value;
                                }
                            }
                        }
                    }
                }
            }
        });

        function exportData() {
            window.location.href = 'export.php';
        }
    </script>
</body>
</html>
