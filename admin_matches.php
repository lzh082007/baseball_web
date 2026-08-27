<?php
require_once 'includes/header.php';
requireAdmin();

// Migration: Ensure batting_first column exists in game table
try {
    $db->getPdo()->exec("ALTER TABLE `game` ADD COLUMN `batting_first` VARCHAR(10) DEFAULT NULL COMMENT '先攻或後攻'");
} catch (PDOException $e) {
    // Column already exists or table issue, ignore
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $db->insert('game', [
            'Team_Id' => (int)$_POST['team_id'],
            'game_date' => $_POST['game_date'],
            'game_time' => !empty($_POST['game_time']) ? $_POST['game_time'] : null,
            'location' => $_POST['location'],
            'opponent' => $_POST['opponent'],
            'batting_first' => !empty($_POST['batting_first']) ? $_POST['batting_first'] : null,
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
            'batting_first' => !empty($_POST['batting_first']) ? $_POST['batting_first'] : null,
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
        <a href="admin_dashboard.php" class="admin-back-btn">
            <i class="fas fa-arrow-left"></i> 返回控制台
        </a>
        <?php if ($msg): ?>
            <div class="admin-msg-box" style="background: var(--primary); color: white; padding: 10px 15px; border-radius: 8px; margin-bottom: 20px;">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <div class="admin-players-layout" style="display: grid; grid-template-columns: 350px 1fr; gap: 2rem;">
            <!-- Add Game Form -->
            <div class="admin-form-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); height: fit-content;">
                <h3 style="margin-bottom: 20px; color: #333; border-bottom: 2px solid var(--primary); padding-bottom: 10px; display: inline-block;"><?= $editRecord ? '修改賽事' : '登錄新賽事' ?></h3>
                <form id="match-form" method="POST" action="admin_matches.php">
                    <input type="hidden" name="Game_id" id="hidden-game-id" value="<?= $editRecord ? $editRecord['Game_id'] : '' ?>">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">參賽隊伍</label>
                        <select name="team_id" id="select-team-id" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" required>
                            <?php foreach ($teams as $t): ?>
                                <option value="<?= $t['team_Id'] ?>" <?= $editRecord && $editRecord['Team_Id'] == $t['team_Id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['team_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">比賽日期</label>
                        <input type="date" name="game_date" id="input-game-date" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" value="<?= $editRecord ? $editRecord['game_date'] : '' ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">比賽時間</label>
                        <input type="time" name="game_time" id="input-game-time" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" value="<?= $editRecord ? $editRecord['game_time'] : '' ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">先攻/後攻</label>
                        <select name="batting_first" id="select-batting-first" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                            <option value="">請選擇</option>
                            <option value="先攻" <?= $editRecord && isset($editRecord['batting_first']) && $editRecord['batting_first'] == '先攻' ? 'selected' : '' ?>>先攻</option>
                            <option value="後攻" <?= $editRecord && isset($editRecord['batting_first']) && $editRecord['batting_first'] == '後攻' ? 'selected' : '' ?>>後攻</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">比賽地點</label>
                        <input type="text" name="location" id="input-location" placeholder="如：台中萬壽棒球場" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" value="<?= $editRecord ? htmlspecialchars($editRecord['location']) : '' ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">交戰對手</label>
                        <input type="text" name="opponent" id="input-opponent" placeholder="如：中山醫學大學" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" value="<?= $editRecord ? htmlspecialchars($editRecord['opponent']) : '' ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">比分</label>
                        <input type="text" name="score" id="input-score" placeholder="如：14 : 12" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" value="<?= htmlspecialchars($editScore) ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">勝負結果</label>
                        <select name="win_loss" id="select-win-loss" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                            <option value="">尚未產生結果</option>
                            <option value="勝" <?= $editWinLoss == '勝' ? 'selected' : '' ?>>勝 (Win)</option>
                            <option value="敗" <?= $editWinLoss == '敗' ? 'selected' : '' ?>>敗 (Loss)</option>
                            <option value="和局" <?= $editWinLoss == '和局' ? 'selected' : '' ?>>和局 (Tie)</option>
                        </select>
                    </div>
                    <button type="submit" name="<?= $editRecord ? 'update' : 'add' ?>" id="btn-submit-action" class="btn-submit" style="width: 100%; padding: 12px; background: <?= $editRecord ? 'var(--secondary)' : '#333' ?>; color: <?= $editRecord ? '#1a1a1a' : 'white' ?>; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: opacity 0.3s;"><?= $editRecord ? '儲存修改' : '確認登錄' ?></button>
                    <button type="button" id="btn-cancel-action" onclick="cancelEdit()" style="display: <?= $editRecord ? 'block' : 'none' ?>; width: 100%; text-align: center; margin-top: 15px; color: #666; background: none; border: none; cursor: pointer; font-family: inherit; font-size: 1rem;">取消修改</button>
                    <a href="admin_dashboard.php" id="link-back-dashboard" style="display: <?= $editRecord ? 'none' : 'block' ?>; text-align: center; margin-top: 15px; color: var(--secondary); text-decoration: none;">返回控制台</a>
                </form>
            </div>

            <!-- Games List -->
            <div class="admin-list-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow-x: auto; min-width: 0;">
                <style>
                    .admin-table th, .admin-table td {
                        white-space: nowrap !important;
                        vertical-align: middle !important;
                    }
                    .admin-table th {
                        font-weight: 700;
                        font-size: 0.9em;
                        letter-spacing: 0.5px;
                        background: #f8f9fa;
                    }
                </style>
                <table class="admin-table" style="width: 100%; min-width: max-content; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd;">
                            <th style="padding: 12px 15px; text-align: left; color: #333;">隊伍</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333;">日期與時間</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333;">攻守</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333;">地點 / 對手</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333;">賽果</th>
                            <th style="padding: 12px 15px; text-align: center; color: #333;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($games)): ?>
                        <tr>
                            <td colspan="6" style="padding: 20px; text-align: center; color: #777;">目前沒有任何比賽紀錄。</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($games as $g): 
                                $gScore = '';
                                $gWinLoss = '';
                                if (!empty($g['result'])) {
                                    $parts = explode(' ', $g['result']);
                                    $gScore = $parts[0];
                                    $gWinLoss = isset($parts[1]) ? $parts[1] : '';
                                }
                            ?>
                                <tr style="border-bottom: 1px solid #eee; transition: background 0.3s; vertical-align: middle;">
                                    <td style="padding: 12px 15px; font-weight: 600; color: var(--secondary);">
                                        <?= isset($teamLookup[$g['Team_Id']]) ? htmlspecialchars($teamLookup[$g['Team_Id']]) : '未知' ?>
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 0.9em; font-family: inherit;">
                                        <strong style="color: #333;"><?= htmlspecialchars($g['game_date']) ?></strong>
                                        <span style="color: #666; margin-left: 8px; font-family: monospace; font-size: 0.95em;"><?= !empty($g['game_time']) ? date('H:i', strtotime($g['game_time'])) : '時間未定' ?></span>
                                    </td>
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
                                    <td style="padding: 12px 15px;">
                                        <strong style="color: #2c3e50; font-size: 0.95em;">vs <?= htmlspecialchars($g['opponent']) ?></strong>
                                        <span style="color: #7f8c8d; font-size: 0.85em; margin-left: 10px;"><i class="fas fa-map-marker-alt" style="margin-right: 4px;"></i><?= htmlspecialchars($g['location']) ?></span>
                                    </td>
                                    <td style="padding: 12px 15px;">
                                        <?php
                                        $resText = htmlspecialchars($g['result']) ?: '未設定';
                                        $resStyle = 'background: #f8f9fa; color: #6c757d;';
                                        if (strpos($g['result'], '勝') !== false) {
                                            $resStyle = 'background: #e8f5e9; color: #2e7d32;';
                                        } elseif (strpos($g['result'], '敗') !== false) {
                                            $resStyle = 'background: #ffebee; color: #c62828;';
                                        } elseif (strpos($g['result'], '和') !== false) {
                                            $resStyle = 'background: #fff3e0; color: #ef6c00;';
                                        }
                                        ?>
                                        <span class="badge" style="padding: 5px 10px; border-radius: 6px; font-size: 0.85em; font-weight: bold; display: inline-block; text-align: center; min-width: 70px; <?= $resStyle ?>">
                                            <?= $resText ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px 15px; text-align: center; white-space: nowrap;">
                                        <button type="button" class="admin-action-btn admin-btn-edit" onclick="editMatchInForm(<?= htmlspecialchars(json_encode([
                                            'Game_id' => $g['Game_id'],
                                            'Team_Id' => $g['Team_Id'],
                                            'game_date' => $g['game_date'],
                                            'game_time' => $g['game_time'] ? substr($g['game_time'], 0, 5) : '',
                                            'location' => $g['location'],
                                            'opponent' => $g['opponent'],
                                            'batting_first' => isset($g['batting_first']) ? $g['batting_first'] : '',
                                            'score' => $gScore,
                                            'win_loss' => $gWinLoss
                                        ]), ENT_QUOTES, 'UTF-8') ?>)"><i class="fas fa-edit"></i> 修改</button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('確定要刪除這場比賽紀錄嗎？警告：刪除比賽後將會一併刪除此場比賽的所有球員數據與相關統計，且無法復原！')">
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

