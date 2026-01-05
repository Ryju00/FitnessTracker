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
$method = $_SERVER['REQUEST_METHOD'];
$db->getConnection()->exec("SET NAMES utf8mb4");

function validateNutrition($data)
{
    $calValid = filter_var($data['calories'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 5000]]) !== false;
    $proValid = filter_var($data['protein'], FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0, 'max_range' => 500]]) !== false;
    $carbsValid = filter_var($data['carbs'], FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0, 'max_range' => 1000]]) !== false;
    $fatsValid = filter_var($data['fats'], FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0, 'max_range' => 500]]) !== false;
    $nameValid = !empty($data['meal_name']) && strlen($data['meal_name']) <= 100;
    $dateValid = preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date']);
    
    return $calValid && $proValid && $carbsValid && $fatsValid && $nameValid && $dateValid;
}

switch ($method) {
    case 'GET':
        $date = $_GET['date'] ?? null;
        $logs = $db->getNutritionLogs($userId, $date);
        echo json_encode(['success' => true, 'data' => $logs]);
        break;
    case 'POST':
        $input = [];
        if (isset($_POST['action']) && $_POST['action'] === 'create') {
            $input = [
                'user_id' => $userId,
                'calories' => (float)$_POST['calories'],
                'protein' => (float)($_POST['protein'] ?? 0),
                'carbs' => (float)($_POST['carbs'] ?? 0),
                'fats' => (float)($_POST['fats'] ?? 0),
                'meal_name' => $_POST['name'],
                'date' => date('Y-m-d')
            ];
        } else {
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input) {
                $input['user_id'] = $userId;
            }
        }
        if (!$input || !validateNutrition($input)) {
            http_response_code(400);
            exit(json_encode(['success' => false, 'message' => 'Nieprawidłowe makra!']));
        }
        if ($db->createNutritionLog($input)) {
            echo json_encode(['success' => true, 'message' => 'Posiłek zapisany!']);
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
        
        $sqlGet = "SELECT calories FROM nutrition_logs WHERE id=? AND user_id=?";
        $stmtGet = $db->getConnection()->prepare($sqlGet);
        $stmtGet->execute([$id, $userId]);
        $nutrition = $stmtGet->fetch();
        
        if (!$nutrition) {
            http_response_code(404);
            exit(json_encode(['success' => false, 'message' => 'Posiłek nie znaleziony']));
        }
        
        $calories = (int)$nutrition['calories'];
        
        $sql = "DELETE FROM nutrition_logs WHERE id=? AND user_id=?";
        $stmt = $db->getConnection()->prepare($sql);
        $success = $stmt->execute([$id, $userId]);
        
        echo json_encode(['success' => $success, 'calories' => $calories]);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Metoda nieobsługiwana']);
}
