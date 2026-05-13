<?php
require_once 'includes/header.php';
requireAdmin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $image_path = '';
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/players/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $filename = time() . '_' . basename($_FILES['image_file']['name']);
            $target_path = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_path)) {
                $image_path = $target_path;
            }
        }

        $db->insert('player', [
            'Team_Id' => (int)$_POST['team_id'],
            'Player_Name' => $_POST['name'],
            'jersey_number' => (int)$_POST['jersey_number'],
            'position' => isset($_POST['position']) && is_array($_POST['position']) ? implode(',', $_POST['position']) : '',
            'height' => (int)$_POST['height'],
            'weight' => (int)$_POST['weight'],
            'image_path' => $image_path
        ]);
        $msg = '球員新增成功！';
    }
    if (isset($_POST['update'])) {
        $updateData = [
            'Team_Id' => (int)$_POST['team_id'],
            'Player_Name' => $_POST['name'],
            'jersey_number' => (int)$_POST['jersey_number'],
            'position' => isset($_POST['position']) && is_array($_POST['position']) ? implode(',', $_POST['position']) : '',
            'height' => (int)$_POST['height'],
            'weight' => (int)$_POST['weight']
        ];
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/players/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $filename = time() . '_' . basename($_FILES['image_file']['name']);
            $target_path = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_path)) {
                $updateData['image_path'] = $target_path;
            }
        }
        $db->update('player', $_POST['Player_id'], $updateData);
        $msg = '球員資料修改成功！';
    }
    if (isset($_POST['delete'])) {
        $db->delete('player', $_POST['player_id']);
        $msg = '球員已刪除。';
    }
}

$editRecord = null;
if (isset($_GET['edit_id'])) {
    $editRecord = $db->find('player', 'Player_id', $_GET['edit_id']);
}

$players = $db->getAll('player');
$teams = $db->getAll('team');
?>

<div class="page-header">
    <h1>球員資料管理</h1>
    <p>維護核心成員名單，更新場上守位與個人數據。</p>
</div>

<section>
    <div class="container">
        <a href="admin_dashboard.php" class="admin-back-btn">
            <i class="fas fa-arrow-left"></i> 返回控制台
        </a>
        <?php if ($msg): ?>
            <div class="admin-msg-box">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <div class="admin-players-layout">
            <!-- Add Player Form -->
            <div class="admin-form-card">
                <h3><?= $editRecord ? '修改球員' : '新增球員' ?></h3>
                <form method="POST" action="admin_players.php" enctype="multipart/form-data">
                    <?php if ($editRecord): ?>
                        <input type="hidden" name="Player_id" value="<?= $editRecord['Player_id'] ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label>姓名</label>
                        <input type="text" name="name" class="form-control" value="<?= $editRecord ? htmlspecialchars($editRecord['Player_Name']) : '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label>背號</label>
                        <input type="number" name="jersey_number" class="form-control" value="<?= $editRecord ? htmlspecialchars($editRecord['jersey_number']) : '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label>守位 (可複選)</label>
                        <div class="checkbox-group">
                            <?php 
                            $posArr = $editRecord ? explode(',', $editRecord['position']) : []; 
                            $availablePos = ['投手', '捕手', '內野手', '外野手', '教練'];
                            foreach ($availablePos as $p):
                            ?>
                                <label class="checkbox-item <?= in_array($p, $posArr) ? 'active' : '' ?>">
                                    <input type="checkbox" name="position[]" value="<?= $p ?>" <?= in_array($p, $posArr) ? 'checked' : '' ?> onchange="this.parentElement.classList.toggle('active', this.checked)">
                                    <span><?= $p ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>所屬球隊</label>
                        <select name="team_id" class="form-control">
                            <?php foreach ($teams as $t): ?>
                                <option value="<?= $t['team_Id'] ?>" <?= $editRecord && $editRecord['Team_Id'] == $t['team_Id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['team_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>球員照片上傳 (若不修改請留空)</label>
                        <input type="file" name="image_file" class="form-control" accept="image/*">
                        <?php if ($editRecord && !empty($editRecord['image_path'])): ?>
                            <small style="color: #666;">目前圖片已上傳</small>
                        <?php endif; ?>
                    </div>
                    <div class="admin-input-grid">
                        <div class="form-group">
                            <label>身高 (cm)</label>
                            <input type="number" name="height" class="form-control" value="<?= $editRecord ? htmlspecialchars($editRecord['height']) : '' ?>">
                        </div>
                        <div class="form-group">
                            <label>體重 (kg)</label>
                            <input type="number" name="weight" class="form-control" value="<?= $editRecord ? htmlspecialchars($editRecord['weight']) : '' ?>">
                        </div>
                    </div>
                    <?php if ($editRecord): ?>
                        <button type="submit" name="update" class="btn-submit" style="width: 100%; padding: 12px; margin-top: 10px; background: var(--secondary); color: #1a1a1a;">儲存修改</button>
                        <a href="admin_players.php" class="admin-form-footer-link" style="display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none;">取消修改</a>
                    <?php else: ?>
                        <button type="submit" name="add" class="btn-submit" style="width: 100%; padding: 12px; margin-top: 10px;">確認新增</button>
                        <a href="admin_dashboard.php" class="admin-form-footer-link">返回控制台</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Players List -->
            <div class="admin-list-card">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>姓名</th>
                            <th>守位</th>
                            <th style="text-align: center;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($players as $p): ?>
                            <tr>
                                <td class="admin-number-cell">#<?= $p['jersey_number'] ?></td>
                                <td class="admin-name-cell"><?= htmlspecialchars($p['Player_Name']) ?></td>
                                <td><?= $p['position'] ?></td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <a href="admin_players.php?edit_id=<?= $p['Player_id'] ?>" class="admin-action-btn admin-btn-edit"><i class="fas fa-edit"></i> 修改</a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('確定要刪除嗎？')">
                                        <input type="hidden" name="player_id" value="<?= $p['Player_id'] ?>">
                                        <button type="submit" name="delete" class="admin-action-btn admin-btn-delete"><i class="fas fa-trash"></i> 刪除</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
