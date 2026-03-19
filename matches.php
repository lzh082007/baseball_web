<?php
include 'includes/header.php';
require_once 'includes/db_handler.php';
$gameDB = new JsonDB('games');
$games = $gameDB->getAll();

// Sort by date, latest first for past matches
usort($games, function($a, $b) {
    return strtotime($b['game_date']) - strtotime($a['game_date']);
});

$past = array_filter($games, function($g) {
    return strtotime($g['game_date']) < time();
});

$future = array_filter($games, function($g) {
    return strtotime($g['game_date']) >= time();
});
?>

<div style="padding: 6rem 2rem; max-width: 1200px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 6rem;">
        <h1 style="font-size: 3.5rem; margin-bottom: 1rem;">賽事資訊彙整</h1>
        <p style="color: var(--text-secondary); font-size: 1.25rem;">隨時追蹤球隊的每一場戰役，從過往的光榮到未來的期待。</p>
    </div>

    <!-- Future Matches -->
    <section style="margin-bottom: 8rem;">
        <h2 style="font-size: 2.25rem; margin-bottom: 3rem; display: flex; align-items: center; gap: 1rem;">
            <i class="fas fa-calendar-alt" style="color: var(--primary-color);"></i> 即將到來的對決
        </h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">
            <?php if(empty($future)): ?>
                <div class="glass-morphism" style="padding: 3rem; text-align: center; color: var(--text-secondary); grid-column: span 2;">
                    目前暫無排定的新賽事，敬請期待球經更新。
                </div>
            <?php else: ?>
                <?php foreach($future as $game): ?>
                    <div class="glass-morphism" style="padding: 2.5rem; position: relative; overflow: hidden; background: linear-gradient(135deg, rgba(0, 71, 171, 0.1), rgba(30, 41, 59, 0.4));">
                        <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 1rem; font-weight: 700;">
                            <?= htmlspecialchars($game['game_date']) ?>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;"><?= htmlspecialchars($game['opponent']) ?></h3>
                                <p style="color: var(--text-secondary);"><i class="fas fa-map-marker-alt" style="font-size: 0.8125rem;"></i> <?= htmlspecialchars($game['location']) ?></p>
                            </div>
                            <div style="font-size: 3rem; opacity: 0.2;"><i class="fas fa-baseball-ball"></i></div>
                        </div>
                        <div style="margin-top: 2rem; border-top: 1px solid var(--glass-border); padding-top: 2rem; display: flex; justify-content: center;">
                            <span style="font-weight: 700; color: var(--primary-color); letter-spacing: 2px;">UPCOMING MATCH</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Past Matches -->
    <section>
        <h2 style="font-size: 2.25rem; margin-bottom: 3rem; display: flex; align-items: center; gap: 1rem;">
            <i class="fas fa-history" style="color: var(--secondary-color);"></i> 經典賽事回顧
        </h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem;">
            <?php foreach($past as $game): ?>
                <div class="glass-morphism" style="padding: 2rem; background: rgba(30, 41, 59, 0.4); border-radius: 16px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 2rem;">
                        <span style="font-size: 0.8125rem; color: var(--text-secondary); font-weight: 700;"><?= htmlspecialchars($game['game_date']) ?></span>
                        <span style="background: <?= $game['result'] == 'W' ? 'rgba(16,185,129,0.1)' : 'rgba(239, 68, 68, 0.1)' ?>; color: <?= $game['result'] == 'W' ? '#10B981' : '#EF4444' ?>; padding: 0.25rem 0.75rem; border-radius: 6px; font-weight: 800; font-size: 0.75rem; text-transform: uppercase;">
                            <?= $game['result'] == 'W' ? '勝利' : '落敗' ?>
                        </span>
                    </div>
                    <div style="text-align: center; margin-bottom: 2rem;">
                        <h4 style="font-size: 1.25rem; margin-bottom: 0.5rem;"><?= htmlspecialchars($game['opponent']) ?></h4>
                        <p style="color: var(--text-secondary); font-size: 0.875rem;"><?= htmlspecialchars($game['location']) ?></p>
                    </div>
                    <?php if(isset($_SESSION['user'])): ?>
                        <div style="display: flex; justify-content: center; border-top: 1px solid var(--glass-border); padding-top: 1.5rem;">
                            <a href="member_zone.php" style="color: var(--primary-color); text-decoration: none; font-weight: 700; font-size: 0.875rem;">查看數據亮點 <i class="fas fa-arrow-right" style="font-size: 0.75rem;"></i></a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<?php include 'includes/footer.php'; ?>
