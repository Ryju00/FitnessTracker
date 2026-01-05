<?php
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Logowanie wymagane']));
}

$db = Database::getInstance();
$userId = Auth::getUserId();
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Metoda nieobsługiwana']));
}

$mode = $_POST['mode'] ?? null;
$date = $_POST['date'] ?? date('Y-m-d');

if ($mode === 'cardio') {
    $type = trim($_POST['cardio_type'] ?? 'running');
    $minutes = (int)($_POST['duration'] ?? 0);
    $distance = isset($_POST['distance']) ? (float)$_POST['distance'] : null;

    if ($minutes <= 0) {
        http_response_code(400);
        exit(json_encode(['success' => false, 'message' => 'Podaj czas trwania w minutach']));
    }

    $kcal_per_min = [
        'running' => 11,
        'cycling' => 8,
        'walking' => 4,
        'rowing' => 9,
        'swimming' => 10,
        'default' => 8
    ];

    $activity_names = [
        'running' => 'Bieganie',
        'cycling' => 'Rower',
        'walking' => 'Spacer',
        'rowing' => 'Wioślarz',
        'swimming' => 'Pływanie'
    ];

    $key = strtolower($type);
    $kpm = $kcal_per_min[$key] ?? $kcal_per_min['default'];
    $calories = (int)round($kpm * $minutes);
    $display_name = $activity_names[$key] ?? $type;

    $db->getConnection()->prepare(
        "INSERT INTO workouts (user_id, type, duration, distance, date, notes) VALUES (?, ?, ?, ?, ?, ?)"
    )->execute([$userId, 'Cardio: ' . $display_name, $minutes, $distance, $date, '']);

    echo json_encode(['success' => true, 'message' => 'Cardio zapisane', 'calories' => $calories]);
    exit();
}

if ($mode === 'workout') {
    $duration = (int)($_POST['duration'] ?? 0);
    $exercisesRaw = $_POST['exercises'] ?? null;
    $exercises = [];
    if ($exercisesRaw) {
        $decoded = json_decode($exercisesRaw, true);
        if (is_array($decoded)) $exercises = $decoded;
    }

    if ($duration <= 0) {
        http_response_code(400);
        exit(json_encode(['success' => false, 'message' => 'Podaj czas trwania treningu w minutach']));
    }

    $kpm_strength = 6;
    $calories = (int)round($kpm_strength * $duration);

    $exercises_json = json_encode($exercises, JSON_UNESCAPED_UNICODE);
    
    $db->getConnection()->prepare(
        "INSERT INTO workouts (user_id, type, duration, date, notes) VALUES (?, ?, ?, ?, ?)"
    )->execute([$userId, 'Trening Siłowy', $duration, $date, $exercises_json]);

    echo json_encode(['success' => true, 'message' => 'Trening zapisany', 'calories' => $calories]);
    exit();
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Nieprawidłowy tryb']);
