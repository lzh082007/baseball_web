<?php
require_once 'includes/header.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

// 只允許 player 存取，其他跳轉 matches.php
if ($user['role'] !== 'player') {
    header("Location: matches.php");
    exit;
}

$roleMap = ['admin' => '管理員', 'player' => '本校球員', 'ob' => '畢業學長'];
$role = isset($roleMap[$user['role']]) ? $roleMap[$user['role']] : '未知';
$playerData = $db->find('player', 'mId', $user['mId']);

// 取得我的比賽紀錄
$myGames = [];
if ($playerData) {
    $myStats = array_filter($db->getAll('player_game_details'), function ($s) use ($playerData) {
        return $s['player_id'] == $playerData['Player_id'];
    });

    $myGameIds = array_unique(array_column($myStats, 'game_id'));

    $allGames = $db->getAll('game');
    $myGames = array_filter($allGames, function ($g) use ($myGameIds) {
        return in_array($g['Game_id'], $myGameIds);
    });

    // 依日期排序（近到遠）
    usort($myGames, function ($a, $b) {
        return strtotime($b['game_date']) - strtotime($a['game_date']);
    });
}
?>

<link rel="stylesheet" href="assets/css/member_dashboard.css">

<div class="page-header">
    <h1>會員控制台</h1>
    <p>歡迎回來，<?= htmlspecialchars($user['name']) ?>。目前的權限等級：<span class="stats-primary"
            style="font-weight:800;"><?= $role ?></span></p>
</div>

<section>
    <div class="container">
        <!-- ── 共用的個人資料區塊 ── -->
        <div
            style="display:flex; align-items:center; gap:25px; margin-bottom:30px; background:white; padding:20px; border-radius:15px; box-shadow:0 4px 15px rgba(0,0,0,0.05); border:1px solid #eee;">
            <div
                style="width:100px; height:100px; border-radius:50%; overflow:hidden; border:3px solid var(--primary); flex-shrink:0;">
                <?php $imgSrc = ($playerData && !empty($playerData['image_path'])) ? htmlspecialchars($playerData['image_path']) : 'assets/images/default-player.png'; ?>
                <img src="<?= $imgSrc ?>" style="width:100%; height:100%; object-fit:cover;">
            </div>
            <div>
                <h2 style="margin:0; color:#333; font-size:1.8rem;"><?= htmlspecialchars($user['name']) ?></h2>
                <p style="margin:5px 0 0; color:#888; font-weight:500;"><i class="fas fa-id-badge"></i> <?= $role ?> |
                    #<?= $playerData ? htmlspecialchars($playerData['jersey_number'] ?? '—') : '—' ?></p>
            </div>
        </div>

        <div class="section-title member-section-title" style="margin-bottom: 20px;">
            <h2>我的比賽紀錄</h2>
            <p>My Game Records</p>
        </div>

        <div class="member-dashboard-layout">

            <!-- Side Menu (同 member_dashboard.php) -->
            <div class="member-side-menu">
                <ul>
                    <li><a href="member_dashboard.php"><i class="fas fa-home"></i> 控制台</a></li>
                    <li><a href="member_matches.php" class="active"><i class="fas fa-baseball-ball"></i> 比賽記錄</a></li>
                    <li><a href="video_zone.php"><i class="fas fa-video"></i> 影片專區</a></li>
                    <li><a href="member_dashboard.php?tab=my_stats"><i class="fas fa-chart-bar"></i> 我的詳細數據</a></li>
                    <li><a href="member_dashboard.php?tab=settings"><i class="fas fa-user-circle"></i> 個人設定</a></li>
                </ul>
                <hr>
                <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> 登出系統</a>
            </div>

            <!-- Main Content -->
            <div>
                <?php if (!$playerData): ?>
                    <div
                        style="background: white; border-radius: 12px; padding: 40px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                        <i class="fas fa-exclamation-triangle"
                            style="font-size: 3rem; color: #ffc107; margin-bottom: 20px;"></i>
                        <h3 style="color: #333; margin-bottom: 10px;">尚未綁定球員資料</h3>
                        <p style="color: #777;">您需要先在「個人設定」中建立並綁定球員資料，才能查看參與過的比賽紀錄。</p>
                        <a href="member_dashboard.php?tab=settings" class="btn-primary"
                            style="display:inline-block; margin-top:15px; padding:10px 20px; border-radius:6px; text-decoration:none;">前往個人設定</a>
                    </div>
                <?php else: ?>
                    <div class="admin-list-card"
                        style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow-x: auto;">
                        <h3
                            style="margin-bottom: 20px; color: #333; border-bottom: 2px solid var(--secondary); padding-bottom: 10px; display: inline-block;">
                            <i class="fas fa-baseball-ball" style="color:var(--secondary); margin-right:8px;"></i>出賽紀錄
                        </h3>

                        <table class="admin-table" style="width: 100%; border-collapse: collapse; min-width:600px;">
                            <thead>
                                <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd;">
                                    <th style="padding: 12px 15px; text-align: left; color: #333;">比賽日期</th>
                                    <th style="padding: 12px 15px; text-align: left; color: #333;">交戰對手</th>
                                    <th style="padding: 12px 15px; text-align: left; color: #333;">地點</th>
                                    <th style="padding: 12px 15px; text-align: left; color: #333;">賽果</th>
                                    <th style="padding: 12px 15px; text-align: center; color: #333;">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($myGames)): ?>
                                    <tr>
                                        <td colspan="5" style="padding: 20px; text-align: center; color: #777;">
                                            您目前還沒有任何被記錄的出賽場次。</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($myGames as $g): ?>
                                        <tr
                                            style="border-bottom: 1px solid #eee; transition: background 0.3s; vertical-align: middle;">
                                            <td style="padding: 12px 15px; font-weight: 500; color: #333;">
                                                <?= htmlspecialchars($g['game_date']) ?></td>
                                            <td style="padding: 12px 15px; font-weight: bold; color: var(--primary);">vs
                                                <?= htmlspecialchars($g['opponent']) ?></td>
                                            <td style="padding: 12px 15px; color: #555;"><i class="fas fa-map-marker-alt"
                                                    style="color:#ccc;"></i> <?= htmlspecialchars($g['location']) ?></td>
                                            <td style="padding: 12px 15px;">
                                                <?php
                                                $isWin = strpos($g['result'], '勝') !== false;
                                                $badgeBg = $isWin ? 'var(--primary)' : (strpos($g['result'], '敗') !== false ? '#dc3545' : '#6c757d');
                                                ?>
                                                <span
                                                    style="background: <?= $badgeBg ?>; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.85em; font-weight: bold;">
                                                    <?= htmlspecialchars($g['result']) ?>
                                                </span>
                                            </td>
                                            <td style="padding: 12px 15px; text-align: center; white-space: nowrap;">
                                                <a href="game_detail.php?id=<?= $g['Game_id'] ?>"
                                                    style="background: var(--secondary); color: #111; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.9em; display: inline-block;">
                                                    <i class="fas fa-search"></i> 查看賽事
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>