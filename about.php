<?php
include 'includes/header.php';
require_once 'includes/db_handler.php';
$historyDB = new JsonDB('team_histories');
$histories = $historyDB->getAll();
?>

<div style="padding: 6rem 2rem; max-width: 1000px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 6rem;">
        <h1 style="font-size: 3.5rem; margin-bottom: 1rem;">關於我們</h1>
        <p style="color: var(--text-secondary); font-size: 1.25rem;">追溯本校球隊的源起與那些輝煌時刻。</p>
    </div>

    <div style="display: flex; flex-direction: column; gap: 4rem;">
        <?php foreach($histories as $h): ?>
            <div class="glass-morphism animate-fade" style="padding: 3.5rem; background: rgba(30, 41, 59, 0.4);">
                <div style="font-size: 0.875rem; color: var(--primary-color); font-weight: 800; margin-bottom: 1rem; letter-spacing: 2px; text-transform: uppercase;">
                    成立於西元 <?= $h['start_year'] ?> 年
                </div>
                <h2 style="font-size: 2.25rem; margin-bottom: 2rem;"><?= htmlspecialchars($h['title']) ?></h2>
                <div style="line-height: 2.2; color: #CBD5E1; font-size: 1.125rem;">
                    <?= nl2br(htmlspecialchars($h['content'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div style="margin-top: 6rem; text-align: center; padding: 4rem; border-top: 1px solid var(--glass-border);">
        <h3 style="font-size: 1.75rem; margin-bottom: 2rem;">我們的球隊憲章</h3>
        <p style="color: var(--text-secondary); font-style: italic; font-size: 1.25rem;">"團隊第一、拚搏到底、勝不驕、敗不餒。"</p>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
