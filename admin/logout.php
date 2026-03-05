<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once LIB_PATH . '/JsonStore.php';
require_once LIB_PATH . '/Auth.php';
Auth::logout();
header('Location: /admin/login.php');
exit;
