<?php
require_once 'includes/header.php';

// Prepare data for homepage
$newsList = array_reverse($db->getAll('news')); // Latest first
$recentNews = array_slice($newsList, 0, 3);

// If empty, let's seed some news
if (empty($newsList)) {
    $db->insert('news', [
        'title' => '2026 全國大專棒球聯賽開跑！',
        'content' => '本校棒球隊將於 4 月 10 日出征，第一場對戰東海大學。歡迎各位同學前往台中棒球場加油！',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    $db->insert('news', [
        'title' => '招募新血：女棒隊熱烈招生中',
        'content' => '想要體驗熱血的棒球生活嗎？中科大女棒隊歡迎妳的加入，不限球技，只要有心！',
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
    ]);
    $db->insert('news', [
        'title' => 'OB 比賽：學長姐風采再現',
        'content' => '上週舉行的 OB 對抗賽圓滿結束，感謝畢業學長姐回校參與，場面溫馨感人。',
        'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
    ]);
    $recentNews = array_reverse($db->getAll('news'));
}
?>

<div class="page-header home-header">
    <h1>NUTC BASEBALL</h1>
    <p>國立台中科技大學．最熱血的棒球精神</p>
    <div class="home-header-actions">
        <a href="join.php" class="btn-submit">立即加入與我們同行</a>
    </div>
</div>

<section id="news-section" class="home-news-section">
    <div class="container">
        <div class="section-title">
            <h2>最新動態</h2>
        </div>
        <div class="card-container">
            <?php foreach ($recentNews as $news): ?>
                <div class="card home-news-card">
                    <div class="card-content">
                        <span class="card-tag">官方消息</span>
                        <h3 class="card-title"><?= htmlspecialchars($news['title']) ?></h3>
                        <p class="news-date"><?= date('Y-m-d', strtotime($news['created_at'])) ?></p>
                        <p class="news-excerpt"><?= mb_substr(htmlspecialchars($news['content']), 0, 45) ?>...</p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Section 2: Join Us Summary -->
<section id="join-summary" class="home-join-summary">
    <div class="container">
        <h2>與我們一起揮灑汗水</h2>
        <p>不論性別與實力，只要妳有一顆熱愛棒球的心，中科大棒球隊隨時歡迎妳的加入。</p>
        <a href="join.php" class="btn-submit btn-cta">填寫申請表單</a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
