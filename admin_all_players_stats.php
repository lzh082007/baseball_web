<?php
require_once 'includes/header.php';
requireAdmin(); // Restriction: admin only

$user = $_SESSION['user'];
$roleMap = ['admin' => '管理員', 'player' => '本校球員', 'ob' => '畢業學長'];
$role = isset($roleMap[$user['role']]) ? $roleMap[$user['role']] : '未知';

// Get associated player data if any
$playerData = $db->find('player', 'mId', $user['mId']);

// Get all players
$players = $db->getAll('player');
usort($players, function($a, $b) {
    return (int)($a['jersey_number'] ?? 999) - (int)($b['jersey_number'] ?? 999);
});

// Get in-progress games
$pdo = $db->getPdo();
$in_progress_stmt = $pdo->prepare("SELECT game_id FROM game_live_state WHERE is_ended = 0");
$in_progress_stmt->execute();
$in_progress_games = $in_progress_stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

// Get all games
$games = $db->getAll('game');

if (!function_exists('getGameName')) {
    function getGameName($gid, $games) {
        foreach($games as $g) {
            if($g['Game_id'] == $gid) return $g['game_date'] . ' vs ' . $g['opponent'];
        }
        return '未知比賽';
    }
}

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

// Fetch all game details
$allDetails = $db->getAll('player_game_details');
$finishedDetails = array_filter($allDetails, function($s) use ($in_progress_games) {
    return !in_array($s['game_id'], $in_progress_games);
});

