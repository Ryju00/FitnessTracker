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

$stmt = $db->getConnection()->prepare("SELECT data FROM users WHERE id = ?");
$stmt->execute([$userId]);
$row = $stmt->fetch();
if ($row && (int)$row['data'] === 1) {
    header('Location: dashboard.php');
    exit();
}

$err = '';
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

        $dataInfo = implode('|', [$age, $gender, $height, $weight, $activity]);

        $db->setCaloriesRemaining($userId, $target);
        $db->setDataInfo($userId, $dataInfo);
        $db->setDataFlag($userId, 1);

        header('Location: dashboard.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Uzupełnij dane</title>
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
            padding: 2rem;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            background: linear-gradient(135deg, #f77f00 0%, #fcbf49 100%);
            color: #003049;
            border-radius: 16px 16px 0 0;
            padding: 1.5rem;
            font-weight: 600;
            border: none;
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
            border-color: #f77f00;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .btn {
            border-radius: 12px;
            padding: 0.875rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #f77f00 0%, #fcbf49 100%);
            color: #003049;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(247, 127, 0, 0.3);
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
        .alert {
            border-radius: 12px;
            border: none;
        }
    </style>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Uzupełnij dane</div>
                <div class="card-body">
                    <?php if ($err): ?>
                        <div class="alert alert-danger"><?=htmlspecialchars($err)?></div>
                    <?php endif; ?>
                    <p>Podaj informacje potrzebne do obliczenia zapotrzebowania kalorycznego.</p>
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Wiek (lata)</label>
                            <input type="number" min="1" max="120" name="age" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Płeć</label>
                            <select name="gender" class="form-select" required>
                                <option value="">-- Wybierz --</option>
                                <option value="male">Mężczyzna</option>
                                <option value="female">Kobieta</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Wzrost (cm)</label>
                            <input type="number" step="0.1" min="50" max="250" name="height" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Masa ciała (kg)</label>
                            <input type="number" step="0.1" min="1" name="weight" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Poziom aktywności</label>
                            <select name="activity" class="form-select" required>
                                <option value="">-- Wybierz --</option>
                                <option value="0">Siedzący: lekka/brak aktywności</option>
                                <option value="1">Lekki: ćwiczenia 1-3 razy/tydzień</option>
                                <option value="2">Średni: ćwiczenia 4-5 razy/tydzień</option>
                                <option value="3">Wysoki: codzienne ćwiczenia lub intensywne ćwiczenia 3-5 razy/tydzień</option>
                                <option value="4">Bardzo wysoki: intensywne ćwiczenia 6-7 razy na tydzień</option>
                                <option value="5">Najwyższy: codzienne intensywne ćwiczenia lub praca fizyczna</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cel</label>
                            <select name="goal" class="form-select">
                                <option value="reduction">Redukcja (-500 kcal)</option>
                                <option value="maintain" selected>Utrzymanie (+0 kcal)</option>
                                <option value="mass">Masa (+500 kcal)</option>
                            </select>
                        </div>
                        <button class="btn btn-primary">Zapisz i kontynuuj</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
