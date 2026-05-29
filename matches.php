<?php
require_once 'includes/header.php';

$matches = $db->getAll('game');

$pdo = $db->getPdo();
$live_stmt = $pdo->query("SELECT game_id FROM game_live_state WHERE is_ended = 0");
$live_game_ids = $live_stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

// Search logic
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $matches = array_filter($matches, function($m) use ($search) {
        return stripos($m['opponent'], $search) !== false || stripos($m['game_date'], $search) !== false || stripos($m['result'], $search) !== false;
    });
}

// Sort games by date (newest first)
usort($matches, function($a, $b) {
    return strtotime($b['game_date']) - strtotime($a['game_date']);
});

$today = date('Y-m-d');

// Today's matches: scheduled for today OR currently live
$todayGames = array_filter($matches, function($m) use ($today, $live_game_ids) {
    return $m['game_date'] === $today || in_array($m['Game_id'], $live_game_ids);
});

// Future matches: after today AND not live AND no result
$futureGames = array_filter($matches, function($m) use ($today, $live_game_ids) {
    $isLive = in_array($m['Game_id'], $live_game_ids) && empty($m['result']);
    return $m['game_date'] > $today && empty($m['result']) && !$isLive;
});

// Past matches: before today OR has result (and not currently live today)
$pastGames = array_filter($matches, function($m) use ($today, $live_game_ids) {
    $isLive = in_array($m['Game_id'], $live_game_ids) && empty($m['result']);
    if ($isLive) return false;
    return $m['game_date'] < $today || !empty($m['result']);
});

// Set default active tab
$defaultTab = !empty($todayGames) ? 'today-matches' : 'past-matches';
?>

<div class="page-header">
    <h1>賽事資訊</h1>
    <p>回顧榮耀戰績，展望未來的熱血對決。</p>
</div>

