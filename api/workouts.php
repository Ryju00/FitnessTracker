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
    exit(json_encode(['success' => false, 'message' => 'Nie zalogowany!']));
}

$db = Database::getInstance();
$userId = Auth::getUserId();
$method = $_SERVER['REQUEST_METHOD'];

function validateWorkout($data)
{
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date']) &&
        preg_match('/^[a-zA-Z0-9\s\.,-]{1,100}$/', $data['type']) &&
        (empty($data['reps']) || filter_var($data['reps'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]])) &&
        (empty($data['weight']) || filter_var($data['weight'], FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0, 'max_range' => 500]]));
}

switch ($method) {
    case 'GET':
        $date = $_GET['date'] ?? null;
        $workouts = $db->getWorkouts($userId, $date);
        echo json_encode(['success' => true, 'data' => $workouts]);
        break;
    case 'POST':
        $input = [];
        if (isset($_POST['action']) && $_POST['action'] === 'create') {
            $input = [
                'user_id' => $userId,
                'date' => $_POST['date'],
                'type' => $_POST['type'],
                'reps' => $_POST['reps'],
                'weight' => $_POST['weight']
            ];
        } else {
            $input = json_decode(file_get_contents('php://input'), true);
        }
        if (!validateWorkout($input)) {
            http_response_code(400);
            exit(json_encode(['success' => false, 'message' => 'Błędne dane!']));
        }
        if ($db->createWorkout($input)) {
            echo json_encode(['success' => true, 'message' => 'Trening dodany!']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Błąd bazy']);
        }
        break;
    case 'DELETE':
        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (!filter_var($id, FILTER_VALIDATE_INT)) {
            http_response_code(400);
            exit(json_encode(['success' => false]));
        }
        
        $sqlGet = "SELECT type, duration FROM workouts WHERE id=? AND user_id=?";
        $stmtGet = $db->getConnection()->prepare($sqlGet);
        $stmtGet->execute([$id, $userId]);
        $workout = $stmtGet->fetch();
        
        if (!$workout) {
            http_response_code(404);
            exit(json_encode(['success' => false, 'message' => 'Trening nie znaleziony']));
        }
        
        $calories = 0;
        if (strpos($workout['type'], 'Cardio:') === 0) {
            $kcal_per_min = [
                'Bieganie' => 11,
                'Rower' => 8,
                'Spacer' => 4,
                'Wioślarz' => 9,
                'Pływanie' => 10
            ];
            $defaultKcal = 8;
            foreach ($kcal_per_min as $activity => $kpm) {
                if (strpos($workout['type'], $activity) !== false) {
                    $calories = (int)round($kpm * $workout['duration']);
                    break;
                }
            }
            if ($calories === 0) {
                $calories = (int)round($defaultKcal * $workout['duration']);
            }
        } elseif ($workout['type'] === 'Trening Siłowy') {
            $calories = (int)round(6 * $workout['duration']);
        }
        
        $sql = "DELETE FROM workouts WHERE id=? AND user_id=?";
        $stmt = $db->getConnection()->prepare($sql);
        $success = $stmt->execute([$id, $userId]);
        
        echo json_encode(['success' => $success, 'calories' => $calories]);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Metoda nieobsługiwana']);
}
