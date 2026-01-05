<?php

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $host = 'localhost';
        $dbname = 'gym_tracker';
        $username = 'root';
        $password = '';

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];

        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
        } catch (PDOException $e) {
            die("Błąd bazy: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function getWorkouts($userId, $date = null) {
        $sql = "SELECT * FROM workouts WHERE user_id = ?";
        $params = [$userId];
        if ($date) {
            $sql .= " AND date = ?";
            $params[] = $date;
        }
        $sql .= " ORDER BY date DESC, created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function createWorkout($data) {
        $sql = "INSERT INTO workouts (user_id, type, reps, sets, weight, duration, distance, date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['user_id'], $data['type'], $data['reps'] ?? null, $data['sets'] ?? null,
            $data['weight'] ?? null, $data['duration'] ?? null, $data['distance'] ?? null,
            $data['date'], $data['notes'] ?? ''
        ]);
    }

    public function getUserByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function validateCalories($cal) {
        return filter_var($cal, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 5000]]);
    }

    public function getNutritionLogs($userId, $date = null) {
        $sql = "SELECT * FROM nutrition_logs WHERE user_id = ?";
        $params = [$userId];
        if ($date) {
            $sql .= " AND date = ?";
            $params[] = $date;
        }
        $sql .= " ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function createNutritionLog($data) {
        $sql = "INSERT INTO nutrition_logs (user_id, calories, protein, carbs, fats, meal_name, date) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['user_id'], $data['calories'], $data['protein'], $data['carbs'],
            $data['fats'], $data['meal_name'], $data['date']
        ]);
    }

    public function getCaloriesRemaining($userId) {
        $stmt = $this->pdo->prepare("SELECT calories_remaining FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ? (int)$row['calories_remaining'] : null;
    }

    public function adjustCalories($userId, $delta) {
        $sql = "UPDATE users SET calories_remaining = GREATEST(0, COALESCE(calories_remaining, 0) + (?)) WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$delta, $userId]);
    }

    public function setCaloriesRemaining($userId, $value) {
        $sql = "UPDATE users SET calories_remaining = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([(int)$value, $userId]);
    }

    public function setDataFlag($userId, $flag) {
        $sql = "UPDATE users SET data = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([($flag ? 1 : 0), $userId]);
    }

    public function setDataInfo($userId, $info) {
        $sql = "UPDATE users SET data_info = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$info, $userId]);
    }

}

?>