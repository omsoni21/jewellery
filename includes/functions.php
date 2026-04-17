<?php
/**
 * Common Utility Functions
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

/**
 * Start secure session
 */
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        session_name(SESSION_NAME);
        session_start();
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            session_unset();
            session_destroy();
            header("Location: /login.php?timeout=1");
            exit();
        }
        
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    startSecureSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check user role
 * @param string|array $allowedRoles
 * @return bool
 */
function hasRole($allowedRoles) {
    startSecureSession();
    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $allowedRoles);
}

/**
 * Require authentication
 */
function requireAuth() {
    if (!isLoggedIn()) {
        header("Location: /login.php");
        exit();
    }
}

/**
 * Require specific role
 * @param string|array $roles
 */
function requireRole($roles) {
    requireAuth();
    if (!hasRole($roles)) {
        header("Location: /unauthorized.php");
        exit();
    }
}

/**
 * Sanitize input
 * @param string $data
 * @return string
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency
 * @param float $amount
 * @return string
 */
function formatCurrency($amount) {
    return CURRENCY_SYMBOL . number_format($amount, 2);
}

/**
 * Format weight
 * @param float $weight
 * @return string
 */
function formatWeight($weight) {
    return number_format($weight, 3) . ' ' . WEIGHT_UNIT;
}

/**
 * Format date
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = DATE_FORMAT) {
    return date($format, strtotime($date));
}

/**
 * Generate unique invoice number
 * @return string
 */
function generateInvoiceNumber() {
    $db = getDBConnection();
    $year = date('Y');
    $prefix = 'INV-' . $year . '-';
    
    $stmt = $db->query("SELECT MAX(CAST(SUBSTRING(invoice_no, LENGTH('$prefix') + 1) AS UNSIGNED)) as max_num 
                        FROM invoices 
                        WHERE invoice_no LIKE '$prefix%'");
    $result = $stmt->fetch();
    $nextNum = ($result['max_num'] ?? 0) + 1;
    
    return $prefix . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
}

/**
 * Calculate GST
 * @param float $amount
 * @param float $rate
 * @return array
 */
function calculateGST($amount, $rate = GST_RATE) {
    $gstAmount = ($amount * $rate) / 100;
    $cgst = $gstAmount / 2;
    $sgst = $gstAmount / 2;
    
    return [
        'gst_amount' => $gstAmount,
        'cgst' => $cgst,
        'sgst' => $sgst,
        'total' => $amount + $gstAmount
    ];
}

/**
 * Display flash message
 * @param string $type
 * @param string $message
 */
function setFlashMessage($type, $message) {
    startSecureSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash message
 * @return array|null
 */
function getFlashMessage() {
    startSecureSession();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Log activity
 * @param string $action
 * @param string $details
 */
function logActivity($action, $details = '') {
    $db = getDBConnection();
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) 
                          VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$userId, $action, $details, $_SERVER['REMOTE_ADDR']]);
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    startSecureSession();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    startSecureSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redirect with message
 * @param string $url
 * @param string $type
 * @param string $message
 */
function redirectWithMessage($url, $type, $message) {
    setFlashMessage($type, $message);
    header("Location: $url");
    exit();
}

/**
 * Format date/time for display
 * @param string $datetime
 * @param string $format
 * @return string
 */
function formatDateTime($datetime, $format = 'd M Y, h:i A') {
    if (empty($datetime)) {
        return 'Never';
    }
    try {
        $date = new DateTime($datetime);
        return $date->format($format);
    } catch (Exception $e) {
        return $datetime;
    }
}
