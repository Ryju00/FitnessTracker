<?php
session_start();
require_once '../includes/auth.php';
Auth::logout();
header('Location: ../index.php');
?>
