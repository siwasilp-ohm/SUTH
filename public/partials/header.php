<?php
$app = require __DIR__ . '/../../config/app.php';
$user = current_user();
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($app['app_name']) ?></title>
    <link rel="stylesheet" href="/assets/styles.css">
    <script defer src="/assets/app.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="app">
    <header class="topbar">
        <div class="brand">
            <div class="logo">HICM</div>
            <div>
                <div class="brand-title"><?= htmlspecialchars($app['app_name']) ?></div>
                <div class="brand-subtitle">HICM V2025 • HURS-aligned Workflow</div>
            </div>
        </div>
        <nav class="nav">
            <a href="/index.php">Home</a>
            <?php if ($user): ?>
                <a href="/dashboard.php">Dashboard</a>
                <span class="nav-user"><?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['role']) ?>)</span>
                <a class="btn ghost" href="/logout.php">Logout</a>
            <?php else: ?>
                <a class="btn ghost" href="/login.php">Login</a>
                <a class="btn primary" href="/register.php">Register</a>
            <?php endif; ?>
        </nav>
    </header>
    <main class="content">
