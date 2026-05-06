<?php
require_once 'includes/header.php';
requireAdmin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $db->insert('game', [
            'Team_Id' => (int)$_POST['team_id'],
            'game_date' => $_POST['game_date'],
            'game_time' => !empty($_POST['game_time']) ? $_POST['game_time'] : null,
            'location' => $_POST['location'],
            'opponent' => $_POST['opponent'],
            'result' => trim($_POST['score'] . ' ' . $_POST['win_loss'])
        ]);
        $msg = '賽事登錄成功！';
    }
    if (isset($_POST['update'])) {
        $db->update('game', $_POST['Game_id'], [
            'Team_Id' => (int)$_POST['team_id'],
            'game_date' => $_POST['game_date'],
            'game_time' => !empty($_POST['game_time']) ? $_POST['game_time'] : null,
            'location' => $_POST['location'],
            'opponent' => $_POST['opponent'],
            'result' => trim($_POST['score'] . ' ' . $_POST['win_loss'])
        ]);
        $msg = '賽事修改成功！';
    }
    if (isset($_POST['delete'])) {
        $db->delete('game', $_POST['Game_id']);
        $msg = '該場賽事紀錄已刪除。';
    }
}

$editRecord = null;
$editScore = '';
$editWinLoss = '';
if (isset($_GET['edit_id'])) {
    $editRecord = $db->find('game', 'Game_id', $_GET['edit_id']);
    if ($editRecord && !empty($editRecord['result'])) {
        $parts = explode(' ', $editRecord['result']);
        $editScore = $parts[0];
        $editWinLoss = isset($parts[1]) ? $parts[1] : '';
    }
}

// Fetch all games
$games = $db->getAll('game');
// Sort by game_date descending
usort($games, function($a, $b) {
    return strtotime($b['game_date']) - strtotime($a['game_date']);
});

$teams = $db->getAll('team');
// lookup table for team id->name
$teamLookup = [];
foreach ($teams as $t) {
    $teamLookup[$t['team_Id']] = $t['team_name'];
}
?>

<div class="page-header">
    <h1>賽事紀錄管理</h1>
    <p>依據球隊登錄比賽紀錄、時間地點與最終比分結果。</p>
</div>

<section>
    <div class="container">
        <?php if ($msg): ?>
            <div class="admin-msg-box" style="background: var(--primary); color: white; padding: 10px 15px; border-radius: 8px; margin-bottom: 20px;">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <div class="admin-players-layout" style="display: grid; grid-template-columns: 350px 1fr; gap: 2rem;">
            <!-- Add Game Form -->
            <div class="admin-form-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); height: fit-content;">
                <h3 style="margin-bottom: 20px; color: #333; border-bottom: 2px solid var(--primary); padding-bottom: 10px; display: inline-block;"><?= $editRecord ? '修改賽事' : '登錄新賽事' ?></h3>
                <form method="POST" action="admin_matches.php">
                    <?php if ($editRecord): ?>
                        <input type="hidden" name="Game_id" value="<?= $editRecord['Game_id'] ?>">
                    <?php endif; ?>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">參賽隊伍</label>
                        <select name="team_id" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" required>
                            <?php foreach ($teams as $t): ?>
                                <option value="<?= $t['team_Id'] ?>" <?= $editRecord && $editRecord['Team_Id'] == $t['team_Id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['team_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">比賽日期</label>
                        <input type="date" name="game_date" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" value="<?= $editRecord ? $editRecord['game_date'] : '' ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">比賽時間</label>
                        <input type="time" name="game_time" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" value="<?= $editRecord ? $editRecord['game_time'] : '' ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">比賽地點</label>
                        <input type="text" name="location" class="form-control" placeholder="如：台中萬壽棒球場" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" value="<?= $editRecord ? htmlspecialchars($editRecord['location']) : '' ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">交戰對手</label>
                        <input type="text" name="opponent" class="form-control" placeholder="如：中山醫學大學" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" value="<?= $editRecord ? htmlspecialchars($editRecord['opponent']) : '' ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">比分</label>
                        <input type="text" name="score" class="form-control" placeholder="如：14 : 12" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" value="<?= htmlspecialchars($editScore) ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">勝負結果</label>
                        <select name="win_loss" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                            <option value="">尚未產生結果</option>
                            <option value="勝" <?= $editWinLoss == '勝' ? 'selected' : '' ?>>勝 (Win)</option>
                            <option value="敗" <?= $editWinLoss == '敗' ? 'selected' : '' ?>>敗 (Loss)</option>
                            <option value="和局" <?= $editWinLoss == '和局' ? 'selected' : '' ?>>和局 (Tie)</option>
                        </select>
                    </div>
                    <?php if ($editRecord): ?>
                        <button type="submit" name="update" class="btn-submit" style="width: 100%; padding: 12px; background: var(--secondary); color: #1a1a1a; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: opacity 0.3s;">儲存修改</button>
                        <a href="admin_matches.php" style="display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none;">取消修改</a>
                    <?php else: ?>
                        <button type="submit" name="add" class="btn-submit" style="width: 100%; padding: 12px; background: #333; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: opacity 0.3s;">確認登錄</button>
                        <a href="admin_dashboard.php" style="display: block; text-align: center; margin-top: 15px; color: var(--secondary); text-decoration: none;">返回控制台</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Games List -->
            <div class="admin-list-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow-x: auto;">
                <table class="admin-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd;">
                            <th style="padding: 12px 15px; text-align: left; color: #333;">隊伍</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333;">日期與時間</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333;">地點 / 對手</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333;">賽果</th>
                            <th style="padding: 12px 15px; text-align: center; color: #333;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($games)): ?>
                        <tr>
                            <td colspan="5" style="padding: 20px; text-align: center; color: #777;">目前沒有任何比賽紀錄。</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($games as $g): ?>
                                <tr style="border-bottom: 1px solid #eee; transition: background 0.3s; vertical-align: top;">
                                    <td style="padding: 12px 15px; font-weight: 500; color: var(--secondary);">
                                        <?= isset($teamLookup[$g['Team_Id']]) ? htmlspecialchars($teamLookup[$g['Team_Id']]) : '未知' ?>
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 0.9em;">
                                        <strong><?= htmlspecialchars($g['game_date']) ?></strong><br>
                                        <span style="color: #666;"><?= !empty($g['game_time']) ? date('H:i', strtotime($g['game_time'])) : '時間未定' ?></span>
                                    </td>
                                    <td style="padding: 12px 15px;">
                                        <span style="color: #555; font-size: 0.85em;"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($g['location']) ?></span><br>
                                        <strong>vs <?= htmlspecialchars($g['opponent']) ?></strong>
                                    </td>
                                    <td style="padding: 12px 15px; font-weight: bold; color: <?= strpos($g['result'], '勝') !== false ? 'var(--primary)' : (strpos($g['result'], '敗') !== false ? '#dc3545' : '#333') ?>;">
                                        <?= htmlspecialchars($g['result']) ?: '-' ?>
                                    </td>
                                    <td style="padding: 12px 15px; text-align: center; white-space: nowrap;">
                                        <a href="admin_matches.php?edit_id=<?= $g['Game_id'] ?>" class="admin-action-btn admin-btn-edit"><i class="fas fa-edit"></i> 修改</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('確定要刪除這場比賽紀錄嗎？')">
                                            <input type="hidden" name="Game_id" value="<?= $g['Game_id'] ?>">
                                            <button type="submit" name="delete" class="admin-action-btn admin-btn-delete"><i class="fas fa-trash"></i> 刪除</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
