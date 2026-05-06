<?php
require_once 'includes/header.php';
requireAdmin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve'])) {
        $db->update('member', $_POST['mId'], [
            'status' => 'active',
            'role' => $_POST['role'] // Allow admin to set their approved role
        ]);
        $msg = '會員已成功核准並開通帳號。';
    }
    if (isset($_POST['update'])) {
        $db->update('member', $_POST['mId'], [
            'name' => $_POST['name'],
            'account' => $_POST['account'],
            'role' => $_POST['role']
        ]);
        $msg = '會員資料已修改成功。';
    }
    if (isset($_POST['delete'])) {
        $db->delete('member', $_POST['mId']);
        $msg = '已拒絕並刪除該申請紀錄/會員。';
    }
}

$editRecord = null;
if (isset($_GET['edit_id'])) {
    $editRecord = $db->find('member', 'mId', $_GET['edit_id']);
}

// Fetch only pending members
$members = $db->getAll('member');
$pendingMembers = array_filter($members, function($m) {
    return $m['status'] === 'pending';
});

// Fetch active members to show a quick list or if admin wants to manage them.
$activeMembers = array_filter($members, function($m) {
    return $m['status'] === 'active';
});
?>

<div class="page-header">
    <h1>會員註冊審核</h1>
    <p>審核新註冊的會員帳號，指派權限等級 (粉絲 / 球員 / 管理員) 並開通系統權限。</p>
</div>

