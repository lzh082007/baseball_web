<?php
require_once 'includes/header.php';
requireAuth();
$user = $_SESSION['user'];
$roleMap = ['admin' => '管理員', 'player' => '本校球員', 'ob' => '畢業學長'];
$role = isset($roleMap[$user['role']]) ? $roleMap[$user['role']] : '未知';

// 判斷目前顯示哪個分頁
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

if ($tab === 'my_stats' && isAdmin()) {
    header('Location: admin_all_players_stats.php');
    exit;
}

// 取得關聯的 player 資料（若有）
$playerData = $db->find('player', 'mId', $user['mId']);

// 取得進行中的賽事 ID，進行中賽事的數據不應呈現在前台或控制台
$pdo = $db->getPdo();
$in_progress_stmt = $pdo->prepare("SELECT game_id FROM game_live_state WHERE is_ended = 0");
$in_progress_stmt->execute();
$in_progress_games = $in_progress_stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

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
        
        <!-- ── 共用的個人資料區塊 ── -->
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

        <?php if ($tab === 'dashboard'): ?>
            <!-- ── 控制台首頁 ── -->
            <div class="section-title member-section-title" style="margin-bottom: 20px;">
                <h2>數據概覽</h2>
                <p>Personal Performance Overview</p>
            </div>
        <?php else: ?>
            <!-- ── 個人設定 ── -->
            <div class="section-title member-section-title" style="margin-bottom: 20px;">
                <h2><?= $tab === 'my_stats' ? '我的詳細數據' : '個人設定' ?></h2>
                <p><?= $tab === 'my_stats' ? 'View your detailed game stats' : 'Update your profile and player stats' ?></p>
            </div>
        <?php endif; ?>

        <div class="member-dashboard-layout">

            <!-- Side Menu -->
            <div class="member-side-menu">
                <ul>
                    <li><a href="member_dashboard.php" class="<?= $tab === 'dashboard' ? 'active' : '' ?>"><i class="fas fa-home"></i> 控制台</a></li>
                    <li><a href="member_matches.php"><i class="fas fa-baseball-ball"></i> 比賽記錄</a></li>
                    <li><a href="video_zone.php"><i class="fas fa-video"></i> 影片專區</a></li>
                    <?php if (isAdmin()): ?>
                        <li><a href="admin_all_players_stats.php"><i class="fas fa-chart-bar"></i> 所有球員數據</a></li>
                    <?php else: ?>
                        <li><a href="member_dashboard.php?tab=my_stats" class="<?= $tab === 'my_stats' ? 'active' : '' ?>"><i class="fas fa-chart-bar"></i> 我的詳細數據</a></li>
                    <?php endif; ?>
                    <li><a href="member_dashboard.php?tab=settings" class="<?= $tab === 'settings' ? 'active' : '' ?>"><i class="fas fa-user-circle"></i> 個人設定</a></li>
                </ul>
                <hr>
                <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> 登出系統</a>
            </div>

            <!-- Main Content -->
            <div>
                <?php if ($tab === 'dashboard'): ?>
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

                </div>

                <?php if (!$playerData): ?>
                <div style="background:#fff8e1; border-left:4px solid var(--secondary); padding:15px 20px; border-radius:8px; margin-top:10px; color:#555;">
                    <i class="fas fa-info-circle" style="color:var(--secondary); margin-right:8px;"></i>
                    尚未設定個人數據，前往 <a href="member_dashboard.php?tab=settings" style="color:var(--primary); font-weight:700;">個人設定</a> 填寫。
                </div>
                <?php endif; ?>

                <?php elseif ($tab === 'my_stats'): ?>
                <!-- ── 我的詳細數據 ── -->
                <div style="background:#fff; border-radius:12px; padding:28px; box-shadow:0 4px 15px rgba(0,0,0,0.06); border:1px solid #eee;">
                    <h3 style="margin-bottom:20px; color:#333; border-bottom:2px solid var(--primary); padding-bottom:10px;">
                        <i class="fas fa-chart-bar" style="margin-right:8px; color:var(--primary);"></i>我的比賽詳細數據
                    </h3>
                    
                    <?php 
                    if ($playerData) {
                        $myStats = array_filter($db->getAll('player_game_details'), function($s) use ($playerData, $in_progress_games) {
                            return $s['player_id'] == $playerData['Player_id'] && !in_array($s['game_id'], $in_progress_games);
                        });
                    } else {
                        $myStats = [];
                    }
                    ?>
                    
                    <?php if (!$playerData): ?>
                        <div style="padding:15px; background:#fff8e1; border-left:4px solid var(--secondary); border-radius:6px; color:#555;">請先在「個人設定」中建立球員數據。</div>
                    <?php elseif (empty($myStats)): ?>
                        <div style="padding:15px; background:#f9f9f9; border-radius:6px; text-align:center; color:#777;">目前無任何比賽數據記錄</div>
                    <?php else: 
                        $games = $db->getAll('game');
                        if (!function_exists('translatePosition')) {
                            function translatePosition($pos) {
                                $map = [
                                    'P' => '投手',
                                    'C' => '捕手',
                                    '1B' => '一壘手',
                                    '2B' => '二壘手',
                                    '3B' => '三壘手',
                                    'SS' => '游擊手',
                                    'LF' => '左外野手',
                                    'CF' => '中外野手',
                                    'RF' => '右外野手',
                                    'DH' => '指定打擊'
                                ];
                                $pos = strtoupper(trim($pos));
                                return isset($map[$pos]) ? $map[$pos] : $pos;
                            }
                        }
                        if (!function_exists('getGameName')) {
                            function getGameName($gid, $games) {
                                foreach($games as $g) {
                                    if($g['Game_id'] == $gid) return $g['game_date'] . ' vs ' . $g['opponent'];
                                }
                                return '未知比賽';
                            }
                        }

                        // 計算總數據
                        if (!function_exists('inningsToDecimal')) {
                            function inningsToDecimal($inn) {
                                $inn = trim((string)$inn);
                                if (empty($inn) || $inn === '0') return 0.0;
                                if (strpos($inn, ' ') !== false) {
                                    list($w, $f) = explode(' ', $inn);
                                    if (strpos($f, '/') !== false) return (float)$w + ((float)explode('/', $f)[0] / 3.0);
                                    return (float)$w;
                                } elseif (strpos($inn, '/') !== false) {
                                    return ((float)explode('/', $inn)[0] / 3.0);
                                } elseif (strpos($inn, '.') !== false) {
                                    list($w, $o) = explode('.', $inn);
                                    return (float)$w + ((float)$o / 3.0);
                                }
                                return (float)$inn;
                            }
                        }

                        // 投球局數加總函數
                        if (!function_exists('sumInnings')) {
                            function sumInnings($list) {
                                $whole = 0;
                                $outs = 0;
                                foreach ($list as $val) {
                                    $val = trim((string)$val);
                                    if (empty($val) || $val === '0') continue;
                                    
                                    if (strpos($val, ' ') !== false) {
                                        list($w, $f) = explode(' ', $val);
                                        $whole += (int)$w;
                                        if (strpos($f, '/') !== false) $outs += (int)explode('/', $f)[0];
                                    } elseif (strpos($val, '/') !== false) {
                                        $outs += (int)explode('/', $val)[0];
                                    } elseif (strpos($val, '.') !== false) {
                                        list($w, $o) = explode('.', $val);
                                        $whole += (int)$w;
                                        $outs += (int)$o;
                                    } else {
                                        $whole += (int)$val;
                                    }
                                }
                                $whole += floor($outs / 3);
                                $rem = $outs % 3;
                                
                                if ($whole == 0 && $rem > 0) return $rem . '/3';
                                if ($whole > 0 && $rem > 0) return $whole . ' ' . $rem . '/3';
                                return (string)$whole;
                            }
                        }

                        // 計算打者詳細總數據
                        $b_games = 0;
                        $b_pa = 0;
                        $b_rbi = 0;
                        $b_runs = 0;
                        $b_1b = 0;
                        $b_2b = 0;
                        $b_3b = 0;
                        $b_hr = 0;
                        $b_so = 0;
                        $b_bb = 0;
                        $b_hbp = 0;
                        $b_sf = 0;
                        $b_sac = 0;
                        $b_sb = 0;
                        $b_go = 0;
                        $b_fo = 0;

                        foreach ($myStats as $s) {
                            if (($s['pa_count'] ?? 0) > 0 || !empty($s['pa_results'])) {
                                $b_games++;
                                $b_pa += (int)($s['pa_count'] ?? 0);
                                $b_rbi += (int)($s['rbi'] ?? 0);
                                $b_runs += (int)($s['runs'] ?? 0);
                                $b_sb += (int)($s['stolen_bases'] ?? 0);

                                if (!empty($s['pa_results'])) {
                                    $results = array_map('trim', explode(',', $s['pa_results']));
                                    $has_hbp = 0; $has_sf = 0; $has_sac = 0; $has_go = 0; $has_fo = 0;
                                    foreach ($results as $res) {
                                        $res = strtoupper($res);
                                        if ($res === '1B' || $res === '一安') $b_1b++;
                                        elseif ($res === '2B' || $res === '二安') $b_2b++;
                                        elseif ($res === '3B' || $res === '三安') $b_3b++;
                                        elseif ($res === 'HR' || $res === '全壘打') $b_hr++;
                                        elseif ($res === 'K' || $res === 'SO' || $res === '三振') $b_so++;
                                        elseif ($res === 'BB' || $res === '保送' || $res === '四壞') $b_bb++;
                                        elseif ($res === 'HBP' || $res === '觸身') { $b_hbp++; $has_hbp = 1; }
                                        elseif ($res === 'SF' || $res === '高飛犧牲打') { $b_sf++; $has_sf = 1; }
                                        elseif ($res === 'SAC' || $res === '犧牲短打') { $b_sac++; $has_sac = 1; }
                                        elseif ($res === 'GO' || $res === '滾地' || $res === 'DP' || $res === 'FC') { $b_go++; $has_go = 1; }
                                        elseif ($res === 'FO' || $res === '飛球') { $b_fo++; $has_fo = 1; }
                                    }
                                    if (!$has_hbp) $b_hbp += (int)($s['hit_by_pitch'] ?? 0);
                                    if (!$has_sf) $b_sf += (int)($s['sac_fly'] ?? 0);
                                    if (!$has_sac) $b_sac += (int)($s['sac_bunt'] ?? 0);
                                    if (!$has_go) $b_go += (int)($s['go_outs'] ?? 0);
                                    if (!$has_fo) $b_fo += (int)($s['fo_outs'] ?? 0);
                                } else {
                                    $b_hbp += (int)($s['hit_by_pitch'] ?? 0);
                                    $b_sf += (int)($s['sac_fly'] ?? 0);
                                    $b_sac += (int)($s['sac_bunt'] ?? 0);
                                    $b_go += (int)($s['go_outs'] ?? 0);
                                    $b_fo += (int)($s['fo_outs'] ?? 0);
                                }
                            }
                        }

                        $b_ab = max(0, $b_pa - $b_bb - $b_hbp - $b_sf - $b_sac);
                        $b_hits = $b_1b + $b_2b + $b_3b + $b_hr;
                        $b_tb = $b_1b + 2*$b_2b + 3*$b_3b + 4*$b_hr;
                        $b_avg = $b_ab > 0 ? $b_hits / $b_ab : 0;
                        $b_obp = ($b_ab + $b_bb + $b_hbp + $b_sf) > 0 ? ($b_hits + $b_bb + $b_hbp) / ($b_ab + $b_bb + $b_hbp + $b_sf) : 0;
                        $b_slg = $b_ab > 0 ? $b_tb / $b_ab : 0;
                        $b_ops = $b_obp + $b_slg;
                        $b_go_fo = $b_fo > 0 ? $b_go / $b_fo : 0.0;
                        $b_k_rate = $b_pa > 0 ? $b_so / $b_pa : 0;
                        $b_bb_rate = $b_pa > 0 ? $b_bb / $b_pa : 0;
                        $b_bb_k = $b_so > 0 ? $b_bb / $b_so : $b_bb;
                        $b_babip = ($b_ab - $b_so - $b_hr + $b_sf) > 0 ? ($b_hits - $b_hr) / ($b_ab - $b_so - $b_hr + $b_sf) : 0;

                        // 計算聯盟平均數據以計算 OPS+
                        $allGameStats = array_filter($db->getAll('player_game_details'), function($s) use ($in_progress_games) {
                            return !in_array($s['game_id'], $in_progress_games);
                        });
                        $lg_pa = 0; $lg_ab = 0; $lg_h = 0; $lg_bb = 0; $lg_hbp = 0; $lg_sf = 0; $lg_tb = 0;
                        foreach ($allGameStats as $s) {
                            $s_pa = (int)($s['pa_count'] ?? 0);
                            $s_1b = 0; $s_2b = 0; $s_3b = 0; $s_hr = 0; $s_bb = 0; $s_hbp = 0; $s_sf = 0; $s_sac = 0;
                            if (!empty($s['pa_results'])) {
                                $results = array_map('trim', explode(',', $s['pa_results']));
                                $has_hbp = 0; $has_sf = 0; $has_sac = 0;
                                foreach ($results as $res) {
                                    $res = strtoupper($res);
                                    if ($res === '1B' || $res === '一安') $s_1b++;
                                    elseif ($res === '2B' || $res === '二安') $s_2b++;
                                    elseif ($res === '3B' || $res === '三安') $s_3b++;
                                    elseif ($res === 'HR' || $res === '全壘打') $s_hr++;
                                    elseif ($res === 'BB' || $res === '保送' || $res === '四壞') $s_bb++;
                                    elseif ($res === 'HBP' || $res === '觸身') { $s_hbp++; $has_hbp = 1; }
                                    elseif ($res === 'SF' || $res === '高飛犧牲打') { $s_sf++; $has_sf = 1; }
                                    elseif ($res === 'SAC' || $res === '犧牲短打') { $s_sac++; $has_sac = 1; }
                                }
                                if (!$has_hbp) $s_hbp += (int)($s['hit_by_pitch'] ?? 0);
                                if (!$has_sf) $s_sf += (int)($s['sac_fly'] ?? 0);
                                if (!$has_sac) $s_sac += (int)($s['sac_bunt'] ?? 0);
                            } else {
                                $s_hbp += (int)($s['hit_by_pitch'] ?? 0);
                                $s_sf += (int)($s['sac_fly'] ?? 0);
                                $s_sac += (int)($s['sac_bunt'] ?? 0);
                            }
                            $s_h = $s_1b + $s_2b + $s_3b + $s_hr;
                            $s_ab = max(0, $s_pa - $s_bb - $s_hbp - $s_sf - $s_sac);
                            $s_tb = $s_1b + 2*$s_2b + 3*$s_3b + 4*$s_hr;

                            $lg_pa += $s_pa;
                            $lg_ab += $s_ab;
                            $lg_h += $s_h;
                            $lg_bb += $s_bb;
                            $lg_hbp += $s_hbp;
                            $lg_sf += $s_sf;
                            $lg_tb += $s_tb;
                        }
                        $lg_obp = ($lg_ab + $lg_bb + $lg_hbp + $lg_sf) > 0 ? ($lg_h + $lg_bb + $lg_hbp) / ($lg_ab + $lg_bb + $lg_hbp + $lg_sf) : 0.320;
                        $lg_slg = $lg_ab > 0 ? $lg_tb / $lg_ab : 0.400;
                        $lg_ops = $lg_obp + $lg_slg;
                        if ($lg_ops == 0) $lg_ops = 0.720;

                        if ($lg_obp > 0 && $lg_slg > 0 && $b_obp > 0 && $b_slg > 0) {
                            $b_ops_plus = 100 * ($b_obp / $lg_obp + $b_slg / $lg_slg - 1);
                        } else {
                            $b_ops_plus = ($lg_ops > 0 && $b_ops > 0) ? ($b_ops / $lg_ops) * 100 : 100;
                        }
                        $b_ops_plus = max(0, round($b_ops_plus));

                        // 計算投手詳細總數據
                        $p_games = 0;
                        $p_starts = 0;
                        $p_reliefs = 0;
                        $p_cg = 0;
                        $p_sho = 0;
                        $p_wins = 0;
                        $p_losses = 0;
                        $p_saves = 0;
                        $p_blown_saves = 0;
                        $p_holds = 0;
                        $p_pitches = 0;
                        $p_strikeouts = 0;
                        $p_walks = 0;
                        $p_earned_runs = 0;
                        $p_batters_faced = 0;
                        $p_hits_allowed = 0;
                        $p_wild_pitches = 0;
                        $p_balks = 0;
                        $p_runs_allowed = 0;
                        $p_go_outs = 0;
                        $p_fo_outs = 0;
                        $p_hit_by_pitch = 0;
                        $p_hr_allowed = 0;

                        // 投球特性
                        $p_strikes = 0;
                        $p_balls = 0;
                        $p_swings = 0;
                        $p_first_pitch_swings = 0;
                        $p_whiffs = 0;
                        $p_gb_count = 0;
                        $p_ld_count = 0;
                        $p_fb_count = 0;

                        $p_innings_list = [];

                        foreach ($myStats as $s) {
                            $has_pitched = ((int)($s['pitches'] ?? 0) > 0 || (!empty($s['innings']) && $s['innings'] !== '0'));
                            if ($has_pitched) {
                                $p_games++;
                                $p_starts += (int)($s['is_start'] ?? 0);
                                $p_reliefs += (int)($s['is_relief'] ?? 0);
                                $p_cg += (int)($s['is_cg'] ?? 0);
                                $p_sho += (int)($s['is_sho'] ?? 0);
                                $p_wins += (int)($s['win'] ?? 0);
                                $p_losses += (int)($s['loss'] ?? 0);
                                $p_saves += (int)($s['save'] ?? 0);
                                $p_blown_saves += (int)($s['blown_save'] ?? 0);
                                $p_holds += (int)($s['hold'] ?? 0);
                                $p_pitches += (int)($s['pitches'] ?? 0);
                                $p_strikeouts += (int)($s['strikeouts'] ?? 0);
                                $p_walks += (int)($s['walks'] ?? 0);
                                $p_earned_runs += (int)($s['earned_runs'] ?? 0);
                                
                                $p_batters_faced += (int)($s['batters_faced'] ?? 0);
                                $p_hits_allowed += (int)($s['hits_allowed'] ?? 0);
                                $p_wild_pitches += (int)($s['wild_pitches'] ?? 0);
                                $p_balks += (int)($s['balks'] ?? 0);
                                $p_runs_allowed += (int)($s['runs_allowed'] ?? 0);
                                
                                $p_go_outs += (int)($s['p_go_outs'] ?? 0);
                                $p_fo_outs += (int)($s['p_fo_outs'] ?? 0);
                                $p_hit_by_pitch += (int)($s['p_hit_by_pitch'] ?? 0);
                                $p_hr_allowed += (int)($s['p_hr_allowed'] ?? 0);
                                
                                $p_strikes += (int)($s['strikes'] ?? 0);
                                $p_balls += (int)($s['balls'] ?? 0);
                                $p_swings += (int)($s['swings'] ?? 0);
                                $p_first_pitch_swings += (int)($s['first_pitch_swings'] ?? 0);
                                $p_whiffs += (int)($s['whiffs'] ?? 0);
                                $p_gb_count += (int)($s['gb_count'] ?? 0);
                                $p_ld_count += (int)($s['ld_count'] ?? 0);
                                $p_fb_count += (int)($s['fb_count'] ?? 0);
                                
                                if (!empty($s['innings'])) {
                                    $p_innings_list[] = $s['innings'];
                                }
                            }
                        }

                        $p_total_innings = sumInnings($p_innings_list);
                        $p_ip_dec = inningsToDecimal($p_total_innings);

                        $p_whip = $p_ip_dec > 0 ? ($p_walks + $p_hits_allowed) / $p_ip_dec : 0;
                        $p_era = $p_ip_dec > 0 ? ($p_earned_runs * 9) / $p_ip_dec : 0;
                        $p_go_fo = $p_fo_outs > 0 ? $p_go_outs / $p_fo_outs : $p_go_outs;
                        $p_k9 = $p_ip_dec > 0 ? ($p_strikeouts * 9) / $p_ip_dec : 0;
                        $p_k_rate = $p_batters_faced > 0 ? $p_strikeouts / $p_batters_faced : 0;
                        $p_bb_rate = $p_batters_faced > 0 ? $p_walks / $p_batters_faced : 0;
                        $p_bb_k = $p_strikeouts > 0 ? $p_walks / $p_strikeouts : $p_walks;

                        $p_babip_denom = ($p_batters_faced - $p_strikeouts - $p_hr_allowed - $p_walks - $p_hit_by_pitch);
                        $p_babip = $p_babip_denom > 0 ? ($p_hits_allowed - $p_hr_allowed) / $p_babip_denom : 0;

                        $p_fip = $p_ip_dec > 0 ? (13 * $p_hr_allowed + 3 * ($p_walks + $p_hit_by_pitch) - 2 * $p_strikeouts) / $p_ip_dec + 3.20 : 0;

                        // 投手聯盟 ERA 與 ERA+
                        $lg_er = 0; $lg_ip_dec = 0;
                        foreach ($allGameStats as $s) {
                            $lg_er += (int)($s['earned_runs'] ?? 0);
                            if (!empty($s['innings'])) {
                                $lg_ip_dec += inningsToDecimal($s['innings']);
                            }
                        }
                        $lg_era = $lg_ip_dec > 0 ? ($lg_er * 9) / $lg_ip_dec : 4.50;
                        $p_era_plus = $p_era > 0 ? ($lg_era / $p_era) * 100 : 100;
                        $p_era_plus = max(0, round($p_era_plus));

                        // 投球特性比率
                        $p_strike_rate = $p_pitches > 0 ? $p_strikes / $p_pitches : 0;
                        $p_ball_rate = $p_pitches > 0 ? $p_balls / $p_pitches : 0;
                        $p_swing_rate = $p_pitches > 0 ? $p_swings / $p_pitches : 0;
                        $p_first_pitch_swing_rate = $p_batters_faced > 0 ? $p_first_pitch_swings / $p_batters_faced : 0;
                        $p_whiff_rate = $p_swings > 0 ? $p_whiffs / $p_swings : 0;

                        $p_b_total = ($p_gb_count + $p_ld_count + $p_fb_count);
                        $p_gb_rate = $p_b_total > 0 ? $p_gb_count / $p_b_total : 0;
                        $p_ld_rate = $p_b_total > 0 ? $p_ld_count / $p_b_total : 0;
                        $p_fb_rate = $p_b_total > 0 ? $p_fb_count / $p_b_total : 0;
                    ?>
                        <!-- ── 切換按鈕 ── -->
                        <style>
                        .stats-table-clean {
                            width: 100%;
                            border-collapse: collapse !important;
                            table-layout: auto !important;
                            color: #000 !important;
                            margin-bottom: 15px;
                        }
                        .stats-table-clean thead {
                            background: #f5f5f5 !important;
                        }
                        .stats-table-clean th {
                            background: #f5f5f5 !important;
                            color: #000 !important;
                            font-weight: 700 !important;
                            font-size: 0.85rem !important;
                            border: 1px solid #ccc !important;
                            height: 38px !important;
                            padding: 6px 4px !important;
                            text-align: center !important;
                            white-space: nowrap !important;
                            overflow: hidden !important;
                            text-overflow: ellipsis !important;
                        }
                        .stats-table-clean td {
                            color: #000 !important;
                            font-size: 0.85rem !important;
                            border: 1px solid #ccc !important;
                            height: 38px !important;
                            padding: 6px 4px !important;
                            text-align: center !important;
                            white-space: nowrap !important;
                            overflow: hidden !important;
                            text-overflow: ellipsis !important;
                        }
                        .stats-table-clean tbody tr:hover {
                            background: #f9f9f9 !important;
                        }
                        </style>
                        <div style="display:flex; gap:10px; margin-bottom:20px;">
                            <button onclick="switchStats('batter')" id="btn-stats-batter" class="stats-toggle-btn" style="padding:8px 16px; border:none; border-radius:6px; font-weight:600; cursor:pointer; background:var(--primary); color:white; transition: 0.3s;">打者數據</button>
                            <button onclick="switchStats('pitcher')" id="btn-stats-pitcher" class="stats-toggle-btn" style="padding:8px 16px; border:none; border-radius:6px; font-weight:600; cursor:pointer; background:#f5f5f5; color:#333; transition: 0.3s;">投手數據</button>
                        </div>

                        <!-- ── 打者數據區 ── -->
                        <div id="stats-section-batter" class="stats-section" style="display:block;">
                            <!-- 打者生涯總數據 -->
                            <div style="margin-bottom:30px;">
                                <h4 style="margin: 0 0 15px 0; color:#000; font-size:1.1rem; display:flex; align-items:center; gap:8px; font-weight:bold;">
                                    <i class="fas fa-calculator" style="color:#000;"></i> 打者生涯總數據
                                </h4>
                                <div style="overflow-x:auto;">
                                    <table class="stats-table-clean" style="min-width:1700px;">
                                        <thead>
                                            <tr>
                                                <th>統計</th>
                                                <th>守備位置類別</th>
                                                <th>出賽</th>
                                                <th>打席</th>
                                                <th>打數</th>
                                                <th>安打</th>
                                                <th>一安</th>
                                                <th>二安</th>
                                                <th>三安</th>
                                                <th>全壘打</th>
                                                <th>打點</th>
                                                <th>得分</th>
                                                <th>被三振</th>
                                                <th>保送</th>
                                                <th>觸身</th>
                                                <th>短打</th>
                                                <th>犧飛</th>
                                                <th>盜壘</th>
                                                <th>打擊率</th>
                                                <th>上壘率</th>
                                                <th>長打率</th>
                                                <th>OPS</th>
                                                <th>BABIP</th>
                                                <th>K%</th>
                                                <th>BB%</th>
                                                <th>BB/K</th>
                                                <th>滾飛比</th>
                                                <th>OPS+</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td style="font-weight:bold; text-align:left; padding-left:10px;">生涯累計</td>
                                                <td><?= $playerData ? htmlspecialchars($playerData['position'] ?? '—') : '—' ?></td>
                                                <td><?= $b_games ?></td>
                                                <td><?= $b_pa ?></td>
                                                <td><?= $b_ab ?></td>
                                                <td style="font-weight:bold;"><?= $b_hits ?></td>
                                                <td><?= $b_1b ?></td>
                                                <td><?= $b_2b ?></td>
                                                <td><?= $b_3b ?></td>
                                                <td><?= $b_hr ?></td>
                                                <td><?= $b_rbi ?></td>
                                                <td><?= $b_runs ?></td>
                                                <td><?= $b_so ?></td>
                                                <td><?= $b_bb ?></td>
                                                <td><?= $b_hbp ?></td>
                                                <td><?= $b_sac ?></td>
                                                <td><?= $b_sf ?></td>
                                                <td><?= $b_sb ?></td>
                                                <td style="font-weight:bold;"><?= number_format($b_avg, 3) ?></td>
                                                <td style="font-weight:bold;"><?= number_format($b_obp, 3) ?></td>
                                                <td style="font-weight:bold;"><?= number_format($b_slg, 3) ?></td>
                                                <td style="font-weight:bold;"><?= number_format($b_ops, 3) ?></td>
                                                <td><?= number_format($b_babip, 3) ?></td>
                                                <td><?= number_format($b_k_rate * 100, 1) ?>%</td>
                                                <td><?= number_format($b_bb_rate * 100, 1) ?>%</td>
                                                <td><?= number_format($b_bb_k, 2) ?></td>
                                                <td><?= number_format($b_go_fo, 2) ?></td>
                                                <td style="font-weight:bold;"><?= $b_ops_plus ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- 打者單場明細 -->
                            <h4 style="margin: 20px 0 15px 0; color:#000; font-size:1.1rem; display:flex; align-items:center; gap:8px; font-weight:bold;">
                                <i class="fas fa-list" style="color:#000;"></i> 打者單場比賽數據明細
                            </h4>
                            <div style="overflow-x:auto;">
                                <table class="stats-table-clean" style="min-width:1040px;">
                                    <thead>
                                        <tr>
                                            <th>比賽</th>
                                            <th>守備位置</th>
                                            <th>打席數</th>
                                            <th>打點</th>
                                            <th>得分</th>
                                            <th>盜壘</th>
                                            <th>滾地出局</th>
                                            <th>高飛出局</th>
                                            <th>打席結果</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($myStats as $s): ?>
                                        <?php if (($s['pa_count'] ?? 0) > 0 || !empty($s['pa_results'])): ?>
                                        <tr>
                                            <td style="font-weight:bold; text-align:left; padding-left:10px;"><?= htmlspecialchars(getGameName($s["game_id"], $games)) ?></td>
                                            <td>
                                                <?php
                                                $lineup_stmt = $pdo->prepare("SELECT DISTINCT position FROM game_lineups WHERE game_id = ? AND player_id = ?");
                                                $lineup_stmt->execute([$s['game_id'], $playerData['Player_id']]);
                                                $positions = $lineup_stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
                                                $translated_positions = array_map('translatePosition', $positions);
                                                echo htmlspecialchars(!empty($translated_positions) ? implode(', ', $translated_positions) : '無紀錄');
                                                ?>
                                            </td>
                                            <td><?= $s['pa_count'] ?></td>
                                            <td><?= $s['rbi'] ?></td>
                                            <td><?= $s['runs'] ?></td>
                                            <td><?= $s['stolen_bases'] ?></td>
                                            <?php
                                            $s_go = (int)($s['go_outs'] ?? 0);
                                            $s_fo = (int)($s['fo_outs'] ?? 0);
                                            if (!empty($s['pa_results'])) {
                                                $res_array = array_map('trim', explode(',', $s['pa_results']));
                                                $parsed_go = 0; $parsed_fo = 0;
                                                foreach ($res_array as $r) {
                                                    $r = strtoupper($r);
                                                    if ($r === 'GO' || $r === 'DP' || $r === 'FC') $parsed_go++;
                                                    if ($r === 'FO') $parsed_fo++;
                                                }
                                                $s_go = max($s_go, $parsed_go);
                                                $s_fo = max($s_fo, $parsed_fo);
                                            }
                                            ?>
                                            <td><?= $s_go ?></td>
                                            <td><?= $s_fo ?></td>
                                            <td style="text-align:left; padding-left:10px;"><?= htmlspecialchars($s['pa_results']) ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- ── 投手數據區 ── -->
                        <div id="stats-section-pitcher" class="stats-section" style="display:none;">
                            <!-- 投手生涯總數據 -->
                            <div style="margin-bottom:30px;">
                                <h4 style="margin: 0 0 15px 0; color:#000; font-size:1.1rem; display:flex; align-items:center; gap:8px; font-weight:bold;">
                                    <i class="fas fa-calculator" style="color:#000;"></i> 投手生涯總數據
                                </h4>
                                <div style="overflow-x:auto;">
                                    <table class="stats-table-clean" style="min-width:2000px;">
                                        <thead>
                                            <tr>
                                                <th>統計</th>
                                                <th>出賽</th>
                                                <th>先發</th>
                                                <th>後援</th>
                                                <th>完投</th>
                                                <th>完封</th>
                                                <th>勝場</th>
                                                <th>敗場</th>
                                                <th>救援</th>
                                                <th>救援敗</th>
                                                <th>中繼</th>
                                                <th>局數</th>
                                                <th>面對打席</th>
                                                <th>投球數</th>
                                                <th>被安打</th>
                                                <th>被全壘打</th>
                                                <th>三振</th>
                                                <th>保送</th>
                                                <th>被觸身</th>
                                                <th>失分</th>
                                                <th>自責分</th>
                                                <th>暴投</th>
                                                <th>投手犯規</th>
                                                <th>防禦率</th>
                                                <th>WHIP</th>
                                                <th>K9</th>
                                                <th>K%</th>
                                                <th>BB%</th>
                                                <th>BB/K</th>
                                                <th>滾地</th>
                                                <th>飛球</th>
                                                <th>滾飛比</th>
                                                <th>BABIP</th>
                                                <th>FIP</th>
                                                <th>ERA+</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td style="font-weight:bold; text-align:left; padding-left:10px;">生涯累計</td>
                                                <td><?= $p_games ?></td>
                                                <td><?= $p_starts ?></td>
                                                <td><?= $p_reliefs ?></td>
                                                <td><?= $p_cg ?></td>
                                                <td><?= $p_sho ?></td>
                                                <td><?= $p_wins ?></td>
                                                <td><?= $p_losses ?></td>
                                                <td><?= $p_saves ?></td>
                                                <td><?= $p_blown_saves ?></td>
                                                <td><?= $p_holds ?></td>
                                                <td style="font-weight:bold;"><?= $p_total_innings ?></td>
                                                <td><?= $p_batters_faced ?></td>
                                                <td><?= $p_pitches ?></td>
                                                <td><?= $p_hits_allowed ?></td>
                                                <td><?= $p_hr_allowed ?></td>
                                                <td><?= $p_strikeouts ?></td>
                                                <td><?= $p_walks ?></td>
                                                <td><?= $p_hit_by_pitch ?></td>
                                                <td><?= $p_runs_allowed ?></td>
                                                <td><?= $p_earned_runs ?></td>
                                                <td><?= $p_wild_pitches ?></td>
                                                <td><?= $p_balks ?></td>
                                                <td style="font-weight:bold;"><?= number_format($p_era, 2) ?></td>
                                                <td style="font-weight:bold;"><?= number_format($p_whip, 3) ?></td>
                                                <td><?= number_format($p_k9, 2) ?></td>
                                                <td><?= number_format($p_k_rate * 100, 1) ?>%</td>
                                                <td><?= number_format($p_bb_rate * 100, 1) ?>%</td>
                                                <td><?= number_format($p_bb_k, 2) ?></td>
                                                <td><?= $p_go_outs ?></td>
                                                <td><?= $p_fo_outs ?></td>
                                                <td><?= number_format($p_go_fo, 2) ?></td>
                                                <td><?= number_format($p_babip, 3) ?></td>
                                                <td style="font-weight:bold;"><?= number_format($p_fip, 2) ?></td>
                                                <td style="font-weight:bold;"><?= $p_era_plus ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- 投球特性數據 -->
                            <div style="margin-bottom:30px;">
                                <h4 style="margin: 0 0 15px 0; color:#000; font-size:1.1rem; display:flex; align-items:center; gap:8px; font-weight:bold;">
                                    <i class="fas fa-chart-line" style="color:#000;"></i> 投球進階特性與比例 (生涯累計)
                                </h4>
                                <div style="overflow-x:auto;">
                                    <table class="stats-table-clean" style="min-width:900px;">
                                        <colgroup>
                                            <col style="width: 100px;">
                                            <col style="width: 100px;" span="8">
                                        </colgroup>
                                        <thead>
                                            <tr>
                                                <th>統計項目</th>
                                                <th>好球率</th>
                                                <th>壞球率</th>
                                                <th>揮棒率</th>
                                                <th>首球揮棒率</th>
                                                <th>揮空率 (Whiff%)</th>
                                                <th>滾地球比例</th>
                                                <th>平飛球比例</th>
                                                <th>高飛球比例</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td style="font-weight:bold; text-align:left; padding-left:10px;">特性累計</td>
                                                <td><?= number_format($p_strike_rate * 100, 1) ?>%</td>
                                                <td><?= number_format($p_ball_rate * 100, 1) ?>%</td>
                                                <td><?= number_format($p_swing_rate * 100, 1) ?>%</td>
                                                <td><?= number_format($p_first_pitch_swing_rate * 100, 1) ?>%</td>
                                                <td><?= number_format($p_whiff_rate * 100, 1) ?>%</td>
                                                <td><?= number_format($p_gb_rate * 100, 1) ?>%</td>
                                                <td><?= number_format($p_ld_rate * 100, 1) ?>%</td>
                                                <td><?= number_format($p_fb_rate * 100, 1) ?>%</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- 投手單場明細 -->
                            <h4 style="margin: 20px 0 15px 0; color:#000; font-size:1.1rem; display:flex; align-items:center; gap:8px; font-weight:bold;">
                                <i class="fas fa-list" style="color:#000;"></i> 投手單場比賽數據明細
                            </h4>
                            <div style="overflow-x:auto;">
                                <table class="stats-table-clean" style="min-width:1450px;">
                                    <thead>
                                        <tr>
                                            <th>比賽</th>
                                            <th>投球數</th>
                                            <th>局數</th>
                                            <th>面對打席</th>
                                            <th>被安打</th>
                                            <th>被全壘打</th>
                                            <th>三振</th>
                                            <th>保送</th>
                                            <th>被觸身</th>
                                            <th>責失分</th>
                                            <th>失分</th>
                                            <th>暴投</th>
                                            <th>投手犯規</th>
                                            <th>滾地</th>
                                            <th>飛球</th>
                                            <th>狀態結果</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($myStats as $s): ?>
                                        <?php if (((int)($s['pitches'] ?? 0) > 0 || (!empty($s['innings']) && $s['innings'] !== '0'))): ?>
                                        <tr>
                                            <td style="font-weight:bold; text-align:left; padding-left:10px;"><?= htmlspecialchars(getGameName($s['game_id'], $games)) ?></td>
                                            <td><?= $s['pitches'] ?></td>
                                            <td><?= formatInningsDisplay($s['innings']) ?></td>
                                            <td><?= (int)($s['batters_faced'] ?? 0) ?></td>
                                            <td><?= (int)($s['hits_allowed'] ?? 0) ?></td>
                                            <td><?= (int)($s['p_hr_allowed'] ?? 0) ?></td>
                                            <td><?= $s['strikeouts'] ?></td>
                                            <td><?= $s['walks'] ?></td>
                                            <td><?= (int)($s['p_hit_by_pitch'] ?? 0) ?></td>
                                            <td><?= $s['earned_runs'] ?></td>
                                            <td><?= $s['runs_allowed'] ?></td>
                                            <td><?= (int)($s['wild_pitches'] ?? 0) ?></td>
                                            <td><?= (int)($s['balks'] ?? 0) ?></td>
                                            <td><?= (int)($s['p_go_outs'] ?? 0) ?></td>
                                            <td><?= (int)($s['p_fo_outs'] ?? 0) ?></td>
                                            <td>
                                                <?php
                                                    $status_labels = [];
                                                    if ($s['is_start']) $status_labels[] = '先發';
                                                    if ($s['is_relief']) $status_labels[] = '後援';
                                                    if ($s['is_cg']) $status_labels[] = '完投';
                                                    if ($s['is_sho']) $status_labels[] = '完封';
                                                    if ($s['win']) $status_labels[] = '勝投';
                                                    if ($s['loss']) $status_labels[] = '敗投';
                                                    if ($s['save']) $status_labels[] = '救援';
                                                    if ($s['blown_save']) $status_labels[] = 'BS';
                                                    if ($s['hold']) $status_labels[] = '中繼';
                                                    echo htmlspecialchars(implode(', ', $status_labels));
                                                ?>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <script>
                        function switchStats(type) {
                            document.querySelectorAll('.stats-section').forEach(el => el.style.display = 'none');
                            document.querySelectorAll('.stats-toggle-btn').forEach(btn => {
                                btn.style.background = '#f5f5f5';
                                btn.style.color = '#333';
                            });
                            
                            document.getElementById('stats-section-' + type).style.display = 'block';
                            const activeBtn = document.getElementById('btn-stats-' + type);
                            activeBtn.style.background = 'var(--primary)';
                            activeBtn.style.color = 'white';
                        }
                        </script>
                    <?php endif; ?>
                </div>

                <?php else: ?>
                <!-- ── 個人設定 ── -->

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
