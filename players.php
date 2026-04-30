<?php
require_once 'includes/header.php';

$players = $db->getAll('player');


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
                        <?php $imgSrc = !empty($p['image_path']) ? htmlspecialchars($p['image_path']) : 'assets/images/default-player.png'; ?>
                        <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($p['Player_Name']) ?>" class="player-card-img">
                        <div class="player-card-number">
                            #<?= $p['jersey_number'] ?>
                        </div>
                    </div>
                    <div class="player-card-body">
                        <span class="player-card-pos"><?= htmlspecialchars($p['position']) ?></span>
                        <h3 class="player-card-name"><?= htmlspecialchars($p['Player_Name']) ?></h3>
                        <div class="player-card-footer">
                            <div>
                                <span>H: <?= $p['height'] ?>CM</span>
                                <span style="margin-left: 10px;">W: <?= $p['weight'] ?>KG</span>
                            </div>
                            <a href="player_detail.php?id=<?= $p['Player_id'] ?>" class="player-card-link">PROFILE <i class="fas fa-chevron-right"></i></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
