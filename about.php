<?php
require_once 'includes/header.php';

$histories = $db->getAll('teamhistory');
usort($histories, function($a, $b) {
    return $a['start_year'] <=> $b['start_year'];
});
?>

<div class="page-header">
    <h1>球隊歷史</h1>
    <p>輝煌歷史與執著夢想，傳承 2012 至今的熱血中科精神。</p>
</div>

<!-- Section 1: Timeline -->
<section class="about-section-1">
    <div class="container">
        <div class="section-title">
            <h2>歷史脈絡</h2>
        </div>
        
        <div class="timeline-wrapper">
            <div class="timeline-container">
                <?php foreach ($histories as $index => $h): ?>
                    <div class="timeline-item">
                        <div class="timeline-card">
                            <div class="timeline-year"><?= (int)$h['start_year'] ?></div>
                            <h3><?= htmlspecialchars($h['title']) ?></h3>
                            <p><?= nl2br(htmlspecialchars($h['content'])) ?></p>
                        </div>
                        <div class="timeline-connector"></div>
                        <div class="timeline-dot"></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="about-dark-section">
    <div class="container">
        <div class="about-grid">
            <div>
                <h2 class="about-h2-secondary">校制變革歷程</h2>
                <p class="about-p-highlight">中科大棒球隊緊隨校慶與發展脈絡，從基礎社團轉向專業校隊培訓系統，我們深信紀律與團隊是成功的核心。</p>
                <div class="about-quote-block">
                    <p><i>「我們在球場上學到的，不只是揮棒與傳接，更是人生的態度。」</i></p>
                    <p class="about-quote-author">- 歷任教練團致辭</p>
                </div>
            </div>
            <div class="trophy-card">
                <i class="fas fa-trophy trophy-icon"></i>
                <h3 class="trophy-h3">追求卓越</h3>
                <p>自 2012 年起的點滴累積，每一場勝利都印證了我們的進步。我們不僅追求成績，更追求每一位球員的成長。</p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
