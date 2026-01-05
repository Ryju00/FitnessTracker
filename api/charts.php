<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/auth.php';
if (!Auth::isLoggedIn()) exit(json_encode([]));
$db = Database::getInstance();
$userId = Auth::getUserId();
$type = $_GET['type'] ?? 'workouts';
if ($type === 'workouts') {
    $sql = "SELECT DATE_FORMAT(date, '%Y-%m') as month, AVG(weight) as avg_weight 
            FROM workouts WHERE user_id = ? AND type = 'bench_press' GROUP BY month ORDER BY month DESC LIMIT 6";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute([$userId]);
    echo json_encode($stmt->fetchAll());
} elseif ($type === 'nutrition') {
    $sql = "SELECT date, SUM(calories) AS total_cal, SUM(protein) AS total_pro 
            FROM nutrition_logs WHERE user_id = ? AND date >= CURDATE() - INTERVAL 7 DAY 
            GROUP BY date ORDER BY date";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute([$userId]);
    echo json_encode($stmt->fetchAll());
}
