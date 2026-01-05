<?php
require_once 'includes/auth.php';
$auth = new Auth();
$token = $_GET['token'] ?? '';
if ($auth->verify($token)) {
    echo '<div class="alert alert-success">Konto zweryfikowane! <a href="index.php">Zaloguj się</a></div>';
} else {
    echo '<div class="alert alert-danger">Błędny lub wygaśnięty token!</div>';
}