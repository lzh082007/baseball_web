<?php
require_once 'includes/header.php';

$matches = $db->getAll('game');

// Search logic
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $matches = array_filter($matches, function($m) use ($search) {
        return stripos($m['opponent'], $search) !== false || stripos($m['game_date'], $search) !== false || stripos($m['result'], $search) !== false;
    });
}

// Split into past and future
usort($matches, function($a, $b) {
    return strtotime($b['game_date']) - strtotime($a['game_date']);
});

$today = date('Y-m-d');
$pastGames = array_filter($matches, function($m) use ($today) { return $m['game_date'] < $today; });
$futureGames = array_filter($matches, function($m) use ($today) { return $m['game_date'] >= $today; });
?>

<div class="page-header">
    <h1>賽事資訊</h1>
    <p>回顧榮耀戰績，展望未來的熱血對決。</p>
</div>

<!-- Section 1: Matches -->
<section class="matches-section">
    <div class="container">
        
        <!-- Controls: Toggle and Search -->
        <div class="matches-controls" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 30px;">
            <div class="matches-tabs" style="margin-bottom: 0;">
                <button class="btn-tab active" data-target="future-matches">未來賽程</button>
                <button class="btn-tab" data-target="past-matches">過去賽程</button>
            </div>
            <form method="GET" class="search-bar-container" style="margin-bottom: 0; width: 350px;">
                <input type="text" name="search" class="search-bar-input" placeholder="搜尋對手、日期或結果..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-bar-btn"><i class="fas fa-search"></i> 搜尋</button>
            </form>
        </div>

        <!-- Future Matches Container -->
        <div id="future-matches" class="matches-tab-content">
            <?php if (empty($futureGames)): ?>
                <p class="text-center">目前暫無賽程安排。</p>
            <?php else: ?>
                <div class="matches-table-card">
                    <table class="matches-table">
                        <tbody>
                            <?php foreach ($futureGames as $g): ?>
                                <tr class="match-item" data-search="<?= htmlspecialchars(strtolower($g['opponent'] . ' ' . $g['game_date'])) ?>">
                                    <td class="matches-date-cell"><?= $g['game_date'] ?></td>
                                    <td class="matches-opponent-cell"><?= htmlspecialchars($g['opponent']) ?></td>
                                    <td class="matches-status-cell"><span class="badge badge-upcoming">UPCOMING</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Past Matches Container -->
        <div id="past-matches" class="matches-tab-content" style="display: none;">
            <?php if (empty($pastGames)): ?>
                <div style="text-align: center; padding: 50px; color: #888; background: white; border-radius: 15px; width: 100%; border: 1px solid #eee;">
                    <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 20px; display: block; color: #ddd;"></i>
                    目前的搜尋條件找不到相關賽事。
                </div>
            <?php else: ?>
                <div class="card-container">
                    <?php foreach ($pastGames as $g): ?>
                        <div class="card match-item" data-search="<?= htmlspecialchars(strtolower($g['opponent'] . ' ' . $g['game_date'] . ' ' . $g['result'])) ?>">
                            <div class="card-content">
                                <div class="match-card-header">
                                    <span class="match-card-date"><?= $g['game_date'] ?></span>
                                    <?php 
                                        $isWin = strpos($g['result'], '勝') !== false;
                                        $badgeClass = $isWin ? 'badge-win' : 'badge-loss';
                                        $resultText = $isWin ? 'VICTORY' : 'DEFEAT';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= $resultText ?></span>
                                </div>
                                <h3 class="card-title match-card-title-lg">vs <?= htmlspecialchars($g['opponent']) ?></h3>
                                <p class="match-card-result-lg">
                                    <?= htmlspecialchars($g['result']) ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabs = document.querySelectorAll('.btn-tab');
    const contents = document.querySelectorAll('.matches-tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            
            const target = tab.getAttribute('data-target');
            contents.forEach(c => {
                if(c.id === target) {
                    c.style.display = 'block';
                } else {
                    c.style.display = 'none';
                }
            });
        });
    });
});
</script>
