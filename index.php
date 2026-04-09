<?php
require_once 'includes/header.php';

// Prepare data for homepage
$newsList = array_reverse($db->getAll('news')); // Latest first
$recentNews = array_slice($newsList, 0, 3);


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
