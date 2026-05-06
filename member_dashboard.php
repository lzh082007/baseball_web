<?php
require_once 'includes/header.php';
requireAuth();

$user = $_SESSION['user'];
$role = $user['role'] == 'admin' ? '管理員' : ($user['role'] == 'player' ? '本校球員' : '一般粉絲');

// 判斷目前顯示哪個分頁
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// 取得關聯的 player 資料（若有）
$playerData = $db->find('player', 'mId', $user['mId']);

$msg = '';
$msgType = 'success';

// ── 處理個人設定表單 ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // 修改基本資料（姓名、密碼）
    if ($_POST['action'] === 'update_profile') {
        $newName = trim($_POST['name']);
        $newPw   = trim($_POST['new_password']);
        $confirmPw = trim($_POST['confirm_password']);

        if (empty($newName)) {
            $msg = '姓名不能為空。'; $msgType = 'error';
        } elseif (!empty($newPw) && $newPw !== $confirmPw) {
            $msg = '兩次輸入的新密碼不一致。'; $msgType = 'error';
        } else {
            $updateData = ['name' => $newName];
            if (!empty($newPw)) {
                $updateData['password'] = password_hash($newPw, PASSWORD_DEFAULT);
            }
            $db->update('member', $user['mId'], $updateData);
            // 更新 session
            $_SESSION['user']['name'] = $newName;
            $user['name'] = $newName;
            $msg = '基本資料已更新！';
        }
        $tab = 'settings';
    }

    // 修改球員數據
    if ($_POST['action'] === 'update_stats') {
        $statsData = [
            'jersey_number'  => trim($_POST['jersey_number']),
            'position'       => trim($_POST['position']),
            'height'         => (int)$_POST['height'] ?: null,
            'weight'         => (int)$_POST['weight'] ?: null,
            'pitching_speed' => (int)$_POST['pitching_speed'] ?: null,
        ];

        if ($playerData) {
            // 已有 player 紀錄，直接更新
            $db->update('player', $playerData['Player_id'], $statsData);
            $msg = '球員數據已更新！';
        } else {
            // 尚無 player 紀錄，建立一筆
            $db->insert('player', array_merge($statsData, [
                'mId'         => $user['mId'],
                'Team_Id'     => 1,
                'Player_Name' => $user['name'],
                'image_path'  => '',
            ]));
            $msg = '球員數據已建立！';
        }
        // 重新取得最新 player 資料
        $playerData = $db->find('player', 'mId', $user['mId']);
        $tab = 'settings';
    }
}
?>

<div class="page-header">
    <h1>會員控制台</h1>
    <p>歡迎回來，<?= htmlspecialchars($user['name']) ?>。目前的權限等級：<span class="stats-primary" style="font-weight:800;"><?= $role ?></span></p>
</div>

