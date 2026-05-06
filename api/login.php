<?php

/**
 * API Login Endpoint
 * Handles user authentication for mobile app
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'] ?? '';
  $password = $_POST['password'] ?? '';

  if (empty($username) || empty($password)) {
    echo json_encode([
      'success' => false,
      'message' => 'Username and password are required'
    ]);
    exit();
  }

  try {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT id, username, email, full_name, role, is_active, password_hash FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
      if (!$user['is_active']) {
        echo json_encode([
          'success' => false,
          'message' => 'Your account has been deactivated'
        ]);
      } else {
        // Update last login
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);

        echo json_encode([
          'success' => true,
          'message' => 'Login successful',
          'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'role' => $user['role']
          ]
        ]);
      }
    } else {
      echo json_encode([
        'success' => false,
        'message' => 'Invalid username or password'
      ]);
    }
  } catch (Exception $e) {
    echo json_encode([
      'success' => false,
      'message' => 'Server error: ' . $e->getMessage()
    ]);
  }
} else {
  echo json_encode([
    'success' => false,
    'message' => 'Invalid request method'
  ]);
}
