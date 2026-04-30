<?php
require_once 'includes/header.php';
requireAuth(); // Require user to be logged in

$user = $_SESSION['user'];
$role = $user['role'] == 'admin' ? '管理員' : ($user['role'] == 'player' ? '本校球員' : '一般粉絲');

// Fetch videos from database
$videos = $db->getAll('video');

// Sort videos by date descending
usort($videos, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Helper function to convert standard YouTube links to embed links
function getEmbedUrl($url) {
    if (preg_match('/youtube\.com\/watch\?v=([^\&\?\/]+)/', $url, $id)) {
        return 'https://www.youtube.com/embed/' . $id[1];
    } else if (preg_match('/youtu\.be\/([^\&\?\/]+)/', $url, $id)) {
        return 'https://www.youtube.com/embed/' . $id[1];
    }
    return $url;
}
?>

<link rel="stylesheet" href="assets/css/member_dashboard.css">
<link rel="stylesheet" href="assets/css/video_zone.css">

<div class="page-header">
    <h1>影片專區</h1>
    <p>會員專屬的賽事精華與內部訓練解析庫。您目前的權限等級：<span class="stats-primary" style="font-weight: 800;"><?= $role ?></span></p>
</div>

<section>
    <div class="container">
        <div class="member-dashboard-layout">
            <!-- Side Menu -->
            <div class="member-side-menu">
                <ul>
                    <li><a href="member_dashboard.php"><i class="fas fa-home"></i> 控制台</a></li>
                    <li><a href="matches.php"><i class="fas fa-baseball-ball"></i> 比賽記錄</a></li>
                    <li><a href="video_zone.php" class="active"><i class="fas fa-video"></i> 影片專區</a></li>
                    <li><a href="#"><i class="fas fa-user-circle"></i> 個人設定</a></li>
                </ul>
                <hr>
                <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> 登出系統</a>
            </div>

            <!-- Main Content -->
            <div>
                <div class="section-title member-section-title">
                    <h2>影音特區</h2>
                    <p>Exclusive Member Content</p>
                </div>

                <div class="video-grid">
                    <?php foreach ($videos as $video): ?>
                    <div class="video-card">
                        <div class="video-wrapper">
                            <iframe src="<?= getEmbedUrl($video['url']) ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
                        <div class="video-info">
                            <div class="video-meta-top">
                                <span class="video-category"><?= htmlspecialchars($video['category']) ?></span>
                                <span class="video-date"><?= htmlspecialchars($video['date']) ?></span>
                            </div>
                            <h3 class="video-title"><?= htmlspecialchars($video['title']) ?></h3>
                            <p class="video-desc"><?= htmlspecialchars($video['description']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
