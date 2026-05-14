<?php
require_once 'includes/header.php';
requireAdmin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $db->insert('teamhistory', [
            'Team_Id'    => (int)$_POST['team_id'],
            'title'      => trim($_POST['title']),
            'content'    => trim($_POST['content']),
            'start_year' => (int)$_POST['start_year'],
            'month'      => !empty($_POST['month']) ? (int)$_POST['month'] : null
        ]);
        $msg = '球隊歷史新增成功！';
    }
    if (isset($_POST['update'])) {
        $db->update('teamhistory', $_POST['History_Id'], [
            'Team_Id'    => (int)$_POST['team_id'],
            'title'      => trim($_POST['title']),
            'content'    => trim($_POST['content']),
            'start_year' => (int)$_POST['start_year'],
            'month'      => !empty($_POST['month']) ? (int)$_POST['month'] : null
        ]);
        $msg = '球隊歷史修改成功！';
    }
    if (isset($_POST['delete'])) {
        $db->delete('teamhistory', $_POST['History_Id']);
        $msg = '球隊歷史已刪除。';
    }
}

$editRecord = null;
if (isset($_GET['edit_id'])) {
    $editRecord = $db->find('teamhistory', 'History_Id', $_GET['edit_id']);
}

$histories = $db->getAll('teamhistory');
usort($histories, function($a, $b) {
    if ($a['start_year'] == $b['start_year']) {
        return (int)($a['month'] ?? 0) <=> (int)($b['month'] ?? 0);
    }
    return $a['start_year'] <=> $b['start_year'];
});

$teams = $db->getAll('team');
?>

<div class="page-header">
    <h1>球隊歷史管理</h1>
    <p>新增、修改或刪除「關於我們」頁面的球隊歷史時間軸內容。</p>
</div>

