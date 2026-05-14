<?php
require_once 'includes/header.php';
requireAdmin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $db->insert('contact_us', [
            'icon_class' => $_POST['icon_class'],
            'content_text' => $_POST['content_text'],
            'link' => $_POST['link'] ?? null
        ]);
        $msg = '聯絡資訊新增成功！';
    }
    if (isset($_POST['update'])) {
        $db->update('contact_us', $_POST['id'], [
            'icon_class' => $_POST['icon_class'],
            'content_text' => $_POST['content_text'],
            'link' => $_POST['link'] ?? null
        ]);
        $msg = '聯絡資訊修改成功！';
    }
    if (isset($_POST['delete'])) {
        $db->delete('contact_us', $_POST['id']);
        $msg = '聯絡資訊已刪除。';
    }
}

$editRecord = null;
if (isset($_GET['edit_id'])) {
    $editRecord = $db->find('contact_us', 'id', $_GET['edit_id']);
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
            <div class="admin-msg-box" style="background: #E8F5E9; color: #2E7D32; padding: 15px; border-radius: 10px; margin-bottom: 30px; font-weight: 700; text-align: center;">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <div class="admin-players-layout" style="display: grid; grid-template-columns: 350px 1fr; gap: 2rem;">
            <!-- Add Contact Form -->
            <div class="admin-form-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); height: fit-content;">
                <h3 style="margin-bottom: 20px; color: #333; border-bottom: 2px solid var(--primary); padding-bottom: 10px; display: inline-block;"><?= $editRecord ? '修改聯絡資訊' : '新增聯絡資訊' ?></h3>
                <form method="POST" action="admin_contact.php">
                    <?php if ($editRecord): ?>
                        <input type="hidden" name="id" value="<?= $editRecord['id'] ?>">
                    <?php endif; ?>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">圖示 (FontAwesome Class)</label>
                        <input type="text" name="icon_class" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" placeholder="例如: fas fa-phone" value="<?= $editRecord ? htmlspecialchars($editRecord['icon_class']) : '' ?>" required>
                        <small style="color: #888; margin-top: 5px; display: block;">可參考 <a href="https://fontawesome.com/v5/search?m=free" target="_blank" style="color: var(--secondary);">FontAwesome 5</a></small>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">文字內容</label>
                        <input type="text" name="content_text" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" placeholder="例如: 04-2219-XXXX" value="<?= $editRecord ? htmlspecialchars($editRecord['content_text']) : '' ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">連結網址 (選填)</label>
                        <input type="text" name="link" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" placeholder="例如: https://... " value="<?= $editRecord ? htmlspecialchars($editRecord['link'] ?? '') : '' ?>">
                    </div>
                    <?php if ($editRecord): ?>
                        <button type="submit" name="update" class="btn-submit" style="width: 100%; padding: 12px; background: var(--secondary); color: #1a1a1a; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: opacity 0.3s;">儲存修改</button>
                        <a href="admin_contact.php" style="display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none;">取消修改</a>
                    <?php else: ?>
                        <button type="submit" name="add" class="btn-submit" style="width: 100%; padding: 12px; background: var(--primary); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: opacity 0.3s;">確認新增</button>
                        <a href="admin_dashboard.php" style="display: block; text-align: center; margin-top: 15px; color: var(--secondary); text-decoration: none;">返回控制台</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Contacts List -->
            <div class="admin-list-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow-x: auto;">
                <table class="admin-table" style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                    <thead>
                        <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd;">
                            <th style="padding: 12px 15px; text-align: center; color: #333; width: 15%;">圖示預覽</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333; width: 45%;">文字內容</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333; width: 20%;">連結</th>
                            <th style="padding: 12px 15px; text-align: center; color: #333; width: 20%;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contacts)): ?>
                        <tr>
                            <td colspan="4" style="padding: 20px; text-align: center; color: #777;">目前沒有聯絡資訊。</td>
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
                                    <td style="padding: 12px 15px; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= !empty($c['link']) ? htmlspecialchars($c['link']) : '' ?>">
                                        <?= !empty($c['link']) ? htmlspecialchars($c['link']) : '<span style="color: #ccc;">無</span>' ?>
                                    </td>
                                    <td style="padding: 12px 15px; text-align: center; white-space: nowrap;">
                                        <a href="admin_contact.php?edit_id=<?= $c['id'] ?>" style="background: #ffc107; color: #212529; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; transition: background 0.3s; text-decoration: none; font-size: 0.9rem; margin-right: 5px;"><i class="fas fa-edit"></i> 修改</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('確定要刪除這筆聯絡資訊嗎？')">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <button type="submit" name="delete" style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; transition: background 0.3s; font-size: 0.9rem;"><i class="fas fa-trash"></i> 刪除</button>
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
