<?php
include 'includes/header.php';
require_once 'includes/db_handler.php';
$obDB = new JsonDB('obs');
$obs = $obDB->getAll();
?>

<div style="padding: 6rem 2rem; max-width: 1200px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 6rem;">
        <h1 style="font-size: 3.5rem; margin-bottom: 1rem;">中科 OB <span style="font-size: 1.5rem; color: var(--text-secondary);">(畢業校友)</span></h1>
        <p style="color: var(--text-secondary); font-size: 1.25rem;">歷史的傳承，永不熄滅的球魂。致敬每一位為中科大棒球隊奉獻過的學長姐。</p>
    </div>

    <!-- Timeline of Alumni -->
    <div style="position: relative; padding-left: 2rem; border-left: 2px solid var(--glass-border); max-width: 800px; margin: 0 auto;">
        <?php foreach($obs as $ob): ?>
            <div style="position: relative; padding-bottom: 4rem;">
                <div style="position: absolute; left: -2.35rem; top: 0.5rem; width: 1.25rem; height: 1.25rem; border-radius: 50%; background: var(--primary-color); border: 4px solid var(--bg-dark); box-shadow: 0 0 10px var(--primary-color);"></div>
                <div class="glass-morphism" style="padding: 2.5rem; background: rgba(30, 41, 59, 0.4); border-radius: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h3 style="font-size: 1.75rem; color: white;"><?= htmlspecialchars($ob['OB_name']) ?></h3>
                        <span style="background: rgba(255, 255, 255, 0.05); padding: 0.4rem 1rem; border-radius: 999px; font-weight: 700; color: var(--primary-color); font-size: 0.875rem;">
                            <?= htmlspecialchars($ob['graduation_year']) ?> 年畢業
                        </span>
                    </div>
                    <p style="color: var(--text-secondary); line-height: 1.7; font-size: 1.1rem; display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fas fa-briefcase" style="font-size: 0.875rem; color: var(--secondary-color);"></i> <?= htmlspecialchars($ob['status']) ?>
                    </p>
                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--glass-border); display: flex; gap: 1rem; color: var(--text-secondary); font-size: 0.875rem;">
                        <span><i class="fas fa-medal"></i> 優異校友</span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
