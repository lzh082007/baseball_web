<?php
require_once 'includes/header.php';

$players = $db->getAll('player');
if (empty($players)) {
    // Seed some players
    $db->insert('player', [
        'team_id' => 1,
        'name' => '黃郁婷',
        'jersey_number' => 11,
        'position' => '投手',
        'height' => 170,
        'weight' => 65,
        'image_path' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=Yuting'
    ]);
    $db->insert('player', [
        'team_id' => 1,
        'name' => '劉詠傑',
        'jersey_number' => 23,
        'position' => '捕手',
        'height' => 178,
        'weight' => 75,
        'image_path' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=Yongjie'
    ]);
    $db->insert('player', [
        'team_id' => 1,
        'name' => '劉宙翰',
        'jersey_number' => 5,
        'position' => '內野手',
        'height' => 175,
        'weight' => 70,
        'image_path' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=Zhouhan'
    ]);
    $players = $db->getAll('player');
}

$positions = ['全部', '投手', '捕手', '內野手', '外野手', '教練'];
$currentPos = $_GET['pos'] ?? '全部';

$filteredPlayers = $players;
if ($currentPos !== '全部') {
    $filteredPlayers = array_filter($players, function($p) use ($currentPos) {
        return $p['position'] === $currentPos;
    });
}
?>

<div class="page-header">
    <h1>球員陣容</h1>
    <p>精英集結，每一位都是追求巔峰的鬥士。</p>
</div>

<!-- Section 1: Player List -->
<section class="players-section">
    <div class="container">
        <div class="players-filter-bar">
            <?php foreach ($positions as $pos): ?>
                <a href="?pos=<?= urlencode($pos) ?>" class="players-filter-badge <?= ($currentPos == $pos) ? 'active' : '' ?>">
                    <?= $pos ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="players-grid">
            <?php foreach ($filteredPlayers as $p): ?>
                <div class="card player-card-custom">
                    <div class="player-card-img-box">
                        <img src="<?= htmlspecialchars($p['image_path']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="player-card-img">
                        <div class="player-card-number">
                            #<?= $p['jersey_number'] ?>
                        </div>
                    </div>
                    <div class="player-card-body">
                        <span class="player-card-pos"><?= htmlspecialchars($p['position']) ?></span>
                        <h3 class="player-card-name"><?= htmlspecialchars($p['name']) ?></h3>
                        <div class="player-card-footer">
                            <div>
                                <span>H: <?= $p['height'] ?>CM</span>
                                <span style="margin-left: 10px;">W: <?= $p['weight'] ?>KG</span>
                            </div>
                            <a href="player_detail.php?id=<?= $p['player_id'] ?>" class="player-card-link">PROFILE <i class="fas fa-chevron-right"></i></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
