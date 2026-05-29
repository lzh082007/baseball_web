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
                'rbi' => (int)$_POST['rbi'],
                'runs' => (int)$_POST['runs'],
                'stolen_bases' => (int)$_POST['stolen_bases'],
                'sac_bunt' => (int)$_POST['sac_bunt'],
                'sac_fly' => (int)$_POST['sac_fly'],
                'hit_by_pitch' => (int)$_POST['hit_by_pitch'],
                'go_outs' => (int)$_POST['go_outs'],
                'fo_outs' => (int)$_POST['fo_outs'],
                'is_start' => (int)($_POST['is_start'] ?? 0),
                'is_relief' => (int)($_POST['is_relief'] ?? 0),
                'is_cg' => (int)($_POST['is_cg'] ?? 0),
                'is_sho' => (int)($_POST['is_sho'] ?? 0),
                'win' => (int)($_POST['win'] ?? 0),
                'loss' => (int)($_POST['loss'] ?? 0),
                'save' => (int)($_POST['save'] ?? 0),
                'blown_save' => (int)($_POST['blown_save'] ?? 0),
                'hold' => (int)($_POST['hold'] ?? 0),
                'batters_faced' => (int)$_POST['batters_faced'],
                'hits_allowed' => (int)$_POST['hits_allowed'],
                'wild_pitches' => (int)$_POST['wild_pitches'],
                'balks' => (int)$_POST['balks'],
                'runs_allowed' => (int)$_POST['runs_allowed'],
                'p_go_outs' => (int)$_POST['p_go_outs'],
                'p_fo_outs' => (int)$_POST['p_fo_outs'],
                'p_hit_by_pitch' => (int)$_POST['p_hit_by_pitch'],
                'p_hr_allowed' => (int)$_POST['p_hr_allowed'],
                'strikes' => (int)$_POST['strikes'],
                'balls' => (int)$_POST['balls'],
                'swings' => (int)$_POST['swings'],
                'first_pitch_swings' => (int)$_POST['first_pitch_swings'],
                'whiffs' => (int)$_POST['whiffs'],
                'gb_count' => (int)$_POST['gb_count'],
                'ld_count' => (int)$_POST['ld_count'],
                'fb_count' => (int)$_POST['fb_count'],
                'hard_hit' => (int)($_POST['hard_hit'] ?? 0),
                'soft_hit' => (int)($_POST['soft_hit'] ?? 0),
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
                'rbi' => (int)$_POST['rbi'],
                'runs' => (int)$_POST['runs'],
                'stolen_bases' => (int)$_POST['stolen_bases'],
                'sac_bunt' => (int)$_POST['sac_bunt'],
                'sac_fly' => (int)$_POST['sac_fly'],
                'hit_by_pitch' => (int)$_POST['hit_by_pitch'],
                'go_outs' => (int)$_POST['go_outs'],
                'fo_outs' => (int)$_POST['fo_outs'],
                'is_start' => (int)($_POST['is_start'] ?? 0),
                'is_relief' => (int)($_POST['is_relief'] ?? 0),
                'is_cg' => (int)($_POST['is_cg'] ?? 0),
                'is_sho' => (int)($_POST['is_sho'] ?? 0),
                'win' => (int)($_POST['win'] ?? 0),
                'loss' => (int)($_POST['loss'] ?? 0),
                'save' => (int)($_POST['save'] ?? 0),
                'blown_save' => (int)($_POST['blown_save'] ?? 0),
                'hold' => (int)($_POST['hold'] ?? 0),
                'batters_faced' => (int)$_POST['batters_faced'],
                'hits_allowed' => (int)$_POST['hits_allowed'],
                'wild_pitches' => (int)$_POST['wild_pitches'],
                'balks' => (int)$_POST['balks'],
                'runs_allowed' => (int)$_POST['runs_allowed'],
                'p_go_outs' => (int)$_POST['p_go_outs'],
                'p_fo_outs' => (int)$_POST['p_fo_outs'],
                'p_hit_by_pitch' => (int)$_POST['p_hit_by_pitch'],
                'p_hr_allowed' => (int)$_POST['p_hr_allowed'],
                'strikes' => (int)$_POST['strikes'],
                'balls' => (int)$_POST['balls'],
                'swings' => (int)$_POST['swings'],
                'first_pitch_swings' => (int)$_POST['first_pitch_swings'],
                'whiffs' => (int)$_POST['whiffs'],
                'gb_count' => (int)$_POST['gb_count'],
                'ld_count' => (int)$_POST['ld_count'],
                'fb_count' => (int)$_POST['fb_count'],
                'hard_hit' => (int)($_POST['hard_hit'] ?? 0),
                'soft_hit' => (int)($_POST['soft_hit'] ?? 0),
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
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">打席數</label>
                                    <input type="number" name="pa_count" min="0" value="<?= $editStat ? $editStat['pa_count'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">打點</label>
                                    <input type="number" name="rbi" min="0" value="<?= $editStat ? $editStat['rbi'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">得分</label>
                                    <input type="number" name="runs" min="0" value="<?= $editStat ? $editStat['runs'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">盜壘</label>
                                    <input type="number" name="stolen_bases" min="0" value="<?= $editStat ? $editStat['stolen_bases'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">犧牲短打</label>
                                    <input type="number" name="sac_bunt" min="0" value="<?= $editStat ? $editStat['sac_bunt'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">犧牲飛球</label>
                                    <input type="number" name="sac_fly" min="0" value="<?= $editStat ? $editStat['sac_fly'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">觸身球</label>
                                    <input type="number" name="hit_by_pitch" min="0" value="<?= $editStat ? $editStat['hit_by_pitch'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">滾地出局</label>
                                    <input type="number" name="go_outs" min="0" value="<?= $editStat ? $editStat['go_outs'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                            </div>
                            <div class="form-group" style="margin-bottom:15px;">
                                <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">高飛出局</label>
                                <input type="number" name="fo_outs" min="0" value="<?= $editStat ? $editStat['fo_outs'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                            </div>
                            <div class="form-group" style="margin-bottom:15px;">
                                <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">打席結果 (例如: 1B, 2B, GO, SO)</label>
                                <input type="text" name="pa_results" placeholder="輸入結果" value="<?= $editStat ? htmlspecialchars($editStat['pa_results']) : '' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                            </div>
                        </div>

                        <!-- 投球數據 - 基礎 -->
                        <div style="background:#f9f9f9; padding:20px; border-radius:8px; border:1px solid #eee;">
                            <h4 style="margin-top:0; margin-bottom:15px; color:var(--secondary);"><i class="fas fa-baseball-ball"></i> 投球數據 - 基礎</h4>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">投球數</label>
                                    <input type="number" name="pitches" min="0" value="<?= $editStat ? $editStat['pitches'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">局數 (如: 1 1/3)</label>
                                    <input type="text" name="innings" value="<?= $editStat ? formatInningsDisplay($editStat['innings']) : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px; margin-bottom:15px;">
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
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">面對打席</label>
                                    <input type="number" name="batters_faced" min="0" value="<?= $editStat ? $editStat['batters_faced'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">被安打</label>
                                    <input type="number" name="hits_allowed" min="0" value="<?= $editStat ? $editStat['hits_allowed'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">失分</label>
                                    <input type="number" name="runs_allowed" min="0" value="<?= $editStat ? $editStat['runs_allowed'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">暴投</label>
                                    <input type="number" name="wild_pitches" min="0" value="<?= $editStat ? $editStat['wild_pitches'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">投手犯規</label>
                                    <input type="number" name="balks" min="0" value="<?= $editStat ? $editStat['balks'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">投出觸身球</label>
                                    <input type="number" name="p_hit_by_pitch" min="0" value="<?= $editStat ? $editStat['p_hit_by_pitch'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">被全壘打</label>
                                    <input type="number" name="p_hr_allowed" min="0" value="<?= $editStat ? $editStat['p_hr_allowed'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">投手誘導滾地</label>
                                    <input type="number" name="p_go_outs" min="0" value="<?= $editStat ? $editStat['p_go_outs'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                            </div>
                            <div class="form-group">
                                <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">投手誘導高飛</label>
                                <input type="number" name="p_fo_outs" min="0" value="<?= $editStat ? $editStat['p_fo_outs'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                            </div>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
                        <!-- 投球數據 - 出賽狀態與結果 -->
                        <div style="background:#f9f9f9; padding:20px; border-radius:8px; border:1px solid #eee;">
                            <h4 style="margin-top:0; margin-bottom:15px; color:#444;"><i class="fas fa-toggle-on"></i> 投手出賽狀態與結果</h4>
                            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; align-items:center;">
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; color:#333; font-weight:500;">
                                    <input type="checkbox" name="is_start" value="1" <?= ($editStat && $editStat['is_start']) ? 'checked' : '' ?>> 先發 (GS)
                                </label>
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; color:#333; font-weight:500;">
                                    <input type="checkbox" name="is_relief" value="1" <?= ($editStat && $editStat['is_relief']) ? 'checked' : '' ?>> 後援 (GR)
                                </label>
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; color:#333; font-weight:500;">
                                    <input type="checkbox" name="is_cg" value="1" <?= ($editStat && $editStat['is_cg']) ? 'checked' : '' ?>> 完投 (CG)
                                </label>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; align-items:center; margin-top:15px;">
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; color:#333; font-weight:500;">
                                    <input type="checkbox" name="is_sho" value="1" <?= ($editStat && $editStat['is_sho']) ? 'checked' : '' ?>> 完封 (SHO)
                                </label>
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; color:#333; font-weight:500;">
                                    <input type="checkbox" name="win" value="1" <?= ($editStat && $editStat['win']) ? 'checked' : '' ?>> 勝場 (W)
                                </label>
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; color:#333; font-weight:500;">
                                    <input type="checkbox" name="loss" value="1" <?= ($editStat && $editStat['loss']) ? 'checked' : '' ?>> 敗場 (L)
                                </label>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; align-items:center; margin-top:15px;">
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; color:#333; font-weight:500;">
                                    <input type="checkbox" name="save" value="1" <?= ($editStat && $editStat['save']) ? 'checked' : '' ?>> 救援成功 (SV)
                                </label>
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; color:#333; font-weight:500;">
                                    <input type="checkbox" name="blown_save" value="1" <?= ($editStat && $editStat['blown_save']) ? 'checked' : '' ?>> 救援失敗 (BS)
                                </label>
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; color:#333; font-weight:500;">
                                    <input type="checkbox" name="hold" value="1" <?= ($editStat && $editStat['hold']) ? 'checked' : '' ?>> 中繼成功 (HLD)
                                </label>
                            </div>
                        </div>

                        <!-- 投球特性數據 -->
                        <div style="background:#f9f9f9; padding:20px; border-radius:8px; border:1px solid #eee;">
                            <h4 style="margin-top:0; margin-bottom:15px; color:#444;"><i class="fas fa-chart-line"></i> 投球特性與球路數據</h4>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">好球數 (Strikes)</label>
                                    <input type="number" name="strikes" min="0" value="<?= $editStat ? $editStat['strikes'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">壞球數 (Balls)</label>
                                    <input type="number" name="balls" min="0" value="<?= $editStat ? $editStat['balls'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px; margin-bottom:15px;">
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">對手揮棒</label>
                                    <input type="number" name="swings" min="0" value="<?= $editStat ? $editStat['swings'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">首球揮棒</label>
                                    <input type="number" name="first_pitch_swings" min="0" value="<?= $editStat ? $editStat['first_pitch_swings'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">揮空次數</label>
                                    <input type="number" name="whiffs" min="0" value="<?= $editStat ? $editStat['whiffs'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px;">
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">滾地球次數</label>
                                    <input type="number" name="gb_count" min="0" value="<?= $editStat ? $editStat['gb_count'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">平飛球次數</label>
                                    <input type="number" name="ld_count" min="0" value="<?= $editStat ? $editStat['ld_count'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">高飛球次數</label>
                                    <input type="number" name="fb_count" min="0" value="<?= $editStat ? $editStat['fb_count'] : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-top:15px;">
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">強勁擊球次數 (Hard Hit)</label>
                                    <input type="number" name="hard_hit" min="0" value="<?= $editStat ? ($editStat['hard_hit'] ?? '0') : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                                </div>
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#555; font-size:0.95rem;">軟弱擊球次數 (Soft Hit)</label>
                                    <input type="number" name="soft_hit" min="0" value="<?= $editStat ? ($editStat['soft_hit'] ?? '0') : '0' ?>" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
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
                                <td style="padding: 12px 15px;"><?= formatInningsDisplay($s['innings']) ?></td>
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
