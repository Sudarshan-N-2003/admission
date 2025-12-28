<?php
// admin/auth.php

// Start session ONLY if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check admin login
if (empty($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
