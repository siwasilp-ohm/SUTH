<?php
require __DIR__ . '/../lib/auth.php';
logout_user();
header('Location: /index.php');
exit;
