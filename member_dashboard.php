<?php
require_once 'includes/header.php';
requireAuth(); // Use pre-defined auth function

$user = $_SESSION['user'];
$role = $user['role'] == 'admin' ? '管理員' : ($user['role'] == 'player' ? '本校球員' : '一般粉絲');

// Fetch user-specific stats if role == player
$playerStats = null;
if ($user['role'] == 'player' || $user['role'] == 'admin') {
    // Just show a summary or placeholder for now
    $playerStats = [
        'hits' => 12,
        'rbi' => 5,
        'avg' => 0.345,
        'games' => 15
    ];
}
?>

<div class="page-header">
    <h1>會員控制台</h1>
    <p>歡迎回來，<?= htmlspecialchars($user['name']) ?>。目前的權限等級：<span class="stats-primary"
            style="font-weight: 800;"><?= $role ?></span></p>
</div>

<section>
    <div class="container">
        <div class="member-dashboard-layout">
            <!-- Side Menu -->
            <div class="member-side-menu">
                <ul>
                    <li><a href="member_dashboard.php" class="active"><i class="fas fa-home"></i> 控制台</a></li>
                    <li><a href="matches.php"><i class="fas fa-baseball-ball"></i> 比賽記錄</a></li>
                    <li><a href="video_zone.php"><i class="fas fa-video"></i> 影片專區</a></li>
                    <li><a href="#"><i class="fas fa-user-circle"></i> 個人設定</a></li>
                </ul>
                <hr>
                <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> 登出系統</a>
            </div>

            <!-- Main Content -->
            <div>
                <div class="section-title member-section-title">
                    <h2>數據概覽</h2>
                    <p>Personal Performance Overview</p>
                </div>

                <div class="member-stats-grid">
                    <div class="member-stats-card">
                        <h4>總安打</h4>
                        <div class="stats-value stats-primary"><?= $playerStats['hits'] ?? 0 ?></div>
                    </div>
                    <div class="member-stats-card">
                        <h4>總打點</h4>
                        <div class="stats-value stats-dark"><?= $playerStats['rbi'] ?? 0 ?></div>
                    </div>
                    <div class="member-stats-card">
                        <h4>打擊率</h4>
                        <div class="stats-value stats-secondary"><?= number_format($playerStats['avg'] ?? 0, 3) ?></div>
                    </div>
                    <div class="member-stats-card">
                        <h4>參戰場數</h4>
                        <div class="stats-value stats-black"><?= $playerStats['games'] ?? 0 ?></div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>