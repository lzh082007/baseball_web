<?php
require_once 'includes/header.php';
requireAdmin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $image_path = '';
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/ob/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $filename = time() . '_' . basename($_FILES['image_file']['name']);
            $target_path = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_path)) {
                $image_path = $target_path;
            }
        }

        $db->insert('ob', [
            'Team_Id' => (int)$_POST['team_id'],
            'OB_name' => $_POST['OB_name'],
            'graduation_year' => (int)$_POST['graduation_year'],
            'status' => $_POST['status'],
            'image_path' => $image_path
        ]);
        $msg = 'OB 校友新增成功！';
    }
    if (isset($_POST['delete'])) {
        $db->delete('ob', $_POST['Ob_id']);
        $msg = 'OB 校友已刪除。';
    }
}

$obMembers = $db->getAll('ob');
usort($obMembers, function($a, $b) {
    return $b['graduation_year'] <=> $a['graduation_year'];
});
$teams = $db->getAll('team');
?>

<div class="page-header">
    <h1>中科 OB 管理</h1>
    <p>管理畢業學長姐名單，更新傳承的英雄榜紀錄。</p>
</div>

<section>
    <div class="container">
        <?php if ($msg): ?>
            <div class="admin-msg-box" style="background: var(--primary); color: white; padding: 10px 15px; border-radius: 8px; margin-bottom: 20px;">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <div class="admin-players-layout" style="display: grid; grid-template-columns: 350px 1fr; gap: 2rem;">
            <!-- Add OB Form -->
            <div class="admin-form-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); height: fit-content;">
                <h3 style="margin-bottom: 20px; color: #333; border-bottom: 2px solid var(--primary); padding-bottom: 10px; display: inline-block;">新增 OB 資料</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">姓名</label>
                        <input type="text" name="OB_name" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">畢業年度 (西元或民國，依團隊統一標準)</label>
                        <input type="number" name="graduation_year" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">所屬球隊</label>
                        <select name="team_id" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                            <?php foreach ($teams as $t): ?>
                                <option value="<?= $t['team_Id'] ?>"><?= htmlspecialchars($t['team_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">上傳照片</label>
                        <input type="file" name="image_file" class="form-control" accept="image/*" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">豐功偉業 / 畢業現況</label>
                        <textarea name="status" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; resize: vertical;" rows="4" placeholder="例如：110年大專盃冠軍隊長、現就職於某科技公司..."></textarea>
                    </div>
                    <button type="submit" name="add" class="btn-submit" style="width: 100%; padding: 12px; background: var(--primary); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: opacity 0.3s;">確認新增</button>
                    <a href="admin_dashboard.php" style="display: block; text-align: center; margin-top: 15px; color: var(--secondary); text-decoration: none;">返回控制台</a>
                </form>
            </div>

            <!-- OB List -->
            <div class="admin-list-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow-x: auto;">
                <table class="admin-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd;">
                            <th style="padding: 12px 15px; text-align: left; color: #333;">年度</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333;">姓名</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333;">現況或偉業</th>
                            <th style="padding: 12px 15px; text-align: center; color: #333;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($obMembers)): ?>
                        <tr>
                            <td colspan="4" style="padding: 20px; text-align: center; color: #777;">目前沒有 OB 名單。</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($obMembers as $ob): ?>
                                <tr style="border-bottom: 1px solid #eee; transition: background 0.3s; /* hover: background: #fdfdfd; */">
                                    <td style="padding: 12px 15px; font-weight: 500; color: var(--secondary);"><?= htmlspecialchars($ob['graduation_year']) ?></td>
                                    <td style="padding: 12px 15px; font-weight: 500; color: var(--primary);"><?= htmlspecialchars($ob['OB_name']) ?></td>
                                    <td style="padding: 12px 15px; color: #666; font-size: 0.9em;"><?= mb_substr(htmlspecialchars($ob['status']), 0, 30) ?><?= mb_strlen($ob['status']) > 30 ? '...' : '' ?></td>
                                    <td style="padding: 12px 15px; text-align: center;">
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('確定要刪除這位學長姐的紀錄嗎？')">
                                            <input type="hidden" name="Ob_id" value="<?= $ob['Ob_id'] ?>">
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
