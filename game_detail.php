<?php
require_once 'includes/header.php';

if (!isset($_GET['id'])) {
    header('Location: matches.php');
    exit;
}

$game_id = (int)$_GET['id'];
$game = $db->find('game', 'Game_id', $game_id);

if (!$game) {
    die("<div class='container' style='padding: 50px; text-align: center;'><h2>找不到該賽事</h2><br><a href='matches.php' class='back-btn' style='margin-bottom:0;'>返回賽事列表</a></div>");
}

$stats = [];
$can_view = isLoggedIn() && (isAdmin() || isPlayer());
$live_state = null;

if ($can_view) {
    $all_stats = $db->getAll('player_game_details');
    $stats = array_filter($all_stats, function($s) use ($game_id) {
        return $s['game_id'] == $game_id;
    });

    $pdo = $db->getPdo();
    $stmt = $pdo->prepare("SELECT * FROM game_live_state WHERE game_id = ?");
    $stmt->execute([$game_id]);
    $live_state = $stmt->fetch();

    $is_our_offense = false;
    $is_opp_offense = false;
    if ($live_state) {
        $batting_first = isset($game['batting_first']) ? $game['batting_first'] : '後攻';
        if ($batting_first === '先攻') {
            $is_our_offense = ((int)$live_state['is_top'] == 1);
        } else {
            $is_our_offense = ((int)$live_state['is_top'] == 0);
        }
        $is_opp_offense = !$is_our_offense;
    }
}

$players = $db->getAll('player');
if (!function_exists('getPlayerName')) {
    function getPlayerName($pid, $players) {
        foreach($players as $p) {
            if ($p['Player_id'] == $pid) return $p['Player_Name'];
        }
        return '未知球員';
    }
}

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

// 區分打者與投手
$hitters = [];
$pitchers = [];

foreach ($stats as $s) {
    $has_pa = ((int)($s['pa_count'] ?? 0) > 0 || !empty($s['pa_results']));
    if ($has_pa) {
        $hitters[] = $s;
    }
    
    $has_pitched = ((int)($s['pitches'] ?? 0) > 0 || (!empty($s['innings']) && $s['innings'] !== '0'));
    if ($has_pitched) {
        $pitchers[] = $s;
    }
}
?>
<style>
.stats-table-clean {
    table-layout: auto !important;
    color: #000 !important;
    margin-bottom: 15px;
}
.stats-table-clean th {
    background: #f8fafc !important;
    color: #0f172a !important;
    font-weight: 700 !important;
    font-size: 0.85rem !important;
    border: 1px solid #cbd5e1 !important;
    height: 38px !important;
    padding: 8px 10px !important;
    text-align: center !important;
    white-space: nowrap !important;
}
.stats-table-clean td {
    color: #334155 !important;
    font-size: 0.85rem !important;
    border: 1px solid #e2e8f0 !important;
    height: 38px !important;
    padding: 8px 10px !important;
    text-align: center !important;
    white-space: nowrap !important;
}
.stats-table-clean tbody tr:hover {
    background: #f1f5f9 !important;
}
@keyframes pulse {
    0% { opacity: 0.6; }
    50% { opacity: 1; }
    100% { opacity: 0.6; }
}
</style>

<div class="page-header">
    <h1>賽事詳細資訊</h1>
    <p>日期：<?= htmlspecialchars($game['game_date']) ?> | 對手：<?= htmlspecialchars($game['opponent']) ?> | 結果：<?php
        if ($live_state && (int)($live_state['is_ended'] ?? 0) === 0 && empty($game['result'])) {
            echo '<span style="background: #ef4444; color: white; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 0.85em; display: inline-block; animation: pulse 1.5s infinite; vertical-align: middle;"><i class="fas fa-broadcast-tower" style="margin-right: 4px;"></i>LIVE 即時直播中</span>';
        } else {
            echo htmlspecialchars($game['result'] ?: '未開始');
        }
    ?></p>
</div>

