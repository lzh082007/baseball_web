<?php
require_once 'includes/header.php';

$matches = $db->getAll('game');


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

<!-- Section 1: Future Matches -->
<section class="matches-section">
    <div class="container">
        <div class="section-title">
            <h2>未來賽程</h2>
        </div>
        
        <?php if (empty($futureGames)): ?>
            <p class="text-center">目前暫無賽程安排。</p>
        <?php else: ?>
            <div class="matches-table-card">
                <table class="matches-table">
                    <tbody>
                        <?php foreach ($futureGames as $g): ?>
                            <tr>
                                <td class="matches-date-cell"><?= $g['game_date'] ?></td>
                                <td class="matches-opponent-cell"><?= htmlspecialchars($g['opponent']) ?></td>
                                <td class="matches-status-cell"><span class="badge badge-upcoming">UPCOMING</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <div class="card-container">
            <?php foreach ($pastGames as $g): ?>
                <div class="card">
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
                        <hr class="match-card-divider">
                        <a href="matches_detail.php?id=<?= $g['Game_id'] ?>" class="match-card-link">
                            查看詳細數據與 AI 分析 <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
