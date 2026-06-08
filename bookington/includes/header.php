<?php
    require_once __DIR__ . '/auth.php';
    $user = getCurrentUser();

    $showSearch = isset($showSearch) ? $showSearch : false;
    $showWelcome = isset($showWelcome) ? $showWelcome : false;
?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $pageTitle ?? 'Bookington' ?></title>
        <link rel="stylesheet" href="<?= $cssPath ?? 'bookington/css/style.css' ?>">
    </head>
    <body>
        <nav class="navbar">
            <a href="dashboard.php" class="navbar-brand">
                <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="5" y="8" width="30" height="28" rx="4" stroke="white" stroke-width="2"/>
                    <rect x="12" y="4" width="3" height="8" rx="1.5" fill="white"/>
                    <rect x="25" y="4" width="3" height="8" rx="1.5" fill="white"/>
                    <line x1="5" y1="17" x2="35" y2="17" stroke="white" stroke-width="2"/>
                    <rect x="10" y="22" width="5" height="4" rx="1" fill="white"/>
                    <rect x="18" y="22" width="5" height="4" rx="1" fill="white"/>
                    <rect x="26" y="22" width="5" height="4" rx="1" fill="white"/>
                </svg>
                Bookington
            </a>

            <div class="navbar-right">
                <?php if ($showSearch): ?>
                    <div class="navbar-search">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" id="search-input" placeholder="Pesquisar" onkeyup="filterTable()">
                    </div>
                <?php endif; ?>

                <?php if ($user && $showWelcome): ?>
                    <?php if ($user && $showWelcome): ?>
                    <span class="navbar-welcome">
                        Bem-vindo, <?= htmlspecialchars(explode(' ', $user['nome'])[0]) ?>!
                    </span>
                    <div class="navbar-avatar" title="<?= htmlspecialchars($user['nome']) ?>" onclick="window.location='logout.php'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                <?php elseif (!$user): ?>
                    <div class="navbar-actions">
                        <span class="navbar-text">Já possui uma conta?</span>
                        <a href="index.php" class="btn-nav-login">Login</a>
                    </div>
                <?php endif; ?>
            </div>
        </nav>