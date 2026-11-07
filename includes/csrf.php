<?php

// Start the session if it has not already started.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate a CSRF token.
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// Verify the submitted CSRF token.
function verify_csrf_token() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $submitted_token = $_POST['csrf_token'] ?? '';
        $session_token = $_SESSION['csrf_token'] ?? '';

        if (
            empty($submitted_token) ||
            empty($session_token) ||
            !hash_equals($session_token, $submitted_token)
        ) {
            http_response_code(403);
            exit('Invalid request. Please refresh the page and try again.');
        }
    }
}