<?php
require_once 'includes/header.php';
requireAdmin();

if (!isset($_GET['game_id'])) {
    header("Location: admin_game_stats.php");
    exit;
}

$game_id = (int)$_GET['game_id'];
$game = $db->find('game', 'Game_id', $game_id);

if (!$game) {
    die("找不到比賽記錄");
}

$msg = '';
$msgType = 'success';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_stat') {
        $player_id = (int)$_POST['player_id'];
        
        $all_stats = $db->getAll('player_game_details');
        $exists = false;
        foreach($all_stats as $s) {
            if ($s['game_id'] == $game_id && $s['player_id'] == $player_id) {
                $exists = true;
                break;
            }
        }
        
        if ($exists) {
            $msg = '該球員已在此比賽中有數據，請先刪除再重新新增。';
            $msgType = 'error';
        } else {
            $db->insert('player_game_details', [
                'game_id' => $game_id,
                'player_id' => $player_id,
                'pa_count' => (int)$_POST['pa_count'],
                'pa_results' => trim($_POST['pa_results']),
                'pitches' => (int)$_POST['pitches'],
                'innings' => trim($_POST['innings']),
                'strikeouts' => (int)$_POST['strikeouts'],
                'walks' => (int)$_POST['walks'],
                'earned_runs' => (int)$_POST['earned_runs'],
            ]);
            $msg = '球員數據已新增！';
        }
    } elseif ($_POST['action'] === 'update_stat') {
        $stat_id = (int)$_POST['stat_id'];
        $player_id = (int)$_POST['player_id'];
        
        $all_stats = $db->getAll('player_game_details');
        $exists = false;
        foreach($all_stats as $s) {
            if ($s['game_id'] == $game_id && $s['player_id'] == $player_id && $s['id'] != $stat_id) {
                $exists = true;
                break;
            }
        }
        
        if ($exists) {
            $msg = '該球員已在此比賽中有數據，無法修改為此球員。';
            $msgType = 'error';
        } else {
            $db->update('player_game_details', $stat_id, [
                'player_id' => $player_id,
                'pa_count' => (int)$_POST['pa_count'],
                'pa_results' => trim($_POST['pa_results']),
                'pitches' => (int)$_POST['pitches'],
                'innings' => trim($_POST['innings']),
                'strikeouts' => (int)$_POST['strikeouts'],
                'walks' => (int)$_POST['walks'],
                'earned_runs' => (int)$_POST['earned_runs'],
            ]);
            $msg = '球員數據已更新！';
        }
    } elseif ($_POST['action'] === 'delete_stat') {
        $id = (int)$_POST['id'];
        $db->delete('player_game_details', $id);
        $msg = '數據已刪除！';
    }
}

// Check for edit record
$editStat = null;
if (isset($_GET['edit_stat_id'])) {
    $editStat = $db->find('player_game_details', 'id', (int)$_GET['edit_stat_id']);
}

// Fetch all players for dropdown
$players = $db->getAll('player');

// Fetch stats for this game
$all_stats = $db->getAll('player_game_details');
$game_stats = array_filter($all_stats, function($s) use ($game_id) {
    return $s['game_id'] == $game_id;
});

// Helper to get player name
function getPlayerName($pid, $players) {
    foreach($players as $p) {
        if ($p['Player_id'] == $pid) return $p['Player_Name'];
    }
    return '未知球員';
}
?>
<div class="page-header">
    <h1>編輯比賽數據</h1>
    <p>日期：<?= htmlspecialchars($game['game_date']) ?> | 對手：<?= htmlspecialchars($game['opponent']) ?> | 結果：<?= htmlspecialchars($game['result']) ?></p>
</div>