<section>
    <div class="container">
        <div class="member-dashboard-layout">

            <!-- Side Menu -->
            <div class="member-side-menu">
                <ul>
                    <li><a href="member_dashboard.php" class="<?= $tab === 'dashboard' ? 'active' : '' ?>"><i class="fas fa-home"></i> 控制台</a></li>
                    <li><a href="matches.php"><i class="fas fa-baseball-ball"></i> 比賽記錄</a></li>
                    <li><a href="video_zone.php"><i class="fas fa-video"></i> 影片專區</a></li>
                    <li><a href="member_dashboard.php?tab=settings" class="<?= $tab === 'settings' ? 'active' : '' ?>"><i class="fas fa-user-circle"></i> 個人設定</a></li>
                </ul>
                <hr>
                <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> 登出系統</a>
            </div>

            <!-- Main Content -->
            <div>

                <?php if ($tab === 'dashboard'): ?>
                <!-- ── 控制台首頁 ── -->
                <div class="section-title member-section-title">
                    <h2>數據概覽</h2>
                    <p>Personal Performance Overview</p>
                </div>

                <div class="member-stats-grid">
                    <div class="member-stats-card">
                        <h4>背號</h4>
                        <div class="stats-value stats-primary"><?= $playerData ? htmlspecialchars($playerData['jersey_number'] ?? '—') : '—' ?></div>
                    </div>
                    <div class="member-stats-card">
                        <h4>守備位置</h4>
                        <div class="stats-value stats-dark" style="font-size:1.6rem;"><?= $playerData ? htmlspecialchars($playerData['position'] ?? '—') : '—' ?></div>
                    </div>
                    <div class="member-stats-card">
                        <h4>身高</h4>
                        <div class="stats-value stats-secondary"><?= $playerData && $playerData['height'] ? $playerData['height'] . '<small style="font-size:1rem;"> cm</small>' : '—' ?></div>
                    </div>
                    <div class="member-stats-card">
                        <h4>體重</h4>
                        <div class="stats-value stats-black"><?= $playerData && $playerData['weight'] ? $playerData['weight'] . '<small style="font-size:1rem;"> kg</small>' : '—' ?></div>
                    </div>
                    <?php if ($playerData && $playerData['pitching_speed']): ?>
                    <div class="member-stats-card">
                        <h4>球速</h4>
                        <div class="stats-value stats-primary"><?= $playerData['pitching_speed'] ?><small style="font-size:1rem;"> km/h</small></div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!$playerData): ?>
                <div style="background:#fff8e1; border-left:4px solid var(--secondary); padding:15px 20px; border-radius:8px; margin-top:10px; color:#555;">
                    <i class="fas fa-info-circle" style="color:var(--secondary); margin-right:8px;"></i>
                    尚未設定個人數據，前往 <a href="member_dashboard.php?tab=settings" style="color:var(--primary); font-weight:700;">個人設定</a> 填寫。
                </div>
                <?php endif; ?>

                <?php else: ?>
                <!-- ── 個人設定 ── -->
                <div class="section-title member-section-title">
                    <h2>個人設定</h2>
                    <p>Update your profile and player stats</p>
                </div>

                <?php if ($msg): ?>
                <div style="padding:12px 18px; border-radius:8px; margin-bottom:24px; font-weight:600;
                    background:<?= $msgType === 'error' ? '#ffebee' : '#e8f5e9' ?>;
                    color:<?= $msgType === 'error' ? '#c62828' : '#2e7d32' ?>;
                    border-left:4px solid <?= $msgType === 'error' ? 'var(--primary)' : '#43a047' ?>;">
                    <i class="fas fa-<?= $msgType === 'error' ? 'exclamation-circle' : 'check-circle' ?>" style="margin-right:8px;"></i>
                    <?= htmlspecialchars($msg) ?>
                </div>
                <?php endif; ?>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">

                    <!-- 基本資料 -->
                    <div style="background:#fff; border-radius:12px; padding:28px; box-shadow:0 4px 15px rgba(0,0,0,0.06); border:1px solid #eee;">
                        <h3 style="margin-bottom:20px; color:#333; border-bottom:2px solid var(--primary); padding-bottom:10px; display:inline-block;">
                            <i class="fas fa-id-card" style="margin-right:8px; color:var(--primary);"></i>基本資料
                        </h3>
                        <form method="POST" action="member_dashboard.php?tab=settings">
                            <input type="hidden" name="action" value="update_profile">
                            <div style="margin-bottom:16px;">
                                <label style="display:block; margin-bottom:6px; font-weight:600; color:#555; font-size:0.9rem;">帳號</label>
                                <input type="text" value="<?= htmlspecialchars($user['account']) ?>" disabled
                                    style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; background:#f5f5f5; color:#999; box-sizing:border-box;">
                            </div>
                            <div style="margin-bottom:16px;">
                                <label style="display:block; margin-bottom:6px; font-weight:600; color:#555; font-size:0.9rem;">顯示名稱</label>
                                <input type="text" name="name" required
                                    value="<?= htmlspecialchars($user['name']) ?>"
                                    style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; box-sizing:border-box;">
                            </div>
                            <div style="margin-bottom:16px;">
                                <label style="display:block; margin-bottom:6px; font-weight:600; color:#555; font-size:0.9rem;">新密碼 <span style="color:#aaa; font-weight:400;">（不修改請留空）</span></label>
                                <input type="password" name="new_password"
                                    style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; box-sizing:border-box;"
                                    placeholder="輸入新密碼">
                            </div>
                            <div style="margin-bottom:20px;">
                                <label style="display:block; margin-bottom:6px; font-weight:600; color:#555; font-size:0.9rem;">確認新密碼</label>
                                <input type="password" name="confirm_password"
                                    style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; box-sizing:border-box;"
                                    placeholder="再次輸入新密碼">
                            </div>
                            <button type="submit"
                                style="width:100%; padding:11px; background:var(--primary); color:#fff; border:none; border-radius:6px; font-weight:700; cursor:pointer; font-size:0.95rem;">
                                儲存基本資料
                            </button>
                        </form>
                    </div>

                    <!-- 球員數據 -->
                    <div style="background:#fff; border-radius:12px; padding:28px; box-shadow:0 4px 15px rgba(0,0,0,0.06); border:1px solid #eee;">
                        <h3 style="margin-bottom:20px; color:#333; border-bottom:2px solid var(--secondary); padding-bottom:10px; display:inline-block;">
                            <i class="fas fa-baseball-ball" style="margin-right:8px; color:var(--secondary);"></i>球員數據
                        </h3>
                        <?php if ($user['role'] === 'fan'): ?>
                        <div style="text-align:center; padding:40px 20px; color:#aaa;">
                            <i class="fas fa-lock" style="font-size:2.5rem; margin-bottom:15px; display:block;"></i>
                            <p>僅限球員身份可設定球員數據。</p>
                        </div>
                        <?php else: ?>
                        <form method="POST" action="member_dashboard.php?tab=settings">
                            <input type="hidden" name="action" value="update_stats">
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                                <div>
                                    <label style="display:block; margin-bottom:6px; font-weight:600; color:#555; font-size:0.9rem;">背號</label>
                                    <input type="text" name="jersey_number" maxlength="10"
                                        value="<?= $playerData ? htmlspecialchars($playerData['jersey_number'] ?? '') : '' ?>"
                                        style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; box-sizing:border-box;"
                                        placeholder="例：18">
                                </div>
                                <div>
                                    <label style="display:block; margin-bottom:6px; font-weight:600; color:#555; font-size:0.9rem;">守備位置</label>
                                    <select name="position"
                                        style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; box-sizing:border-box;">
                                        <option value="">— 請選擇 —</option>
                                        <?php
                                        $positions = ['投手','捕手','一壘手','二壘手','三壘手','游擊手','左外野手','中外野手','右外野手','內野手','外野手'];
                                        foreach ($positions as $pos):
                                            $sel = ($playerData && $playerData['position'] === $pos) ? 'selected' : '';
                                        ?>
                                        <option value="<?= $pos ?>" <?= $sel ?>><?= $pos ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                                <div>
                                    <label style="display:block; margin-bottom:6px; font-weight:600; color:#555; font-size:0.9rem;">身高 (cm)</label>
                                    <input type="number" name="height" min="100" max="250"
                                        value="<?= $playerData ? (int)$playerData['height'] : '' ?>"
                                        style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; box-sizing:border-box;"
                                        placeholder="例：178">
                                </div>
                                <div>
                                    <label style="display:block; margin-bottom:6px; font-weight:600; color:#555; font-size:0.9rem;">體重 (kg)</label>
                                    <input type="number" name="weight" min="30" max="200"
                                        value="<?= $playerData ? (int)$playerData['weight'] : '' ?>"
                                        style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; box-sizing:border-box;"
                                        placeholder="例：75">
                                </div>
                            </div>
                            <div style="margin-bottom:20px;">
                                <label style="display:block; margin-bottom:6px; font-weight:600; color:#555; font-size:0.9rem;">球速 (km/h) <span style="color:#aaa; font-weight:400;">（投手填寫）</span></label>
                                <input type="number" name="pitching_speed" min="0" max="200"
                                    value="<?= $playerData ? (int)$playerData['pitching_speed'] : '' ?>"
                                    style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; box-sizing:border-box;"
                                    placeholder="例：138">
                            </div>
                            <button type="submit"
                                style="width:100%; padding:11px; background:var(--secondary); color:#1a1a1a; border:none; border-radius:6px; font-weight:700; cursor:pointer; font-size:0.95rem;">
                                儲存球員數據
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>

                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
