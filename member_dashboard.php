<?php
require_once 'includes/header.php';
requireAuth();
$user = $_SESSION['user'];
$roleMap = ['admin' => '管理員', 'player' => '本校球員', 'ob' => '畢業學長'];
$role = isset($roleMap[$user['role']]) ? $roleMap[$user['role']] : '未知';

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
            
            // 處理照片上傳 (存入 player 表)
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/players/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $filename = time() . '_' . basename($_FILES['profile_image']['name']);
                $target_path = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_path)) {
                    if ($playerData) {
                        $db->update('player', $playerData['Player_id'], ['image_path' => $target_path]);
                    } else {
                        $db->insert('player', [
                            'mId' => $user['mId'],
                            'Team_Id' => 1,
                            'Player_Name' => $newName,
                            'image_path' => $target_path
                        ]);
                    }
                }
            }

            // 更新 session
            $_SESSION['user']['name'] = $newName;
            $user['name'] = $newName;
            $msg = '基本資料與照片已更新！';
        }
        $tab = 'settings';
    }

    // 修改球員數據
    if ($_POST['action'] === 'update_stats') {
        $statsData = [
            'jersey_number'  => trim($_POST['jersey_number']),
            'position'       => isset($_POST['position']) && is_array($_POST['position']) ? implode(',', $_POST['position']) : '',
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

                <div style="display:flex; align-items:center; gap:25px; margin-bottom:30px; background:white; padding:20px; border-radius:15px; box-shadow:0 4px 15px rgba(0,0,0,0.05); border:1px solid #eee;">
                    <div style="width:100px; height:100px; border-radius:50%; overflow:hidden; border:3px solid var(--primary); flex-shrink:0;">
                        <?php $imgSrc = ($playerData && !empty($playerData['image_path'])) ? htmlspecialchars($playerData['image_path']) : 'assets/images/default-player.png'; ?>
                        <img src="<?= $imgSrc ?>" style="width:100%; height:100%; object-fit:cover;">
                    </div>
                    <div>
                        <h2 style="margin:0; color:#333; font-size:1.8rem;"><?= htmlspecialchars($user['name']) ?></h2>
                        <p style="margin:5px 0 0; color:#888; font-weight:500;"><i class="fas fa-id-badge"></i> <?= $role ?> | #<?= $playerData ? htmlspecialchars($playerData['jersey_number'] ?? '—') : '—' ?></p>
                    </div>
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
                        <form method="POST" action="member_dashboard.php?tab=settings" enctype="multipart/form-data">
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
                            <div style="margin-bottom:16px;">
                                <label style="display:block; margin-bottom:6px; font-weight:600; color:#555; font-size:0.9rem;">確認新密碼</label>
                                <input type="password" name="confirm_password"
                                    style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; box-sizing:border-box;"
                                    placeholder="再次輸入新密碼">
                            </div>
                            <div style="margin-bottom:20px;">
                                <label style="display:block; margin-bottom:6px; font-weight:600; color:#555; font-size:0.9rem;">個人照片 <span style="color:#aaa; font-weight:400;">（建議比例 1:1）</span></label>
                                <input type="file" name="profile_image" accept="image/*"
                                    style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px; box-sizing:border-box;">
                                <?php if ($playerData && !empty($playerData['image_path'])): ?>
                                    <div style="margin-top:10px; display:flex; align-items:center; gap:10px;">
                                        <img src="<?= htmlspecialchars($playerData['image_path']) ?>" style="width:50px; height:50px; border-radius:50%; object-fit:cover; border:1px solid #eee;">
                                        <span style="font-size:0.8rem; color:#888;">目前已上傳照片</span>
                                    </div>
                                <?php endif; ?>
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
                                <div style="grid-column: 1 / -1;">
                                    <label style="display:block; margin-bottom:6px; font-weight:600; color:#555; font-size:0.9rem;">守備位置 (可複選)</label>
                                    <div class="checkbox-group">
                                        <?php 
                                        $userPosArr = $playerData ? explode(',', $playerData['position']) : [];
                                        $availablePositions = ['投手', '捕手', '內野手', '外野手', '教練'];
                                        foreach ($availablePositions as $pos):
                                        ?>
                                            <label class="checkbox-item <?= in_array($pos, $userPosArr) ? 'active' : '' ?>" style="padding: 6px 12px; font-size: 0.85rem;">
                                                <input type="checkbox" name="position[]" value="<?= $pos ?>" <?= in_array($pos, $userPosArr) ? 'checked' : '' ?> onchange="this.parentElement.classList.toggle('active', this.checked)">
                                                <span><?= $pos ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
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
                    </div>

                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