<script>
function editMatchInForm(data) {
    // 1. Change title
    const formTitle = document.querySelector('.admin-form-card h3');
    if (formTitle) {
        formTitle.textContent = '修改賽事';
    }
    
    // 2. Fill values
    document.getElementById('hidden-game-id').value = data.Game_id;
    document.getElementById('select-team-id').value = data.Team_Id;
    document.getElementById('input-game-date').value = data.game_date;
    document.getElementById('input-game-time').value = data.game_time;
    document.getElementById('select-batting-first').value = data.batting_first || '';
    document.getElementById('input-location').value = data.location;
    document.getElementById('input-opponent').value = data.opponent;
    document.getElementById('input-score').value = data.score;
    document.getElementById('select-win-loss').value = data.win_loss;
    
    // 3. Update submit button attributes
    const submitBtn = document.getElementById('btn-submit-action');
    if (submitBtn) {
        submitBtn.name = 'update';
        submitBtn.textContent = '儲存修改';
        submitBtn.style.background = 'var(--secondary)';
        submitBtn.style.color = '#1a1a1a';
    }
    
    // 4. Update helper buttons visibility
    document.getElementById('btn-cancel-action').style.display = 'block';
    document.getElementById('link-back-dashboard').style.display = 'none';
    
    // Scroll to form smoothly
    document.querySelector('.admin-form-card').scrollIntoView({ behavior: 'smooth' });
}

function cancelEdit() {
    if (window.location.search.indexOf('edit_id') !== -1) {
        window.location.href = 'admin_matches.php';
        return;
    }
    
    // 1. Revert title
    const formTitle = document.querySelector('.admin-form-card h3');
    if (formTitle) {
        formTitle.textContent = '登錄新賽事';
    }
    
    // 2. Clear values / Reset form
    document.getElementById('hidden-game-id').value = '';
    document.getElementById('match-form').reset();
    
    // 3. Update submit button
    const submitBtn = document.getElementById('btn-submit-action');
    if (submitBtn) {
        submitBtn.name = 'add';
        submitBtn.textContent = '確認登錄';
        submitBtn.style.background = '#333';
        submitBtn.style.color = 'white';
    }
    
    // 4. Update helper buttons visibility
    document.getElementById('btn-cancel-action').style.display = 'none';
    document.getElementById('link-back-dashboard').style.display = 'block';
}
</script>

<?php require_once 'includes/footer.php'; ?>
