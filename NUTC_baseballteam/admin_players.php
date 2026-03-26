<?php
require_once 'includes/header.php';
requireAdmin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $db->insert('player', [
            'team_id' => (int)$_POST['team_id'],
            'name' => $_POST['name'],
            'jersey_number' => (int)$_POST['jersey_number'],
            'position' => $_POST['position'],
            'height' => (int)$_POST['height'],
            'weight' => (int)$_POST['weight'],
            'image_path' => !empty($_POST['image_path']) ? $_POST['image_path'] : 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($_POST['name'])
        ]);
        $msg = '球員新增成功！';
    }
    if (isset($_POST['delete'])) {
        $db->delete('player', $_POST['player_id']);
        $msg = '球員已刪除。';
    }
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
        <?php if ($msg): ?>
            <div class="admin-msg-box">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <div class="admin-players-layout">
            <!-- Add Player Form -->
            <div class="admin-form-card">
                <h3>新增球員</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>姓名</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>背號</label>
                        <input type="number" name="jersey_number" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>守位</label>
                        <select name="position" class="form-control">
                            <option value="投手">投手</option>
                            <option value="捕手">捕手</option>
                            <option value="內野手">內野手</option>
                            <option value="外野手">外野手</option>
                            <option value="教練">教練</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>所屬球隊</label>
                        <select name="team_id" class="form-control">
                            <?php foreach ($teams as $t): ?>
                                <option value="<?= $t['team_id'] ?>"><?= $t['team_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>球員照片內容 (URL)</label>
                        <input type="text" name="image_path" class="form-control" placeholder="留空則自動生成...">
                    </div>
                    <div class="admin-input-grid">
                        <div class="form-group">
                            <label>身高 (cm)</label>
                            <input type="number" name="height" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>體重 (kg)</label>
                            <input type="number" name="weight" class="form-control">
                        </div>
                    </div>
                    <button type="submit" name="add" class="btn-submit" style="width: 100%; padding: 12px; margin-top: 10px;">確認新增</button>
                    <a href="admin_dashboard.php" class="admin-form-footer-link">返回控制台</a>
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
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($players as $p): ?>
                            <tr>
                                <td class="admin-number-cell">#<?= $p['jersey_number'] ?></td>
                                <td class="admin-name-cell"><?= htmlspecialchars($p['name']) ?></td>
                                <td><?= $p['position'] ?></td>
                                <td>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('確定要刪除嗎？')">
                                        <input type="hidden" name="player_id" value="<?= $p['player_id'] ?>">
                                        <button type="submit" name="delete" class="admin-delete-btn"><i class="fas fa-trash"></i> 刪除</button>
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
