<?php
require_once 'includes/header.php';
requireAdmin();

$games = $db->getAll('game');

// Sort games by date DESC
usort($games, function($a, $b) {
    return strtotime($b['game_date']) - strtotime($a['game_date']);
});

?>
<div class="page-header">
    <h1>比賽數據管理</h1>
    <p>管理所有比賽的球員詳細數據</p>
</div>

<section>
    <div class="container">
        <a href="admin_dashboard.php" class="admin-back-btn">
            <i class="fas fa-arrow-left"></i> 返回控制台
        </a>
        
        <div class="admin-list-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow-x: auto;">
            <table class="admin-table" style="width:100%; border-collapse:collapse; text-align:left;">
                <thead>
                    <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd;">
                        <th style="padding: 12px 15px; text-align: left; color: #333;">比賽日期</th>
                        <th style="padding: 12px 15px; text-align: left; color: #333;">對手</th>
                        <th style="padding: 12px 15px; text-align: left; color: #333;">攻守</th>
                        <th style="padding: 12px 15px; text-align: left; color: #333;">地點</th>
                        <th style="padding: 12px 15px; text-align: left; color: #333;">結果</th>
                        <th style="padding: 12px 15px; text-align: center; color: #333;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($games) === 0): ?>
                    <tr>
                        <td colspan="6" style="padding:20px; text-align:center; color:#777;">目前無任何比賽記錄</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($games as $g): ?>
                        <tr style="border-bottom:1px solid #eee; transition: background 0.3s; vertical-align: middle;">
                            <td style="padding: 12px 15px;"><?= htmlspecialchars($g['game_date']) ?></td>
                            <td style="padding: 12px 15px; font-weight: 500;">vs <?= htmlspecialchars($g['opponent']) ?></td>
                            <td style="padding: 12px 15px;">
                                <?php
                                $batting_label = htmlspecialchars($g['batting_first']) ?: '未設定';
                                $batting_style = 'background: #f8f9fa; color: #6c757d;';
                                if ($g['batting_first'] === '先攻') {
                                    $batting_style = 'background: #e3f2fd; color: #0d47a1;';
                                } elseif ($g['batting_first'] === '後攻') {
                                    $batting_style = 'background: #fbe9e7; color: #d84315;';
                                }
                                ?>
                                <span class="badge" style="padding: 5px 10px; border-radius: 6px; font-size: 0.85em; font-weight: bold; display: inline-block; text-align: center; min-width: 50px; <?= $batting_style ?>">
                                    <?= $batting_label ?>
                                </span>
                            </td>
                            <td style="padding: 12px 15px;"><span style="color:#555; font-size:0.85em;"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($g['location']) ?></span></td>
                            <td style="padding: 12px 15px; font-weight: bold; color: <?= strpos($g['result'], '勝') !== false ? 'var(--primary)' : (strpos($g['result'], '敗') !== false ? '#dc3545' : '#333') ?>;"><?= htmlspecialchars($g['result']) ?></td>
                            <td style="padding: 12px 15px; text-align:center; white-space: nowrap;">
                                <a href="admin_game_live_score.php?game_id=<?= $g['Game_id'] ?>" class="admin-action-btn" style="background: var(--primary); color: white;">
                                    <i class="fas fa-broadcast-tower"></i> 現場直接登記
                                </a>
                                <a href="admin_game_stats_edit.php?game_id=<?= $g['Game_id'] ?>" class="admin-action-btn admin-btn-edit">
                                    <i class="fas fa-edit"></i> 編輯數據
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