// Calculate league averages for advanced stats (OPS+, ERA+)
$lg_pa = 0; $lg_ab = 0; $lg_h = 0; $lg_bb = 0; $lg_hbp = 0; $lg_sf = 0; $lg_tb = 0;
foreach ($finishedDetails as $s) {
    $s_pa = (int)($s['pa_count'] ?? 0);
    if ($s_pa > 0 || !empty($s['pa_results'])) {
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
}
$lg_obp = ($lg_ab + $lg_bb + $lg_hbp + $lg_sf) > 0 ? ($lg_h + $lg_bb + $lg_hbp) / ($lg_ab + $lg_bb + $lg_hbp + $lg_sf) : 0.320;
$lg_slg = $lg_ab > 0 ? $lg_tb / $lg_ab : 0.400;
$lg_ops = $lg_obp + $lg_slg;
if ($lg_ops == 0) $lg_ops = 0.720;

$lg_er = 0; $lg_ip_dec = 0;
foreach ($finishedDetails as $s) {
    $has_pitched = ((int)($s['pitches'] ?? 0) > 0 || (!empty($s['innings']) && $s['innings'] !== '0'));
    if ($has_pitched) {
        $lg_er += (int)($s['earned_runs'] ?? 0);
        if (!empty($s['innings'])) {
            $lg_ip_dec += inningsToDecimal($s['innings']);
        }
    }
}
$lg_era = $lg_ip_dec > 0 ? ($lg_er * 9) / $lg_ip_dec : 4.50;

// Aggregate player stats
$playerBatting = [];
$playerPitching = [];

foreach ($players as $p) {
    $pId = $p['Player_id'];
    
    // Filter details for this player
    $pDetails = array_filter($finishedDetails, function($d) use ($pId) {
        return $d['player_id'] == $pId;
    });
    
    // --- Batting calculations ---
    $b_games = 0;
    $b_pa = 0;
    $b_rbi = 0;
    $b_runs = 0;
    $b_1b = 0; $b_2b = 0; $b_3b = 0; $b_hr = 0;
    $b_so = 0; $b_bb = 0; $b_hbp = 0; $b_sf = 0; $b_sac = 0; $b_sb = 0;
    $b_go = 0; $b_fo = 0;
    
    foreach ($pDetails as $s) {
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
    
    if ($lg_obp > 0 && $lg_slg > 0 && $b_obp > 0 && $b_slg > 0) {
        $b_ops_plus = 100 * ($b_obp / $lg_obp + $b_slg / $lg_slg - 1);
    } else {
        $b_ops_plus = ($lg_ops > 0 && $b_ops > 0) ? ($b_ops / $lg_ops) * 100 : 100;
    }
    $b_ops_plus = max(0, round($b_ops_plus));
    
    $playerBatting[$pId] = [
        'player' => $p,
        'g' => $b_games,
        'pa' => $b_pa,
        'ab' => $b_ab,
        'hits' => $b_hits,
        '1b' => $b_1b,
        '2b' => $b_2b,
        '3b' => $b_3b,
        'hr' => $b_hr,
        'rbi' => $b_rbi,
        'runs' => $b_runs,
        'so' => $b_so,
        'bb' => $b_bb,
        'hbp' => $b_hbp,
        'sac' => $b_sac,
        'sf' => $b_sf,
        'sb' => $b_sb,
        'avg' => $b_avg,
        'obp' => $b_obp,
        'slg' => $b_slg,
        'ops' => $b_ops,
        'babip' => $b_babip,
        'k_rate' => $b_k_rate,
        'bb_rate' => $b_bb_rate,
        'bb_k' => $b_bb_k,
        'go_fo' => $b_go_fo,
        'ops_plus' => $b_ops_plus
    ];
    
    // --- Pitching calculations ---
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
    
    $p_strikes = 0;
    $p_balls = 0;
    $p_swings = 0;
    $p_first_pitch_swings = 0;
    $p_whiffs = 0;
    $p_gb_count = 0;
    $p_ld_count = 0;
    $p_fb_count = 0;
    
    $p_innings_list = [];
    
    foreach ($pDetails as $s) {
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
    
    $p_era_plus = $p_era > 0 ? ($lg_era / $p_era) * 100 : 100;
    $p_era_plus = max(0, round($p_era_plus));
    
    $p_strike_rate = $p_pitches > 0 ? $p_strikes / $p_pitches : 0;
    $p_ball_rate = $p_pitches > 0 ? $p_balls / $p_pitches : 0;
    $p_swing_rate = $p_pitches > 0 ? $p_swings / $p_pitches : 0;
    $p_first_pitch_swing_rate = $p_batters_faced > 0 ? $p_first_pitch_swings / $p_batters_faced : 0;
    $p_whiff_rate = $p_swings > 0 ? $p_whiffs / $p_swings : 0;
    
    $p_b_total = ($p_gb_count + $p_ld_count + $p_fb_count);
    $p_gb_rate = $p_b_total > 0 ? $p_gb_count / $p_b_total : 0;
    $p_ld_rate = $p_b_total > 0 ? $p_ld_count / $p_b_total : 0;
    $p_fb_rate = $p_b_total > 0 ? $p_fb_count / $p_b_total : 0;
    
    $playerPitching[$pId] = [
        'player' => $p,
        'g' => $p_games,
        'starts' => $p_starts,
        'reliefs' => $p_reliefs,
        'cg' => $p_cg,
        'sho' => $p_sho,
        'wins' => $p_wins,
        'losses' => $p_losses,
        'saves' => $p_saves,
        'blown_saves' => $p_blown_saves,
        'holds' => $p_holds,
        'total_innings' => $p_total_innings,
        'batters_faced' => $p_batters_faced,
        'pitches' => $p_pitches,
        'hits_allowed' => $p_hits_allowed,
        'hr_allowed' => $p_hr_allowed,
        'strikeouts' => $p_strikeouts,
        'walks' => $p_walks,
        'hit_by_pitch' => $p_hit_by_pitch,
        'runs_allowed' => $p_runs_allowed,
        'earned_runs' => $p_earned_runs,
        'wild_pitches' => $p_wild_pitches,
        'balks' => $p_balks,
        'era' => $p_era,
        'whip' => $p_whip,
        'k9' => $p_k9,
        'k_rate' => $p_k_rate,
        'bb_rate' => $p_bb_rate,
        'bb_k' => $p_bb_k,
        'go_outs' => $p_go_outs,
        'fo_outs' => $p_fo_outs,
        'go_fo' => $p_go_fo,
        'babip' => $p_babip,
        'fip' => $p_fip,
        'era_plus' => $p_era_plus,
        'strike_rate' => $p_strike_rate,
        'ball_rate' => $p_ball_rate,
        'swing_rate' => $p_swing_rate,
        'first_pitch_swing_rate' => $p_first_pitch_swing_rate,
        'whiff_rate' => $p_whiff_rate,
        'gb_rate' => $p_gb_rate,
        'ld_rate' => $p_ld_rate,
        'fb_rate' => $p_fb_rate
    ];
}
?>

<link rel="stylesheet" href="assets/css/member_dashboard.css">
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
    position: sticky;
    top: 0;
    z-index: 10;
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
.player-row-link {
    cursor: pointer;
    color: var(--primary) !important;
    text-decoration: underline !important;
    font-weight: bold;
}
</style>

<div class="page-header">
    <h1>所有球員數據</h1>
    <p>歡迎回來，<?= htmlspecialchars($user['name']) ?>。目前的權限等級：<span class="stats-primary" style="font-weight:800;"><?= $role ?></span></p>
</div>

<section>
    <div class="container">
        
        <!-- 共用的個人資料區塊 -->
        <div style="display:flex; align-items:center; gap:25px; margin-bottom:30px; background:white; padding:20px; border-radius:15px; box-shadow:0 4px 15px rgba(0,0,0,0.05); border:1px solid #eee;">
            <div style="width:100px; height:100px; border-radius:50%; overflow:hidden; border:3px solid var(--primary); flex-shrink:0;">
                <?php $imgSrc = ($playerData && !empty($playerData['image_path'])) ? htmlspecialchars($playerData['image_path']) : 'assets/images/default-player.png'; ?>
                <img src="<?= $imgSrc ?>" style="width:100%; height:100%; object-fit:cover;">
            </div>
            <div>
                <h2 style="margin:0; color:#333; font-size:1.8rem;"><?= htmlspecialchars($user['name']) ?></h2>
                <p style="margin:5px 0 0; color:#888; font-weight:500;"><i class="fas fa-id-badge"></i> <?= $role ?> (所有球員數據檢視模式)</p>
            </div>
        </div>

        <div class="section-title member-section-title" style="margin-bottom: 20px;">
            <h2>全隊數據概覽</h2>
            <p>Team Roster Performance Summary</p>
        </div>

        <div class="member-dashboard-layout">

            <!-- Side Menu -->
            <div class="member-side-menu">
                <ul>
                    <li><a href="member_dashboard.php"><i class="fas fa-home"></i> 控制台</a></li>
                    <li><a href="member_matches.php"><i class="fas fa-baseball-ball"></i> 比賽記錄</a></li>
                    <li><a href="video_zone.php"><i class="fas fa-video"></i> 影片專區</a></li>
                    <li><a href="admin_all_players_stats.php" class="active"><i class="fas fa-chart-bar"></i> 所有球員數據</a></li>
                    <li><a href="member_dashboard.php?tab=settings"><i class="fas fa-user-circle"></i> 個人設定</a></li>
                </ul>
                <hr>
                <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> 登出系統</a>
            </div>

            <!-- Main Content -->
            <div>
                
                <div style="background:#fff; border-radius:12px; padding:28px; box-shadow:0 4px 15px rgba(0,0,0,0.06); border:1px solid #eee; height: 365px; box-sizing: border-box;">
                    <h3 style="margin-bottom:20px; color:#333; border-bottom:2px solid var(--primary); padding-bottom:10px;">
                        <i class="fas fa-users" style="margin-right:8px; color:var(--primary);"></i>球員累計數據
                    </h3>
                    
                    <!-- 切換按鈕 -->
                    <div style="display:flex; gap:10px; margin-bottom:20px;">
                        <button onclick="switchStats('batter')" id="btn-stats-batter" class="stats-toggle-btn" style="padding:8px 16px; border:none; border-radius:6px; font-weight:600; cursor:pointer; background:var(--primary); color:white; transition: 0.3s;">所有打者累計</button>
                        <button onclick="switchStats('pitcher')" id="btn-stats-pitcher" class="stats-toggle-btn" style="padding:8px 16px; border:none; border-radius:6px; font-weight:600; cursor:pointer; background:#f5f5f5; color:#333; transition: 0.3s;">所有投手累計</button>
                        <button onclick="switchStats('characteristic')" id="btn-stats-characteristic" class="stats-toggle-btn" style="padding:8px 16px; border:none; border-radius:6px; font-weight:600; cursor:pointer; background:#f5f5f5; color:#333; transition: 0.3s;">投手特性比例</button>
                    </div>

                    <!-- ── 所有打者數據區 ── -->
                    <div id="stats-section-batter" class="stats-section" style="display:block;">
                        <div style="height: 180px; overflow: auto; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 10px;">
                            <table class="stats-table-clean" style="min-width:1750px; margin-bottom: 0;">
                                <thead>
                                    <tr>
                                        <th style="width: 100px;">姓名</th>
                                        <th>背號</th>
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
                                    <?php foreach ($playerBatting as $pId => $bs): ?>
                                    <tr>
                                        <td class="player-row-link" onclick="selectPlayer(<?= $pId ?>)">
                                            <?= htmlspecialchars($bs['player']['Player_Name']) ?>
                                        </td>
                                        <td>#<?= htmlspecialchars($bs['player']['jersey_number'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($bs['player']['position'] ?? '—') ?></td>
                                        <td><?= $bs['g'] ?></td>
                                        <td><?= $bs['pa'] ?></td>
                                        <td><?= $bs['ab'] ?></td>
                                        <td style="font-weight:bold;"><?= $bs['hits'] ?></td>
                                        <td><?= $bs['1b'] ?></td>
                                        <td><?= $bs['2b'] ?></td>
                                        <td><?= $bs['3b'] ?></td>
                                        <td><?= $bs['hr'] ?></td>
                                        <td><?= $bs['rbi'] ?></td>
                                        <td><?= $bs['runs'] ?></td>
                                        <td><?= $bs['so'] ?></td>
                                        <td><?= $bs['bb'] ?></td>
                                        <td><?= $bs['hbp'] ?></td>
                                        <td><?= $bs['sac'] ?></td>
                                        <td><?= $bs['sf'] ?></td>
                                        <td><?= $bs['sb'] ?></td>
                                        <td style="font-weight:bold;"><?= number_format($bs['avg'], 3) ?></td>
                                        <td style="font-weight:bold;"><?= number_format($bs['obp'], 3) ?></td>
                                        <td style="font-weight:bold;"><?= number_format($bs['slg'], 3) ?></td>
                                        <td style="font-weight:bold;"><?= number_format($bs['ops'], 3) ?></td>
                                        <td><?= number_format($bs['babip'], 3) ?></td>
                                        <td><?= number_format($bs['k_rate'] * 100, 1) ?>%</td>
                                        <td><?= number_format($bs['bb_rate'] * 100, 1) ?>%</td>
                                        <td><?= number_format($bs['bb_k'], 2) ?></td>
                                        <td><?= number_format($bs['go_fo'], 2) ?></td>
                                        <td style="font-weight:bold;"><?= $bs['ops_plus'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ── 所有投手數據區 ── -->
                    <div id="stats-section-pitcher" class="stats-section" style="display:none;">
                        <div style="height: 180px; overflow: auto; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 10px;">
                            <table class="stats-table-clean" style="min-width:2050px; margin-bottom: 0;">
                                <thead>
                                    <tr>
                                        <th style="width: 100px;">姓名</th>
                                        <th>背號</th>
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
                                        <th>失分</th>
                                        <th>自責分</th>
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
                                    <?php foreach ($playerPitching as $pId => $ps): ?>
                                    <?php if ($ps['g'] > 0 || $ps['pitches'] > 0): ?>
                                    <tr>
                                        <td class="player-row-link" onclick="selectPlayer(<?= $pId ?>)">
                                            <?= htmlspecialchars($ps['player']['Player_Name']) ?>
                                        </td>
                                        <td>#<?= htmlspecialchars($ps['player']['jersey_number'] ?? '—') ?></td>
                                        <td><?= $ps['g'] ?></td>
                                        <td><?= $ps['starts'] ?></td>
                                        <td><?= $ps['reliefs'] ?></td>
                                        <td><?= $ps['cg'] ?></td>
                                        <td><?= $ps['sho'] ?></td>
                                        <td><?= $ps['wins'] ?></td>
                                        <td><?= $ps['losses'] ?></td>
                                        <td><?= $ps['saves'] ?></td>
                                        <td><?= $ps['blown_saves'] ?></td>
                                        <td><?= $ps['holds'] ?></td>
                                        <td style="font-weight:bold;"><?= $ps['total_innings'] ?></td>
                                        <td><?= $ps['batters_faced'] ?></td>
                                        <td><?= $ps['pitches'] ?></td>
                                        <td><?= $ps['hits_allowed'] ?></td>
                                        <td><?= $ps['hr_allowed'] ?></td>
                                        <td><?= $ps['strikeouts'] ?></td>
                                        <td><?= $ps['walks'] ?></td>
                                        <td><?= $ps['runs_allowed'] ?></td>
                                        <td><?= $ps['earned_runs'] ?></td>
                                        <td style="font-weight:bold;"><?= number_format($ps['era'], 2) ?></td>
                                        <td style="font-weight:bold;"><?= number_format($ps['whip'], 3) ?></td>
                                        <td><?= number_format($ps['k9'], 2) ?></td>
                                        <td><?= number_format($ps['k_rate'] * 100, 1) ?>%</td>
                                        <td><?= number_format($ps['bb_rate'] * 100, 1) ?>%</td>
                                        <td><?= number_format($ps['bb_k'], 2) ?></td>
                                        <td><?= $ps['go_outs'] ?></td>
                                        <td><?= $ps['fo_outs'] ?></td>
                                        <td><?= number_format($ps['go_fo'], 2) ?></td>
                                        <td><?= number_format($ps['babip'], 3) ?></td>
                                        <td style="font-weight:bold;"><?= number_format($ps['fip'], 2) ?></td>
                                        <td style="font-weight:bold;"><?= $ps['era_plus'] ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ── 所有投手特性數據區 ── -->
                    <div id="stats-section-characteristic" class="stats-section" style="display:none;">
                        <div style="height: 180px; overflow: auto; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 10px;">
                            <table class="stats-table-clean" style="min-width:1050px; margin-bottom: 0;">
                                <thead>
                                    <tr>
                                        <th style="width: 100px;">姓名</th>
                                        <th>背號</th>
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
                                    <?php foreach ($playerPitching as $pId => $ps): ?>
                                    <?php if ($ps['g'] > 0 || $ps['pitches'] > 0): ?>
                                    <tr>
                                        <td class="player-row-link" onclick="selectPlayer(<?= $pId ?>)">
                                            <?= htmlspecialchars($ps['player']['Player_Name']) ?>
                                        </td>
                                        <td>#<?= htmlspecialchars($ps['player']['jersey_number'] ?? '—') ?></td>
                                        <td><?= number_format($ps['strike_rate'] * 100, 1) ?>%</td>
                                        <td><?= number_format($ps['ball_rate'] * 100, 1) ?>%</td>
                                        <td><?= number_format($ps['swing_rate'] * 100, 1) ?>%</td>
                                        <td><?= number_format($ps['first_pitch_swing_rate'] * 100, 1) ?>%</td>
                                        <td><?= number_format($ps['whiff_rate'] * 100, 1) ?>%</td>
                                        <td><?= number_format($ps['gb_rate'] * 100, 1) ?>%</td>
                                        <td><?= number_format($ps['ld_rate'] * 100, 1) ?>%</td>
                                        <td><?= number_format($ps['fb_rate'] * 100, 1) ?>%</td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- ── 單一球員單場明細數據區 ── -->
                <div style="background:#fff; border-radius:12px; padding:28px; box-shadow:0 4px 15px rgba(0,0,0,0.06); border:1px solid #eee; margin-top:30px;">
                    <h3 style="margin-bottom:20px; color:#333; border-bottom:2px solid var(--secondary); padding-bottom:10px;">
                        <i class="fas fa-baseball-ball" style="margin-right:8px; color:var(--secondary);"></i>球員單場詳細比賽明細
                    </h3>
                    
                    <div style="margin-bottom:20px; display:flex; align-items:center; gap:12px;">
                        <label for="player-select" style="font-weight:600; color:#555;">選擇要檢視的球員：</label>
                        <select id="player-select" onchange="showPlayerDetails(this.value)" style="padding:10px 16px; border:1px solid #ddd; border-radius:6px; background:#fff; font-size:0.95rem; min-width:220px; box-shadow:0 2px 5px rgba(0,0,0,0.05); font-weight: 600;">
                            <?php foreach ($players as $p): ?>
                            <option value="<?= $p['Player_id'] ?>">#<?= htmlspecialchars($p['jersey_number'] ?? '—') ?> - <?= htmlspecialchars($p['Player_Name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php foreach ($players as $p): ?>
                    <?php
                    $pId = $p['Player_id'];
                    $pDetails = array_filter($allDetails, function($d) use ($pId, $in_progress_games) {
                        return $d['player_id'] == $pId && !in_array($d['game_id'], $in_progress_games);
                    });
                    
                    $hasBattingRecord = false;
                    $hasPitchingRecord = false;
                    foreach ($pDetails as $s) {
                        if (($s['pa_count'] ?? 0) > 0 || !empty($s['pa_results'])) {
                            $hasBattingRecord = true;
                        }
                        if ((int)($s['pitches'] ?? 0) > 0 || (!empty($s['innings']) && $s['innings'] !== '0')) {
                            $hasPitchingRecord = true;
                        }
                    }
                    ?>
                    <div id="details-player-<?= $pId ?>" class="player-details-section" style="display:none;">
                        
                        <div style="display:flex; align-items:center; gap:15px; margin-bottom:20px; padding:10px; background:#f9f9f9; border-radius:8px; border-left:4px solid var(--secondary);">
                            <div style="font-weight:bold; font-size:1.1rem; color:#333;">
                                #<?= htmlspecialchars($p['jersey_number'] ?? '—') ?> <?= htmlspecialchars($p['Player_Name']) ?> 的單場紀錄明細
                            </div>
                        </div>

                        <!-- 單場打擊明細 -->
                        <div style="margin-bottom:25px;">
                            <h4 style="margin: 0 0 12px 0; color:#000; font-size:1rem; font-weight:bold; display:flex; align-items:center; gap:6px;">
                                <i class="fas fa-edit" style="color:var(--primary); font-size:0.95rem;"></i> 打者單場打擊紀錄
                            </h4>
                            <?php if (!$hasBattingRecord): ?>
                                <div style="padding:15px; background:#f9f9f9; border-radius:6px; color:#777; font-size:0.9rem;">該球員在已結束的比賽中無打擊紀錄。</div>
                            <?php else: ?>
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
                                            <?php foreach ($pDetails as $s): ?>
                                            <?php if (($s['pa_count'] ?? 0) > 0 || !empty($s['pa_results'])): ?>
                                            <tr>
                                                <td style="font-weight:bold; text-align:left; padding-left:10px;"><?= htmlspecialchars(getGameName($s['game_id'], $games)) ?></td>
                                                <td>
                                                    <?php
                                                    $lineup_stmt = $pdo->prepare("SELECT DISTINCT position FROM game_lineups WHERE game_id = ? AND player_id = ?");
                                                    $lineup_stmt->execute([$s['game_id'], $pId]);
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
                                                <td style="text-align:left; padding-left:10px;"><?= htmlspecialchars($s['pa_results'] ?? '') ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- ── 單場投球明細 ── -->
                        <div>
                            <h4 style="margin: 0 0 12px 0; color:#000; font-size:1rem; font-weight:bold; display:flex; align-items:center; gap:6px;">
                                <i class="fas fa-baseball-ball" style="color:var(--secondary); font-size:0.95rem;"></i> 投手單場投球紀錄
                            </h4>
                            <?php if (!$hasPitchingRecord): ?>
                                <div style="padding:15px; background:#f9f9f9; border-radius:6px; color:#777; font-size:0.9rem;">該球員在已結束的比賽中無投球紀錄。</div>
                            <?php else: ?>
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
                                            <?php foreach ($pDetails as $s): ?>
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
                            <?php endif; ?>
                        </div>

                    </div>
                    <?php endforeach; ?>

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

                function showPlayerDetails(playerId) {
                    document.querySelectorAll('.player-details-section').forEach(el => el.style.display = 'none');
                    const targetSection = document.getElementById('details-player-' + playerId);
                    if (targetSection) {
                        targetSection.style.display = 'block';
                    }
                }

                function selectPlayer(playerId) {
                    const select = document.getElementById('player-select');
                    if (select) {
                        select.value = playerId;
                        showPlayerDetails(playerId);
                        select.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }

                document.addEventListener('DOMContentLoaded', function() {
                    const select = document.getElementById('player-select');
                    if (select && select.value) {
                        showPlayerDetails(select.value);
                    }
                });
                </script>

            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
