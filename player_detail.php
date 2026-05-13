<?php
require_once 'includes/header.php';

$player_id = $_GET['id'] ?? 0;
$player = $db->find('player', 'Player_id', $player_id);

if (!$player) {
    echo "<div class='container player-not-found'><h2>找不到該球員</h2><a href='players.php'>返回列表</a></div>";
    require_once 'includes/footer.php';
    exit;
}

// Simple logic to fetch performances if any
$performances = array_filter($db->getAll('playerrecord'), function($p) use ($player_id) {
    return $p['Player_Id'] == $player_id;
});
?>

<div class="page-header">
    <h1>球員個人檔案</h1>
    <p>詳細生涯數據與場上記錄</p>
</div>

<section class="player-detail-section">
    <div class="container">
        <div class="player-detail-layout">
            <!-- Left: Portrait -->
            <div class="player-portrait-card">
                <?php $imgSrc = !empty($player['image_path']) ? htmlspecialchars($player['image_path']) : 'assets/images/default-player.png'; ?>
                <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($player['Player_Name']) ?>" class="player-portrait-img">
                <div class="player-portrait-info">
                    <h2 class="player-portrait-name"><?= htmlspecialchars($player['Player_Name']) ?></h2>
                    <p class="player-portrait-subtitle"><?= str_replace(',', ' / ', htmlspecialchars($player['position'])) ?> | #<?= $player['jersey_number'] ?></p>
                </div>
            </div>

            <!-- Right: Details -->
            <div>
                <h3 class="player-detail-h3">基本資料 Specs</h3>
                <div class="player-specs-grid">
                    <div class="player-spec-card">
                        <h4 class="player-spec-label">身高 Height</h4>
                        <div class="player-spec-value"><?= $player['height'] ?> CM</div>
                    </div>
                    <div class="player-spec-card">
                        <h4 class="player-spec-label">體重 Weight</h4>
                        <div class="player-spec-value"><?= $player['weight'] ?> KG</div>
                    </div>
                </div>

                <h3 class="player-detail-h3 secondary">生涯紀錄 Records</h3>
                <?php if (empty($performances)): ?>
                    <p style="color: #888;">目前暫無詳細比賽數據記錄。</p>
                <?php else: ?>
                    <table class="player-records-table">
                        <thead>
                            <tr>
                                <th>比賽日期</th>
                                <th>安打</th>
                                <th>打點</th>
                                <th>得分</th>
                                <th>打席</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($performances as $perf): ?>
                                <tr>
                                    <td style="font-weight: 700;">比賽 ID: <?= $perf['Record_Id'] ?></td>
                                    <td><?= $perf['hit'] ?></td>
                                    <td><?= $perf['rbi'] ?></td>
                                    <td><?= $perf['runs'] ?></td>
                                    <td><?= $perf['at_bats'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                
                <div class="player-detail-footer">
                    <a href="players.php" class="player-detail-footer-link"><i class="fas fa-arrow-left"></i> 返回球員陣容列表</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