<section>
    <div class="container">
        <a href="admin_dashboard.php" class="admin-back-btn">
            <i class="fas fa-arrow-left"></i> 返回控制台
        </a>
        <?php if ($msg): ?>
            <div class="admin-msg-box" style="background: var(--primary); color: white; padding: 10px 15px; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <div class="admin-players-layout" style="display: grid; grid-template-columns: 380px 1fr; gap: 2rem;">

            <!-- 表單區 -->
            <div class="admin-form-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); height: fit-content;">
                <h3 style="margin-bottom: 20px; color: #333; border-bottom: 2px solid var(--primary); padding-bottom: 10px; display: inline-block;">
                    <?= $editRecord ? '修改歷史紀錄' : '新增歷史紀錄' ?>
                </h3>
                <form method="POST" action="admin_history.php">
                    <?php if ($editRecord): ?>
                        <input type="hidden" name="History_Id" value="<?= (int)$editRecord['History_Id'] ?>">
                    <?php endif; ?>

                    <div class="form-group" style="margin-bottom: 15px; display: flex; gap: 15px;">
                        <div style="flex: 1;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">年份</label>
                            <input type="number" name="start_year" min="1900" max="2100"
                                   class="form-control"
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;"
                                   value="<?= $editRecord ? (int)$editRecord['start_year'] : date('Y') ?>"
                                   required>
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">月份 (選填)</label>
                            <input type="number" name="month" min="1" max="12"
                                   class="form-control"
                                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;"
                                   placeholder="1-12"
                                   value="<?= $editRecord ? ($editRecord['month'] ? (int)$editRecord['month'] : '') : '' ?>">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">標題</label>
                        <input type="text" name="title"
                               class="form-control"
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;"
                               placeholder="例如：2012 球隊創立"
                               value="<?= $editRecord ? htmlspecialchars($editRecord['title']) : '' ?>"
                               required>
                        <small style="color: #888; display: block; margin-top: 4px;">建議格式：「年份 事件名稱」，如「2015 首次全國賽」</small>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">所屬球隊</label>
                        <select name="team_id"
                                class="form-control"
                                style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                            <?php foreach ($teams as $t): ?>
                                <option value="<?= (int)$t['team_Id'] ?>"
                                    <?= ($editRecord && $editRecord['Team_Id'] == $t['team_Id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['team_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">內容描述</label>
                        <textarea name="content"
                                  class="form-control"
                                  style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; resize: vertical;"
                                  rows="6"
                                  placeholder="描述這段歷史的重要事件、成就或里程碑..."
                                  required><?= $editRecord ? htmlspecialchars($editRecord['content']) : '' ?></textarea>
                    </div>

                    <?php if ($editRecord): ?>
                        <button type="submit" name="update"
                                style="width: 100%; padding: 12px; background: var(--secondary); color: #1a1a1a; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: opacity 0.3s;">
                            儲存修改
                        </button>
                        <a href="admin_history.php"
                           style="display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none;">
                            取消修改
                        </a>
                    <?php else: ?>
                        <button type="submit" name="add"
                                style="width: 100%; padding: 12px; background: var(--primary); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: opacity 0.3s;">
                            確認新增
                        </button>
                        <a href="admin_dashboard.php"
                           style="display: block; text-align: center; margin-top: 15px; color: var(--secondary); text-decoration: none;">
                            返回控制台
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- 列表區 -->
            <div class="admin-list-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow-x: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; color: #333;">歷史紀錄列表</h3>
                    <span style="font-size: 0.85rem; color: #888;">共 <?= count($histories) ?> 筆，依年份排序</span>
                </div>
                <table class="admin-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd;">
                            <th style="padding: 12px 15px; text-align: left; color: #333; width: 80px;">年份</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333;">標題</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333; width: 120px;">所屬球隊</th>
                            <th style="padding: 12px 15px; text-align: left; color: #333;">內容摘要</th>
                            <th style="padding: 12px 15px; text-align: center; color: #333; width: 140px;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($histories)): ?>
                            <tr>
                                <td colspan="5" style="padding: 30px; text-align: center; color: #777;">
                                    <i class="fas fa-history" style="font-size: 2rem; opacity: 0.3; display: block; margin-bottom: 10px;"></i>
                                    目前沒有球隊歷史紀錄，請從左側表單新增。
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php
                            // Build a team name lookup map
                            $teamMap = [];
                            foreach ($teams as $t) {
                                $teamMap[$t['team_Id']] = $t['team_name'];
                            }
                            ?>
                            <?php foreach ($histories as $h): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 12px 15px; font-weight: 700; color: var(--secondary); font-size: 1rem;">
                                        <?= (int)$h['start_year'] ?>
                                        <?php if (!empty($h['month'])): ?>
                                            <span> / <?= (int)$h['month'] ?>月</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px 15px; font-weight: 500; color: var(--primary);">
                                        <?= htmlspecialchars($h['title']) ?>
                                    </td>
                                    <td style="padding: 12px 15px; color: #555; font-size: 0.9em;">
                                        <?= htmlspecialchars($teamMap[$h['Team_Id']] ?? '—') ?>
                                    </td>
                                    <td style="padding: 12px 15px; color: #666; font-size: 0.9em;">
                                        <?= htmlspecialchars(mb_substr($h['content'], 0, 40)) ?><?= mb_strlen($h['content']) > 40 ? '...' : '' ?>
                                    </td>
                                    <td style="padding: 12px 15px; text-align: center; white-space: nowrap;">
                                        <a href="admin_history.php?edit_id=<?= (int)$h['History_Id'] ?>"
                                           class="admin-action-btn admin-btn-edit">
                                            <i class="fas fa-edit"></i> 修改
                                        </a>
                                        <form method="POST" style="display: inline;"
                                              onsubmit="return confirm('確定要刪除「<?= htmlspecialchars(addslashes($h['title'])) ?>」這筆歷史紀錄嗎？')">
                                            <input type="hidden" name="History_Id" value="<?= (int)$h['History_Id'] ?>">
                                            <button type="submit" name="delete" class="admin-action-btn admin-btn-delete">
                                                <i class="fas fa-trash"></i> 刪除
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>

        <div style="margin-top: 20px; text-align: right;">
            <a href="about.php" target="_blank"
               style="color: var(--primary); text-decoration: none; font-size: 0.9rem;">
                <i class="fas fa-external-link-alt"></i> 預覽關於我們頁面
            </a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