<section>
    <div class="container">
        <div style="margin-bottom: 25px;">
            <a href="matches.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> 返回賽事列表
            </a>
        </div>

        <?php if (!$can_view): ?>
            <div style="background: #fff; padding: 60px 40px; text-align: center; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <i class="fas fa-lock" style="font-size: 3.5rem; color: #ccc; margin-bottom: 20px;"></i>
                <h3 style="color: #333; margin-bottom: 10px;">進階數據已隱藏</h3>
                <p style="color: #777; font-size: 1.1rem; margin-bottom: 20px;">此賽事的詳細球員攻守數據僅供管理員與球員查閱。<br>請先使用相應權限之帳號登入系統。</p>
                <?php if (!isLoggedIn()): ?>
                    <a href="login.php" class="btn-primary" style="display: inline-block; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 1.1rem;"><i class="fas fa-sign-in-alt"></i> 前往登入</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php if ($live_state): ?>
                <!-- ── 賽事即時計分板 ── -->
                <div class="live-scoreboard-card" style="background: #0f172a; color: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); margin-bottom: 30px;">
                    <h4 style="margin-top:0; border-bottom: 1px solid #1e293b; padding-bottom: 10px; color:#94a3b8; font-size:0.95rem; font-weight:700; margin-bottom: 15px;">
                        <i class="fas fa-desktop" style="color:var(--secondary); margin-right:8px;"></i> 賽事即時狀態計分板
                    </h4>
                    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 20px;">
                        <!-- Left: Teams and Scores (R-H-E Table Style) -->
                        <div style="flex: 2; min-width: 320px;">
                            <table style="width: 100%; border-collapse: collapse; text-align: center; color: white; font-family: 'Outfit', sans-serif;">
                                <thead>
                                    <tr style="border-bottom: 1px solid #334155; font-size: 0.8rem; color: #94a3b8; text-transform: uppercase;">
                                        <th style="text-align: left; padding: 8px 12px; font-weight: 600;">球隊</th>
                                        <th style="padding: 8px; font-weight: 600; width: 50px;">R</th>
                                        <th style="padding: 8px; font-weight: 600; width: 50px;">H</th>
                                        <th style="padding: 8px; font-weight: 600; width: 50px;">E</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr style="font-size: 1.1rem; border-bottom: 1px solid #1e293b;">
                                        <td style="text-align: left; padding: 12px; font-weight: 700; color: var(--secondary);">
                                            NUTC (我方) 
                                            <?php if ($is_our_offense): ?>
                                                <span style="display: inline-block; width: 8px; height: 8px; background: #ef4444; border-radius: 50%; margin-left: 6px;" title="進攻中"></span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-size: 1.5rem; font-weight: 900; color: white; padding: 8px;"><?= $live_state['our_score'] ?></td>
                                        <td style="font-weight: 700; color: #cbd5e1; padding: 8px;"><?= $live_state['our_hits'] ?></td>
                                        <td style="font-weight: 700; color: #cbd5e1; padding: 8px;"><?= $live_state['our_errors'] ?></td>
                                    </tr>
                                    <tr style="font-size: 1.1rem;">
                                        <td style="text-align: left; padding: 12px; font-weight: 700; color: #94a3b8;">
                                            <?= htmlspecialchars($game['opponent']) ?> (對手)
                                            <?php if ($is_opp_offense): ?>
                                                <span style="display: inline-block; width: 8px; height: 8px; background: #ef4444; border-radius: 50%; margin-left: 6px;" title="進攻中"></span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-size: 1.5rem; font-weight: 900; color: white; padding: 8px;"><?= $live_state['opponent_score'] ?></td>
                                        <td style="font-weight: 700; color: #cbd5e1; padding: 8px;"><?= $live_state['opponent_hits'] ?></td>
                                        <td style="font-weight: 700; color: #cbd5e1; padding: 8px;"><?= $live_state['opponent_errors'] ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Center: Inning Indicator & B/S/O Lights -->
                        <div style="flex: 1.2; min-width: 220px; display: flex; flex-direction: column; align-items: center; justify-content: center; border-left: 1px solid #1e293b; border-right: 1px solid #1e293b; padding: 0 15px; box-sizing: border-box;">
                            <div style="font-size: 1.6rem; font-weight: 900; color: #f8fafc; margin-bottom: 12px; letter-spacing: 1px;">
                                <?= $live_state['inning'] ?> 局<?= $live_state['is_top'] ? '上' : '下' ?>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 8px; width: 100%; max-width: 160px;">
                                <!-- Balls -->
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <span style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; width: 20px;">B</span>
                                    <div style="display: flex; gap: 6px;">
                                        <?php for($i=1; $i<=3; $i++): 
                                            $active = ($live_state['balls'] >= $i);
                                            $color = $active ? '#10b981' : '#334155';
                                            $shadow = $active ? 'box-shadow: 0 0 8px #10b981;' : '';
                                        ?>
                                            <span style="width: 12px; height: 12px; border-radius: 50%; background: <?= $color ?>; <?= $shadow ?> display: inline-block;"></span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <!-- Strikes -->
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <span style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; width: 20px;">S</span>
                                    <div style="display: flex; gap: 6px;">
                                        <?php for($i=1; $i<=2; $i++): 
                                            $active = ($live_state['strikes'] >= $i);
                                            $color = $active ? '#f59e0b' : '#334155';
                                            $shadow = $active ? 'box-shadow: 0 0 8px #f59e0b;' : '';
                                        ?>
                                            <span style="width: 12px; height: 12px; border-radius: 50%; background: <?= $color ?>; <?= $shadow ?> display: inline-block;"></span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <!-- Outs -->
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <span style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; width: 20px;">O</span>
                                    <div style="display: flex; gap: 6px;">
                                        <?php for($i=1; $i<=2; $i++): 
                                            $active = ($live_state['outs'] >= $i);
                                            $color = $active ? '#ef4444' : '#334155';
                                            $shadow = $active ? 'box-shadow: 0 0 8px #ef4444;' : '';
                                        ?>
                                            <span style="width: 12px; height: 12px; border-radius: 50%; background: <?= $color ?>; <?= $shadow ?> display: inline-block;"></span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Diamond Base Runner Visual -->
                        <div style="flex: 1; min-width: 180px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                            <div style="position: relative; width: 100px; height: 100px; margin-bottom: 8px;">
                                <!-- Second Base -->
                                <div style="position: absolute; top: 0; left: 42px; width: 16px; height: 16px; border: 2px solid #64748b; background: <?= $live_state['runner_second'] ? '#fbbf24' : '#1e293b' ?>; transform: rotate(45deg); <?= $live_state['runner_second'] ? 'box-shadow: 0 0 8px #fbbf24;' : '' ?>"></div>
                                <!-- Third Base -->
                                <div style="position: absolute; top: 42px; left: 0; width: 16px; height: 16px; border: 2px solid #64748b; background: <?= $live_state['runner_third'] ? '#fbbf24' : '#1e293b' ?>; transform: rotate(45deg); <?= $live_state['runner_third'] ? 'box-shadow: 0 0 8px #fbbf24;' : '' ?>"></div>
                                <!-- First Base -->
                                <div style="position: absolute; top: 42px; right: 0; width: 16px; height: 16px; border: 2px solid #64748b; background: <?= $live_state['runner_first'] ? '#fbbf24' : '#1e293b' ?>; transform: rotate(45deg); <?= $live_state['runner_first'] ? 'box-shadow: 0 0 8px #fbbf24;' : '' ?>"></div>
                                <!-- Home Plate -->
                                <div style="position: absolute; bottom: 0; left: 44px; width: 12px; height: 12px; border: 2px solid #475569; background: #cbd5e1; transform: rotate(45deg); opacity: 0.6;"></div>
                            </div>
                            <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;">壘包狀態</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php 
            $is_in_progress = ($live_state && (int)$live_state['is_ended'] == 0 && empty($game['result']));
            if ($is_in_progress): ?>
                <div style="background: white; border-radius: 12px; padding: 40px 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 25px; text-align: center;">
                    <i class="fas fa-baseball-bat-ball fa-spin" style="font-size: 3rem; color: var(--primary); margin-bottom: 15px;"></i>
                    <h3 style="color: #1e293b; font-weight: 800; margin-bottom: 8px;">⚾ 比賽仍在進行中</h3>
                    <p style="color: #64748b; font-size: 0.95rem; margin: 0;">詳細數據將於賽事結束後更新，敬請期待！</p>
                </div>
            <?php else: ?>
                <div style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 25px;">
                    <!-- 1. 打擊成績 -->
                    <div style="margin-bottom: 35px;">
                    <h3 style="margin-bottom: 15px; color: #333; border-bottom: 2px solid var(--primary); padding-bottom: 10px; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-baseball-bat-ball" style="color: var(--primary); font-size: 1.2rem;"></i> 本場打擊成績 (Hitting Box Score)
                    </h3>
                    <div style="overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <table class="stats-table-clean" style="min-width: 1300px; width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th>球員</th>
                                    <th>打席</th>
                                    <th>打數</th>
                                    <th>安打</th>
                                    <th>一安</th>
                                    <th>二安</th>
                                    <th>三安</th>
                                    <th>全壘打</th>
                                    <th>打點</th>
                                    <th>得分</th>
                                    <th>三振</th>
                                    <th>保送</th>
                                    <th>觸身</th>
                                    <th>短打</th>
                                    <th>犧飛</th>
                                    <th>盜壘</th>
                                    <th>打擊率</th>
                                    <th>上壘率</th>
                                    <th>長打率</th>
                                    <th>OPS</th>
                                    <th style="text-align: left; min-width: 250px;">打席明細</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($hitters)): ?>
                                    <tr>
                                        <td colspan="21" style="padding: 20px; text-align: center; color: #64748b;">本場比賽尚無打擊數據。</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($hitters as $s): ?>
                                        <?php
                                        $pa_count = (int)($s['pa_count'] ?? 0);
                                        $pa_results = trim((string)$s['pa_results']);
                                        
                                        $rbi = (int)($s['rbi'] ?? 0);
                                        $runs = (int)($s['runs'] ?? 0);
                                        $stolen_bases = (int)($s['stolen_bases'] ?? 0);
                                        $go_outs = (int)($s['go_outs'] ?? 0);
                                        $fo_outs = (int)($s['fo_outs'] ?? 0);
                                        $hit_by_pitch = (int)($s['hit_by_pitch'] ?? 0);
                                        $sac_fly = (int)($s['sac_fly'] ?? 0);
                                        $sac_bunt = (int)($s['sac_bunt'] ?? 0);
                                        
                                        $h_1b = 0; $h_2b = 0; $h_3b = 0; $h_hr = 0; $h_so = 0; $h_bb = 0;
                                        
                                        if (!empty($pa_results)) {
                                            $results = array_map('trim', explode(',', $pa_results));
                                            $parsed_go = 0; $parsed_fo = 0; $parsed_hbp = 0; $parsed_sf = 0; $parsed_sac = 0;
                                            foreach ($results as $res) {
                                                $res = strtoupper($res);
                                                if ($res === '1B' || $res === '一安') $h_1b++;
                                                elseif ($res === '2B' || $res === '二安') $h_2b++;
                                                elseif ($res === '3B' || $res === '三安') $h_3b++;
                                                elseif ($res === 'HR' || $res === '全壘打') $h_hr++;
                                                elseif ($res === 'K' || $res === 'SO' || $res === '三振') $h_so++;
                                                elseif ($res === 'BB' || $res === '保送' || $res === '四壞') $h_bb++;
                                                elseif ($res === 'HBP' || $res === '觸身') $parsed_hbp++;
                                                elseif ($res === 'SF' || $res === '高飛犧牲打') $parsed_sf++;
                                                elseif ($res === 'SAC' || $res === '犧牲短打') $parsed_sac++;
                                                elseif ($res === 'GO' || $res === '滾地' || $res === 'DP' || $res === 'FC') $parsed_go++;
                                                elseif ($res === 'FO' || $res === '飛球') $parsed_fo++;
                                            }
                                            $go_outs = max($go_outs, $parsed_go);
                                            $fo_outs = max($fo_outs, $parsed_fo);
                                            $hit_by_pitch = max($hit_by_pitch, $parsed_hbp);
                                            $sac_fly = max($sac_fly, $parsed_sf);
                                            $sac_bunt = max($sac_bunt, $parsed_sac);
                                        }
                                        
                                        $ab = max(0, $pa_count - $h_bb - $hit_by_pitch - $sac_fly - $sac_bunt);
                                        $hits = $h_1b + $h_2b + $h_3b + $h_hr;
                                        $avg = $ab > 0 ? $hits / $ab : 0.0;
                                        $obp = ($ab + $h_bb + $hit_by_pitch + $sac_fly) > 0 ? ($hits + $h_bb + $hit_by_pitch) / ($ab + $h_bb + $hit_by_pitch + $sac_fly) : 0.0;
                                        $tb = $h_1b + 2*$h_2b + 3*$h_3b + 4*$h_hr;
                                        $slg = $ab > 0 ? $tb / $ab : 0.0;
                                        $ops = $obp + $slg;
                                        ?>
                                        <tr>
                                            <td style="font-weight: 600; color: #0f172a;"><?= htmlspecialchars(getPlayerName($s['player_id'], $players)) ?></td>
                                            <td><?= $pa_count ?></td>
                                            <td><?= $ab ?></td>
                                            <td style="font-weight: 700; color: #1e3a8a;"><?= $hits ?></td>
                                            <td><?= $h_1b ?></td>
                                            <td><?= $h_2b ?></td>
                                            <td><?= $h_3b ?></td>
                                            <td><?= $h_hr ?></td>
                                            <td><?= $rbi ?></td>
                                            <td><?= $runs ?></td>
                                            <td><?= $h_so ?></td>
                                            <td><?= $h_bb ?></td>
                                            <td><?= $hit_by_pitch ?></td>
                                            <td><?= $sac_bunt ?></td>
                                            <td><?= $sac_fly ?></td>
                                            <td><?= $stolen_bases ?></td>
                                            <td style="font-weight: 700;"><?= number_format($avg, 3) ?></td>
                                            <td style="font-weight: 700;"><?= number_format($obp, 3) ?></td>
                                            <td style="font-weight: 700;"><?= number_format($slg, 3) ?></td>
                                            <td style="font-weight: 900; color: #0284c7;"><?= number_format($ops, 3) ?></td>
                                            <td style="text-align: left; font-size: 0.8rem; color: #475569;"><?= htmlspecialchars($pa_results) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. 投球成績 -->
                <div>
                    <h3 style="margin-bottom: 15px; color: #333; border-bottom: 2px solid var(--secondary); padding-bottom: 10px; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-baseball-ball" style="color: var(--secondary); font-size: 1.2rem;"></i> 本場投球成績 (Pitching Box Score)
                    </h3>
                    <div style="overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <table class="stats-table-clean" style="min-width: 1200px; width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th>球員</th>
                                    <th>局數</th>
                                    <th>投球數</th>
                                    <th>面對打席</th>
                                    <th>被安打</th>
                                    <th>被全壘打</th>
                                    <th>三振</th>
                                    <th>保送</th>
                                    <th>被觸身</th>
                                    <th>責失分</th>
                                    <th>失分</th>
                                    <th>暴投</th>
                                    <th>犯規</th>
                                    <th>滾地</th>
                                    <th>飛球</th>
                                    <th>單場ERA</th>
                                    <th>單場WHIP</th>
                                    <th style="min-width: 150px;">出賽狀態</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pitchers)): ?>
                                    <tr>
                                        <td colspan="18" style="padding: 20px; text-align: center; color: #64748b;">本場比賽尚無投球數據。</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pitchers as $s): ?>
                                        <?php
                                        $ip_dec = inningsToDecimal($s['innings']);
                                        $game_era = $ip_dec > 0 ? ($s['earned_runs'] * 9) / $ip_dec : 0.0;
                                        $game_whip = $ip_dec > 0 ? ($s['walks'] + $s['hits_allowed']) / $ip_dec : 0.0;
                                        
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
                                        $status_str = implode(', ', $status_labels);
                                        ?>
                                        <tr>
                                            <td style="font-weight: 600; color: #0f172a;"><?= htmlspecialchars(getPlayerName($s['player_id'], $players)) ?></td>
                                            <td style="font-weight: 700;"><?= formatInningsDisplay($s['innings']) ?></td>
                                            <td><?= $s['pitches'] ?></td>
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
                                            <td style="font-weight: 700;"><?= $ip_dec > 0 ? number_format($game_era, 2) : '0.00' ?></td>
                                            <td style="font-weight: 700;"><?= $ip_dec > 0 ? number_format($game_whip, 2) : '0.00' ?></td>
                                            <td style="font-size: 0.85rem; font-weight: 600; color: #4f46e5;"><?= htmlspecialchars($status_str ?: '—') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Play-by-Play Log Card -->
        <?php
        $logs_stmt = $pdo->prepare("SELECT * FROM game_live_logs WHERE game_id = ? ORDER BY id DESC");
        $logs_stmt->execute([$game_id]);
        $game_logs = $logs_stmt->fetchAll();
        ?>
        <div style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; margin-top: 25px;">
            <h3 style="margin-top:0; color:#1e293b; font-size:1.15rem; font-weight:800; border-bottom:2px solid #f1f5f9; padding-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
                <span><i class="fas fa-history" style="color:var(--primary); margin-right:6px;"></i> 本場比賽打席紀錄歷史 (Play-by-Play Timeline)</span>
                <span style="font-size:0.75rem; color:#64748b; font-weight:normal;">最新紀錄顯示在最上方</span>
            </h3>
            <div style="max-height: 400px; overflow-y: auto; padding-right:5px; margin-top:15px;">
                <?php if (empty($game_logs)): ?>
                    <div style="text-align:center; color:#94a3b8; padding:20px; font-size:0.9rem;">本場比賽暫無打席敘述紀錄。</div>
                <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <?php foreach ($game_logs as $log): ?>
                            <div style="display:flex; align-items:flex-start; background:#f8fafc; border:1px solid #e2e8f0; padding:12px 16px; border-radius:8px; gap:12px; transition: 0.2s;">
                                <div style="display:flex; flex-direction:column; align-items:center; background:#1e293b; color:white; padding:6px 10px; border-radius:6px; min-width:65px; text-align:center;">
                                    <span style="font-size:0.85rem; font-weight:800;"><?= $log['inning'] ?>局<?= $log['is_top'] ? '上' : '下' ?></span>
                                    <span style="font-size:0.7rem; opacity:0.85; margin-top:2px;"><?= $log['outs'] ?>出局</span>
                                </div>
                                <div style="flex:1;">
                                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                                        <span style="background:<?= $log['type'] === 'offense' ? '#3b82f6' : '#64748b' ?>; color:white; padding:2px 8px; border-radius:4px; font-size:0.7rem; font-weight:800;">
                                            <?= $log['type'] === 'offense' ? '我方進攻' : '對手進攻' ?>
                                        </span>
                                        <span style="background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:4px; font-size:0.7rem; font-weight:800; font-family:'Outfit',sans-serif;">
                                            <?= htmlspecialchars($log['pa_result']) ?>
                                        </span>
                                        <span style="font-size:0.7rem; color:#94a3b8;"><?= $log['created_at'] ?></span>
                                    </div>
                                    <div style="font-size:0.9rem; color:#334155; font-weight:600; line-height:1.4;">
                                        <?= htmlspecialchars($log['description'] ?: '無打席描述') ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
