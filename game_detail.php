<?php
require_once 'includes/header.php';

if (!isset($_GET['id'])) {
    header('Location: matches.php');
    exit;
}

$game_id = (int)$_GET['id'];
$game = $db->find('game', 'Game_id', $game_id);

if (!$game) {
    die("<div class='container' style='padding: 50px; text-align: center;'><h2>找不到該賽事</h2><a href='matches.php' class='btn-primary' style='padding: 10px 20px; text-decoration: none; border-radius: 6px;'>返回賽事列表</a></div>");
}

$stats = [];
if (isLoggedIn()) {
    $all_stats = $db->getAll('player_game_details');
    $stats = array_filter($all_stats, function($s) use ($game_id) {
        return $s['game_id'] == $game_id;
    });
}

$players = $db->getAll('player');
function getPlayerName($pid, $players) {
    foreach($players as $p) {
        if ($p['Player_id'] == $pid) return $p['Player_Name'];
    }
    return '未知球員';
}
?>
<div class="page-header">
    <h1>賽事詳細資訊</h1>
    <p>日期：<?= htmlspecialchars($game['game_date']) ?> | 對手：<?= htmlspecialchars($game['opponent']) ?> | 結果：<?= htmlspecialchars($game['result']) ?></p>
</div>

<section>
    <div class="container">
        <div style="margin-bottom: 20px;">
            <a href="matches.php" class="btn-primary" style="padding:10px 20px; border-radius:6px; display:inline-block; text-decoration:none;"><i class="fas fa-arrow-left"></i> 返回賽事列表</a>
        </div>

        <?php if (!isLoggedIn()): ?>
            <div style="background: #fff; padding: 60px 40px; text-align: center; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <i class="fas fa-lock" style="font-size: 3.5rem; color: #ccc; margin-bottom: 20px;"></i>
                <h3 style="color: #333; margin-bottom: 10px;">進階數據已隱藏</h3>
                <p style="color: #777; font-size: 1.1rem; margin-bottom: 20px;">此賽事的詳細球員攻守數據僅供內部成員查閱。<br>請先登入系統後再回來查看。</p>
                <a href="login.php" class="btn-primary" style="display: inline-block; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 1.1rem;"><i class="fas fa-sign-in-alt"></i> 前往登入</a>
            </div>
        <?php else: ?>
            <div class="admin-list-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow-x: auto;">
                <h3 style="margin-bottom: 20px; color: #333; border-bottom: 2px solid var(--secondary); padding-bottom: 10px; display: inline-block;">
                    <i class="fas fa-chart-bar" style="color:var(--secondary); margin-right:8px;"></i>球員各項數據
                </h3>
                
                <table class="admin-table" style="width: 100%; border-collapse: collapse; min-width:800px; text-align: left;">
                    <thead>
                        <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd;">
                            <th style="padding: 12px 15px; text-align: left; color: #333;">球員</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333;">打席數</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333;">打席結果</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333;">投球數</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333;">局數</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333;">三振</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333;">保送</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333;">責失分</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($stats)): ?>
                        <tr>
                            <td colspan="8" style="padding: 30px; text-align: center; color: #777;">
                                <i class="fas fa-info-circle" style="font-size: 2rem; color: #ccc; margin-bottom: 10px; display: block;"></i>
                                本場比賽尚無登錄任何球員數據。
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach($stats as $s): ?>
                            <tr style="border-bottom: 1px solid #eee; transition: background 0.3s; vertical-align: middle;">
                                <td style="padding: 12px 15px; font-weight: 600; color: var(--secondary);"><?= htmlspecialchars(getPlayerName($s['player_id'], $players)) ?></td>
                                <td style="padding: 12px 15px;"><?= $s['pa_count'] ?></td>
                                <td style="padding: 12px 15px;"><?= htmlspecialchars($s['pa_results']) ?></td>
                                <td style="padding: 12px 15px;"><?= $s['pitches'] ?></td>
                                <td style="padding: 12px 15px;"><?= htmlspecialchars($s['innings']) ?></td>
                                <td style="padding: 12px 15px;"><?= $s['strikeouts'] ?></td>
                                <td style="padding: 12px 15px;"><?= $s['walks'] ?></td>
                                <td style="padding: 12px 15px;"><?= $s['earned_runs'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
