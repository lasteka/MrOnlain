<?php
// /admin/logout.php
session_start();
unset($_SESSION['admin_id']);
session_destroy();
header('Location: /admin/login.php');
exit;