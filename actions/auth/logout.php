<?php
session_start();

// Sauvegarder le rôle avant de détruire la session
$userRole = $_SESSION['user_role'] ?? null;

// Destroy all session data
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: ../../views/auth/login.php');
exit;
?>