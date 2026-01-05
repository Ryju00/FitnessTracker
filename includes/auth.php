<?php
require_once __DIR__ . '/db.php';
class Auth {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance();
    }
    public function register($email, $password) {
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Hasło musi mieć min. 8 znaków!'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Nieprawidłowy format email!'];
        }
        if ($this->db->getUserByEmail($email)) {
            return ['success' => false, 'message' => 'Email już istnieje!'];
        }
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (email, password, email_verified) VALUES (?, ?, TRUE)";
        $stmt = $this->db->getConnection()->prepare($sql);
        if ($stmt->execute([$email, $hashed])) {
            return ['success' => true, 'message' => 'Rejestracja udana! Możesz się zalogować. ✅'];
        }
        return ['success' => false, 'message' => 'Błąd rejestracji. Spróbuj ponownie.'];
    }
    public function login($email, $password) {
        $user = $this->db->getUserByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            return ['success' => true, 'role' => $user['role']];
        }
        return ['success' => false, 'message' => 'Błędne dane logowania!'];
    }
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    public static function getRole() {
        return $_SESSION['role'] ?? null;
    }
    public static function logout() {
        session_unset();
        session_destroy();
    }
}