<section>
    <div class="container">
        <?php if ($msg): ?>
            <div class="admin-msg-box" style="background: var(--primary); color: white; padding: 10px 15px; border-radius: 8px; margin-bottom: 20px;">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <?php if ($editRecord): ?>
            <div class="admin-form-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 2rem;">
                <h3 style="margin-bottom: 20px; color: #333; border-bottom: 2px solid var(--primary); padding-bottom: 10px; display: inline-block;">修改會員資料</h3>
                <form method="POST" action="admin_members.php">
                    <input type="hidden" name="mId" value="<?= $editRecord['mId'] ?>">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">姓名</label>
                        <input type="text" name="name" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" value="<?= htmlspecialchars($editRecord['name']) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">帳號 (Email)</label>
                        <input type="email" name="account" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;" value="<?= htmlspecialchars($editRecord['account']) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">權限角色</label>
                        <select name="role" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                            <?php $r = $editRecord['role']; ?>
                            <option value="fan" <?= $r == 'fan' ? 'selected' : '' ?>>一般粉絲</option>
                            <option value="player" <?= $r == 'player' ? 'selected' : '' ?>>本校球員</option>
                            <option value="admin" <?= $r == 'admin' ? 'selected' : '' ?>>管理員</option>
                        </select>
                    </div>
                    <button type="submit" name="update" class="btn-submit" style="width: 100%; padding: 12px; background: var(--secondary); color: #1a1a1a; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: opacity 0.3s;">儲存修改</button>
                    <a href="admin_members.php" style="display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none;">取消修改</a>
                </form>
            </div>
        <?php endif; ?>

        <div class="admin-list-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow-x: auto;">
            <div style="margin-bottom: 20px;">
                <a href="admin_dashboard.php" style="color: var(--secondary); text-decoration: none; font-weight: bold;"><i class="fas fa-arrow-left"></i> 返回控制台</a>
            </div>
            
            <h3 style="margin-bottom: 15px; color: #333; border-bottom: 2px solid var(--secondary); padding-bottom: 10px; display: inline-block;">待核准名單</h3>
            
            <table class="admin-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd;">
                        <th style="padding: 12px 15px; text-align: left; color: #333;">姓名</th>
                        <th style="padding: 12px 15px; text-align: left; color: #333;">帳號 (Email/Account)</th>
                        <th style="padding: 12px 15px; text-align: left; color: #333;">註冊時間</th>
                        <th style="padding: 12px 15px; text-align: center; color: #333;">核准並選擇身分</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pendingMembers)): ?>
                    <tr>
                        <td colspan="4" style="padding: 20px; text-align: center; color: #777;">目前沒有待審核的會員名單。</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($pendingMembers as $m): ?>
                            <tr style="border-bottom: 1px solid #eee; transition: background 0.3s; vertical-align: middle;">
                                <td style="padding: 12px 15px; font-weight: 500; color: var(--primary);">
                                    <?= htmlspecialchars($m['name']) ?>
                                </td>
                                <td style="padding: 12px 15px;">
                                    <?= htmlspecialchars($m['account']) ?>
                                </td>
                                <td style="padding: 12px 15px; color: #666;">
                                    <?= htmlspecialchars($m['created_at']) ?>
                                </td>
                                <td style="padding: 12px 15px; text-align: center; white-space: nowrap;">
                                    <!-- Approve Form -->
                                    <form method="POST" style="display: inline-flex; align-items: center; gap: 10px; justify-content: center; width: 100%;">
                                        <input type="hidden" name="mId" value="<?= $m['mId'] ?>">
                                        <select name="role" class="form-control" style="padding: 6px; border-radius: 4px; border: 1px solid #ccc; max-width: 120px;" required>
                                            <option value="fan" <?= $m['role'] == 'fan' ? 'selected' : '' ?>>一般粉絲</option>
                                            <option value="player" <?= $m['role'] == 'player' ? 'selected' : '' ?>>本校球員</option>
                                            <option value="admin" <?= $m['role'] == 'admin' ? 'selected' : '' ?>>管理員</option>
                                        </select>
                                        <button type="submit" name="approve" style="background: #28a745; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; transition: background 0.3s; font-weight: bold; display: inline-flex; align-items: center; gap: 5px; font-size: 0.9rem;"><i class="fas fa-check"></i> 批准</button>
                                        <button type="submit" name="delete" onclick="return confirm('確定要拒絕並刪除這位成員的註冊申請嗎？');" class="admin-action-btn admin-btn-delete" style="margin-right: 0;"><i class="fas fa-times"></i> 拒絕</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="admin-list-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow-x: auto; margin-top: 2rem;">
            <h3 style="margin-bottom: 15px; color: #333; border-bottom: 2px solid var(--secondary); padding-bottom: 10px; display: inline-block;">已核准會員名單</h3>
            <table class="admin-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd;">
                        <th style="padding: 12px 15px; text-align: left; color: #333;">姓名</th>
                        <th style="padding: 12px 15px; text-align: left; color: #333;">帳號 (Email)</th>
                        <th style="padding: 12px 15px; text-align: left; color: #333;">權限角色</th>
                        <th style="padding: 12px 15px; text-align: center; color: #333;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($activeMembers)): ?>
                    <tr>
                        <td colspan="4" style="padding: 20px; text-align: center; color: #777;">目前沒有已開通的會員。</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($activeMembers as $m): ?>
                            <tr style="border-bottom: 1px solid #eee; transition: background 0.3s; vertical-align: middle;">
                                <td style="padding: 12px 15px; font-weight: 500; color: var(--primary);">
                                    <?= htmlspecialchars($m['name']) ?>
                                </td>
                                <td style="padding: 12px 15px;">
                                    <?= htmlspecialchars($m['account']) ?>
                                </td>
                                <td style="padding: 12px 15px; color: #666;">
                                    <?php 
                                        $rMap = ['fan'=>'一般粉絲', 'player'=>'本校球員', 'admin'=>'管理員'];
                                        echo isset($rMap[$m['role']]) ? $rMap[$m['role']] : htmlspecialchars($m['role']);
                                    ?>
                                </td>
                                <td style="padding: 12px 15px; text-align: center; white-space: nowrap;">
                                    <a href="admin_members.php?edit_id=<?= $m['mId'] ?>" class="admin-action-btn admin-btn-edit"><i class="fas fa-edit"></i> 修改</a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('確定要刪除這位會員的帳號嗎？此操作無法復原。');">
                                        <input type="hidden" name="mId" value="<?= $m['mId'] ?>">
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
</section>

<?php require_once 'includes/footer.php'; ?>
