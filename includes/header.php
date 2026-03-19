<?php
session_start();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>中科大棒球隊 | 官方管理系統</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+TC:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="glass-morphism" id="mainNav">
        <a href="index.php" style="text-decoration: none; color: white; display: flex; align-items: center; gap: 0.75rem;">
            <div style="background: var(--primary-color); width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.25rem;">N</div>
            <span style="font-weight: 700; font-size: 1.1rem; letter-spacing: 0.5px;">中科大棒球隊</span>
        </a>
        <ul class="nav-links">
            <li><a href="about.php" class="<?= $current_page == 'about.php' ? 'active' : '' ?>">關於我們</a></li>
            <li><a href="matches.php" class="<?= $current_page == 'matches.php' ? 'active' : '' ?>">賽事資訊</a></li>
            <li><a href="players.php" class="<?= $current_page == 'players.php' ? 'active' : '' ?>">球員資訊</a></li>
            <li><a href="join.php" class="<?= $current_page == 'join.php' ? 'active' : '' ?>">加入我們</a></li>
            <li><a href="ob.php" class="<?= $current_page == 'ob.php' ? 'active' : '' ?>">中科OB</a></li>
            <?php if(isset($_SESSION['user'])): ?>
                <li><a href="member_zone.php" class="<?= $current_page == 'member_zone.php' ? 'active' : '' ?>">會員專區</a></li>
                <?php if($_SESSION['user']['account'] == 'admin'): ?>
                    <li><a href="admin/" class="btn-login" style="background: #334155;">後台管理</a></li>
                <?php endif; ?>
                <li><a href="logout.php" class="btn-login" style="background: rgba(239, 68, 68, 0.8);">登出</a></li>
            <?php else: ?>
                <li><a href="login.php" class="btn-login">登入</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <main>
