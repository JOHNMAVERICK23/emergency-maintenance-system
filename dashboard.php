<?php
require_once 'includes/auth.php';
requireLogin();

if (isAdmin()) {
    header('Location: manager_dashboard.php');
} else {
    header('Location: ap_dashboard.php');
}
exit();
?>