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

// Search logic
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $videos = array_filter($videos, function($v) use ($search) {
        return stripos($v['title'], $search) !== false || stripos($v['description'], $search) !== false || stripos($v['category'], $search) !== false;
    });
}

// Function to extract YouTube ID for thumbnail
function getYoutubeThumb($url) {
    $id = '';
    if (preg_match('/embed\/([^\&\?\/]+)/', $url, $match)) {
        $id = $match[1];
    } else if (preg_match('/watch\?v=([^\&\?\/]+)/', $url, $match)) {
        $id = $match[1];
    } else if (preg_match('/youtu\.be\/([^\&\?\/]+)/', $url, $match)) {
        $id = $match[1];
    }
    
    if ($id) {
        return "https://img.youtube.com/vi/{$id}/mqdefault.jpg";
    }
    // Fallback if not a standard video (e.g. playlist)
    return 'assets/images/video-placeholder.jpg'; 
}

// Function to get the actual jump link
function getJumpLink($url) {
    if (strpos($url, 'embed') !== false) {
        // Convert embed to watch if it's a single video
        if (preg_match('/embed\/([^\&\?\/]+)/', $url, $match)) {
            return "https://www.youtube.com/watch?v=" . $match[1];
        }
    }
    return $url;
}
?>

<link rel="stylesheet" href="assets/css/member_dashboard.css">
<link rel="stylesheet" href="assets/css/video_zone.css">
<style>
    .video-search-bar {
        display: flex;
        gap: 10px;
        margin-bottom: 25px;
        background: white;
        padding: 15px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .video-search-input {
        flex: 1;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 1rem;
    }
    .video-search-btn {
        padding: 10px 25px;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 700;
        transition: var(--transition);
    }
    .video-search-btn:hover {
        background: #A00000;
        transform: translateY(-2px);
    }
    .video-card-link {
        text-decoration: none;
        color: inherit;
        display: block;
        transition: var(--transition);
    }
    .video-card-link:hover {
        transform: translateY(-5px);
    }
    .video-thumbnail {
        width: 100%;
        aspect-ratio: 16/9;
        object-fit: cover;
        border-radius: 12px 12px 0 0;
    }
    .video-play-overlay {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: var(--transition);
        color: white;
        font-size: 3rem;
    }
    .video-wrapper:hover .video-play-overlay {
        opacity: 1;
    }
</style>

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
                    <li><a href="member_dashboard.php?tab=settings"><i class="fas fa-user-circle"></i> 個人設定</a></li>
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

                <!-- Search Bar -->
                <form method="GET" class="video-search-bar">
                    <input type="text" name="search" class="video-search-input" placeholder="搜尋影片標題、描述或分類..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="video-search-btn"><i class="fas fa-search"></i> 搜尋</button>
                </form>

                <?php if (empty($videos)): ?>
                    <div style="text-align: center; padding: 50px; color: #888;">
                        <i class="fas fa-video-slash" style="font-size: 3rem; margin-bottom: 20px; display: block;"></i>
                        未找到符合搜尋條件的影片。
                    </div>
                <?php else: ?>
                    <div class="video-grid">
                        <?php foreach ($videos as $video): ?>
                        <a href="<?= getJumpLink($video['url']) ?>" target="_blank" class="video-card-link">
                            <div class="video-card">
                                <div class="video-wrapper" style="position: relative;">
                                    <img src="<?= getYoutubeThumb($video['url']) ?>" class="video-thumbnail" alt="<?= htmlspecialchars($video['title']) ?>">
                                    <div class="video-play-overlay">
                                        <i class="fas fa-play-circle"></i>
                                    </div>
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
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
