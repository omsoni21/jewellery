<?php

/**
 * Common Utility Functions (ULTIMATE FINAL VERSION)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

// ✅ Define BASE_URL if not defined
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/jewellery');
}

/**
 * Start secure session
 */
function startSecureSession()
{
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        session_name(SESSION_NAME);
        session_start();

        // Session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            session_unset();
            session_destroy();
            header("Location: " . BASE_URL . "/login.php?timeout=1");
            exit();
        }

        $_SESSION['last_activity'] = time();
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn()
{
    startSecureSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require authentication
 */
function requireAuth()
{
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }
}

/**
 * Require specific role
 */
function requireRole($roles)
{
    requireAuth();
    if (!hasRole($roles)) {
        header("Location: " . BASE_URL . "/unauthorized.php");
        exit();
    }
}

/**
 * Check user role
 */
function hasRole($allowedRoles)
{
    startSecureSession();
    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $allowedRoles);
}

/**
 * Sanitize input
 */
function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect helper
 */
function redirect($path)
{
    header("Location: " . BASE_URL . "/" . ltrim($path, '/'));
    exit();
}

/**
 * Flash message
 */
function setFlashMessage($type, $message)
{
    startSecureSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage()
{
    startSecureSession();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Redirect with flash message helper
 *
 * @param string $path  Path relative to BASE_URL
 * @param string $type  Message type (success, error, info, warning)
 * @param string $message Message text
 */
function redirectWithMessage($path, $type = 'info', $message = '')
{
    setFlashMessage($type, $message);
    redirect($path);
}

/* =====================================================
   ✅ ALL REQUIRED HELPER FUNCTIONS
===================================================== */

/**
 * Log activity
 */
function logActivity($type, $message)
{
    $file = __DIR__ . '/../activity.log';
    $date = date('Y-m-d H:i:s');
    file_put_contents($file, "[$date] [$type] $message\n", FILE_APPEND);
}

/**
 * Format currency
 */
function formatCurrency($amount)
{
    return '₹' . number_format((float)$amount, 2);
}

/**
 * Format date
 */
function formatDate($date)
{
    if (empty($date)) return '';
    return date('d M Y', strtotime($date));
}

/**
 * Format weight
 */
function formatWeight($weight)
{
    if ($weight == 0 || $weight === null) return '0 g';
    return number_format((float)$weight, 2) . ' g';
}

/**
 * Generate invoice number
 */
function generateInvoiceNumber()
{
    $date = date('Ymd');
    $file = __DIR__ . '/../invoice_counter.txt';

    if (!file_exists($file)) {
        file_put_contents($file, "1");
    }

    $counter = (int)file_get_contents($file);
    $counter++;

    file_put_contents($file, $counter);

    return "INV-" . $date . "-" . str_pad($counter, 3, '0', STR_PAD_LEFT);
}
