<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!Auth::isLoggedIn() || Auth::getRole() !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$db = Database::getInstance();

$filename = 'fitness_tracker_raport_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, ['=== UŻYTKOWNICY ===']);
fputcsv($output, ['ID', 'Email', 'Rola', 'Data rejestracji', 'Email zweryfikowany', 'Wiek', 'Płeć', 'Wzrost (cm)', 'Waga (kg)', 'Poziom aktywności']);

$stmt = $db->getConnection()->query("SELECT id, email, role, created_at, email_verified, data_info FROM users ORDER BY created_at DESC");
foreach ($stmt->fetchAll() as $user) {
    $profileData = json_decode($user['data_info'], true);
    fputcsv($output, [
        $user['id'],
        $user['email'],
        $user['role'],
        $user['created_at'],
        $user['email_verified'] ? 'Tak' : 'Nie',
        $profileData['age'] ?? '',
        $profileData['gender'] === 'male' ? 'Mężczyzna' : ($profileData['gender'] === 'female' ? 'Kobieta' : ''),
        $profileData['height'] ?? '',
        $profileData['weight'] ?? '',
        $profileData['activity'] ?? ''
    ]);
}

fputcsv($output, []);

fputcsv($output, ['=== TRENINGI ===']);
fputcsv($output, ['ID', 'Email użytkownika', 'Typ treningu', 'Data', 'Czas trwania (min)', 'Serie', 'Powtórzenia', 'Waga (kg)', 'Dodatkowe info', 'Data utworzenia']);

$stmt = $db->getConnection()->query("
    SELECT w.*, u.email 
    FROM workouts w 
    JOIN users u ON w.user_id = u.id 
    ORDER BY w.date DESC, w.created_at DESC
");

foreach ($stmt->fetchAll() as $workout) {
    $notes = '';
    if ($workout['type'] === 'Trening Siłowy' && !empty($workout['notes'])) {
        $exercises = json_decode($workout['notes'], true);
        if (is_array($exercises)) {
            $exerciseStrings = [];
            foreach ($exercises as $ex) {
                $exerciseStrings[] = $ex['name'] . ': ' . $ex['sets'] . 'x' . $ex['reps'] . ($ex['weight'] ? ' @ ' . $ex['weight'] . 'kg' : '');
            }
            $notes = implode('; ', $exerciseStrings);
        }
    }
    
    fputcsv($output, [
        $workout['id'],
        $workout['email'],
        $workout['type'],
        $workout['date'],
        $workout['duration'] ?? '',
        $workout['sets'] ?? '',
        $workout['reps'] ?? '',
        $workout['weight'] ?? '',
        $notes,
        $workout['created_at']
    ]);
}

fputcsv($output, []);

fputcsv($output, ['=== POSIŁKI ===']);
fputcsv($output, ['ID', 'Email użytkownika', 'Nazwa posiłku', 'Data', 'Kalorie', 'Białko (g)', 'Węglowodany (g)', 'Tłuszcze (g)', 'Data utworzenia']);

$stmt = $db->getConnection()->query("
    SELECT n.*, u.email 
    FROM nutrition_logs n 
    JOIN users u ON n.user_id = u.id 
    ORDER BY n.date DESC, n.created_at DESC
");

foreach ($stmt->fetchAll() as $meal) {
    fputcsv($output, [
        $meal['id'],
        $meal['email'],
        $meal['meal_name'] ?? $meal['name'] ?? '',
        $meal['date'],
        $meal['calories'] ?? 0,
        $meal['protein'] ?? 0,
        $meal['carbs'] ?? 0,
        $meal['fats'] ?? 0,
        $meal['created_at']
    ]);
}

fputcsv($output, []);

fputcsv($output, ['=== STATYSTYKI ===']);
fputcsv($output, ['Metryka', 'Wartość']);

$totalUsers = $db->getConnection()->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$totalWorkouts = $db->getConnection()->query("SELECT COUNT(*) FROM workouts")->fetchColumn();
$totalMeals = $db->getConnection()->query("SELECT COUNT(*) FROM nutrition_logs")->fetchColumn();
$totalCaloriesConsumed = $db->getConnection()->query("SELECT SUM(calories) FROM nutrition_logs")->fetchColumn() ?? 0;

fputcsv($output, ['Liczba użytkowników', $totalUsers]);
fputcsv($output, ['Liczba treningów', $totalWorkouts]);
fputcsv($output, ['Liczba posiłków', $totalMeals]);
fputcsv($output, ['Suma spożytych kalorii', $totalCaloriesConsumed . ' kcal']);

fclose($output);
exit();
