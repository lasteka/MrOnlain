<?php
///kursa-darbi/nails-booking/core/auth.php
// /nails-booking/core/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}