<!-- Section 1: Matches -->
<section class="matches-section">
    <div class="container">
        
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 40px;">
            <div class="matches-tabs" style="margin-bottom: 0; display: flex; align-items: center; gap: 10px;">
                <button class="btn-tab <?= $defaultTab === 'past-matches' ? 'active' : '' ?>" data-target="past-matches" style="height: 48px;">過去</button>
                <button class="btn-tab <?= $defaultTab === 'today-matches' ? 'active' : '' ?>" data-target="today-matches" style="height: 48px;">今日</button>
                <button class="btn-tab" data-target="future-matches" style="height: 48px;">未來</button>
            </div>
            <form method="GET" class="search-bar-container" style="margin-bottom: 0;">
                <input type="text" name="search" class="search-bar-input" placeholder="搜尋對手、日期或結果..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-bar-btn"><i class="fas fa-search"></i> 搜尋賽事</button>
            </form>
        </div>

        <!-- Today Matches Container -->
        <div id="today-matches" class="matches-tab-content" style="display: <?= $defaultTab === 'today-matches' ? 'block' : 'none' ?>;">
            <?php if (empty($todayGames)): ?>
                <div class="empty-state-message">
                    <i class="fas fa-calendar-day"></i>
                    今日暫無賽程安排。
                </div>
            <?php else: ?>
                <div class="card-container">
                    <?php foreach ($todayGames as $g): ?>
                        <?php 
                            $isLive = in_array($g['Game_id'], $live_game_ids) && empty($g['result']);
                        ?>
                        <div class="card match-item" data-search="<?= htmlspecialchars(strtolower($g['opponent'] . ' ' . $g['game_date'])) ?>">
                            <div class="card-content" style="display: flex; flex-direction: column; height: 100%;">
                                <div style="flex-grow: 1;">
                                    <div class="match-card-header">
                                        <span class="match-card-date"><?= $g['game_date'] ?></span>
                                        <?php if ($isLive): ?>
                                            <span class="badge badge-live">LIVE</span>
                                        <?php elseif (!empty($g['result'])): ?>
                                            <?php 
                                                $isWin = strpos($g['result'], '勝') !== false;
                                                $badgeClass = $isWin ? 'badge-win' : 'badge-loss';
                                                $resultText = $isWin ? 'VICTORY' : 'DEFEAT';
                                            ?>
                                            <span class="badge <?= $badgeClass ?>"><?= $resultText ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-today">TODAY</span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="card-title match-card-title-lg">
                                        <?php if ($isLive): ?>
                                            <i class="fas fa-broadcast-tower" style="margin-right: 8px; color: #2e7d32;"></i>
                                        <?php endif; ?>
                                        vs <?= htmlspecialchars($g['opponent']) ?>
                                    </h3>
                                    <p class="match-card-result-lg">
                                        <?php if ($isLive): ?>
                                            <span style="background: #2e7d32; color: white; padding: 4px 12px; border-radius: 50px; font-size: 0.95rem; font-weight: 700; display: inline-block; animation: live-pulse 2s infinite ease-in-out;"><i class="fas fa-broadcast-tower" style="margin-right: 5px;"></i>LIVE</span>
                                        <?php elseif (!empty($g['result'])): ?>
                                            <?= htmlspecialchars($g['result']) ?>
                                        <?php else: ?>
                                            <?= htmlspecialchars($g['game_time'] ?? '時間未定') ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <a href="game_detail.php?id=<?= $g['Game_id'] ?>" style="display: block; text-align: center; padding: 10px; margin-top: 15px; border-radius: 6px; text-decoration: none; background: #f1f1f1; color: #333; font-weight: 600; border: 1px solid #ddd; transition: 0.3s;" onmouseover="this.style.background='var(--primary)'; this.style.color='white'; this.style.borderColor='var(--primary)';" onmouseout="this.style.background='#f1f1f1'; this.style.color='#333'; this.style.borderColor='#ddd';">
                                    <?php if ($isLive): ?>
                                        <i class="fas fa-broadcast-tower"></i> 進入即時直播
                                    <?php else: ?>
                                        <i class="fas fa-chart-line"></i> 查看賽事數據
                                    <?php endif; ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Future Matches Container -->
        <div id="future-matches" class="matches-tab-content" style="display: none;">
            <?php if (empty($futureGames)): ?>
                <div class="empty-state-message">
                    <i class="fas fa-calendar-alt"></i>
                    目前暫無賽程安排。
                </div>
            <?php else: ?>
                <div class="card-container">
                    <?php foreach ($futureGames as $g): ?>
                        <?php 
                            $isLive = in_array($g['Game_id'], $live_game_ids) && empty($g['result']);
                        ?>
                        <div class="card match-item" data-search="<?= htmlspecialchars(strtolower($g['opponent'] . ' ' . $g['game_date'])) ?>">
                            <div class="card-content" style="display: flex; flex-direction: column; height: 100%;">
                                <div style="flex-grow: 1;">
                                    <div class="match-card-header">
                                        <span class="match-card-date"><?= $g['game_date'] ?></span>
                                        <?php if ($isLive): ?>
                                            <span class="badge badge-live">LIVE</span>
                                        <?php else: ?>
                                            <span class="badge badge-upcoming">UPCOMING</span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="card-title match-card-title-lg">
                                        <?php if ($isLive): ?>
                                            <i class="fas fa-broadcast-tower" style="margin-right: 8px; color: #2e7d32;"></i>
                                        <?php endif; ?>
                                        vs <?= htmlspecialchars($g['opponent']) ?>
                                    </h3>
                                    <p class="match-card-result-lg" style="color: #888; font-size: 1.2rem; font-weight: normal;">
                                        <?= htmlspecialchars($g['game_time'] ?? '時間未定') ?>
                                    </p>
                                </div>
                                <a href="game_detail.php?id=<?= $g['Game_id'] ?>" style="display: block; text-align: center; padding: 10px; margin-top: 15px; border-radius: 6px; text-decoration: none; background: #f1f1f1; color: #333; font-weight: 600; border: 1px solid #ddd; transition: 0.3s;" onmouseover="this.style.background='var(--primary)'; this.style.color='white'; this.style.borderColor='var(--primary)';" onmouseout="this.style.background='#f1f1f1'; this.style.color='#333'; this.style.borderColor='#ddd';">
                                    <?php if ($isLive): ?>
                                        <i class="fas fa-broadcast-tower"></i> 進入即時直播
                                    <?php else: ?>
                                        <i class="fas fa-chart-line"></i> 查看賽事數據
                                    <?php endif; ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Past Matches Container -->
        <div id="past-matches" class="matches-tab-content" style="display: <?= $defaultTab === 'past-matches' ? 'block' : 'none' ?>;">
            <?php if (empty($pastGames)): ?>
                <div class="empty-state-message">
                    <i class="fas fa-search"></i>
                    目前的搜尋條件找不到相關賽事。
                </div>
            <?php else: ?>
                <div class="card-container">
                    <?php foreach ($pastGames as $g): ?>
                        <?php 
                            $isLive = in_array($g['Game_id'], $live_game_ids) && empty($g['result']);
                        ?>
                        <div class="card match-item" data-search="<?= htmlspecialchars(strtolower($g['opponent'] . ' ' . $g['game_date'] . ' ' . $g['result'])) ?>">
                            <div class="card-content" style="display: flex; flex-direction: column; height: 100%;">
                                <div style="flex-grow: 1;">
                                    <div class="match-card-header">
                                        <span class="match-card-date"><?= $g['game_date'] ?></span>
                                        <?php if ($isLive): ?>
                                            <span class="badge badge-live">LIVE</span>
                                        <?php else: ?>
                                            <?php 
                                                $isWin = strpos($g['result'], '勝') !== false;
                                                $badgeClass = $isWin ? 'badge-win' : 'badge-loss';
                                                $resultText = $isWin ? 'VICTORY' : 'DEFEAT';
                                            ?>
                                            <span class="badge <?= $badgeClass ?>"><?= $resultText ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="card-title match-card-title-lg">
                                        <?php if ($isLive): ?>
                                            <i class="fas fa-broadcast-tower" style="margin-right: 8px; color: #2e7d32;"></i>
                                        <?php endif; ?>
                                        vs <?= htmlspecialchars($g['opponent']) ?>
                                    </h3>
                                    <p class="match-card-result-lg">
                                        <?php if ($isLive): ?>
                                            <span style="background: #2e7d32; color: white; padding: 4px 12px; border-radius: 50px; font-size: 0.95rem; font-weight: 700; display: inline-block; animation: live-pulse 2s infinite ease-in-out;"><i class="fas fa-broadcast-tower" style="margin-right: 5px;"></i>LIVE</span>
                                        <?php else: ?>
                                            <?= htmlspecialchars($g['result']) ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <a href="game_detail.php?id=<?= $g['Game_id'] ?>" style="display: block; text-align: center; padding: 10px; margin-top: 15px; border-radius: 6px; text-decoration: none; background: #f1f1f1; color: #333; font-weight: 600; border: 1px solid #ddd; transition: 0.3s;" onmouseover="this.style.background='var(--primary)'; this.style.color='white'; this.style.borderColor='var(--primary)';" onmouseout="this.style.background='#f1f1f1'; this.style.color='#333'; this.style.borderColor='#ddd';">
                                    <?php if ($isLive): ?>
                                        <i class="fas fa-broadcast-tower"></i> 進入即時直播
                                    <?php else: ?>
                                        <i class="fas fa-chart-line"></i> 查看賽事數據
                                    <?php endif; ?>
                                </a>
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
