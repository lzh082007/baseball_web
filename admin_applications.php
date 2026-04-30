<?php
require_once 'includes/header.php';
requireAdmin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete'])) {
        $db->delete('form', $_POST['form_id']);
        $msg = '申請單已移除。';
    }
}

// Fetch all forms
$applications = $db->getAll('form');
$teams = $db->getAll('team');

// Create a lookup for team names
$teamLookup = [];
foreach ($teams as $t) {
    $teamLookup[$t['team_Id']] = $t['team_name'];
}
?>

<div class="page-header">
    <h1>招募申請審核</h1>
    <p>審理來自前台「招募資訊」表單的入隊申請，您可以查看申請者的動機並聯絡後刪除紀錄。</p>
</div>

<section>
    <div class="container">
        <?php if ($msg): ?>
            <div class="admin-msg-box" style="background: var(--primary); color: white; padding: 10px 15px; border-radius: 8px; margin-bottom: 20px;">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <div class="admin-list-card" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow-x: auto;">
            <div style="margin-bottom: 20px;">
                <a href="admin_dashboard.php" style="color: var(--secondary); text-decoration: none; font-weight: bold;"><i class="fas fa-arrow-left"></i> 返回控制台</a>
            </div>
            
            <table class="admin-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd;">
                        <th style="padding: 12px 15px; text-align: left; color: #333;">目標球隊</th>
                        <th style="padding: 12px 15px; text-align: left; color: #333;">姓名 / 性別</th>
                        <th style="padding: 12px 15px; text-align: left; color: #333;">年齡 / 學制</th>
                        <th style="padding: 12px 15px; text-align: left; color: #333; width: 40%;">守備與動機</th>
                        <th style="padding: 12px 15px; text-align: center; color: #333;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($applications)): ?>
                    <tr>
                        <td colspan="5" style="padding: 20px; text-align: center; color: #777;">目前沒有待審核的申請單。</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($applications as $app): ?>
                            <tr style="border-bottom: 1px solid #eee; transition: background 0.3s; vertical-align: top;">
                                <td style="padding: 12px 15px; font-weight: bold; color: var(--secondary);">
                                    <?= isset($teamLookup[$app['team_id']]) ? htmlspecialchars($teamLookup[$app['team_id']]) : '未知' ?>
                                </td>
                                <td style="padding: 12px 15px; font-weight: 500; color: var(--primary);">
                                    <?= htmlspecialchars($app['form_name']) ?> <br>
                                    <span style="font-size: 0.85em; color: #666;">(<?= htmlspecialchars($app['form_gender']) ?>)</span>
                                </td>
                                <td style="padding: 12px 15px;">
                                    <?= htmlspecialchars($app['form_age']) ?> 歲 <br>
                                    <span style="font-size: 0.85em; color: #666;"><?= htmlspecialchars($app['form_education']) ?></span>
                                </td>
                                <td style="padding: 12px 15px; width: 40%;">
                                    <?php if (!empty($app['form_position'])): ?>
                                        <div style="margin-bottom: 5px; font-size: 0.9em;"><strong>預期守位：</strong> <?= htmlspecialchars($app['form_position']) ?></div>
                                    <?php endif; ?>
                                    <div style="font-size: 0.9em; background: #f9f9f9; padding: 10px; border-radius: 6px; color: #555;">
                                        <strong>動機：</strong><br><?= nl2br(htmlspecialchars($app['form_motive'])) ?>
                                    </div>
                                </td>
                                <td style="padding: 12px 15px; text-align: center;">
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('審核完畢後移除此申請單？')">
                                        <input type="hidden" name="form_id" value="<?= $app['form_id'] ?>">
                                        <button type="submit" name="delete" style="background: #28a745; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; transition: background 0.3s; font-weight: bold;"><i class="fas fa-check"></i> 完成並移除</button>
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
