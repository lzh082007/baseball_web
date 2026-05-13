<?php
require_once 'includes/header.php';
requireAdmin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $db->insert('video', [
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
            'date' => $_POST['date'],
            'category' => $_POST['category']
        ]);
        $msg = '影片新增成功！';
    }
    if (isset($_POST['update'])) {
        $db->update('video', $_POST['Video_id'], [
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
            'date' => $_POST['date'],
            'category' => $_POST['category']
        ]);
        $msg = '影片修改成功！';
    }
    if (isset($_POST['delete'])) {
        $db->delete('video', $_POST['Video_id']);
        $msg = '影片已刪除。';
    }
}

$editRecord = null;
if (isset($_GET['edit_id'])) {
    $editRecord = $db->find('video', 'Video_id', $_GET['edit_id']);
}

$videos = $db->getAll('video');
?>

<div class="page-header">
    <h1>影片專區管理</h1>
    <p>管理會員專屬的賽事精華與教學影片。</p>
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
            <!-- Add Video Form -->
            <div class="admin-form-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); height: fit-content;">
                <h3 style="margin-bottom: 20px; color: #333; border-bottom: 2px solid var(--primary); padding-bottom: 10px; display: inline-block;"><?= $editRecord ? '修改影片' : '新增影片' ?></h3>
                <form method="POST" action="admin_videos.php">
                    <?php if ($editRecord): ?>
                        <input type="hidden" name="Video_id" value="<?= $editRecord['Video_id'] ?>">
                    <?php endif; ?>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">影片標題</label>
                        <input type="text" name="title" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" value="<?= $editRecord ? htmlspecialchars($editRecord['title']) : '' ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">YouTube 嵌入連結 (Embed URL)</label>
                        <input type="url" name="url" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" placeholder="https://www.youtube.com/embed/..." value="<?= $editRecord ? htmlspecialchars($editRecord['url']) : '' ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">分類 (請手動輸入)</label>
                        <input type="text" name="category" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" placeholder="例如：比賽精華、訓練解析..." value="<?= $editRecord ? htmlspecialchars($editRecord['category']) : '' ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">日期</label>
                        <input type="date" name="date" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" value="<?= $editRecord ? $editRecord['date'] : '' ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">描述</label>
                        <textarea name="description" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; resize: vertical;" rows="3"><?= $editRecord ? htmlspecialchars($editRecord['description']) : '' ?></textarea>
                    </div>
                    <?php if ($editRecord): ?>
                        <button type="submit" name="update" class="btn-submit" style="width: 100%; padding: 12px; background: var(--secondary); color: #1a1a1a; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: opacity 0.3s;">儲存修改</button>
                        <a href="admin_videos.php" style="display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none;">取消修改</a>
                    <?php else: ?>
                        <button type="submit" name="add" class="btn-submit" style="width: 100%; padding: 12px; background: var(--primary); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: opacity 0.3s;">確認新增</button>
                        <a href="admin_dashboard.php" style="display: block; text-align: center; margin-top: 15px; color: var(--secondary); text-decoration: none;">返回控制台</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Videos List -->
            <div class="admin-list-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow-x: auto;">
                <table class="admin-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd;">
                            <th style="padding: 12px 15px; text-align: left; color: #333;">分類</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333;">標題</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333;">日期</th>
                            <th style="padding: 12px 15px; text-align: center; color: #333;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($videos)): ?>
                        <tr>
                            <td colspan="4" style="padding: 20px; text-align: center; color: #777;">目前沒有影片資料。</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($videos as $v): ?>
                                <tr style="border-bottom: 1px solid #eee; transition: background 0.3s; /* hover: background: #fdfdfd; */">
                                    <td style="padding: 12px 15px;"><span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-size: 0.85em; color: #555;"><?= htmlspecialchars($v['category']) ?></span></td>
                                    <td style="padding: 12px 15px; font-weight: 500; color: var(--primary);"><?= htmlspecialchars($v['title']) ?></td>
                                    <td style="padding: 12px 15px; color: #666;"><?= htmlspecialchars($v['date']) ?></td>
                                    <td style="padding: 12px 15px; text-align: center; white-space: nowrap;">
                                        <a href="admin_videos.php?edit_id=<?= $v['Video_id'] ?>" class="admin-action-btn admin-btn-edit"><i class="fas fa-edit"></i> 修改</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('確定要刪除這部影片嗎？')">
                                            <input type="hidden" name="Video_id" value="<?= $v['Video_id'] ?>">
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
