<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once 'includes/header.php';

$newsList = $db->getAll('news');

// Search logic
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $newsList = array_filter($newsList, function($n) use ($search) {
        return stripos($n['title'], $search) !== false || stripos($n['content'], $search) !== false;
    });
}

usort($newsList, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
// Remove array_slice so we render all news but hide them via JS
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
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 30px;">
            <div class="section-title" style="margin-bottom: 0;">
                <h2 style="margin-bottom: 0;">最新動態</h2>
            </div>
            <form method="GET" class="search-bar-container" style="margin-bottom: 0; max-width: 350px;">
                <input type="text" name="search" class="search-bar-input" placeholder="搜尋消息..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-bar-btn"><i class="fas fa-search"></i> 搜尋動態</button>
            </form>
        </div>
        <div class="card-container" id="newsContainer">
            <?php if (empty($newsList)): ?>
                <div class="empty-state-message">
                    <i class="fas fa-search"></i>
                    很抱歉，找不到與「<?= htmlspecialchars($search) ?>」相關的動態。
                </div>
            <?php else: ?>
                <?php foreach ($newsList as $index => $news): ?>
                    <div class="card home-news-card" data-index="<?= $index ?>" style="<?= $index >= 3 ? 'display: none;' : '' ?>">
                        <div class="card-content">
                            <span class="card-tag">官方消息</span>
                            <h3 class="card-title"><?= htmlspecialchars($news['title']) ?></h3>
                            <p class="news-date"><?= date('Y-m-d', strtotime($news['created_at'])) ?></p>
                            <p class="news-excerpt"><?= mb_substr(htmlspecialchars($news['content']), 0, 45) ?>...</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="slider-controls">
            <button id="prevNews" class="btn-slider" disabled>&#10094; 上一則</button>
            <button id="nextNews" class="btn-slider">下一則 &#10095;</button>
        </div>
    </div>
</section>

<!-- Section 2: Join Us Summary -->
<section id="join-summary" class="home-join-summary">
    <div class="container">
        <h2>與我們一起揮灑汗水，實現夢想</h2>
        <p>不論性別與實力，只要有一顆熱愛棒球的心，中科大棒球隊隨時歡迎妳的加入。</p>
        <a href="join.php" class="btn-submit btn-cta">填寫申請表單</a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.home-news-card');
    const prevBtn = document.getElementById('prevNews');
    const nextBtn = document.getElementById('nextNews');
    let currentIndex = 0;
    
    const itemsToShow = 3;

    function updateSlider() {
        cards.forEach((card, index) => {
            if (index >= currentIndex && index < currentIndex + itemsToShow) {
                card.style.display = ''; 
            } else {
                card.style.display = 'none';
            }
        });
        
        if (prevBtn && nextBtn) {
            prevBtn.disabled = currentIndex === 0;
            nextBtn.disabled = currentIndex + itemsToShow >= cards.length;
        }
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            if (currentIndex > 0) {
                currentIndex--;
                updateSlider();
            }
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            if (currentIndex + itemsToShow < cards.length) {
                currentIndex++;
                updateSlider();
            }
        });
    }
    
    if (cards.length <= itemsToShow) {
        if (prevBtn) prevBtn.disabled = true;
        if (nextBtn) nextBtn.disabled = true;
    }
});
</script>
