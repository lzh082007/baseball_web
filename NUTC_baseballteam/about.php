<?php
require_once 'includes/header.php';

$histories = $db->getAll('team_history');
if (empty($histories)) {
    // Seed sample history
    $db->insert('team_history', [
        'team_id' => 1,
        'title' => '2012 創團元年',
        'content' => '國立台中科技大學棒球隊正式成立，集結熱愛棒球的學子，開啟中科大棒球新篇章。'
    ]);
    $db->insert('team_history', [
        'team_id' => 1,
        'title' => '2018 全國大專聯賽突破',
        'content' => '球隊在全國大專聯賽中取得歷史最佳戰績，成功打入全國前十六強，展現強大戰鬥力。'
    ]);
    $db->insert('team_history', [
        'team_id' => 2,
        'title' => '2024 女棒隊招生開啟',
        'content' => '致力推廣棒球運動多元化，正式開啟女棒招生計畫，為校隊注入新活力。'
    ]);
    $histories = $db->getAll('team_history');
}
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
        
        <div class="timeline-container">
            <?php foreach ($histories as $index => $h): ?>
                <div class="timeline-item">
                    <div class="timeline-year">
                        <?= explode(' ', $h['title'])[0] ?>
                    </div>
                    <div class="timeline-content">
                        <h3><?= implode(' ', array_slice(explode(' ', $h['title']), 1)) ?></h3>
                        <p><?= nl2br(htmlspecialchars($h['content'])) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
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
