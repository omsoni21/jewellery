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

    // Ensure counter file exists
    if (!file_exists($file)) {
        // initialize to 0 so first generated number will be 1
        file_put_contents($file, "0");
    }

    // Try to generate a unique invoice number atomically using file locking.
    // Also verify against the database to avoid collisions if the counter
    // was adjusted externally.
    $maxAttempts = 10000;
    for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
        $fp = fopen($file, 'c+');
        if ($fp === false) {
            throw new Exception('Unable to open invoice counter file for writing');
        }

        // Acquire exclusive lock
        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            usleep(50000); // wait 50ms and retry
            continue;
        }

        // Read current counter, increment and write back
        $contents = stream_get_contents($fp);
        $counter = (int)trim($contents);
        $counter++;

        // Rewind and write the new counter
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, (string)$counter);
        fflush($fp);

        // Release lock and close
        flock($fp, LOCK_UN);
        fclose($fp);

        $invoiceNo = 'INV-' . $date . '-' . str_pad($counter, 3, '0', STR_PAD_LEFT);

        // Verify uniqueness in DB if available
        try {
            if (function_exists('getDBConnection')) {
                $db = getDBConnection();
                $stmt = $db->prepare('SELECT COUNT(1) FROM invoices WHERE invoice_no = ?');
                $stmt->execute([$invoiceNo]);
                $exists = (int)$stmt->fetchColumn();
                if ($exists) {
                    // Collision detected — loop to try next counter
                    continue;
                }
            }
        } catch (Exception $e) {
            // DB not available — still return generated value because
            // we've used a locked file counter to avoid concurrent dupes
        }

        return $invoiceNo;
    }

    throw new Exception('Failed to generate unique invoice number after ' . $maxAttempts . ' attempts');
}
