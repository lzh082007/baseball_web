<?php
require_once 'includes/header.php';
requireAdmin();

$pendingApps = array_filter($db->getAll('form_application'), function($a) {
    return $a['status'] == 'pending';
});

$pendingMembers = array_filter($db->getAll('member'), function($m) {
    return $m['status'] == 'pending';
});
?>

<div class="page-header">
    <h1>管理後台系統</h1>
    <p>掌控系統運作，維護球隊最新資訊與成員名單。</p>
</div>

<section>
    <div class="container">
        <!-- Dashboard Summary Grid -->
        <div class="admin-summary-grid">
            <div class="admin-summary-card primary">
                <i class="fas fa-file-signature" style="color: var(--primary);"></i>
                <h3>待核准申請</h3>
                <div class="admin-count"><?= count($pendingApps) ?></div>
                <a href="admin_applications.php" class="admin-link primary">前往審核 <i class="fas fa-chevron-right"></i></a>
            </div>
            
            <div class="admin-summary-card secondary">
                <i class="fas fa-user-plus" style="color: var(--secondary);"></i>
                <h3>待審核成員</h3>
                <div class="admin-count"><?= count($pendingMembers) ?></div>
                <a href="admin_members.php" class="admin-link secondary">前往批准 <i class="fas fa-chevron-right"></i></a>
            </div>

            <div class="admin-summary-card dark">
                <i class="fas fa-baseball-ball" style="color: #333;"></i>
                <h3>已登錄比賽</h3>
                <div class="admin-count"><?= count($db->getAll('game')) ?></div>
                <a href="admin_matches.php" class="admin-link dark">管理賽事 <i class="fas fa-plus-circle"></i></a>
            </div>
        </div>

        <div class="section-title admin-section-mod">
            <h2>管理模組清單</h2>
            <p>Select a module to manage its contents.</p>
        </div>

        <div class="admin-module-grid">
            <a href="admin_players.php" class="admin-module-card hover-primary">
                <i class="fas fa-users"></i> 球員管理 (CRUD)
            </a>
            <a href="admin_news.php" class="admin-module-card hover-secondary">
                <i class="fas fa-newspaper"></i> 最新消息 (CRUD)
            </a>
            <a href="admin_ob.php" class="admin-module-card hover-primary">
                <i class="fas fa-graduation-cap"></i> 中科 OB 管理
            </a>
            <a href="admin_videos.php" class="admin-module-card hover-secondary">
                <i class="fas fa-video"></i> 影片專區管理
            </a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
