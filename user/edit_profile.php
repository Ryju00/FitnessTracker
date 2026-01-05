<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';
if (!Auth::isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}
$db = Database::getInstance();
$userId = Auth::getUserId();

$stmt = $db->getConnection()->prepare("SELECT email, data_info, calories_remaining FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$dataInfo = $user['data_info'] ?? '';
$parts = explode('|', $dataInfo);
$currentAge = $parts[0] ?? '';
$currentGender = $parts[1] ?? '';
$currentHeight = $parts[2] ?? '';
$currentWeight = $parts[3] ?? '';
$currentActivity = $parts[4] ?? '';

$err = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $age = isset($_POST['age']) ? (int)$_POST['age'] : 0;
    $gender = $_POST['gender'] ?? '';
    $height = isset($_POST['height']) ? (float)$_POST['height'] : 0;
    $weight = isset($_POST['weight']) ? (float)$_POST['weight'] : 0;
    $activity = isset($_POST['activity']) ? (int)$_POST['activity'] : 0;
    $goal = $_POST['goal'] ?? 'maintain';

    if ($age <= 0 || $height <= 0 || $weight <= 0 || !in_array($gender, ['male', 'female']) || !in_array($activity, [0,1,2,3,4,5])) {
        $err = 'Wszystkie pola są wymagane i muszą być prawidłowe.';
    } else {
        if ($gender === 'male') {
            $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) + 5;
        } else {
            $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) - 161;
        }

        $palLevels = [1.2, 1.4, 1.6, 1.75, 1.9, 2.2];
        $pal = $palLevels[$activity];
        $tdee = (int)round($bmr * $pal);

        $adjust = 0;
        if ($goal === 'reduction') $adjust = -500;
        if ($goal === 'mass') $adjust = 500;
        $target = max(0, $tdee + $adjust);

        $newDataInfo = implode('|', [$age, $gender, $height, $weight, $activity]);

        $db->setCaloriesRemaining($userId, $target);
        $db->setDataInfo($userId, $newDataInfo);
        
        $success = 'Profil zaktualizowany! Nowy limit kalorii: ' . $target . ' kcal';
        
        $currentAge = $age;
        $currentGender = $gender;
        $currentHeight = $height;
        $currentWeight = $weight;
        $currentActivity = $activity;
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edytuj Profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background: linear-gradient(135deg, #f77f00 0%, #fcbf49 100%);
            color: #003049;
            border: none;
            border-radius: 16px 16px 0 0 !important;
            padding: 1.5rem;
        }

        .card-header h3 {
            margin: 0;
            font-weight: 600;
        }

        .form-control, .form-select {
            border-radius: 12px;
            padding: 0.875rem 1.25rem;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #f77f00;
            box-shadow: 0 0 0 4px rgba(247, 127, 0, 0.1);
        }

        .btn-primary, .btn-save {
            background: linear-gradient(135deg, #f77f00 0%, #fcbf49 100%);
            border: none;
            border-radius: 12px;
            padding: 0.875rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            color: #003049;
        }

        .btn-primary:hover, .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(247, 127, 0, 0.3);
        }

        .info-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #f77f00 0%, #fcbf49 100%);
            color: #003049;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .section-title {
            color: #2d3748;
            font-weight: 700;
            margin-top: 2rem;
            margin-bottom: 1rem;
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
    <a href="dashboard.php" class="btn btn-light mb-4 shadow-sm" style="border-radius: 10px;">
        ← Powrót do Dashboard
    </a>
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="profile-card card">
                <div class="card-header">
                    <h3 class="m-0 text-center">🔧 Twój Profil</h3>
                </div>
                <div class="card-body p-4 p-md-5">
                    <?php if ($err): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?=htmlspecialchars($err)?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?=htmlspecialchars($success)?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-5">
                        <div class="row text-center">
                            <div class="col-md-6 mb-3">
                                <p class="text-muted mb-2">Email</p>
                                <p class="h5"><?=htmlspecialchars($user['email'])?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="text-muted mb-2">Aktualny limit kalorii</p>
                                <p class="info-badge"><?=number_format($user['calories_remaining'])?> kcal</p>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h5 class="section-title text-center">Edytuj dane</h5>
                    <form method="post">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Wiek (lata)</label>
                                <input type="number" min="1" max="120" name="age" class="form-control" value="<?=htmlspecialchars($currentAge)?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Płeć</label>
                                <select name="gender" class="form-select" required>
                                    <option value="">-- Wybierz --</option>
                                    <option value="male" <?=$currentGender === 'male' ? 'selected' : ''?>>Mężczyzna</option>
                                    <option value="female" <?=$currentGender === 'female' ? 'selected' : ''?>>Kobieta</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Wzrost (cm)</label>
                                <input type="number" step="0.1" min="50" max="250" name="height" class="form-control" value="<?=htmlspecialchars($currentHeight)?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Masa ciała (kg)</label>
                                <input type="number" step="0.1" min="1" name="weight" class="form-control" value="<?=htmlspecialchars($currentWeight)?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Poziom aktywności</label>
                            <select name="activity" class="form-select" required>
                                <option value="">-- Wybierz --</option>
                                <option value="0" <?=$currentActivity === '0' ? 'selected' : ''?>>Siedzący: lekka/brak aktywności</option>
                                <option value="1" <?=$currentActivity === '1' ? 'selected' : ''?>>Lekki: ćwiczenia 1-3 razy/tydzień</option>
                                <option value="2" <?=$currentActivity === '2' ? 'selected' : ''?>>Średni: ćwiczenia 4-5 razy/tydzień</option>
                                <option value="3" <?=$currentActivity === '3' ? 'selected' : ''?>>Wysoki: codzienne ćwiczenia lub intensywne ćwiczenia 3-5 razy/tydzień</option>
                                <option value="4" <?=$currentActivity === '4' ? 'selected' : ''?>>Bardzo wysoki: intensywne ćwiczenia 6-7 razy na tydzień</option>
                                <option value="5" <?=$currentActivity === '5' ? 'selected' : ''?>>Najwyższy: codzienne intensywne ćwiczenia lub praca fizyczna</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Cel</label>
                            <select name="goal" class="form-select">
                                <option value="reduction">Redukcja (-500 kcal)</option>
                                <option value="maintain" selected>Utrzymanie (+0 kcal)</option>
                                <option value="mass">Masa (+500 kcal)</option>
                            </select>
                        </div>
                        <div class="text-center">
                            <button class="btn btn-save btn-primary btn-lg px-5">💾 Zapisz zmiany</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
