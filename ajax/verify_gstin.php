<?php
/**
 * AJAX Endpoint for GSTIN Verification
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/gst_api.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$gstin = isset($_GET['gstin']) ? sanitize($_GET['gstin']) : '';

if (empty($gstin)) {
    echo json_encode(['success' => false, 'error' => 'GSTIN is required']);
    exit();
}

// Verify GSTIN
$result = verifyGSTIN($gstin);

echo json_encode($result);
