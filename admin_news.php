<?php
require_once 'includes/header.php';
requireAdmin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $db->insert('news', [
            'title' => $_POST['title'],
            'content' => $_POST['content']
            // created_at will use DB default current_timestamp() if handled by DB,
            // or we can let the Database.php insert handle it. 
            // In Database.php we see created_at is strictly handled for member table,
            // but news table has DEFAULT current_timestamp(). So we don't need to specify it.
        ]);
        $msg = '最新消息新增成功！';
    }
    if (isset($_POST['update'])) {
        $db->update('news', $_POST['news_id'], [
            'title' => $_POST['title'],
            'content' => $_POST['content']
        ]);
        $msg = '最新消息修改成功！';
    }
    if (isset($_POST['delete'])) {
        $db->delete('news', $_POST['news_id']);
        $msg = '最新消息已刪除。';
    }
}

$editRecord = null;
if (isset($_GET['edit_id'])) {
    $editRecord = $db->find('news', 'news_id', $_GET['edit_id']);
}

// Fetch all news
$newsRecords = $db->getAll('news');
// Sort by created_at descending
usort($newsRecords, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
?>

<div class="page-header">
    <h1>最新消息管理</h1>
    <p>管理首頁顯示的官方消息與公告。</p>
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
            <!-- Add News Form -->
            <div class="admin-form-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); height: fit-content;">
                <h3 style="margin-bottom: 20px; color: #333; border-bottom: 2px solid var(--primary); padding-bottom: 10px; display: inline-block;"><?= $editRecord ? '修改消息' : '發佈消息' ?></h3>
                <form method="POST" action="admin_news.php">
                    <?php if ($editRecord): ?>
                        <input type="hidden" name="news_id" value="<?= $editRecord['news_id'] ?>">
                    <?php endif; ?>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">標題</label>
                        <input type="text" name="title" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" value="<?= $editRecord ? htmlspecialchars($editRecord['title']) : '' ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">內容</label>
                        <textarea name="content" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; resize: vertical;" rows="8" required><?= $editRecord ? htmlspecialchars($editRecord['content']) : '' ?></textarea>
                    </div>
                    <?php if ($editRecord): ?>
                        <button type="submit" name="update" class="btn-submit" style="width: 100%; padding: 12px; background: var(--secondary); color: #1a1a1a; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: opacity 0.3s;">儲存修改</button>
                        <a href="admin_news.php" style="display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none;">取消修改</a>
                    <?php else: ?>
                        <button type="submit" name="add" class="btn-submit" style="width: 100%; padding: 12px; background: var(--primary); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: opacity 0.3s;">發佈消息</button>
                        <a href="admin_dashboard.php" style="display: block; text-align: center; margin-top: 15px; color: var(--secondary); text-decoration: none;">返回控制台</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- News List -->
            <div class="admin-list-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow-x: auto;">
                <table class="admin-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd;">
                            <th style="padding: 12px 15px; text-align: left; color: #333; width: 50%;">標題</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333; width: 30%;">發佈時間</th>
                            <th style="padding: 12px 15px; text-align: center; color: #333; width: 20%;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($newsRecords)): ?>
                        <tr>
                            <td colspan="3" style="padding: 20px; text-align: center; color: #777;">目前沒有最新消息。</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($newsRecords as $news): ?>
                                <tr style="border-bottom: 1px solid #eee; transition: background 0.3s;">
                                    <td style="padding: 12px 15px; font-weight: 500; color: var(--primary);"><?= htmlspecialchars($news['title']) ?></td>
                                    <td style="padding: 12px 15px; color: #666;"><?= htmlspecialchars($news['created_at']) ?></td>
                                    <td style="padding: 12px 15px; text-align: center; white-space: nowrap;">
                                        <a href="admin_news.php?edit_id=<?= $news['news_id'] ?>" class="admin-action-btn admin-btn-edit"><i class="fas fa-edit"></i> 修改</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('確定要刪除這則消息嗎？')">
                                            <input type="hidden" name="news_id" value="<?= $news['news_id'] ?>">
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
