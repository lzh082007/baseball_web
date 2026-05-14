<?php
require_once 'includes/header.php';

$players = $db->getAll('player');


$positions = ['全部', '投手', '捕手', '內野手', '外野手', '教練'];
$currentPos = $_GET['pos'] ?? '全部';
$search = $_GET['search'] ?? '';

$filteredPlayers = $players;

// 1. Filter by position
if ($currentPos !== '全部') {
    $filteredPlayers = array_filter($filteredPlayers, function($p) use ($currentPos) {
        $pPositions = explode(',', $p['position']);
        return in_array($currentPos, $pPositions);
    });
}

// 2. Filter by search keyword
if (!empty($search)) {
    $filteredPlayers = array_filter($filteredPlayers, function($p) use ($search) {
        return stripos($p['Player_Name'], $search) !== false || stripos($p['position'], $search) !== false || stripos((string)$p['jersey_number'], $search) !== false;
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
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 40px;">
            <div class="players-filter-bar" style="margin-bottom: 0; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <?php foreach ($positions as $pos): ?>
                    <a href="?pos=<?= urlencode($pos) ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>" class="players-filter-badge <?= ($currentPos == $pos) ? 'active' : '' ?>" style="margin: 0; height: 48px; display: flex; align-items: center; box-sizing: border-box;">
                        <?= $pos ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <form method="GET" class="search-bar-container" style="margin-bottom: 0;">
                <input type="hidden" name="pos" value="<?= htmlspecialchars($currentPos) ?>">
                <input type="text" name="search" class="search-bar-input" placeholder="搜尋球員姓名、背號..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-bar-btn"><i class="fas fa-search"></i> 搜尋球員</button>
            </form>
        </div>

        <div class="players-grid">
            <?php if (empty($filteredPlayers)): ?>
                <div class="empty-state-message" style="grid-column: 1 / -1;">
                    <i class="fas fa-search"></i>
                    找不到符合條件的球員。
                </div>
            <?php else: ?>
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
                        <span class="player-card-pos"><?= str_replace(',', ' / ', htmlspecialchars($p['position'])) ?></span>
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
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
