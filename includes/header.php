<?php
/**
 * MelodyLogs - Common Header Component
 */
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$flash = get_flash();
$appName = env('APP_NAME', 'MelodyLogs');
$pageTitle = isset($pageTitle) ? "{$pageTitle} · {$appName}" : "{$appName} · The Vocalist & Singer Community";
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MelodyLogs - A dedicated platform for singers, vocalists, and vocal coaches to share vocal techniques, warmups, and studio logs.">
    <title><?= e($pageTitle) ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom MelodyLogs Theme CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container">
            <!-- Brand Logo -->
            <a class="brand-logo" href="index.php">
                <div class="brand-icon-box">
                    <i class="bi bi-soundwave fs-5"></i>
                </div>
                <span>Melody<span class="text-gradient">Logs</span></span>
            </a>

            <!-- Mobile Hamburger Toggle -->
            <button class="navbar-toggler border-0 shadow-none text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMelody" aria-controls="navbarMelody" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-list fs-2"></i>
            </button>

            <!-- Navbar Links -->
            <div class="collapse navbar-collapse" id="navbarMelody">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'index.php' && !isset($_GET['user'])) ? 'active' : '' ?>" href="index.php">
                            <i class="bi bi-compass me-1"></i> Explore Logs
                        </a>
                    </li>
                </ul>

                <!-- Auth Navigation Controls -->
                <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                    <?php if (is_logged_in()): ?>
                        <?php $user = current_user(); ?>
                        
                        <!-- Write Log CTA -->
                        <a href="editor.php" class="btn btn-gradient btn-sm px-3 py-2 rounded-pill me-2">
                            <i class="bi bi-pencil-square me-1"></i> Write Log
                        </a>

                        <!-- User Profile Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-outline-glass btn-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="author-avatar" style="width: 24px; height: 24px; font-size: 0.75rem;">
                                    <?= mb_strtoupper(mb_substr($user['username'], 0, 1, 'UTF-8')) ?>
                                </div>
                                <span class="d-none d-sm-inline fw-semibold"><?= e($user['username']) ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                                <li class="px-3 py-2 text-muted small border-bottom border-secondary border-opacity-25">
                                    <div class="fw-bold text-white"><?= e($user['username']) ?></div>
                                    <div class="text-secondary"><?= e($user['vocal_type']) ?></div>
                                </li>
                                <li>
                                    <a class="dropdown-item mt-1" href="index.php?user=<?= e($user['id']) ?>">
                                        <i class="bi bi-collection-play me-2 text-primary"></i> My Melody Logs
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="editor.php">
                                        <i class="bi bi-plus-circle me-2 text-success"></i> New Entry
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i> Log Out
                                    </a>
                                </li>
                            </ul>
                        </div>

                    <?php else: ?>
                        <!-- Guest Controls -->
                        <a href="login.php" class="btn btn-outline-glass btn-sm px-3 py-2 rounded-pill <?= $currentPage === 'login.php' ? 'active' : '' ?>">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                        </a>
                        <a href="register.php" class="btn btn-gradient btn-sm px-3 py-2 rounded-pill <?= $currentPage === 'register.php' ? 'active' : '' ?>">
                            <i class="bi bi-person-plus me-1"></i> Join MelodyLogs
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Global Flash Notification Message Banner -->
    <?php if ($flash): ?>
        <div class="container mt-3">
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show border-0 shadow-lg d-flex align-items-center" role="alert" style="background: <?= $flash['type'] === 'success' ? 'rgba(16, 185, 129, 0.2)' : ($flash['type'] === 'danger' ? 'rgba(239, 68, 68, 0.2)' : 'rgba(245, 158, 11, 0.2)') ?>; border: 1px solid <?= $flash['type'] === 'success' ? '#10b981' : ($flash['type'] === 'danger' ? '#ef4444' : '#f59e0b') ?>; color: #ffffff;">
                <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill text-success' : ($flash['type'] === 'danger' ? 'bi-exclamation-octagon-fill text-danger' : 'bi-info-circle-fill text-warning') ?> fs-4 me-3"></i>
                <div class="flex-grow-1"><?= e($flash['message']) ?></div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content Container -->
    <main class="flex-grow-1">
