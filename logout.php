<?php
/**
 * Logout Page
 */

require_once __DIR__ . '/includes/functions.php';

// Log activity before logout
if (isLoggedIn()) {
    logActivity('logout', 'User logged out');
}

// Clear session
session_unset();
session_destroy();

// Redirect to login
header("Location: " . BASE_URL . "/login.php");
exit();
