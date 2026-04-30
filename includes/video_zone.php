<?php
require_once 'includes/header.php';
requireAuth(); // Require user to be logged in

$user = $_SESSION['user'];
$role = $user['role'] == 'admin' ? '管理員' : ($user['role'] == 'player' ? '本校球員' : '一般粉絲');

// Sample videos array (placeholder for DB data)
$videos = [
    [
        'title' => '2025 全國大專盃決賽 (全場精華)',
        'description' => '精采逆轉勝，第9局下半的再見安打！',
        'url' => 'https://www.youtube.com/embed/zH3vH40qY60', 
        'date' => '2025-10-15',
        'category' => '比賽精華'
    ],
    [
        'title' => '打擊姿勢調整訓練',
        'description' => '教練針對核心發力與下盤穩定性的專項訓練。',
        'url' => 'https://www.youtube.com/embed/qL0lDq0I-Uo',
        'date' => '2025-09-20',
        'category' => '訓練解析'
    ],
    [
        'title' => '投手牛棚測速與球種分析',
        'description' => '本機測速與轉速分析，包含曲球與滑球的軌跡比較。',
        'url' => 'https://www.youtube.com/embed/U3i5M61jMIs',
        'date' => '2025-09-05',
        'category' => '科學分析'
    ],
    [
        'title' => '雙殺守備戰術演練',
        'description' => '內野手站位與補位速度實戰演練。',
        'url' => 'https://www.youtube.com/embed/G28lCgA92G4',
        'date' => '2025-08-30',
        'category' => '團隊戰術'
    ]
];
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
                            <iframe src="<?= $video['url'] ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
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
