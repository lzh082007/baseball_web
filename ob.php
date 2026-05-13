<?php
require_once 'includes/header.php';

$obMembers = $db->getAll('ob');

// Search logic
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $obMembers = array_filter($obMembers, function($ob) use ($search) {
        return stripos($ob['OB_name'], $search) !== false || stripos((string)$ob['graduation_year'], $search) !== false || stripos($ob['status'], $search) !== false;
    });
}

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
        <div class="section-title" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 40px;">
            <h2 style="margin-bottom: 0;">畢業英雄榜</h2>
            <form method="GET" class="search-bar-container" style="margin-bottom: 0; width: 350px;">
                <input type="text" name="search" class="search-bar-input" placeholder="搜尋姓名、畢業年度或成就..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-bar-btn"><i class="fas fa-search"></i> 搜尋</button>
            </form>
        </div>

        <div class="card-container">
            <?php if (empty($obMembers)): ?>
                <div style="width: 100%; text-align: center; padding: 60px; color: #888; background: white; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #eee;">
                    <i class="fas fa-search" style="font-size: 3.5rem; margin-bottom: 20px; display: block; color: #ddd;"></i>
                    找不到符合條件的 OB 學長姐。
                </div>
            <?php else: ?>
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
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>