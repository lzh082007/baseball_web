<?php
require_once 'includes/header.php';
requireAdmin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $db->insert('contact_us', [
            'icon_class' => $_POST['icon_class'],
            'content_text' => $_POST['content_text']
        ]);
        $msg = '聯絡資訊新增成功！';
    }
    if (isset($_POST['delete'])) {
        $db->delete('contact_us', $_POST['id']);
        $msg = '聯絡資訊已刪除。';
    }
}

// Fetch all contact info
$contacts = $db->getAll('contact_us');
?>

<div class="page-header">
    <h1>聯繫我們管理</h1>
    <p>管理網站底部的聯絡資訊。</p>
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
            <!-- Add Contact Form -->
            <div class="admin-form-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); height: fit-content;">
                <h3 style="margin-bottom: 20px; color: #333; border-bottom: 2px solid var(--primary); padding-bottom: 10px; display: inline-block;">新增聯絡資訊</h3>
                <form method="POST">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">圖示 (FontAwesome Class)</label>
                        <input type="text" name="icon_class" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" placeholder="例如: fas fa-phone" required>
                        <small style="color: #888; margin-top: 5px; display: block;">可參考 <a href="https://fontawesome.com/v5/search?m=free" target="_blank" style="color: var(--secondary);">FontAwesome 5</a></small>
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">文字內容</label>
                        <input type="text" name="content_text" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" placeholder="例如: 04-2219-XXXX" required>
                    </div>
                    <button type="submit" name="add" class="btn-submit" style="width: 100%; padding: 12px; background: var(--primary); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: opacity 0.3s;">確認新增</button>
                    <a href="admin_dashboard.php" style="display: block; text-align: center; margin-top: 15px; color: var(--secondary); text-decoration: none;">返回控制台</a>
                </form>
            </div>

            <!-- Contacts List -->
            <div class="admin-list-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow-x: auto;">
                <table class="admin-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd;">
                            <th style="padding: 12px 15px; text-align: center; color: #333; width: 20%;">圖示預覽</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333; width: 60%;">文字內容</th>
                            <th style="padding: 12px 15px; text-align: center; color: #333; width: 20%;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contacts)): ?>
                        <tr>
                            <td colspan="3" style="padding: 20px; text-align: center; color: #777;">目前沒有聯絡資訊。</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($contacts as $c): ?>
                                <tr style="border-bottom: 1px solid #eee; transition: background 0.3s;">
                                    <td style="padding: 12px 15px; text-align: center; font-size: 1.5rem; color: var(--secondary);">
                                        <i class="<?= htmlspecialchars($c['icon_class']) ?>"></i>
                                    </td>
                                    <td style="padding: 12px 15px; font-weight: 500; color: var(--text-dark);">
                                        <?= htmlspecialchars($c['content_text']) ?>
                                        <div style="font-size: 0.8em; color: #999; margin-top: 4px;"><?= htmlspecialchars($c['icon_class']) ?></div>
                                    </td>
                                    <td style="padding: 12px 15px; text-align: center;">
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('確定要刪除這筆聯絡資訊嗎？')">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <button type="submit" name="delete" style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; transition: background 0.3s;"><i class="fas fa-trash"></i> 刪除</button>
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
