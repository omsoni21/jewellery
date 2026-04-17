<?php
/**
 * Index / Redirect Page
 */

require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header("Location: " . BASE_URL . "/dashboard.php");
} else {
    header("Location: " . BASE_URL . "/login.php");
}
exit();
