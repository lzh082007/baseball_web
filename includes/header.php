<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚾ 中科大棒球隊 - 球隊管理系統</title>
    <meta name="description" content="國立臺中科技大學棒球隊官方管理系統，提供賽事資訊、球員數據、AI 戰報分析及招募資訊。">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&family=Noto+Sans+TC:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <?php 
    $css_file = 'assets/css/' . basename($_SERVER['PHP_SELF'], '.php') . '.css';
    if (file_exists($css_file)): ?>
        <link rel="stylesheet" href="<?= $css_file ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav>
        <div class="logo">
            <a href="index.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;">
                <img src="https://img.icons8.com/color/96/baseball.png" alt="Logo" style="height: 40px;">
                <span style="font-size: 1.1rem; font-weight: 900; letter-spacing: 0;">NUTC</span>
            </a>
        </div>
        
        <div class="nav-links-container">
            <ul class="nav-links">
                <li class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>"><a href="index.php">首頁</a></li>
                <li class="<?= basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : '' ?>"><a href="about.php">球隊歷史</a></li>
                <li class="<?= basename($_SERVER['PHP_SELF']) == 'matches.php' ? 'active' : '' ?>"><a href="matches.php">賽事資訊</a></li>
                <li class="<?= basename($_SERVER['PHP_SELF']) == 'players.php' ? 'active' : '' ?>"><a href="players.php">球員陣容</a></li>
                <li class="<?= basename($_SERVER['PHP_SELF']) == 'join.php' ? 'active' : '' ?>"><a href="join.php">招募資訊</a></li>
                <li class="<?= basename($_SERVER['PHP_SELF']) == 'ob.php' ? 'active' : '' ?>"><a href="ob.php">OB 專區</a></li>
                <?php if (isLoggedIn()): ?>
                    <li class="<?= strpos($_SERVER['PHP_SELF'], 'dashboard') !== false ? 'active' : '' ?>"><a href="member_dashboard.php">我的數據</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="user-action">
            <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                    <a href="admin_dashboard.php" style="margin-right: 15px; color: var(--secondary); text-decoration: none; font-size: 0.8rem; font-weight: 700;">管理後台</a>
                <?php endif; ?>
                <a href="logout.php" class="btn-login" style="background: transparent; color: var(--text-white); border: 2px solid var(--primary); padding: 5px 15px; font-size: 0.8rem;">登出</a>
            <?php else: ?>
                <a href="login.php" class="btn-login" style="padding: 5px 20px; font-size: 0.9rem;">登入</a>
            <?php endif; ?>
        </div>
    </nav>
