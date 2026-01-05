<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Logowanie wymagane!']));
}

$db = Database::getInstance();
$userId = Auth::getUserId();

$sevenDaysAgo = date('Y-m-d', strtotime('-6 days'));
$today = date('Y-m-d');

$sql = "SELECT type, weight, sets, reps, notes, duration FROM workouts WHERE user_id = ? AND date >= ? AND date <= ? ORDER BY date DESC";
$stmt = $db->getConnection()->prepare($sql);
$stmt->execute([$userId, $sevenDaysAgo, $today]);
$workouts = $stmt->fetchAll();

$totalWeight = 0;
$totalCalories = 0;

$activity_calories = [
    'Bieganie' => 11,
    'Rower' => 8,
    'Spacer' => 4,
    'Wioślarz' => 9,
    'Pływanie' => 10
];

foreach ($workouts as $w) {
    if ($w['type'] === 'Trening Siłowy' && $w['notes']) {
        $exercises = json_decode($w['notes'], true);
        if (is_array($exercises)) {
            foreach ($exercises as $ex) {
                if (isset($ex['weight']) && isset($ex['reps']) && isset($ex['sets'])) {
                    $totalWeight += (float)$ex['weight'] * (int)$ex['reps'] * (int)$ex['sets'];
                }
            }
        }
        $totalCalories += (int)round(6 * $w['duration']);
    } 
    elseif ($w['weight'] && $w['reps'] && $w['sets']) {
        $totalWeight += (float)$w['weight'] * (int)$w['reps'] * (int)$w['sets'];
    }
    
    if (strpos($w['type'], 'Cardio:') === 0) {
        $defaultKcal = 8;
        $kcalPerMin = $defaultKcal;
        
        foreach ($activity_calories as $activity => $kpm) {
            if (strpos($w['type'], $activity) !== false) {
                $kcalPerMin = $kpm;
                break;
            }
        }
        
        $totalCalories += (int)round($kcalPerMin * $w['duration']);
    }
}

echo json_encode([
    'success' => true,
    'totalWeight' => round($totalWeight, 1),
    'totalCalories' => $totalCalories
]);
