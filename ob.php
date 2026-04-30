<?php
require_once 'includes/header.php';

$obMembers = $db->getAll('ob');
usort($obMembers, function ($a, $b) {
    return $b['graduation_year'] <=> $a['graduation_year'];
});
?>
<div class="page-header">
    <h1>OB 專區</h1>
    <p>傳承經典，每一屆畢業學長姐都是後輩的耀眼楷模。</p>
</div>

<!-- Section 1: Alumni List -->
<section class="ob-section">
    <div class="container">
        <div class="section-title">
            <h2>畢業英雄榜</h2>
        </div>

        <div class="card-container">
            <?php foreach ($obMembers as $ob): ?>
                <div class="card ob-card">
                    <div class="ob-card-img-box">
                        <?php if (!empty($ob['image_path'])): ?>
                            <img src="<?= htmlspecialchars($ob['image_path']) ?>" alt="<?= htmlspecialchars($ob['OB_name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <i class="fas fa-user-graduate"></i>
                        <?php endif; ?>
                    </div>
                    <div class="card-content">
                        <span class="ob-card-badge">
                            <?= $ob['graduation_year'] ?> 年度畢業
                        </span>
                        <h3 class="card-title ob-card-name"><?= htmlspecialchars($ob['OB_name']) ?></h3>
                        <div class="ob-card-achievements">
                            <p class="label">🏆 豐功偉業</p>
                            <p class="content"><?= nl2br(htmlspecialchars($ob['status'])) ?></p>
                        </div>
                        <p class="ob-card-desc">中科 OB 不僅僅是回憶，更是力量。感謝學長姐對球隊的深遠影響。</p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>