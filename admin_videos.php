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
    if (isset($_POST['delete'])) {
        $db->delete('video', $_POST['Video_id']);
        $msg = '影片已刪除。';
    }
}

$videos = $db->getAll('video');
?>

<div class="page-header">
    <h1>影片專區管理</h1>
    <p>管理會員專屬的賽事精華與教學影片。</p>
</div>

<section>
    <div class="container">
        <?php if ($msg): ?>
            <div class="admin-msg-box" style="background: var(--primary); color: white; padding: 10px 15px; border-radius: 8px; margin-bottom: 20px;">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <div class="admin-players-layout" style="display: grid; grid-template-columns: 350px 1fr; gap: 2rem;">
            <!-- Add Video Form -->
            <div class="admin-form-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); height: fit-content;">
                <h3 style="margin-bottom: 20px; color: #333; border-bottom: 2px solid var(--primary); padding-bottom: 10px; display: inline-block;">新增影片</h3>
                <form method="POST">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">影片標題</label>
                        <input type="text" name="title" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">YouTube 嵌入連結 (Embed URL)</label>
                        <input type="url" name="url" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" placeholder="https://www.youtube.com/embed/..." required>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">分類</label>
                        <select name="category" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                            <option value="比賽精華">比賽精華</option>
                            <option value="訓練解析">訓練解析</option>
                            <option value="科學分析">科學分析</option>
                            <option value="團隊戰術">團隊戰術</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">日期</label>
                        <input type="date" name="date" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">描述</label>
                        <textarea name="description" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; resize: vertical;" rows="3"></textarea>
                    </div>
                    <button type="submit" name="add" class="btn-submit" style="width: 100%; padding: 12px; background: var(--primary); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: opacity 0.3s;">確認新增</button>
                    <a href="admin_dashboard.php" style="display: block; text-align: center; margin-top: 15px; color: var(--secondary); text-decoration: none;">返回控制台</a>
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
                                    <td style="padding: 12px 15px; text-align: center;">
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('確定要刪除這部影片嗎？')">
                                            <input type="hidden" name="Video_id" value="<?= $v['Video_id'] ?>">
                                            <button type="submit" name="delete" style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; transition: background 0.3s; /* hover: background: #c82333; */"><i class="fas fa-trash"></i> 刪除</button>
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
