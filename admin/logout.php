<?php
// /nails-booking/admin/logout.php
session_start();
unset($_SESSION['admin_id']);
session_destroy();
header('Location: /nails-booking/admin/login.php');
exit;