<section>
    <div class="container">
        <a href="admin_game_stats.php" class="admin-back-btn">
            <i class="fas fa-arrow-left"></i> 返回比賽列表
        </a>
        
        <?php if ($msg): ?>
            <div class="admin-msg-box" style="background: <?= $msgType === 'error' ? '#dc3545' : 'var(--primary)' ?>; color: white; padding: 10px 15px; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns:1fr; gap:2rem;">
            
            <!-- Add Stat Form -->
            <div class="admin-form-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); height: fit-content;">
                <h3 style="margin-bottom: 20px; color: #333; border-bottom: 2px solid var(--primary); padding-bottom: 10px; display: inline-block;">
                    <i class="fas fa-<?= $editStat ? 'edit' : 'plus-circle' ?>" style="color:var(--primary); margin-right:8px;"></i><?= $editStat ? '編輯球員詳細數據' : '新增球員詳細數據' ?>
                </h3>
                
                <form method="POST" action="admin_game_stats_edit.php?game_id=<?= $game_id ?>">
                    <input type="hidden" name="action" value="<?= $editStat ? 'update_stat' : 'add_stat' ?>">
                    <?php if ($editStat): ?>
                        <input type="hidden" name="stat_id" value="<?= $editStat['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom:8px; font-weight:500; color:#555;">選擇球員</label>
                        <select name="player_id" class="form-control" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                            <option value="">請選擇球員...</option>
                            <?php foreach($players as $p): ?>
                                <option value="<?= $p['Player_id'] ?>" <?= ($editStat && $editStat['player_id'] == $p['Player_id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['Player_Name']) ?> <?= $p['jersey_number'] ? '(#'.$p['jersey_number'].')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
                        <!-- 打擊數據 -->
                        <div style="background:#f9f9f9; padding:20px; border-radius:8px; border:1px solid #eee;">
                            <h4 style="margin-top:0; margin-bottom:15px; color:var(--primary);"><i class="fas fa-baseball-bat-ball"></i> 打擊數據</h4>
                            <div class="form-group" style="margin-bottom:15px;">
                                <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">打席數</label>
                                <input type="number" name="pa_count" min="0" value="<?= $editStat ? $editStat['pa_count'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                            </div>
                            <div class="form-group" style="margin-bottom:15px;">
                                <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">打席結果 (例如: 1B, 2B, GO, SO)</label>
                                <input type="text" name="pa_results" placeholder="輸入結果" value="<?= $editStat ? htmlspecialchars($editStat['pa_results']) : '' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                            </div>
                        </div>

                        <!-- 投球數據 -->
                        <div style="background:#f9f9f9; padding:20px; border-radius:8px; border:1px solid #eee;">
                            <h4 style="margin-top:0; margin-bottom:15px; color:var(--secondary);"><i class="fas fa-baseball-ball"></i> 投球數據</h4>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">投球數</label>
                                    <input type="number" name="pitches" min="0" value="<?= $editStat ? $editStat['pitches'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">局數 (如: 1.1)</label>
                                    <input type="text" name="innings" value="<?= $editStat ? htmlspecialchars($editStat['innings']) : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px;">
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">三振</label>
                                    <input type="number" name="strikeouts" min="0" value="<?= $editStat ? $editStat['strikeouts'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">保送</label>
                                    <input type="number" name="walks" min="0" value="<?= $editStat ? $editStat['walks'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">責失分</label>
                                    <input type="number" name="earned_runs" min="0" value="<?= $editStat ? $editStat['earned_runs'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($editStat): ?>
                        <button type="submit" class="btn-submit" style="width: 100%; padding: 12px; background: var(--secondary); color: #1a1a1a; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: opacity 0.3s;">
                            儲存修改
                        </button>
                        <a href="admin_game_stats_edit.php?game_id=<?= $game_id ?>" style="display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none;">取消修改</a>
                    <?php else: ?>
                        <button type="submit" class="btn-submit" style="width: 100%; padding: 12px; background: #333; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: opacity 0.3s;">
                            確認登錄數據
                        </button>
                    <?php endif; ?>
                </form>
            </div>

            <!-- List of Stats -->
            <div class="admin-list-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow-x: auto;">
                <h3 style="margin-bottom: 20px; color: #333; border-bottom: 2px solid var(--secondary); padding-bottom: 10px; display: inline-block;">
                    <i class="fas fa-list" style="color:var(--secondary); margin-right:8px;"></i>已登錄球員數據
                </h3>
                
                <table class="admin-table" style="width: 100%; border-collapse: collapse; min-width:800px;">
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
                            <th style="padding: 12px 15px; text-align: center; color: #333;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($game_stats)): ?>
                        <tr>
                            <td colspan="9" style="padding: 20px; text-align: center; color: #777;">尚無任何球員數據</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach($game_stats as $s): ?>
                            <tr style="border-bottom: 1px solid #eee; transition: background 0.3s; vertical-align: middle;">
                                <td style="padding: 12px 15px; font-weight: 600; color: var(--secondary);"><?= htmlspecialchars(getPlayerName($s['player_id'], $players)) ?></td>
                                <td style="padding: 12px 15px;"><?= $s['pa_count'] ?></td>
                                <td style="padding: 12px 15px;"><?= htmlspecialchars($s['pa_results']) ?></td>
                                <td style="padding: 12px 15px;"><?= $s['pitches'] ?></td>
                                <td style="padding: 12px 15px;"><?= htmlspecialchars($s['innings']) ?></td>
                                <td style="padding: 12px 15px;"><?= $s['strikeouts'] ?></td>
                                <td style="padding: 12px 15px;"><?= $s['walks'] ?></td>
                                <td style="padding: 12px 15px;"><?= $s['earned_runs'] ?></td>
                                <td style="padding: 12px 15px; text-align: center; white-space: nowrap;">
                                    <a href="admin_game_stats_edit.php?game_id=<?= $game_id ?>&edit_stat_id=<?= $s['id'] ?>" class="admin-action-btn admin-btn-edit"><i class="fas fa-edit"></i> 修改</a>
                                    <form method="POST" action="admin_game_stats_edit.php?game_id=<?= $game_id ?>" onsubmit="return confirm('確定要刪除這筆數據嗎？');" style="display:inline-block;">
                                        <input type="hidden" name="action" value="delete_stat">
                                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="admin-action-btn admin-btn-delete"><i class="fas fa-trash"></i> 刪除</button>
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
