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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $userId = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
    if ($userId) {
        $conn = $db->getConnection();
        $conn->beginTransaction();
        try {
            $stmt = $conn->prepare("DELETE FROM workouts WHERE user_id = ?");
            $stmt->execute([$userId]);
            $stmt = $conn->prepare("DELETE FROM nutrition_logs WHERE user_id = ?");
            $stmt->execute([$userId]);
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
            $stmt->execute([$userId]);
            $conn->commit();
            $success = "Użytkownik został usunięty pomyślnie!";
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Błąd podczas usuwania użytkownika!";
        }
    }
}

$searchEmail = $_GET['search_email'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$ageMin = $_GET['age_min'] ?? '';
$ageMax = $_GET['age_max'] ?? '';
$heightMin = $_GET['height_min'] ?? '';
$heightMax = $_GET['height_max'] ?? '';
$weightMin = $_GET['weight_min'] ?? '';
$weightMax = $_GET['weight_max'] ?? '';
$gender = $_GET['gender'] ?? '';

$sql = "SELECT id, email, role, created_at, email_verified, data_info FROM users WHERE 1=1";
$params = [];

if (!empty($searchEmail)) {
    $sql .= " AND email LIKE ?";
    $params[] = "%$searchEmail%";
}

if (!empty($dateFrom)) {
    $sql .= " AND DATE(created_at) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $sql .= " AND DATE(created_at) <= ?";
    $params[] = $dateTo;
}

if (!empty($roleFilter)) {
    $sql .= " AND role = ?";
    $params[] = $roleFilter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $db->getConnection()->prepare($sql);
$stmt->execute($params);
$allUsers = $stmt->fetchAll();

$users = [];
foreach ($allUsers as $user) {
    $profileData = json_decode($user['data_info'], true);
    
    if (!empty($ageMin) || !empty($ageMax)) {
        $age = $profileData['age'] ?? null;
        if ($age === null) continue;
        if (!empty($ageMin) && $age < $ageMin) continue;
        if (!empty($ageMax) && $age > $ageMax) continue;
    }
    
    if (!empty($heightMin) || !empty($heightMax)) {
        $height = $profileData['height'] ?? null;
        if ($height === null) continue;
        if (!empty($heightMin) && $height < $heightMin) continue;
        if (!empty($heightMax) && $height > $heightMax) continue;
    }
    
    if (!empty($weightMin) || !empty($weightMax)) {
        $weight = $profileData['weight'] ?? null;
        if ($weight === null) continue;
        if (!empty($weightMin) && $weight < $weightMin) continue;
        if (!empty($weightMax) && $weight > $weightMax) continue;
    }
    
    if (!empty($gender)) {
        $userGender = $profileData['gender'] ?? null;
        if ($userGender !== $gender) continue;
    }
    
    $users[] = $user;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarządzanie Użytkownikami - Fitness Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            margin-bottom: 2rem;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }

        .card-header {
            background: white !important;
            border: none !important;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem 2rem !important;
        }

        .card-header h5 {
            color: #2d3748 !important;
            font-weight: 600;
            margin: 0 !important;
        }

        .card-body {
            padding: 2rem;
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
            text-align: center;
            margin-bottom: 2rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            border-bottom: 2px solid #f77f00;
            color: #f77f00;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        .table tbody tr:hover {
            background-color: rgba(247, 127, 0, 0.08) !important;
        }

        .badge-admin {
            background: linear-gradient(135deg, #f77f00 0%, #fcbf49 100%);
            color: #003049 !important;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            border-radius: 10px;
        }

        .badge-user {
            background: #d62828;
            color: white !important;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            border-radius: 10px;
        }

        .btn {
            border-radius: 12px;
            font-weight: 600;
            padding: 0.6rem 1.5rem;
            transition: all 0.3s ease;
            border: none;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #f77f00, #fcbf49) !important;
            color: #003049 !important;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #f77f00, #fcbf49) !important;
            color: white !important;
        }

        .btn-success {
            background: linear-gradient(135deg, #fcbf49, #f77f00) !important;
            color: #003049 !important;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #fcbf49, #f77f00) !important;
            color: #003049 !important;
        }

        .btn-secondary {
            background: #003049 !important;
            color: white !important;
        }

        .btn-secondary:hover {
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

        .btn-outline-danger {
            color: #d62828 !important;
            border-color: #d62828 !important;
            background: transparent;
        }

        .btn-outline-danger:hover {
            background: #d62828 !important;
            color: white !important;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 0.875rem 1.25rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #f77f00;
            box-shadow: 0 0 0 4px rgba(247, 127, 0, 0.1);
        }

        .form-label {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            color: #495057;
        }

        .advanced-filters {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }

        .filter-toggle {
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(240, 147, 251, 0.1));
            border-radius: 10px;
            font-size: 1rem;
        }

        .filter-toggle:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(240, 147, 251, 0.2));
            transform: translateY(-2px);
        }

        .search-section {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(240, 147, 251, 0.05));
            border-radius: 15px;
            padding: 1.5rem;
        }

        .input-group-container {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .input-group-container label {
            flex: 0 0 auto;
        }

        .input-group-container input,
        .input-group-container select {
            flex: 1 1 auto;
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="admin_dashboard.php" class="btn btn-success">← Powrót</a>
            <h1 class="page-title text-center flex-grow-1 mb-0">Zarządzanie Użytkownikami</h1>
            <div style="width: 100px;"></div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="m-0">Wyszukiwanie użytkowników</h5>
            </div>
            <div class="card-body">
                <form method="GET">
                    <div class="search-section mb-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <div class="input-group-container">
                                    <label class="form-label fw-bold">Email użytkownika</label>
                                    <input type="text" name="search_email" class="form-control" 
                                           placeholder="np. user@example.com" 
                                           value="<?= htmlspecialchars($searchEmail) ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group-container">
                                    <label class="form-label fw-bold">Data od</label>
                                    <input type="date" name="date_from" class="form-control" 
                                           value="<?= htmlspecialchars($dateFrom) ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group-container">
                                    <label class="form-label fw-bold">Data do</label>
                                    <input type="date" name="date_to" class="form-control" 
                                           value="<?= htmlspecialchars($dateTo) ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    🔎 Szukaj
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mb-3">
                        <div class="filter-toggle" onclick="toggleAdvancedFilters()">
                            <span id="toggleIcon">▶</span>
                            <span>Zaawansowane filtry</span>
                        </div>
                    </div>

                    <div id="advancedFilters" class="advanced-filters" style="display: none;">
                        <h6 class="text-primary mb-3 fw-bold">⚙️ Dodatkowe kryteria wyszukiwania</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">👥 Rola użytkownika</label>
                                <select name="role" class="form-control">
                                    <option value="">Wszystkie role</option>
                                    <option value="user" <?= $roleFilter === 'user' ? 'selected' : '' ?>>👤 Użytkownik</option>
                                    <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>🛡️ Administrator</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">⚧️ Płeć</label>
                                <select name="gender" class="form-control">
                                    <option value="">Wszystkie</option>
                                    <option value="male" <?= $gender === 'male' ? 'selected' : '' ?>>♂️ Mężczyzna</option>
                                    <option value="female" <?= $gender === 'female' ? 'selected' : '' ?>>♀️ Kobieta</option>
                                </select>
                            </div>
                            <div class="col-md-4"></div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Wiek</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="number" name="age_min" class="form-control" 
                                           placeholder="od" 
                                           value="<?= htmlspecialchars($ageMin) ?>"
                                           min="0" max="120">
                                    <span>-</span>
                                    <input type="number" name="age_max" class="form-control" 
                                           placeholder="do" 
                                           value="<?= htmlspecialchars($ageMax) ?>"
                                           min="0" max="120">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Wzrost (cm)</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="number" name="height_min" class="form-control" 
                                           placeholder="od" 
                                           value="<?= htmlspecialchars($heightMin) ?>"
                                           min="50" max="250">
                                    <span>-</span>
                                    <input type="number" name="height_max" class="form-control" 
                                           placeholder="do" 
                                           value="<?= htmlspecialchars($heightMax) ?>"
                                           min="50" max="250">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Waga (kg)</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="number" name="weight_min" class="form-control" 
                                           placeholder="od" 
                                           value="<?= htmlspecialchars($weightMin) ?>"
                                           min="20" max="300">
                                    <span>-</span>
                                    <input type="number" name="weight_max" class="form-control" 
                                           placeholder="do" 
                                           value="<?= htmlspecialchars($weightMax) ?>"
                                           min="20" max="300">
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($searchEmail) || !empty($dateFrom) || !empty($dateTo) || !empty($roleFilter) || !empty($ageMin) || !empty($ageMax) || !empty($heightMin) || !empty($heightMax) || !empty($weightMin) || !empty($weightMax) || !empty($gender)): ?>
                        <div class="text-center mt-3">
                            <a href="manage_users.php" class="btn btn-secondary">
                                ✖️ Wyczyść filtry
                            </a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="m-0">👥 Lista użytkowników</h5>
                <span class="badge bg-white text-success"><?= count($users) ?> znalezionych</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 8%;">ID</th>
                                <th style="width: 35%;">Email</th>
                                <th style="width: 12%;">Rola</th>
                                <th style="width: 20%;">Data rejestracji</th>
                                <th style="width: 10%;">Weryfikacja</th>
                                <th style="width: 15%;">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Brak użytkowników</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= $user['id'] ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td>
                                            <span class="badge badge-<?= $user['role'] ?> px-3 py-2">
                                                <?= $user['role'] === 'admin' ? '🛡️' : '👤' ?> <?= $user['role'] ?>
                                            </span>
                                        </td>
                                        <td><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
                                        <td class="text-center">
                                            <?= $user['email_verified'] ? '<span class="badge bg-success">✅ Tak</span>' : '<span class="badge bg-warning">❌ Nie</span>' ?>
                                        </td>
                                        <td>
                                            <?php if ($user['role'] !== 'admin'): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Czy na pewno chcesz usunąć tego użytkownika?\n\nUsuną się też wszystkie jego treningi i posiłki!');">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <button type="submit" name="delete_user" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                                        🗑️ Usuń
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Chroniony</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleAdvancedFilters() {
            const filters = document.getElementById('advancedFilters');
            const icon = document.getElementById('toggleIcon');
            
            if (filters.style.display === 'none' || filters.style.display === '') {
                filters.style.display = 'block';
                icon.textContent = '▼';
            } else {
                filters.style.display = 'none';
                icon.textContent = '▶';
            }
        }

        window.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($roleFilter) || !empty($ageMin) || !empty($ageMax) || !empty($heightMin) || !empty($heightMax) || !empty($weightMin) || !empty($weightMax) || !empty($gender)): ?>
                toggleAdvancedFilters();
            <?php endif; ?>
        });
    </script>
</body>
</html>
