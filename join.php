<?php
include 'includes/header.php';
require_once 'includes/db_handler.php';
$joinDB = new JsonDB('join_team');
$teamDB = new JsonDB('teams');
$formDB = new JsonDB('forms');

$recruitment = $joinDB->getAll();
$teams = $teamDB->getAll();

$success_msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_form'])) {
    $newId = $formDB->getLastId('form_id') + 1;
    $formData = [
        'form_id' => $newId,
        'team_id' => $_POST['team_id'],
        'form_name' => $_POST['form_name'],
        'form_gender' => $_POST['form_gender'],
        'form_age' => $_POST['form_age'],
        'form_education' => $_POST['form_education'],
        'form_level' => $_POST['form_level'],
        'form_position' => $_POST['form_position'],
        'form_motive' => $_POST['form_motive'],
        'form_date' => date('Y-m-d H:i:s')
    ];
    $formDB->insert($formData);
    $success_msg = "報名成功！我們將會盡快與您聯繫。";
}
?>

<div style="padding: 6rem 2rem; max-width: 1200px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 6rem;">
        <h1 style="font-size: 3.5rem; margin-bottom: 1rem;">加入我們</h1>
        <p style="color: var(--text-secondary); font-size: 1.25rem;">揮灑青春，成就卓越。我們期待你的身影出現在球場上！</p>
    </div>

    <!-- Recruitment Info Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 3rem; margin-bottom: 8rem;">
        <?php foreach($recruitment as $info): 
            $team = $teamDB->getById('team_id', $info['team_id']);
        ?>
            <div class="glass-morphism" style="padding: 3rem; position: relative; overflow: hidden;">
                <div style="position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: var(--primary-color);"></div>
                <h2 style="font-size: 2rem; margin-bottom: 1.5rem;"><?= htmlspecialchars($team['team_name']) ?></h2>
                <p style="line-height: 1.8; color: #CBD5E1; margin-bottom: 2rem;"><?= htmlspecialchars($info['intro']) ?></p>
                <div style="background: rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem;">
                    <h4 style="font-size: 0.875rem; color: var(--primary-color); text-transform: uppercase; margin-bottom: 0.5rem; letter-spacing: 1px;">招生資訊</h4>
                    <p style="color: white;"><?= htmlspecialchars($info['recruitment_info']) ?></p>
                </div>
                <div style="display: flex; align-items: center; gap: 0.75rem; color: var(--text-secondary); font-size: 0.875rem;">
                    <i class="fas fa-info-circle"></i> <?= htmlspecialchars($info['contact_info']) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Application Form -->
    <div class="glass-morphism" style="padding: 4rem; max-width: 800px; margin: 0 auto; background: rgba(15, 23, 42, 0.4);">
        <h2 style="font-size: 2.25rem; text-align: center; margin-bottom: 3rem;">報名申請</h2>
        
        <?php if($success_msg): ?>
            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10B981; color: #10B981; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; text-align: center; font-weight: 600;">
                <i class="fas fa-check-circle"></i> <?= $success_msg ?>
            </div>
        <?php endif; ?>

        <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div style="grid-column: span 2;">
                <label style="display: block; margin-bottom: 0.75rem; font-weight: 600;">報名隊伍</label>
                <select name="team_id" required style="width: 100%; padding: 1rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--glass-border); border-radius: 8px; color: white;">
                    <?php foreach($teams as $team): ?>
                        <option value="<?= $team['team_id'] ?>"><?= htmlspecialchars($team['team_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.75rem; font-weight: 600;">姓名</label>
                <input type="text" name="form_name" required style="width: 100%; padding: 1rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--glass-border); border-radius: 8px; color: white;" placeholder="請輸入姓名">
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.75rem; font-weight: 600;">性別</label>
                <select name="form_gender" style="width: 100%; padding: 1rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--glass-border); border-radius: 8px; color: white;">
                    <option value="男">男</option>
                    <option value="女">女</option>
                </select>
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.75rem; font-weight: 600;">年齡</label>
                <input type="number" name="form_age" required style="width: 100%; padding: 1rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--glass-border); border-radius: 8px; color: white;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.75rem; font-weight: 600;">學制</label>
                <select name="form_education" style="width: 100%; padding: 1rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--glass-border); border-radius: 8px; color: white;">
                    <option value="五專">五專</option>
                    <option value="四技">四技</option>
                    <option value="二技">二技</option>
                    <option value="碩博">碩博</option>
                </select>
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.75rem; font-weight: 600;">棒球程度</label>
                <select name="form_level" style="width: 100%; padding: 1rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--glass-border); border-radius: 8px; color: white;">
                    <option value="新手">新手</option>
                    <option value="乙組">乙組</option>
                    <option value="甲組">甲組</option>
                </select>
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.75rem; font-weight: 600;">期望守備位置</label>
                <input type="text" name="form_position" style="width: 100%; padding: 1rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--glass-border); border-radius: 8px; color: white;" placeholder="例：投手 / 捕手">
            </div>
            <div style="grid-column: span 2;">
                <label style="display: block; margin-bottom: 0.75rem; font-weight: 600;">加入動機</label>
                <textarea name="form_motive" rows="5" style="width: 100%; padding: 1rem; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--glass-border); border-radius: 8px; color: white; resize: none;" placeholder="告訴我們你為什麼想加入球隊？"></textarea>
            </div>
            <div style="grid-column: span 2; display: flex; justify-content: center; margin-top: 1rem;">
                <button type="submit" name="submit_form" class="btn-login" style="padding: 1.25rem 4rem; font-size: 1.125rem; border: none; cursor: pointer; display: flex; align-items: center; gap: 0.75rem;">
                    正式送出申請 <i class="fas fa-paper-plane" style="font-size: 0.875rem;"></i>
                </button>
            </div>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
