<?php
require_once 'includes/header.php';
requireAdmin();

if (!isset($_GET['game_id'])) {
    header("Location: admin_game_stats.php");
    exit;
}

$game_id = (int)$_GET['game_id'];
$game = $db->find('game', 'Game_id', $game_id);
if (!$game) {
    die("找不到比賽記錄");
}

$pdo = $db->getPdo();

// ── 1. 初始化資料庫資料表 ──
$pdo->exec("CREATE TABLE IF NOT EXISTS `game_lineups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `batting_order` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `position` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `sub_seq` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`game_id`) REFERENCES `game` (`Game_id`) ON DELETE CASCADE,
  FOREIGN KEY (`player_id`) REFERENCES `player` (`Player_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$pdo->exec("CREATE TABLE IF NOT EXISTS `game_pitchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `pitcher_seq` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`game_id`) REFERENCES `game` (`Game_id`) ON DELETE CASCADE,
  FOREIGN KEY (`player_id`) REFERENCES `player` (`Player_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$pdo->exec("CREATE TABLE IF NOT EXISTS `game_live_state` (
  `game_id` int(11) NOT NULL,
  `current_batter_order` int(11) NOT NULL DEFAULT 1,
  `our_score` int(11) NOT NULL DEFAULT 0,
  `opponent_score` int(11) NOT NULL DEFAULT 0,
  `inning` int(11) NOT NULL DEFAULT 1,
  `is_top` tinyint(4) NOT NULL DEFAULT 1,
  `outs` int(11) NOT NULL DEFAULT 0,
  `balls` int(11) NOT NULL DEFAULT 0,
  `strikes` int(11) NOT NULL DEFAULT 0,
  `our_hits` int(11) NOT NULL DEFAULT 0,
  `opponent_hits` int(11) NOT NULL DEFAULT 0,
  `our_errors` int(11) NOT NULL DEFAULT 0,
  `opponent_errors` int(11) NOT NULL DEFAULT 0,
  `runner_first` tinyint(4) NOT NULL DEFAULT 0,
  `runner_second` tinyint(4) NOT NULL DEFAULT 0,
  `runner_third` tinyint(4) NOT NULL DEFAULT 0,
  `runner_first_id` int(11) DEFAULT NULL,
  `runner_second_id` int(11) DEFAULT NULL,
  `runner_third_id` int(11) DEFAULT NULL,
  `is_ended` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`game_id`),
  FOREIGN KEY (`game_id`) REFERENCES `game` (`Game_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$pdo->exec("CREATE TABLE IF NOT EXISTS `game_live_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `inning` int(11) NOT NULL,
  `is_top` tinyint(4) NOT NULL,
  `outs` int(11) NOT NULL,
  `pa_result` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`game_id`) REFERENCES `game` (`Game_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$alter_cols = [
    'our_score' => 'int(11) NOT NULL DEFAULT 0',
    'opponent_score' => 'int(11) NOT NULL DEFAULT 0',
    'inning' => 'int(11) NOT NULL DEFAULT 1',
    'is_top' => 'tinyint(4) NOT NULL DEFAULT 1',
    'outs' => 'int(11) NOT NULL DEFAULT 0',
    'balls' => 'int(11) NOT NULL DEFAULT 0',
    'strikes' => 'int(11) NOT NULL DEFAULT 0',
    'our_hits' => 'int(11) NOT NULL DEFAULT 0',
    'opponent_hits' => 'int(11) NOT NULL DEFAULT 0',
    'our_errors' => 'int(11) NOT NULL DEFAULT 0',
    'opponent_errors' => 'int(11) NOT NULL DEFAULT 0',
    'runner_first' => 'tinyint(4) NOT NULL DEFAULT 0',
    'runner_second' => 'tinyint(4) NOT NULL DEFAULT 0',
    'runner_third' => 'tinyint(4) NOT NULL DEFAULT 0',
    'runner_first_id' => 'int(11) DEFAULT NULL',
    'runner_second_id' => 'int(11) DEFAULT NULL',
    'runner_third_id' => 'int(11) DEFAULT NULL',
    'is_ended' => 'tinyint(4) NOT NULL DEFAULT 0',
];
// 先讀取現有欄位，避免每次都執行 ALTER TABLE 產生資料庫 Exception
$existing_cols = [];
try {
    $q = $pdo->query("DESCRIBE `game_live_state`");
    if ($q) {
        $existing_cols = $q->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (Exception $e) {
    // 忽略
}

foreach ($alter_cols as $col => $def) {
    if (!in_array($col, $existing_cols)) {
        try {
            $pdo->exec("ALTER TABLE `game_live_state` ADD COLUMN `$col` $def");
        } catch (Exception $e) {
            // Column already exists, ignore
        }
    }
}


// 局數加總/扣除輔助函數
function addInning($current, $diff) {
    $currentWhole = 0;
    $currentOuts = 0;
    $current = trim((string)$current);
    
    if (!empty($current) && $current !== '0') {
        if (strpos($current, ' ') !== false) {
            // format: "1 1/3" or "1 2/3"
            list($w, $f) = explode(' ', $current);
            $currentWhole = (int)$w;
            if (strpos($f, '/') !== false) {
                $currentOuts = (int)explode('/', $f)[0];
            }
        } elseif (strpos($current, '/') !== false) {
            // format: "1/3" or "2/3"
            $currentOuts = (int)explode('/', $current)[0];
        } elseif (strpos($current, '.') !== false) {
            // legacy decimal: "1.1" or "1.2"
            list($w, $o) = explode('.', $current);
            $currentWhole = (int)$w;
            $currentOuts = (int)$o;
        } else {
            // format: "1" or "2"
            $currentWhole = (int)$current;
        }
    }
    
    $totalOuts = ($currentWhole * 3) + $currentOuts;
    
    if ($diff == 0.1 || $diff == '0.1') {
        $diffOuts = 1;
    } elseif ($diff == -0.1 || $diff == '-0.1') {
        $diffOuts = -1;
    } else {
        $diffOuts = (int)round((float)$diff * 3);
    }
    
    $finalOuts = max(0, $totalOuts + $diffOuts);
    
    $finalWhole = floor($finalOuts / 3);
    $finalRem = $finalOuts % 3;
    
    if ($finalWhole == 0 && $finalRem > 0) {
        return $finalRem . '/3';
    } elseif ($finalWhole > 0 && $finalRem > 0) {
        return $finalWhole . ' ' . $finalRem . '/3';
    }
    
    return (string)$finalWhole;
}

$msg = '';
$msgType = 'success';

// ── 2. POST 請求處理 ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // 檢查比賽是否已宣告結束
    $check_state_stmt = $pdo->prepare("SELECT is_ended FROM game_live_state WHERE game_id = ?");
    $check_state_stmt->execute([$game_id]);
    $check_state_ended = $check_state_stmt->fetchColumn();
    $is_game_ended = ($check_state_ended !== false) ? (int)$check_state_ended : 0;
    
    $modification_actions = [
        'record_pa', 'update_scoreboard', 'base_event', 
        'update_pitcher', 'pitcher_quick', 'pinch_batter', 
        'pinch_pitcher', 'change_position'
    ];
    if ($is_game_ended == 1 && in_array($action, $modification_actions)) {
        $msg = "錯誤：此比賽已宣告結束，無法再修改任何數據！";
        $msgType = "error";
    }
    else if ($action === 'setup_lineup') {
        $batters = $_POST['batters']; 
        $positions = $_POST['positions']; 
        $starting_pitcher_id = (int)$_POST['starting_pitcher'];
        
        $unique_batters = array_unique(array_filter($batters));
        
        // 檢查是否有重覆選擇球員在 9 個棒次中
        $has_duplicate_batter = (count($unique_batters) < 9);
        
        // 檢查守備位置是否重複
        $unique_positions = array_unique(array_filter($positions));
        $has_duplicate_position = (count($unique_positions) < 9);
        
        // 檢查是否有指定打擊 (DH)
        $has_dh = false;
        $pitcher_order = 0; // 投手在打線中的棒次，若有
        for ($i = 1; $i <= 9; $i++) {
            if (trim($positions[$i]) === 'DH') {
                $has_dh = true;
            }
            if (trim($positions[$i]) === 'P') {
                $pitcher_order = $i;
            }
        }
        
        if ($has_duplicate_batter) {
            $msg = "錯誤：先發名單必須選擇 9 位不同的球員，不可重複登錄！";
            $msgType = "error";
        } elseif ($has_duplicate_position) {
            $msg = "錯誤：先發打者中不可有重複的守備位置！";
            $msgType = "error";
        } elseif ($has_dh) {
            // 如果使用 DH，先發投手不可同時出現在 9 人打線中
            if (in_array($starting_pitcher_id, $batters)) {
                $msg = "錯誤：本場使用指定打擊 (DH)，先發投手不可同時為先發打者之一！";
                $msgType = "error";
            }
            // 且打線中不可以有位置為 P 的打者
            elseif ($pitcher_order > 0) {
                $msg = "錯誤：有指定打擊 (DH) 時，先發打者中不可有人擔任「P (投手)」位置！";
                $msgType = "error";
            }
        } else {
            // 如果不使用 DH，打線中必須有一人擔任 P (投手)
            if ($pitcher_order === 0) {
                $msg = "錯誤：未使用指定打擊 (DH) 時，先發名單的 9 位打者中必須有一位守備位置為「P (投手)」！";
                $msgType = "error";
            }
            // 且該擔任 P 的打者必須是指定的先發投手
            elseif ($batters[$pitcher_order] != $starting_pitcher_id) {
                $msg = "錯誤：先發投手必須與先發打者中擔任「P (投手)」位置的球員相同！";
                $msgType = "error";
            }
        }
        
        // 驗證通過，執行寫入
        if ($msg === '') {
            $pdo->prepare("DELETE FROM game_lineups WHERE game_id = ?")->execute([$game_id]);
            $pdo->prepare("DELETE FROM game_pitchers WHERE game_id = ?")->execute([$game_id]);
            $pdo->prepare("DELETE FROM game_live_state WHERE game_id = ?")->execute([$game_id]);
            
            $stmt = $pdo->prepare("INSERT INTO game_lineups (game_id, batting_order, player_id, position, status, sub_seq) VALUES (?, ?, ?, ?, 'active', 0)");
            for ($i = 1; $i <= 9; $i++) {
                $stmt->execute([$game_id, $i, (int)$batters[$i], trim($positions[$i])]);
            }
            
            $pdo->prepare("INSERT INTO game_pitchers (game_id, player_id, status, pitcher_seq) VALUES (?, ?, 'active', 1)")->execute([$game_id, $starting_pitcher_id]);
            $pdo->prepare("INSERT INTO game_live_state (game_id, current_batter_order, inning, is_top) VALUES (?, 1, 1, 1)")->execute([$game_id]);
            
            $msg = "先發陣容與投手初始化成功！開始即時登錄。";
        }
    }
    
    elseif ($action === 'record_pa') {
        $recording_type = isset($_POST['recording_type']) ? trim($_POST['recording_type']) : 'offense';
        $pa_result = trim($_POST['pa_result']);
        $pitches_thrown = (int)$_POST['pitches_thrown'];
        $p_wp_diff = isset($_POST['wild_pitches']) ? (int)$_POST['wild_pitches'] : 0;
        $p_balk_diff = isset($_POST['balks']) ? (int)$_POST['balks'] : 0;

        // Fetch current live state
        $state_stmt = $pdo->prepare("SELECT * FROM game_live_state WHERE game_id = ?");
        $state_stmt->execute([$game_id]);
        $curr_state = $state_stmt->fetch();
        if (!$curr_state) {
            $pdo->prepare("INSERT INTO game_live_state (game_id, current_batter_order, inning, is_top) VALUES (?, 1, 1, 1)")->execute([$game_id]);
            $state_stmt->execute([$game_id]);
            $curr_state = $state_stmt->fetch();
        }

        // Determine if we are on offense or defense
        $is_our_offense = false;
        $batting_first = isset($game['batting_first']) ? $game['batting_first'] : '後攻';
        if ($curr_state) {
            if ($batting_first === '先攻') {
                $is_our_offense = ((int)$curr_state['is_top'] == 1);
            } else {
                $is_our_offense = ((int)$curr_state['is_top'] == 0);
            }
        }

        if ($recording_type === 'offense') {
            if (!$is_our_offense) {
                $msg = "錯誤：目前非我方進攻半局 (對手打擊中)，無法登記我方打擊結果！";
                $msgType = "error";
            } else {
                $batter_id = (int)$_POST['batter_id'];
                $current_order = (int)$_POST['current_order'];
                
                $go_diff = in_array($pa_result, ['GO', 'DP', 'FC']) ? 1 : 0;
                $fo_diff = ($pa_result === 'FO') ? 1 : 0;
                $hbp_diff = ($pa_result === 'HBP') ? 1 : 0;
                $sf_diff = ($pa_result === 'SF') ? 1 : 0;
                $sac_diff = ($pa_result === 'SAC' || $pa_result === 'SH') ? 1 : 0;
                
                // 取得表單提交的跑者 ID
                $runner_first_id = isset($_POST['runner_first_id']) ? (int)$_POST['runner_first_id'] : 0;
                $runner_second_id = isset($_POST['runner_second_id']) ? (int)$_POST['runner_second_id'] : 0;
                $runner_third_id = isset($_POST['runner_third_id']) ? (int)$_POST['runner_third_id'] : 0;

                // 取得表單提交的跑者結果動作
                $r1_action = isset($_POST['runner_first_action']) ? trim($_POST['runner_first_action']) : 'stay';
                $r2_action = isset($_POST['runner_second_action']) ? trim($_POST['runner_second_action']) : 'stay';
                $r3_action = isset($_POST['runner_third_action']) ? trim($_POST['runner_third_action']) : 'stay';

                // 打者個人額外數據
                $batter_sb = isset($_POST['batter_sb']) ? 1 : 0;
                $batter_extra_rbi = isset($_POST['batter_extra_rbi']) ? (int)$_POST['batter_extra_rbi'] : 0;

                // 新的壘包跑者 ID
                $next_first_id = 0;
                $next_second_id = 0;
                $next_third_id = 0;

                $runs_scored = 0;
                $rbi_added = $batter_extra_rbi;
                $outs_added = 0;

                $stolen_bases_updates = []; // player_id => SB diff
                $runs_updates = []; // player_id => Runs diff

                // 1. 處理一壘跑者結果
                if ($runner_first_id > 0) {
                    if ($r1_action === 'stay') {
                        $next_first_id = $runner_first_id;
                    } elseif ($r1_action === 'adv_2b') {
                        $next_second_id = $runner_first_id;
                    } elseif ($r1_action === 'adv_3b') {
                        $next_third_id = $runner_first_id;
                    } elseif ($r1_action === 'score') {
                        $runs_scored++;
                        $rbi_added++;
                        $runs_updates[$runner_first_id] = 1;
                    } elseif ($r1_action === 'score_no_rbi') {
                        $runs_scored++;
                        $runs_updates[$runner_first_id] = 1;
                    } elseif ($r1_action === 'sb_2b') {
                        $next_second_id = $runner_first_id;
                        $stolen_bases_updates[$runner_first_id] = 1;
                    } elseif ($r1_action === 'out') {
                        $outs_added++;
                    }
                }

                // 2. 處理二壘跑者結果
                if ($runner_second_id > 0) {
                    if ($r2_action === 'stay') {
                        $next_second_id = $runner_second_id;
                    } elseif ($r2_action === 'adv_3b') {
                        $next_third_id = $runner_second_id;
                    } elseif ($r2_action === 'score') {
                        $runs_scored++;
                        $rbi_added++;
                        $runs_updates[$runner_second_id] = 1;
                    } elseif ($r2_action === 'score_no_rbi') {
                        $runs_scored++;
                        $runs_updates[$runner_second_id] = 1;
                    } elseif ($r2_action === 'sb_3b') {
                        $next_third_id = $runner_second_id;
                        $stolen_bases_updates[$runner_second_id] = 1;
                    } elseif ($r2_action === 'out') {
                        $outs_added++;
                    }
                }

                // 3. 處理三壘跑者結果
                if ($runner_third_id > 0) {
                    if ($r3_action === 'stay') {
                        $next_third_id = $runner_third_id;
                    } elseif ($r3_action === 'score') {
                        $runs_scored++;
                        $rbi_added++;
                        $runs_updates[$runner_third_id] = 1;
                    } elseif ($r3_action === 'score_no_rbi') {
                        $runs_scored++;
                        $runs_updates[$runner_third_id] = 1;
                    } elseif ($r3_action === 'sb_h') {
                        $runs_scored++;
                        $runs_updates[$runner_third_id] = 1;
                        $stolen_bases_updates[$runner_third_id] = 1;
                    } elseif ($r3_action === 'out') {
                        $outs_added++;
                    }
                }

                // 4. 處理打者打擊結果的自身位置
                if (in_array($pa_result, ['1B', 'BB', 'HBP', 'E', 'FC'])) {
                    $next_first_id = $batter_id;
                } elseif ($pa_result === '2B') {
                    $next_second_id = $batter_id;
                } elseif ($pa_result === '3B') {
                    $next_third_id = $batter_id;
                } elseif ($pa_result === 'HR') {
                    $runs_scored++;
                    $rbi_added++;
                    $runs_updates[$batter_id] = 1;
                } elseif (in_array($pa_result, ['K', 'GO', 'FO'])) {
                    $outs_added++;
                } elseif ($pa_result === 'DP') {
                    $outs_added += 2;
                }

                // 5. 更新其他非打者球員的個人數據（得分、盜壘）
                $all_affected_players = array_unique(array_merge(array_keys($stolen_bases_updates), array_keys($runs_updates)));
                foreach ($all_affected_players as $p_id) {
                    if ($p_id == $batter_id) continue;

                    $p_sb = $stolen_bases_updates[$p_id] ?? 0;
                    $p_run = $runs_updates[$p_id] ?? 0;
                    
                    $p_stmt = $pdo->prepare("SELECT * FROM player_game_details WHERE game_id = ? AND player_id = ?");
                    $p_stmt->execute([$game_id, $p_id]);
                    $p_details = $p_stmt->fetch();
                    
                    if ($p_details) {
                        $new_p_sb = (int)($p_details['stolen_bases'] ?? 0) + $p_sb;
                        $new_p_run = (int)($p_details['runs'] ?? 0) + $p_run;
                        $pdo->prepare("UPDATE player_game_details SET stolen_bases = ?, runs = ? WHERE id = ?")
                            ->execute([$new_p_sb, $new_p_run, $p_details['id']]);
                    } else {
                        $pdo->prepare("INSERT INTO player_game_details (game_id, player_id, stolen_bases, runs) VALUES (?, ?, ?, ?)")
                            ->execute([$game_id, $p_id, $p_sb, $p_run]);
                    }
                }

                // 6. 更新我方打者個人打擊數據
                $stmt = $pdo->prepare("SELECT * FROM player_game_details WHERE game_id = ? AND player_id = ?");
                $stmt->execute([$game_id, $batter_id]);
                $details = $stmt->fetch();
                
                $runs_diff = $runs_updates[$batter_id] ?? 0;
                $hard_hit_diff = isset($_POST['hard_hit']) ? (int)$_POST['hard_hit'] : 0;
                $soft_hit_diff = isset($_POST['soft_hit']) ? (int)$_POST['soft_hit'] : 0;
                
                // Opponent's pitching & batter's state against the pitcher
                $balls_diff = isset($_POST['balls']) ? (int)$_POST['balls'] : 0;
                $strikes_diff = isset($_POST['strikes']) ? (int)$_POST['strikes'] : 0;
                $swings_diff = isset($_POST['swings']) ? (int)$_POST['swings'] : 0;
                $fps_diff = isset($_POST['first_pitch_swings']) ? (int)$_POST['first_pitch_swings'] : 0;
                $whiffs_diff = isset($_POST['whiffs']) ? (int)$_POST['whiffs'] : 0;
                $gb_diff_adv = isset($_POST['gb_count']) ? (int)$_POST['gb_count'] : 0;
                $fb_diff_adv = isset($_POST['fb_count']) ? (int)$_POST['fb_count'] : 0;
                $ld_diff_adv = isset($_POST['ld_count']) ? (int)$_POST['ld_count'] : 0;
                
                if ($details) {
                    $new_pa_count = (int)$details['pa_count'] + 1;
                    $new_pa_results = empty($details['pa_results']) ? $pa_result : $details['pa_results'] . ', ' . $pa_result;
                    $new_go = (int)($details['go_outs'] ?? 0) + $go_diff;
                    $new_fo = (int)($details['fo_outs'] ?? 0) + $fo_diff;
                    $new_hbp = (int)($details['hit_by_pitch'] ?? 0) + $hbp_diff;
                    $new_sf = (int)($details['sac_fly'] ?? 0) + $sf_diff;
                    $new_sac = (int)($details['sac_bunt'] ?? 0) + $sac_diff;
                    $new_rbi = (int)($details['rbi'] ?? 0) + $rbi_added;
                    $new_runs = (int)($details['runs'] ?? 0) + $runs_diff;
                    $new_sb = (int)($details['stolen_bases'] ?? 0) + $batter_sb;
                    $new_hard_hit = (int)($details['hard_hit'] ?? 0) + $hard_hit_diff;
                    $new_soft_hit = (int)($details['soft_hit'] ?? 0) + $soft_hit_diff;

                    $new_strikes = (int)($details['strikes'] ?? 0) + $strikes_diff;
                    $new_balls = (int)($details['balls'] ?? 0) + $balls_diff;
                    $new_swings = (int)($details['swings'] ?? 0) + $swings_diff;
                    $new_fps = (int)($details['first_pitch_swings'] ?? 0) + $fps_diff;
                    $new_whiffs = (int)($details['whiffs'] ?? 0) + $whiffs_diff;
                    $new_gb = (int)($details['gb_count'] ?? 0) + $gb_diff_adv;
                    $new_ld = (int)($details['ld_count'] ?? 0) + $ld_diff_adv;
                    $new_fb = (int)($details['fb_count'] ?? 0) + $fb_diff_adv;

                    $pdo->prepare("UPDATE player_game_details SET pa_count = ?, pa_results = ?, go_outs = ?, fo_outs = ?, hit_by_pitch = ?, sac_fly = ?, sac_bunt = ?, rbi = ?, runs = ?, stolen_bases = ?, hard_hit = ?, soft_hit = ?, strikes = ?, balls = ?, swings = ?, first_pitch_swings = ?, whiffs = ?, gb_count = ?, ld_count = ?, fb_count = ? WHERE id = ?")
                        ->execute([$new_pa_count, $new_pa_results, $new_go, $new_fo, $new_hbp, $new_sf, $new_sac, $new_rbi, $new_runs, $new_sb, $new_hard_hit, $new_soft_hit, $new_strikes, $new_balls, $new_swings, $new_fps, $new_whiffs, $new_gb, $new_ld, $new_fb, $details['id']]);
                } else {
                    $pdo->prepare("INSERT INTO player_game_details (game_id, player_id, pa_count, pa_results, go_outs, fo_outs, hit_by_pitch, sac_fly, sac_bunt, rbi, runs, stolen_bases, hard_hit, soft_hit, strikes, balls, swings, first_pitch_swings, whiffs, gb_count, ld_count, fb_count) VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                        ->execute([$game_id, $batter_id, $pa_result, $go_diff, $fo_diff, $hbp_diff, $sf_diff, $sac_diff, $rbi_added, $runs_diff, $batter_sb, $hard_hit_diff, $soft_hit_diff, $strikes_diff, $balls_diff, $swings_diff, $fps_diff, $whiffs_diff, $gb_diff_adv, $ld_diff_adv, $fb_diff_adv]);
                }

                // 7. 更新計分板狀態 (團隊分數與壘包狀態，包含 ID)
                $new_our_score = (int)$curr_state['our_score'] + $runs_scored;
                $new_our_hits_diff = in_array($pa_result, ['1B', '2B', '3B', 'HR']) ? 1 : 0;
                $new_our_hits = (int)$curr_state['our_hits'] + $new_our_hits_diff;
                
                $new_outs = (int)$curr_state['outs'] + $outs_added;

                $new_inning = (int)$curr_state['inning'];
                $new_is_top = (int)$curr_state['is_top'];
                if ($new_outs >= 3) {
                    $new_outs = 0;
                    $next_first_id = 0;
                    $next_second_id = 0;
                    $next_third_id = 0;
                    if ($new_is_top == 1) {
                        $new_is_top = 0;
                    } else {
                        $new_is_top = 1;
                        $new_inning++;
                    }
                }

                $next_order = $current_order + 1;
                if ($next_order > 9) $next_order = 1;

                $pdo->prepare("UPDATE game_live_state SET 
                    current_batter_order = ?, our_score = ?, our_hits = ?, outs = ?, 
                    balls = 0, strikes = 0, 
                    runner_first = ?, runner_second = ?, runner_third = ?,
                    runner_first_id = ?, runner_second_id = ?, runner_third_id = ?,
                    inning = ?, is_top = ?
                    WHERE game_id = ?")
                    ->execute([
                        $next_order, $new_our_score, $new_our_hits, $new_outs,
                        ($next_first_id > 0 ? 1 : 0), ($next_second_id > 0 ? 1 : 0), ($next_third_id > 0 ? 1 : 0),
                        ($next_first_id > 0 ? $next_first_id : null),
                        ($next_second_id > 0 ? $next_second_id : null),
                        ($next_third_id > 0 ? $next_third_id : null),
                        $new_inning, $new_is_top, $game_id
                    ]);

                $play_desc = isset($_POST['play_desc']) ? trim($_POST['play_desc']) : '';
                $pdo->prepare("INSERT INTO game_live_logs (game_id, inning, is_top, outs, pa_result, description, type) VALUES (?, ?, ?, ?, ?, ?, 'offense')")
                    ->execute([$game_id, $curr_state['inning'], $curr_state['is_top'], $curr_state['outs'], $pa_result, $play_desc]);
                
                $msg = "我方打擊結果「{$pa_result}」及壘包跑者事件已成功登記！自動輪到下一棒 (第 {$next_order} 棒)。";
            }
        } else {
            // 我方防守 (對手打擊) -> 更新我方投手數據
            if ($is_our_offense) {
                $msg = "錯誤：目前為我方進攻半局 (我方打擊中)，無法登記對方打席投球數據！";
                $msgType = "error";
            } else {
                $stmt = $pdo->prepare("SELECT * FROM game_pitchers WHERE game_id = ? AND status = 'active'");
                $stmt->execute([$game_id]);
                $active_pitcher_row = $stmt->fetch();
                
                if ($active_pitcher_row) {
                    $pitcher_id = $active_pitcher_row['player_id'];
                    
                    $p_so_diff = ($pa_result === 'K') ? 1 : 0;
                    $p_bb_diff = ($pa_result === 'BB') ? 1 : 0;
                    $p_hbp_diff = ($pa_result === 'HBP') ? 1 : 0;
                    $p_hr_diff = ($pa_result === 'HR') ? 1 : 0;
                    $p_hit_diff = in_array($pa_result, ['1B', '2B', '3B', 'HR']) ? 1 : 0;
                    $p_go_diff = in_array($pa_result, ['GO', 'DP', 'FC']) ? 1 : 0;
                    $p_fo_diff = ($pa_result === 'FO') ? 1 : 0;
                    
                    $outs_diff = 0;
                    if (in_array($pa_result, ['K', 'GO', 'FO', 'FC'])) {
                        $outs_diff = 1;
                    } elseif ($pa_result === 'DP') {
                        $outs_diff = 2;
                    }

                    // Retrieve new pitching characteristics from POST
                    $p_strikes_diff = isset($_POST['p_strikes']) ? (int)$_POST['p_strikes'] : 0;
                    $p_balls_diff = isset($_POST['p_balls']) ? (int)$_POST['p_balls'] : 0;
                    $p_swings_diff = isset($_POST['p_swings']) ? (int)$_POST['p_swings'] : 0;
                    $p_whiffs_diff = isset($_POST['p_whiffs']) ? (int)$_POST['p_whiffs'] : 0;
                    $p_fps_diff = isset($_POST['p_first_pitch_swing']) ? (int)$_POST['p_first_pitch_swing'] : 0;
                    $p_gb_diff = isset($_POST['p_gb_count']) ? (int)$_POST['p_gb_count'] : 0;
                    $p_ld_diff = isset($_POST['p_ld_count']) ? (int)$_POST['p_ld_count'] : 0;
                    $p_fb_diff = isset($_POST['p_fb_count']) ? (int)$_POST['p_fb_count'] : 0;
                    $p_runs_allowed_diff = isset($_POST['p_runs_allowed']) ? (int)$_POST['p_runs_allowed'] : 0;
                    $p_earned_runs_diff = isset($_POST['p_earned_runs']) ? (int)$_POST['p_earned_runs'] : 0;

                    $new_first = (int)$curr_state['runner_first'];
                    $new_second = (int)$curr_state['runner_second'];
                    $new_third = (int)$curr_state['runner_third'];
                    $runs_opp_auto = 0;

                    if ($pa_result === '1B' || $pa_result === 'E' || $pa_result === 'FC') {
                        $runs_opp_auto = $new_third;
                        $new_third = $new_second;
                        $new_second = $new_first;
                        $new_first = 1;
                    } elseif ($pa_result === 'BB' || $pa_result === 'HBP') {
                        if ($new_first) {
                            if ($new_second) {
                                if ($new_third) {
                                    $runs_opp_auto = 1;
                                }
                                $new_third = 1;
                            }
                            $new_second = 1;
                        }
                        $new_first = 1;
                    } elseif ($pa_result === '2B') {
                        $runs_opp_auto = $new_second + $new_third;
                        $new_third = $new_first;
                        $new_second = 1;
                        $new_first = 0;
                    } elseif ($pa_result === '3B') {
                        $runs_opp_auto = $new_first + $new_second + $new_third;
                        $new_third = 1;
                        $new_second = 0;
                        $new_first = 0;
                    } elseif ($pa_result === 'HR') {
                        $runs_opp_auto = $new_first + $new_second + $new_third + 1;
                        $new_first = 0;
                        $new_second = 0;
                        $new_third = 0;
                    }

                    // 與手動輸入數據同步
                    if ($p_runs_allowed_diff <= 0 && $runs_opp_auto > 0) {
                        $p_runs_allowed_diff = $runs_opp_auto;
                    }
                    if ($p_earned_runs_diff <= 0 && $runs_opp_auto > 0) {
                        $p_earned_runs_diff = $runs_opp_auto;
                    }
                    
                    $stmt = $pdo->prepare("SELECT * FROM player_game_details WHERE game_id = ? AND player_id = ?");
                    $stmt->execute([$game_id, $pitcher_id]);
                    $p_details = $stmt->fetch();
                    
                    // 取得先發/救援狀態
                    $seq_stmt = $pdo->prepare("SELECT pitcher_seq FROM game_pitchers WHERE game_id = ? AND player_id = ? ORDER BY pitcher_seq DESC LIMIT 1");
                    $seq_stmt->execute([$game_id, $pitcher_id]);
                    $seq_row = $seq_stmt->fetch();
                    $pitcher_seq = $seq_row ? (int)$seq_row['pitcher_seq'] : 2;
                    $is_start = ($pitcher_seq === 1) ? 1 : 0;
                    $is_relief = ($pitcher_seq > 1) ? 1 : 0;

                    if ($p_details) {
                        $new_pitches = (int)$p_details['pitches'] + $pitches_thrown;
                        $new_so = (int)$p_details['strikeouts'] + $p_so_diff;
                        $new_bb = (int)$p_details['walks'] + $p_bb_diff;
                        $new_hbp = (int)$p_details['p_hit_by_pitch'] + $p_hbp_diff;
                        $new_hr = (int)$p_details['p_hr_allowed'] + $p_hr_diff;
                        $new_hits_allowed = (int)$p_details['hits_allowed'] + $p_hit_diff;
                        $new_go = (int)$p_details['p_go_outs'] + $p_go_diff;
                        $new_fo = (int)$p_details['p_fo_outs'] + $p_fo_diff;
                        $new_bf = (int)$p_details['batters_faced'] + 1;
                        $new_innings = addInning($p_details['innings'], $outs_diff * 0.1);
                        $new_wp = (int)($p_details['wild_pitches'] ?? 0) + $p_wp_diff;
                        $new_balks = (int)($p_details['balks'] ?? 0) + $p_balk_diff;
                        
                        $new_runs_allowed = (int)($p_details['runs_allowed'] ?? 0) + $p_runs_allowed_diff;
                        $new_earned_runs = (int)($p_details['earned_runs'] ?? 0) + $p_earned_runs_diff;
                        $new_strikes = (int)($p_details['strikes'] ?? 0) + $p_strikes_diff;
                        $new_balls = (int)($p_details['balls'] ?? 0) + $p_balls_diff;
                        $new_swings = (int)($p_details['swings'] ?? 0) + $p_swings_diff;
                        $new_whiffs = (int)($p_details['whiffs'] ?? 0) + $p_whiffs_diff;
                        $new_fps = (int)($p_details['first_pitch_swings'] ?? 0) + $p_fps_diff;
                        $new_gb = (int)($p_details['gb_count'] ?? 0) + $p_gb_diff;
                        $new_ld = (int)($p_details['ld_count'] ?? 0) + $p_ld_diff;
                        $new_fb = (int)($p_details['fb_count'] ?? 0) + $p_fb_diff;

                        $pdo->prepare("UPDATE player_game_details SET 
                            pitches = ?, strikeouts = ?, walks = ?, p_hit_by_pitch = ?, 
                            p_hr_allowed = ?, hits_allowed = ?, p_go_outs = ?, p_fo_outs = ?, 
                            batters_faced = ?, innings = ?, wild_pitches = ?, balks = ?,
                            is_start = ?, is_relief = ?, runs_allowed = ?, earned_runs = ?,
                            strikes = ?, balls = ?, swings = ?, whiffs = ?, first_pitch_swings = ?,
                            gb_count = ?, ld_count = ?, fb_count = ?
                            WHERE id = ?")
                            ->execute([
                                $new_pitches, $new_so, $new_bb, $new_hbp, 
                                $new_hr, $new_hits_allowed, $new_go, $new_fo, 
                                $new_bf, $new_innings, $new_wp, $new_balks,
                                $is_start, $is_relief, $new_runs_allowed, $new_earned_runs,
                                $new_strikes, $new_balls, $new_swings, $new_whiffs, $new_fps,
                                $new_gb, $new_ld, $new_fb,
                                $p_details['id']
                            ]);
                    } else {
                        $new_innings = addInning('0', $outs_diff * 0.1);
                        $pdo->prepare("INSERT INTO player_game_details (
                            game_id, player_id, pitches, strikeouts, walks, p_hit_by_pitch, 
                            p_hr_allowed, hits_allowed, p_go_outs, p_fo_outs, batters_faced, innings, wild_pitches, balks,
                            is_start, is_relief, runs_allowed, earned_runs,
                            strikes, balls, swings, whiffs, first_pitch_swings,
                            gb_count, ld_count, fb_count
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                        ->execute([
                            $game_id, $pitcher_id, $pitches_thrown, $p_so_diff, $p_bb_diff, $p_hbp_diff,
                            $p_hr_diff, $p_hit_diff, $p_go_diff, $p_fo_diff, $new_innings, $p_wp_diff, $p_balk_diff,
                            $is_start, $is_relief, $p_runs_allowed_diff, $p_earned_runs_diff,
                            $p_strikes_diff, $p_balls_diff, $p_swings_diff, $p_whiffs_diff, $p_fps_diff,
                            $p_gb_diff, $p_ld_diff, $p_fb_diff
                        ]);
                    }

                    // 2. 更新計分板狀態 (對手得分、安打、出局與壘包)
                    $new_opp_score = (int)$curr_state['opponent_score'] + $p_runs_allowed_diff;
                    $new_opp_hits_diff = in_array($pa_result, ['1B', '2B', '3B', 'HR']) ? 1 : 0;
                    $new_opp_hits = (int)$curr_state['opponent_hits'] + $new_opp_hits_diff;
                    $new_outs = (int)$curr_state['outs'] + $outs_diff;

                    $new_inning = (int)$curr_state['inning'];
                    $new_is_top = (int)$curr_state['is_top'];
                    if ($new_outs >= 3) {
                        $new_outs = 0;
                        $new_first = 0;
                        $new_second = 0;
                        $new_third = 0;
                        if ($new_is_top == 1) {
                            $new_is_top = 0;
                        } else {
                            $new_is_top = 1;
                            $new_inning++;
                        }
                    }

                    $pdo->prepare("UPDATE game_live_state SET 
                        opponent_score = ?, opponent_hits = ?, outs = ?, 
                        balls = 0, strikes = 0, runner_first = ?, runner_second = ?, runner_third = ?,
                        runner_first_id = NULL, runner_second_id = NULL, runner_third_id = NULL,
                        inning = ?, is_top = ?
                        WHERE game_id = ?")
                        ->execute([
                            $new_opp_score, $new_opp_hits, $new_outs,
                            $new_first, $new_second, $new_third, $new_inning, $new_is_top, $game_id
                        ]);

                    $play_desc = isset($_POST['play_desc']) ? trim($_POST['play_desc']) : '';
                    $pdo->prepare("INSERT INTO game_live_logs (game_id, inning, is_top, outs, pa_result, description, type) VALUES (?, ?, ?, ?, ?, ?, 'defense')")
                        ->execute([$game_id, $curr_state['inning'], $curr_state['is_top'], $curr_state['outs'], $pa_result, $play_desc]);

                    $msg = "對方打擊結果「{$pa_result}」已登錄！我方投手投球數據及計分板已同步更新。";
                } else {
                    $msg = "錯誤：目前沒有指定現役投手，無法登記對方打席。";
                    $msgType = "error";
                }
            }
        }
    }

    elseif ($action === 'delete_live_log') {
        $log_id = (int)$_POST['log_id'];
        $pdo->prepare("DELETE FROM game_live_logs WHERE id = ? AND game_id = ?")->execute([$log_id, $game_id]);
        $msg = "已成功刪除該打席紀錄敘述。";
    }

    elseif ($action === 'update_scoreboard') {
        $our_score = (int)$_POST['our_score'];
        $opponent_score = (int)$_POST['opponent_score'];
        $inning = (int)$_POST['inning'];
        $is_top = (int)$_POST['is_top'];
        $outs = (int)$_POST['outs'];
        $balls = (int)$_POST['balls'];
        $strikes = (int)$_POST['strikes'];
        $our_hits = (int)$_POST['our_hits'];
        $opponent_hits = (int)$_POST['opponent_hits'];
        $our_errors = (int)$_POST['our_errors'];
        $opponent_errors = (int)$_POST['opponent_errors'];
        
        $runner_first = isset($_POST['runner_first']) ? 1 : 0;
        $runner_second = isset($_POST['runner_second']) ? 1 : 0;
        $runner_third = isset($_POST['runner_third']) ? 1 : 0;

        // 讀取原本的跑者 ID，如果壘包被清空則設為 NULL
        $state_stmt = $pdo->prepare("SELECT * FROM game_live_state WHERE game_id = ?");
        $state_stmt->execute([$game_id]);
        $curr = $state_stmt->fetch();
        
        $runner_first_id = ($runner_first && $curr) ? $curr['runner_first_id'] : null;
        $runner_second_id = ($runner_second && $curr) ? $curr['runner_second_id'] : null;
        $runner_third_id = ($runner_third && $curr) ? $curr['runner_third_id'] : null;
        
        $pdo->prepare("UPDATE game_live_state SET 
            our_score = ?, opponent_score = ?, inning = ?, is_top = ?,
            outs = ?, balls = ?, strikes = ?, our_hits = ?, opponent_hits = ?,
            our_errors = ?, opponent_errors = ?, runner_first = ?, runner_second = ?, runner_third = ?,
            runner_first_id = ?, runner_second_id = ?, runner_third_id = ?
            WHERE game_id = ?")
            ->execute([
                $our_score, $opponent_score, $inning, $is_top,
                $outs, $balls, $strikes, $our_hits, $opponent_hits,
                $our_errors, $opponent_errors, $runner_first, $runner_second, $runner_third,
                $runner_first_id, $runner_second_id, $runner_third_id,
                $game_id
            ]);
        $msg = "計分板數據已手動更新！";
    }
    
    elseif ($action === 'base_event') {
        $team = trim($_POST['team']); // 'our' or 'opponent'
        $event_type = trim($_POST['event_type']);
        
        // Retrieve current live state
        $state_stmt = $pdo->prepare("SELECT * FROM game_live_state WHERE game_id = ?");
        $state_stmt->execute([$game_id]);
        $curr_state = $state_stmt->fetch();
        if (!$curr_state) {
            $pdo->prepare("INSERT INTO game_live_state (game_id, current_batter_order, inning, is_top) VALUES (?, 1, 1, 1)")->execute([$game_id]);
            $state_stmt->execute([$game_id]);
            $curr_state = $state_stmt->fetch();
        }

        // Determine if we are on offense or defense
        $is_our_offense = false;
        $batting_first = isset($game['batting_first']) ? $game['batting_first'] : '後攻';
        if ($curr_state) {
            if ($batting_first === '先攻') {
                $is_our_offense = ((int)$curr_state['is_top'] == 1);
            } else {
                $is_our_offense = ((int)$curr_state['is_top'] == 0);
            }
        }

        if (!$is_our_offense && $team === 'our') {
            $msg = "錯誤：目前為對手進攻半局 (防守中)，無法登記我方跑壘事件！";
            $msgType = "error";
        } elseif ($is_our_offense && $team === 'opponent') {
            $msg = "錯誤：目前為我方進攻半局 (進攻中)，無法登記對方跑壘事件！";
            $msgType = "error";
        } else {
            $new_first = (int)$curr_state['runner_first'];
            $new_second = (int)$curr_state['runner_second'];
            $new_third = (int)$curr_state['runner_third'];
            $new_outs = (int)$curr_state['outs'];
            $new_our_score = (int)$curr_state['our_score'];
            $new_opp_score = (int)$curr_state['opponent_score'];
            $new_inning = (int)$curr_state['inning'];
            $new_is_top = (int)$curr_state['is_top'];

            $runner_first_id = $curr_state['runner_first_id'];
            $runner_second_id = $curr_state['runner_second_id'];
            $runner_third_id = $curr_state['runner_third_id'];

            $event_label = "";
            
            // Calculate base changes and out updates based on event type
            if ($event_type === 'SB_1_2') {
                $new_first = 0;
                $new_second = 1;
                $runner_second_id = $runner_first_id;
                $runner_first_id = null;
                $event_label = "盜二壘成功";
            } elseif ($event_type === 'SB_2_3') {
                $new_second = 0;
                $new_third = 1;
                $runner_third_id = $runner_second_id;
                $runner_second_id = null;
                $event_label = "盜三壘成功";
            } elseif ($event_type === 'SB_3_H') {
                $new_third = 0;
                $runner_third_id = null;
                if ($team === 'our') {
                    $new_our_score++;
                } else {
                    $new_opp_score++;
                }
                $event_label = "盜本壘成功";
            } elseif ($event_type === 'CS_1_2') {
                $new_first = 0;
                $runner_first_id = null;
                $new_outs++;
                $event_label = "盜二壘失敗出局";
            } elseif ($event_type === 'CS_2_3') {
                $new_second = 0;
                $runner_second_id = null;
                $new_outs++;
                $event_label = "盜三壘失敗出局";
            } elseif ($event_type === 'PO_1') {
                $new_first = 0;
                $runner_first_id = null;
                $new_outs++;
                $event_label = "一壘牽制出局";
            } elseif ($event_type === 'PO_2') {
                $new_second = 0;
                $runner_second_id = null;
                $new_outs++;
                $event_label = "二壘牽制出局";
            } elseif ($event_type === 'PO_3') {
                $new_third = 0;
                $runner_third_id = null;
                $new_outs++;
                $event_label = "三壘牽制出局";
            } elseif ($event_type === 'OB_1') {
                $new_first = 0;
                $new_second = 1;
                $runner_second_id = $runner_first_id;
                $runner_first_id = null;
                $event_label = "跑壘/牽制失誤進二壘";
            } elseif ($event_type === 'OB_2') {
                $new_second = 0;
                $new_third = 1;
                $runner_third_id = $runner_second_id;
                $runner_second_id = null;
                $event_label = "跑壘/牽制失誤進三壘";
            } elseif ($event_type === 'OB_3') {
                $new_third = 0;
                $runner_third_id = null;
                if ($team === 'our') {
                    $new_our_score++;
                } else {
                    $new_opp_score++;
                }
                $event_label = "跑壘/牽制失誤回本壘得分";
            }

            // Handle Three-Out Transition
            $new_balls = (int)$curr_state['balls'];
            $new_strikes = (int)$curr_state['strikes'];
            if ($new_outs >= 3) {
                $new_outs = 0;
                $new_first = 0;
                $new_second = 0;
                $new_third = 0;
                $runner_first_id = null;
                $runner_second_id = null;
                $runner_third_id = null;
                $new_balls = 0;
                $new_strikes = 0;
                if ($new_is_top == 1) {
                    $new_is_top = 0;
                } else {
                    $new_is_top = 1;
                    $new_inning++;
                }
                $event_label .= " (三人出局，攻守交換！)";
            }

            // Update game live state in database
            $pdo->prepare("UPDATE game_live_state SET 
                our_score = ?, opponent_score = ?, outs = ?, 
                balls = ?, strikes = ?,
                runner_first = ?, runner_second = ?, runner_third = ?,
                runner_first_id = ?, runner_second_id = ?, runner_third_id = ?,
                inning = ?, is_top = ?
                WHERE game_id = ?")
                ->execute([
                    $new_our_score, $new_opp_score, $new_outs,
                    $new_balls, $new_strikes,
                    $new_first, $new_second, $new_third,
                    $runner_first_id, $runner_second_id, $runner_third_id,
                    $new_inning, $new_is_top, $game_id
                ]);

            // If it's OUR player stealing or scoring, increment their counts in database
            if ($team === 'our' && isset($_POST['player_id']) && !empty($_POST['player_id'])) {
                $player_id = (int)$_POST['player_id'];
                
                // Check if player details already exist for this game
                $stmt = $pdo->prepare("SELECT * FROM player_game_details WHERE game_id = ? AND player_id = ?");
                $stmt->execute([$game_id, $player_id]);
                $details = $stmt->fetch();
                
                $sb_add = (strpos($event_type, 'SB_') !== false) ? 1 : 0;
                $run_add = ($event_type === 'SB_3_H' || $event_type === 'OB_3') ? 1 : 0;
                
                // Fetch name for message
                $p_stmt = $pdo->prepare("SELECT Player_Name FROM player WHERE Player_id = ?");
                $p_stmt->execute([$player_id]);
                $player_name = $p_stmt->fetchColumn() ?: "球員";

                if ($sb_add > 0 || $run_add > 0) {
                    if ($details) {
                        $new_sb = (int)$details['stolen_bases'] + $sb_add;
                        $new_runs = (int)($details['runs'] ?? 0) + $run_add;
                        $pdo->prepare("UPDATE player_game_details SET stolen_bases = ?, runs = ? WHERE id = ?")
                            ->execute([$new_sb, $new_runs, $details['id']]);
                    } else {
                        $pdo->prepare("INSERT INTO player_game_details (game_id, player_id, stolen_bases, runs) VALUES (?, ?, ?, ?)")
                            ->execute([$game_id, $player_id, $sb_add, $run_add]);
                    }
                    $msg = "成功記錄 {$player_name} 「{$event_label}」！球員個人數據已累加。";
                } else {
                    $msg = "成功記錄 {$player_name} 「{$event_label}」！計分板及壘包狀態已更新。";
                }
            } else {
                $msg = "成功記錄對方「{$event_label}」！計分板及壘包狀態已更新。";
            }
        }
    }
    
    elseif ($action === 'update_pitcher') {
        // 檢查是否為我方進攻半局
        $check_top_stmt = $pdo->prepare("SELECT is_top FROM game_live_state WHERE game_id = ?");
        $check_top_stmt->execute([$game_id]);
        $c_top = $check_top_stmt->fetchColumn();
        
        $is_our_offense = false;
        $batting_first = isset($game['batting_first']) ? $game['batting_first'] : '後攻';
        if ($c_top !== false) {
            if ($batting_first === '先攻') {
                $is_our_offense = ((int)$c_top == 1);
            } else {
                $is_our_offense = ((int)$c_top == 0);
            }
        }
        
        if ($is_our_offense) {
            $msg = "錯誤：目前為我方進攻半局 (我方打擊中)，無法手動修改我方投手投球數據！";
            $msgType = "error";
        } else {
            $pitcher_id = (int)$_POST['pitcher_id'];
            $pitches = (int)$_POST['pitches'];
            $innings = trim($_POST['innings']);
            $strikeouts = (int)$_POST['strikeouts'];
            $walks = (int)$_POST['walks'];
            $earned_runs = (int)$_POST['earned_runs'];

            // Retrieve new manual inputs
            $batters_faced = isset($_POST['batters_faced']) ? (int)$_POST['batters_faced'] : 0;
            $hits_allowed = isset($_POST['hits_allowed']) ? (int)$_POST['hits_allowed'] : 0;
            $runs_allowed = isset($_POST['runs_allowed']) ? (int)$_POST['runs_allowed'] : 0;
            $p_hr_allowed = isset($_POST['p_hr_allowed']) ? (int)$_POST['p_hr_allowed'] : 0;
            $p_hit_by_pitch = isset($_POST['p_hit_by_pitch']) ? (int)$_POST['p_hit_by_pitch'] : 0;
            $wild_pitches = isset($_POST['wild_pitches']) ? (int)$_POST['wild_pitches'] : 0;
            $balks = isset($_POST['balks']) ? (int)$_POST['balks'] : 0;
            $p_go_outs = isset($_POST['p_go_outs']) ? (int)$_POST['p_go_outs'] : 0;
            $p_fo_outs = isset($_POST['p_fo_outs']) ? (int)$_POST['p_fo_outs'] : 0;

            $win = isset($_POST['win']) ? (int)$_POST['win'] : 0;
            $loss = isset($_POST['loss']) ? (int)$_POST['loss'] : 0;
            $save = isset($_POST['save']) ? (int)$_POST['save'] : 0;
            $blown_save = isset($_POST['blown_save']) ? (int)$_POST['blown_save'] : 0;
            $hold = isset($_POST['hold']) ? (int)$_POST['hold'] : 0;
            $is_cg = isset($_POST['is_cg']) ? (int)$_POST['is_cg'] : 0;
            $is_sho = isset($_POST['is_sho']) ? (int)$_POST['is_sho'] : 0;

            $strikes = isset($_POST['strikes']) ? (int)$_POST['strikes'] : 0;
            $balls = isset($_POST['balls']) ? (int)$_POST['balls'] : 0;
            $swings = isset($_POST['swings']) ? (int)$_POST['swings'] : 0;
            $first_pitch_swings = isset($_POST['first_pitch_swings']) ? (int)$_POST['first_pitch_swings'] : 0;
            $whiffs = isset($_POST['whiffs']) ? (int)$_POST['whiffs'] : 0;
            $gb_count = isset($_POST['gb_count']) ? (int)$_POST['gb_count'] : 0;
            $ld_count = isset($_POST['ld_count']) ? (int)$_POST['ld_count'] : 0;
            $fb_count = isset($_POST['fb_count']) ? (int)$_POST['fb_count'] : 0;
            
            $seq_stmt = $pdo->prepare("SELECT pitcher_seq FROM game_pitchers WHERE game_id = ? AND player_id = ? ORDER BY pitcher_seq DESC LIMIT 1");
            $seq_stmt->execute([$game_id, $pitcher_id]);
            $seq_row = $seq_stmt->fetch();
            $pitcher_seq = $seq_row ? (int)$seq_row['pitcher_seq'] : 2;
            $is_start = ($pitcher_seq === 1) ? 1 : 0;
            $is_relief = ($pitcher_seq > 1) ? 1 : 0;

            $stmt = $pdo->prepare("SELECT * FROM player_game_details WHERE game_id = ? AND player_id = ?");
            $stmt->execute([$game_id, $pitcher_id]);
            $details = $stmt->fetch();
            
            if ($details) {
                $pdo->prepare("UPDATE player_game_details SET 
                    pitches = ?, innings = ?, strikeouts = ?, walks = ?, earned_runs = ?, 
                    is_start = ?, is_relief = ?, batters_faced = ?, hits_allowed = ?, runs_allowed = ?, 
                    p_hr_allowed = ?, p_hit_by_pitch = ?, wild_pitches = ?, balks = ?, p_go_outs = ?, p_fo_outs = ?, 
                    win = ?, loss = ?, save = ?, blown_save = ?, hold = ?, is_cg = ?, is_sho = ?,
                    strikes = ?, balls = ?, swings = ?, first_pitch_swings = ?, whiffs = ?, 
                    gb_count = ?, ld_count = ?, fb_count = ?
                    WHERE id = ?")
                    ->execute([
                        $pitches, $innings, $strikeouts, $walks, $earned_runs, 
                        $is_start, $is_relief, $batters_faced, $hits_allowed, $runs_allowed, 
                        $p_hr_allowed, $p_hit_by_pitch, $wild_pitches, $balks, $p_go_outs, $p_fo_outs, 
                        $win, $loss, $save, $blown_save, $hold, $is_cg, $is_sho,
                        $strikes, $balls, $swings, $first_pitch_swings, $whiffs, 
                        $gb_count, $ld_count, $fb_count,
                        $details['id']
                    ]);
            } else {
                $pdo->prepare("INSERT INTO player_game_details (
                    game_id, player_id, pitches, innings, strikeouts, walks, earned_runs, 
                    is_start, is_relief, batters_faced, hits_allowed, runs_allowed, 
                    p_hr_allowed, p_hit_by_pitch, wild_pitches, balks, p_go_outs, p_fo_outs, 
                    win, loss, save, blown_save, hold, is_cg, is_sho,
                    strikes, balls, swings, first_pitch_swings, whiffs, 
                    gb_count, ld_count, fb_count
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?
                )")
                ->execute([
                    $game_id, $pitcher_id, $pitches, $innings, $strikeouts, $walks, $earned_runs, 
                    $is_start, $is_relief, $batters_faced, $hits_allowed, $runs_allowed, 
                    $p_hr_allowed, $p_hit_by_pitch, $wild_pitches, $balks, $p_go_outs, $p_fo_outs, 
                    $win, $loss, $save, $blown_save, $hold, $is_cg, $is_sho,
                    $strikes, $balls, $swings, $first_pitch_swings, $whiffs, 
                    $gb_count, $ld_count, $fb_count
                ]);
            }
            $msg = "投手局數/投球數據登記成功！";
        }
    }
    
    elseif ($action === 'pitcher_quick') {
        // 檢查是否為我方進攻半局
        $check_top_stmt = $pdo->prepare("SELECT is_top FROM game_live_state WHERE game_id = ?");
        $check_top_stmt->execute([$game_id]);
        $c_top = $check_top_stmt->fetchColumn();
        
        $is_our_offense = false;
        $batting_first = isset($game['batting_first']) ? $game['batting_first'] : '後攻';
        if ($c_top !== false) {
            if ($batting_first === '先攻') {
                $is_our_offense = ((int)$c_top == 1);
            } else {
                $is_our_offense = ((int)$c_top == 0);
            }
        }
        
        if ($is_our_offense) {
            $msg = "錯誤：目前為我方進攻半局 (我方打擊中)，無法快速調整我方投手投球數據！";
            $msgType = "error";
        } else {
            $pitcher_id = (int)$_POST['pitcher_id'];
            $stat = trim($_POST['stat']);
            $diff = (float)$_POST['diff'];
            
            $seq_stmt = $pdo->prepare("SELECT pitcher_seq FROM game_pitchers WHERE game_id = ? AND player_id = ? ORDER BY pitcher_seq DESC LIMIT 1");
            $seq_stmt->execute([$game_id, $pitcher_id]);
            $seq_row = $seq_stmt->fetch();
            $pitcher_seq = $seq_row ? (int)$seq_row['pitcher_seq'] : 2;
            $is_start = ($pitcher_seq === 1) ? 1 : 0;
            $is_relief = ($pitcher_seq > 1) ? 1 : 0;

            $stmt = $pdo->prepare("SELECT * FROM player_game_details WHERE game_id = ? AND player_id = ?");
            $stmt->execute([$game_id, $pitcher_id]);
            $details = $stmt->fetch();
            
            if ($details) {
                if ($stat === 'innings') {
                    $new_innings = addInning($details['innings'], $diff);
                    $pdo->prepare("UPDATE player_game_details SET innings = ?, is_start = ?, is_relief = ? WHERE id = ?")
                        ->execute([$new_innings, $is_start, $is_relief, $details['id']]);
                } else {
                    $new_val = max(0, (int)$details[$stat] + (int)$diff);
                    $pdo->prepare("UPDATE player_game_details SET `$stat` = ?, is_start = ?, is_relief = ? WHERE id = ?")
                        ->execute([$new_val, $is_start, $is_relief, $details['id']]);
                }
            } else {
                $pitches = ($stat === 'pitches') ? max(0, (int)$diff) : 0;
                $innings = ($stat === 'innings') ? addInning('0', $diff) : '0';
                $strikeouts = ($stat === 'strikeouts') ? max(0, (int)$diff) : 0;
                $walks = ($stat === 'walks') ? max(0, (int)$diff) : 0;
                $earned_runs = ($stat === 'earned_runs') ? max(0, (int)$diff) : 0;
                
                $pdo->prepare("INSERT INTO player_game_details (game_id, player_id, pitches, innings, strikeouts, walks, earned_runs, is_start, is_relief) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$game_id, $pitcher_id, $pitches, $innings, $strikeouts, $walks, $earned_runs, $is_start, $is_relief]);
            }
            $msg = "投手數據已快速更新！";
        }
    }
    
    elseif ($action === 'pinch_batter') {
        $batting_order = (int)$_POST['batting_order'];
        $old_player_id = (int)$_POST['old_player_id'];
        $new_player_id = (int)$_POST['new_player_id'];
        $position = trim($_POST['position']);
        
        $pdo->prepare("UPDATE game_lineups SET status = 'substituted' WHERE game_id = ? AND batting_order = ? AND player_id = ? AND status = 'active'")
            ->execute([$game_id, $batting_order, $old_player_id]);
            
        $stmt = $pdo->prepare("SELECT MAX(sub_seq) as max_seq FROM game_lineups WHERE game_id = ? AND batting_order = ?");
        $stmt->execute([$game_id, $batting_order]);
        $row = $stmt->fetch();
        $next_seq = (int)$row['max_seq'] + 1;
        
        $pdo->prepare("INSERT INTO game_lineups (game_id, batting_order, player_id, position, status, sub_seq) VALUES (?, ?, ?, ?, 'active', ?)")
            ->execute([$game_id, $batting_order, $new_player_id, $position, $next_seq]);
            
        $msg = "成功更換代打！第 {$batting_order} 棒現由新隊員上場。";
    }
    
    elseif ($action === 'pinch_pitcher') {
        $old_pitcher_id = (int)$_POST['old_pitcher_id'];
        $new_pitcher_id = (int)$_POST['new_pitcher_id'];
        
        $pdo->prepare("UPDATE game_pitchers SET status = 'substituted' WHERE game_id = ? AND player_id = ? AND status = 'active'")
            ->execute([$game_id, $old_pitcher_id]);
            
        $stmt = $pdo->prepare("SELECT MAX(pitcher_seq) as max_seq FROM game_pitchers WHERE game_id = ?");
        $stmt->execute([$game_id]);
        $row = $stmt->fetch();
        $next_seq = (int)$row['max_seq'] + 1;
        
        $pdo->prepare("INSERT INTO game_pitchers (game_id, player_id, status, pitcher_seq) VALUES (?, ?, 'active', ?)")
            ->execute([$game_id, $new_pitcher_id, $next_seq]);
            
        $msg = "成功更換投手！";
    }
    
    elseif ($action === 'change_position') {
        $lineup_id = (int)$_POST['lineup_id'];
        $new_position = trim($_POST['position']);
        
        $pdo->prepare("UPDATE game_lineups SET position = ? WHERE id = ?")
            ->execute([$new_position, $lineup_id]);
            
        $msg = "守備位置已更變為 {$new_position}。";
    }
    
    elseif ($action === 'reset_game') {
        $pdo->prepare("DELETE FROM game_lineups WHERE game_id = ?")->execute([$game_id]);
        $pdo->prepare("DELETE FROM game_pitchers WHERE game_id = ?")->execute([$game_id]);
        $pdo->prepare("DELETE FROM game_live_state WHERE game_id = ?")->execute([$game_id]);
        $pdo->prepare("DELETE FROM player_game_details WHERE game_id = ?")->execute([$game_id]);
        $pdo->prepare("DELETE FROM game_live_logs WHERE game_id = ?")->execute([$game_id]);
        
        $msg = "本場登記狀態與先發陣容已成功重設。";
    }
    
    elseif ($action === 'end_game') {
        $state_stmt = $pdo->prepare("SELECT * FROM game_live_state WHERE game_id = ?");
        $state_stmt->execute([$game_id]);
        $curr_state = $state_stmt->fetch();
        if ($curr_state) {
            $our_score = (int)$curr_state['our_score'];
            $opponent_score = (int)$curr_state['opponent_score'];
            
            if ($our_score > $opponent_score) {
                $status_word = "勝";
            } elseif ($our_score < $opponent_score) {
                $status_word = "敗";
            } else {
                $status_word = "和";
            }
            $result_str = $our_score . ":" . $opponent_score . " " . $status_word;
            
            // 1. Update result in game
            $pdo->prepare("UPDATE game SET result = ? WHERE Game_id = ?")->execute([$result_str, $game_id]);
            
            // 2. Set is_ended = 1 in game_live_state
            $pdo->prepare("UPDATE game_live_state SET is_ended = 1 WHERE game_id = ?")->execute([$game_id]);
            
            // Reload page to reflect updated is_game_ended
            $is_game_ended = 1;
            
            $msg = "比賽已宣告結束！最終比分「{$result_str}」，數據已同步至球員控制台。";
            $msgType = "success";
        } else {
            $msg = "錯誤：賽事尚未初始化，無法結束比賽。";
            $msgType = "error";
        }
    }
    
    elseif ($action === 'reopen_game') {
        // 1. Clear result in game table
        $pdo->prepare("UPDATE game SET result = NULL WHERE Game_id = ?")->execute([$game_id]);
        
        // 2. Set is_ended = 0 in game_live_state
        $pdo->prepare("UPDATE game_live_state SET is_ended = 0 WHERE game_id = ?")->execute([$game_id]);
        
        // Reload page to reflect updated is_game_ended
        $is_game_ended = 0;
        
        $msg = "已成功重新開啟此比賽的即時登記！";
        $msgType = "success";
    }
}

// ── 3. 讀取數據與狀態 ──
$players = $db->getAll('player');
usort($players, function($a, $b) {
    return (int)$a['jersey_number'] - (int)$b['jersey_number'];
});

// 先發陣容
$stmt = $pdo->prepare("
    SELECT gl.*, p.Player_Name, p.jersey_number 
    FROM game_lineups gl
    JOIN player p ON gl.player_id = p.Player_id
    WHERE gl.game_id = ? AND gl.status = 'active'
    ORDER BY gl.batting_order ASC
");
$stmt->execute([$game_id]);
$active_lineup = $stmt->fetchAll();
$lineup_configured = (count($active_lineup) === 9);

$live_state = null;
$active_pitcher = null;
$pitcher_stats = null;
$current_batter = null;

if ($lineup_configured) {
    // 取得當前棒次狀態
    $stmt = $pdo->prepare("SELECT * FROM game_live_state WHERE game_id = ?");
    $stmt->execute([$game_id]);
    $live_state = $stmt->fetch();
    
    if (!$live_state) {
        $live_state = [
            'game_id' => $game_id,
            'current_batter_order' => 1,
            'our_score' => 0,
            'opponent_score' => 0,
            'inning' => 1,
            'is_top' => 1,
            'outs' => 0,
            'balls' => 0,
            'strikes' => 0,
            'our_hits' => 0,
            'opponent_hits' => 0,
            'our_errors' => 0,
            'opponent_errors' => 0,
            'runner_first' => 0,
            'runner_second' => 0,
            'runner_third' => 0,
        ];
    }
    
    // 當前打者
    $curr_order = $live_state['current_batter_order'];
    foreach ($active_lineup as $b) {
        if ($b['batting_order'] == $curr_order) {
            $current_batter = $b;
            break;
        }
    }
    
    // 取得當前投手
    $stmt = $pdo->prepare("
        SELECT gp.*, p.Player_Name, p.jersey_number 
        FROM game_pitchers gp
        JOIN player p ON gp.player_id = p.Player_id
        WHERE gp.game_id = ? AND gp.status = 'active'
    ");
    $stmt->execute([$game_id]);
    $active_pitcher = $stmt->fetch();
    
    if ($active_pitcher) {
        // 取得當前投手本場累積數據
        $stmt = $pdo->prepare("SELECT * FROM player_game_details WHERE game_id = ? AND player_id = ?");
        $stmt->execute([$game_id, $active_pitcher['player_id']]);
        $pitcher_stats = $stmt->fetch();
        if (!$pitcher_stats) {
            $pitcher_stats = [
                'pitches' => 0, 'innings' => '0', 'strikeouts' => 0, 'walks' => 0, 'earned_runs' => 0,
                'batters_faced' => 0, 'hits_allowed' => 0, 'runs_allowed' => 0, 'p_hr_allowed' => 0,
                'p_hit_by_pitch' => 0, 'wild_pitches' => 0, 'balks' => 0, 'p_go_outs' => 0, 'p_fo_outs' => 0,
                'win' => 0, 'loss' => 0, 'save' => 0, 'blown_save' => 0, 'hold' => 0, 'is_cg' => 0, 'is_sho' => 0,
                'strikes' => 0, 'balls' => 0, 'swings' => 0, 'first_pitch_swings' => 0, 'whiffs' => 0,
                'gb_count' => 0, 'ld_count' => 0, 'fb_count' => 0
            ];
        }
    }
}

// 守備位置定義
$positions_list = ['P', 'C', '1B', '2B', '3B', 'SS', 'LF', 'CF', 'RF', 'DH'];
$positions_map = [
    'P'  => 'P (投手)',
    'C'  => 'C (捕手)',
    '1B' => '1B (一壘手)',
    '2B' => '2B (二壘手)',
    '3B' => '3B (三壘手)',
    'SS' => 'SS (游擊手)',
    'LF' => 'LF (左外野手)',
    'CF' => 'CF (中外野手)',
    'RF' => 'RF (右外野手)',
    'DH' => 'DH (指定打擊)'
];

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
$is_game_ended = ($live_state && isset($live_state['is_ended']) && $live_state['is_ended'] == 1) ? 1 : 0;
?>

<div class="page-header">
    <h1>現場賽事登記系統</h1>
    <p>比賽：vs <?= htmlspecialchars($game['opponent']) ?> | 日期：<?= htmlspecialchars($game['game_date']) ?></p>
</div>

<section style="padding-bottom: 50px;">
    <div class="container">
        
        <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 20px;">
            <a href="admin_game_stats.php" class="admin-back-btn" style="margin-bottom:0;">
                <i class="fas fa-arrow-left"></i> 返回比賽列表
            </a>
            <?php if ($lineup_configured): ?>
                <div style="display:flex; gap:10px;">
                    <?php if ($is_game_ended): ?>
                        <form method="POST" onsubmit="return confirm('確定要重新開啟此比賽的即時登記嗎？');" style="margin:0;">
                            <input type="hidden" name="action" value="reopen_game">
                            <button type="submit" class="admin-action-btn" style="background:#fbbf24; color:#222; border-radius:6px; padding:10px 18px; font-weight:800;">
                                <i class="fas fa-undo"></i> 重新開啟賽事登記
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" onsubmit="return confirm('確定要重設本場的所有登記狀態與名單嗎？此操作無法還原。');" style="margin:0;">
                            <input type="hidden" name="action" value="reset_game">
                            <button type="submit" class="admin-action-btn" style="background:#dc3545; color:white; border-radius:6px; padding:10px 18px;">
                                <i class="fas fa-redo"></i> 重設先發名單
                            </button>
                        </form>
                        <form method="POST" onsubmit="return confirm('確定要結束比賽嗎？結束後數據將會正式更新至前台及球員控制台。');" style="margin:0;">
                            <input type="hidden" name="action" value="end_game">
                            <button type="submit" class="admin-action-btn" style="background:#10b981; color:white; border-radius:6px; padding:10px 18px; font-weight: 800;">
                                <i class="fas fa-flag-checkered"></i> 比賽結束
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($msg): ?>
            <div style="background: <?= $msgType === 'error' ? '#dc3545' : 'var(--primary)' ?>; color: white; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                <i class="fas fa-info-circle" style="margin-right: 8px;"></i> <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <?php if (!$lineup_configured): ?>
            <?php
            // 預設先發名單自動安排演算法 - 依球員基本資料的守備位置分配
            $default_selected_batters = []; // index 1 to 9 -> player_id
            $default_selected_positions = []; // index 1 to 9 -> position string (P, C, 1B, 2B, 3B, SS, LF, CF, RF, DH)
            $used_player_ids = [];
            $used_field_positions = [];

            // 定義每個棒次優先匹配的守備位置類別
            $order_requirements = [
                1 => '投手',
                2 => '捕手',
                3 => '內野手',
                4 => '內野手',
                5 => '內野手',
                6 => '內野手',
                7 => '外野手',
                8 => '外野手',
                9 => '外野手'
            ];

            // 內外野位置的細分候選值
            $infield_positions = ['1B', '2B', '3B', 'SS'];
            $outfield_positions = ['LF', 'CF', 'RF'];

            // 第一輪：嘗試精準匹配球員，同時給予最適合的守備位置
            for ($i = 1; $i <= 9; $i++) {
                $req = $order_requirements[$i];
                foreach ($players as $p) {
                    $p_id = $p['Player_id'];
                    if (in_array($p_id, $used_player_ids)) {
                        continue;
                    }
                    if ($p['position'] && strpos($p['position'], $req) !== false) {
                        $default_selected_batters[$i] = $p_id;
                        $used_player_ids[] = $p_id;
                        
                        // 分配具體守備位置
                        $assigned_pos = 'DH';
                        if ($req === '投手' && !in_array('P', $used_field_positions)) {
                            $assigned_pos = 'P';
                        } elseif ($req === '捕手' && !in_array('C', $used_field_positions)) {
                            $assigned_pos = 'C';
                        } elseif ($req === '內野手') {
                            foreach ($infield_positions as $ip) {
                                if (!in_array($ip, $used_field_positions)) {
                                    $assigned_pos = $ip;
                                    break;
                                }
                            }
                        } elseif ($req === '外野手') {
                            foreach ($outfield_positions as $op) {
                                if (!in_array($op, $used_field_positions)) {
                                    $assigned_pos = $op;
                                    break;
                                }
                            }
                        }
                        $default_selected_positions[$i] = $assigned_pos;
                        $used_field_positions[] = $assigned_pos;
                        break;
                    }
                }
            }

            // 第二輪：若有未排滿的棒次，用尚未被選取的剩餘球員填補
            for ($i = 1; $i <= 9; $i++) {
                if (!isset($default_selected_batters[$i])) {
                    foreach ($players as $p) {
                        $p_id = $p['Player_id'];
                        if (!in_array($p_id, $used_player_ids)) {
                            $default_selected_batters[$i] = $p_id;
                            $used_player_ids[] = $p_id;
                            
                            // 依據該球員的第一守位分配一個未被佔用的守位
                            $player_pos_str = $p['position'] ?: '';
                            $assigned_pos = 'DH';
                            if (strpos($player_pos_str, '投手') !== false && !in_array('P', $used_field_positions)) {
                                $assigned_pos = 'P';
                            } elseif (strpos($player_pos_str, '捕手') !== false && !in_array('C', $used_field_positions)) {
                                $assigned_pos = 'C';
                            } elseif (strpos($player_pos_str, '內野手') !== false) {
                                foreach ($infield_positions as $ip) {
                                    if (!in_array($ip, $used_field_positions)) {
                                        $assigned_pos = $ip;
                                        break;
                                    }
                                }
                            } elseif (strpos($player_pos_str, '外野手') !== false) {
                                foreach ($outfield_positions as $op) {
                                    if (!in_array($op, $used_field_positions)) {
                                        $assigned_pos = $op;
                                        break;
                                    }
                                }
                            }
                            
                            // 如果還是找不到未被佔用的特定位置，就隨便找一個沒被佔用的位置
                            if ($assigned_pos === 'DH' && in_array('DH', $used_field_positions)) {
                                $all_possible = ['P', 'C', '1B', '2B', '3B', 'SS', 'LF', 'CF', 'RF', 'DH'];
                                foreach ($all_possible as $ap) {
                                    if (!in_array($ap, $used_field_positions)) {
                                        $assigned_pos = $ap;
                                        break;
                                    }
                                }
                            }
                            
                            $default_selected_positions[$i] = $assigned_pos;
                            $used_field_positions[] = $assigned_pos;
                            break;
                        }
                    }
                }
            }

            // 第三輪：如果球員總數不足 9 人，直接用空值與未佔用位置填補
            for ($i = 1; $i <= 9; $i++) {
                if (!isset($default_selected_batters[$i])) {
                    $default_selected_batters[$i] = '';
                    $all_possible = ['P', 'C', '1B', '2B', '3B', 'SS', 'LF', 'CF', 'RF', 'DH'];
                    $assigned_pos = 'DH';
                    foreach ($all_possible as $ap) {
                        if (!in_array($ap, $used_field_positions)) {
                            $assigned_pos = $ap;
                            break;
                        }
                    }
                    $default_selected_positions[$i] = $assigned_pos;
                    $used_field_positions[] = $assigned_pos;
                }
            }
            ?>
            <!-- ── 設置先發名單與投手 ── -->
            <div class="admin-form-card" style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                <h3 style="margin-bottom: 20px; color: #333; border-bottom: 2px solid var(--primary); padding-bottom: 10px; display: inline-block;">
                    <i class="fas fa-users-cog" style="color:var(--primary); margin-right:8px;"></i> 設定本場比賽先發名單
                </h3>
                
                <!-- 錯誤訊息提示區 -->
                <div id="lineup-validation-msg" style="display:none; background:#fee2e2; border:1px solid #fecaca; color:#991b1b; padding:15px; border-radius:8px; margin-bottom:20px; font-weight:600; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                    <i class="fas fa-exclamation-triangle" style="margin-right:8px;"></i>
                    <span id="lineup-validation-text"></span>
                </div>

                <form method="POST" id="lineup-form">
                    <input type="hidden" name="action" value="setup_lineup">
                    
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px;">
                        <?php for ($i = 1; $i <= 9; $i++): ?>
                            <div style="background:#f8f9fa; border:1px solid #e2e8f0; padding:18px; border-radius:8px;">
                                <h4 style="margin:0 0 12px 0; color:var(--primary); font-size:1.05rem;">第 <?= $i ?> 棒</h4>
                                <div class="form-group" style="margin-bottom:10px;">
                                    <label style="display:block; margin-bottom:4px; font-size:0.85rem; color:#666;">選擇球員</label>
                                    <select name="batters[<?= $i ?>]" required style="width:100%; padding:8px; border-radius:6px; border:1px solid #cbd5e1;">
                                        <option value="">-- 請選擇球員 --</option>
                                        <?php foreach ($players as $p): ?>
                                            <option value="<?= $p['Player_id'] ?>" <?= ($default_selected_batters[$i] == $p['Player_id']) ? 'selected' : '' ?>>
                                                #<?= $p['jersey_number'] ? htmlspecialchars($p['jersey_number']) : '—' ?> - <?= htmlspecialchars($p['Player_Name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label style="display:block; margin-bottom:4px; font-size:0.85rem; color:#666;">守備位置</label>
                                    <select name="positions[<?= $i ?>]" required style="width:100%; padding:8px; border-radius:6px; border:1px solid #cbd5e1;">
                                        <?php 
                                        $curr_pos = $default_selected_positions[$i] ?? 'DH';
                                        ?>
                                        <?php foreach ($positions_list as $pos): ?>
                                            <option value="<?= $pos ?>" <?= ($pos === $curr_pos) ? 'selected' : '' ?>><?= htmlspecialchars($positions_map[$pos] ?? $pos) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div style="background:#edf2f7; border: 1px solid #cbd5e1; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                        <h4 style="margin:0 0 10px 0; color:#333;"><i class="fas fa-baseball-ball" style="color:var(--secondary);"></i> 指定先發投手</h4>
                        <div class="form-group" style="max-width: 400px;">
                            <select name="starting_pitcher" required style="width:100%; padding:10px; border-radius:6px; border:1px solid #cbd5e1;">
                                <option value="">-- 請選擇先發投手 --</option>
                                <?php foreach ($players as $p): ?>
                                    <option value="<?= $p['Player_id'] ?>" <?= ($default_selected_batters[1] == $p['Player_id']) ? 'selected' : '' ?>>
                                        #<?= $p['jersey_number'] ? htmlspecialchars($p['jersey_number']) : '—' ?> - <?= htmlspecialchars($p['Player_Name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" style="background:#333; color:white; border:none; padding:15px 30px; font-size:1.1rem; font-weight:700; border-radius:6px; cursor:pointer; width:100%;">
                        確認名單，進入記分登記
                    </button>
                </form>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('lineup-form');
                if (!form) return;

                const playersData = <?= json_encode($players) ?>;
                const playerPositions = {};
                playersData.forEach(p => {
                    playerPositions[p.Player_id] = p.position || '';
                });

                const batterSelects = Array.from(form.querySelectorAll('select[name^="batters["]'));
                const positionSelects = Array.from(form.querySelectorAll('select[name^="positions["]'));
                const pitcherSelect = form.querySelector('select[name="starting_pitcher"]');
                const errorBox = document.getElementById('lineup-validation-msg');
                const errorText = document.getElementById('lineup-validation-text');

                // 依據球員守備位置自動分配場上守備位置的對應
                function autoAssignPosition(batterSelect, positionSelect) {
                    const playerId = batterSelect.value;
                    if (!playerId) return;

                    const profilePosStr = playerPositions[playerId];
                    if (!profilePosStr) return;

                    const profilePositions = profilePosStr.split(',');
                    const currentFieldPos = positionSelect.value;

                    // 檢查當前選定的場上守位是否已符合球員基本資料的守位
                    let alreadyMatches = false;
                    profilePositions.forEach(pPos => {
                        if (pPos === '投手' && currentFieldPos === 'P') alreadyMatches = true;
                        if (pPos === '捕手' && currentFieldPos === 'C') alreadyMatches = true;
                        if (pPos === '內野手' && ['1B', '2B', '3B', 'SS'].includes(currentFieldPos)) alreadyMatches = true;
                        if (pPos === '外野手' && ['LF', 'CF', 'RF'].includes(currentFieldPos)) alreadyMatches = true;
                    });

                    if (alreadyMatches) {
                        return; // 已符合，不需要自動變更
                    }

                    // 依據球員的第一守位（最前面的那個）進行自動切換
                    const primaryPos = profilePositions[0];
                    if (primaryPos === '投手') {
                        positionSelect.value = 'P';
                    } else if (primaryPos === '捕手') {
                        positionSelect.value = 'C';
                    } else if (primaryPos === '內野手') {
                        // 儘量挑選一個未被其他棒次佔用的內野位置
                        const infields = ['1B', '2B', '3B', 'SS'];
                        let selected = '1B';
                        for (let ip of infields) {
                            let isUsed = false;
                            batterSelects.forEach((sel, idx) => {
                                if (sel !== batterSelect && positionSelects[idx].value === ip) {
                                    isUsed = true;
                                }
                            });
                            if (!isUsed) {
                                selected = ip;
                                break;
                            }
                        }
                        positionSelect.value = selected;
                    } else if (primaryPos === '外野手') {
                        // 儘量挑選一個未被其他棒次佔用的外野位置
                        const outfields = ['LF', 'CF', 'RF'];
                        let selected = 'LF';
                        for (let op of outfields) {
                            let isUsed = false;
                            batterSelects.forEach((sel, idx) => {
                                if (sel !== batterSelect && positionSelects[idx].value === op) {
                                    isUsed = true;
                                }
                            });
                            if (!isUsed) {
                                selected = op;
                                break;
                            }
                        }
                        positionSelect.value = selected;
                    } else {
                        positionSelect.value = 'DH';
                    }
                }

                // 檢查名單是否有任何問題
                function checkLineupValidity() {
                    let errors = [];
                    
                    // 1. 檢查是否有重複球員在 9 個棒次中
                    const batterValues = batterSelects.map(s => s.value).filter(val => val !== '');
                    const uniqueBatters = new Set(batterValues);
                    if (uniqueBatters.size < batterValues.length) {
                        errors.push("先發名單中不可有重複的球員登入！");
                    }

                    // 2. 檢查守備位置是否重複
                    const positionValues = positionSelects.map(s => s.value).filter(val => val !== '');
                    const uniquePositions = new Set(positionValues);
                    if (uniquePositions.size < positionValues.length) {
                        errors.push("先發名單的守備位置不可重複！");
                    }

                    // 3. DH 與投手的關係檢查
                    const hasDH = positionValues.includes('DH');
                    let pitcherOrderIndex = -1;
                    positionSelects.forEach((s, idx) => {
                        if (s.value === 'P') {
                            pitcherOrderIndex = idx;
                        }
                    });

                    const selectedPitcher = pitcherSelect.value;

                    if (hasDH) {
                        // 有 DH：先發投手不可在打線中
                        if (selectedPitcher && batterValues.includes(selectedPitcher)) {
                            errors.push("本場使用指定打擊 (DH)，先發投手不可同時出現在先發打席 (9人打線) 中！");
                        }
                        if (pitcherOrderIndex !== -1) {
                            errors.push("有指定打擊 (DH) 時，先發打者中不可有人擔任「P (投手)」位置！");
                        }
                    } else {
                        // 無 DH：打線中必須有一人擔任 P
                        if (pitcherOrderIndex === -1) {
                            errors.push("未使用指定打擊 (DH) 時，先發名單的 9 位打者中必須有一位守備位置為「P (投手)」！");
                        } else if (selectedPitcher) {
                            const batterAtP = batterSelects[pitcherOrderIndex].value;
                            if (batterAtP && batterAtP !== selectedPitcher) {
                                                    errors.push("先發投手必須與先發打者中擔任「P (投手)」位置的球員相同！");
                            }
                        }
                    }

                    return errors;
                }

                // 處理選擇變更時的即時視覺反饋
                function handleSelectChange() {
                    const batterValues = batterSelects.map(s => s.value);
                    
                    // 高亮重複球員選擇
                    batterSelects.forEach((select, idx) => {
                        const val = select.value;
                        if (val && batterValues.filter(v => v === val).length > 1) {
                            select.style.borderColor = '#ef4444';
                            select.style.backgroundColor = '#fef2f2';
                        } else {
                            select.style.borderColor = '#cbd5e1';
                            select.style.backgroundColor = '';
                        }
                    });

                    // 守備位置重複高亮
                    const posValues = positionSelects.map(s => s.value);
                    positionSelects.forEach((select, idx) => {
                        const val = select.value;
                        if (val && posValues.filter(v => v === val).length > 1) {
                            select.style.borderColor = '#ef4444';
                            select.style.backgroundColor = '#fef2f2';
                        } else {
                            select.style.borderColor = '#cbd5e1';
                            select.style.backgroundColor = '';
                        }
                    });

                    // 更新即時錯誤訊息顯示
                    const errors = checkLineupValidity();
                    if (errors.length > 0) {
                        errorBox.style.display = 'block';
                        errorText.innerHTML = errors.join('<br><i class="fas fa-exclamation-triangle" style="margin-right:8px; margin-top:5px;"></i> ');
                    } else {
                        errorBox.style.display = 'none';
                    }
                }

                // 綁定事件監聽器
                batterSelects.forEach((s, idx) => {
                    s.addEventListener('change', function() {
                        autoAssignPosition(s, positionSelects[idx]);
                        handleSelectChange();
                    });
                });

                positionSelects.forEach(s => {
                    s.addEventListener('change', handleSelectChange);
                });

                pitcherSelect.addEventListener('change', handleSelectChange);

                // 表單提交驗證
                form.addEventListener('submit', function(e) {
                    const errors = checkLineupValidity();
                    if (errors.length > 0) {
                        e.preventDefault();
                        alert("先發名單設定錯誤，請修正以下項目：\n\n- " + errors.join("\n- "));
                    }
                });

                // 頁面加載時執行一次
                handleSelectChange();
            });
            </script>
        <?php else: ?>
            <!-- ── 賽事即時登記主界面 (含 Stadium Scoreboard 與 3-Column Layout) ── -->
            <div style="position: relative; width: 100%;">
                <?php if ($is_game_ended): ?>
                    <div style="position: absolute; top:0; left:0; width:100%; height:100%; background:rgba(241,245,249,0.3); z-index:90; display:flex; flex-direction:column; align-items:center; justify-content:center; border-radius:12px; text-align:center; padding: 20px; box-sizing: border-box; backdrop-filter: blur(2.5px); pointer-events: all;">
                        <div style="background: white; border: 2px solid #dc3545; padding: 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); display: flex; flex-direction: column; align-items: center; max-width: 90%;">
                            <i class="fas fa-lock" style="font-size: 3rem; color: #dc3545; margin-bottom: 15px;"></i>
                            <span style="font-weight: 900; color: #1e293b; font-size: 1.5rem; margin-bottom: 8px;">比賽已宣告結束</span>
                            <span style="font-size: 0.95rem; color: #64748b;">(本場即時登記已鎖定，數據已同步至前台與個人控制台)</span>
                            <span style="font-size: 0.85rem; color: #94a3b8; margin-top: 15px;">如需修改數據，請點擊上方「重新開啟賽事登記」</span>
                        </div>
                    </div>
                <?php endif; ?>

            <?php
            $batting_first = isset($game['batting_first']) ? $game['batting_first'] : '後攻';
            $is_our_offense = false;
            if ($live_state) {
                if ($batting_first === '先攻') {
                    $is_our_offense = ((int)$live_state['is_top'] == 1);
                } else {
                    $is_our_offense = ((int)$live_state['is_top'] == 0);
                }
            }
            $is_opp_offense = $live_state && !$is_our_offense;
            
            $our_base_event_locked = !$is_our_offense;
            $opp_base_event_locked = $is_our_offense;
            $offense_locked = !$is_our_offense;
            $defense_locked = $is_our_offense;
            ?>
            
            <!-- 1. Stadium Scoreboard Component -->
            <div class="live-scoreboard-card" style="background: #0f172a; color: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); margin-bottom: 25px;">
                <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 20px;">
                    <!-- Left: Teams and Scores (R-H-E Table Style) -->
                    <div style="flex: 2; min-width: 320px;">
                        <table style="width: 100%; border-collapse: collapse; text-align: center; color: white; font-family: 'Outfit', sans-serif;">
                            <thead>
                                <tr style="border-bottom: 1px solid #334155; font-size: 0.8rem; color: #94a3b8; text-transform: uppercase;">
                                    <th style="text-align: left; padding: 8px 12px; font-weight: 600;">球隊</th>
                                    <th style="padding: 8px; font-weight: 600; width: 50px;">R (得分)</th>
                                    <th style="padding: 8px; font-weight: 600; width: 50px;">H (安打)</th>
                                    <th style="padding: 8px; font-weight: 600; width: 50px;">E (失誤)</th>
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
                                    <td style="font-size: 1.5rem; font-weight: 900; color: white; padding: 8px;"><?= $live_state ? $live_state['our_score'] : 0 ?></td>
                                    <td style="font-weight: 700; color: #cbd5e1; padding: 8px;"><?= $live_state ? $live_state['our_hits'] : 0 ?></td>
                                    <td style="font-weight: 700; color: #cbd5e1; padding: 8px;"><?= $live_state ? $live_state['our_errors'] : 0 ?></td>
                                </tr>
                                <tr style="font-size: 1.1rem;">
                                    <td style="text-align: left; padding: 12px; font-weight: 700; color: #94a3b8;">
                                        <?= htmlspecialchars($game['opponent']) ?> (對手)
                                        <?php if ($is_opp_offense): ?>
                                            <span style="display: inline-block; width: 8px; height: 8px; background: #ef4444; border-radius: 50%; margin-left: 6px;" title="進攻中"></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 1.5rem; font-weight: 900; color: white; padding: 8px;"><?= $live_state ? $live_state['opponent_score'] : 0 ?></td>
                                    <td style="font-weight: 700; color: #cbd5e1; padding: 8px;"><?= $live_state ? $live_state['opponent_hits'] : 0 ?></td>
                                    <td style="font-weight: 700; color: #cbd5e1; padding: 8px;"><?= $live_state ? $live_state['opponent_errors'] : 0 ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Center: Inning Indicator & B/S/O Lights -->
                    <div class="scoreboard-center-panel">
                        <div style="font-size: 1.6rem; font-weight: 900; color: #f8fafc; margin-bottom: 12px; letter-spacing: 1px;">
                            <?php 
                            if ($live_state) {
                                echo $live_state['inning'] . ' 局' . ($live_state['is_top'] ? '上' : '下');
                            } else {
                                echo '1 局上';
                            }
                            ?>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 8px; width: 100%; max-width: 160px;">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <span style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; width: 20px;">B</span>
                                <div style="display: flex; gap: 6px;">
                                    <?php for($i=1; $i<=3; $i++): 
                                        $active = ($live_state && $live_state['balls'] >= $i);
                                        $color = $active ? '#10b981' : '#334155';
                                        $shadow = $active ? 'box-shadow: 0 0 8px #10b981;' : '';
                                    ?>
                                        <span style="width: 12px; height: 12px; border-radius: 50%; background: <?= $color ?>; <?= $shadow ?> display: inline-block;"></span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <span style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; width: 20px;">S</span>
                                <div style="display: flex; gap: 6px;">
                                    <?php for($i=1; $i<=2; $i++): 
                                        $active = ($live_state && $live_state['strikes'] >= $i);
                                        $color = $active ? '#f59e0b' : '#334155';
                                        $shadow = $active ? 'box-shadow: 0 0 8px #f59e0b;' : '';
                                    ?>
                                        <span style="width: 12px; height: 12px; border-radius: 50%; background: <?= $color ?>; <?= $shadow ?> display: inline-block;"></span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <span style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; width: 20px;">O</span>
                                <div style="display: flex; gap: 6px;">
                                    <?php for($i=1; $i<=2; $i++): 
                                        $active = ($live_state && $live_state['outs'] >= $i);
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
                            <div id="base-2" style="position: absolute; top: 0; left: 42px; width: 16px; height: 16px; border: 2px solid #64748b; background: <?= ($live_state && $live_state['runner_second']) ? '#fbbf24' : '#1e293b' ?>; transform: rotate(45deg); cursor: pointer; transition: all 0.2s; <?= ($live_state && $live_state['runner_second']) ? 'box-shadow: 0 0 8px #fbbf24;' : '' ?>" title="二壘"></div>
                            <div id="base-3" style="position: absolute; top: 42px; left: 0; width: 16px; height: 16px; border: 2px solid #64748b; background: <?= ($live_state && $live_state['runner_third']) ? '#fbbf24' : '#1e293b' ?>; transform: rotate(45deg); cursor: pointer; transition: all 0.2s; <?= ($live_state && $live_state['runner_third']) ? 'box-shadow: 0 0 8px #fbbf24;' : '' ?>" title="三壘"></div>
                            <div id="base-1" style="position: absolute; top: 42px; right: 0; width: 16px; height: 16px; border: 2px solid #64748b; background: <?= ($live_state && $live_state['runner_first']) ? '#fbbf24' : '#1e293b' ?>; transform: rotate(45deg); cursor: pointer; transition: all 0.2s; <?= ($live_state && $live_state['runner_first']) ? 'box-shadow: 0 0 8px #fbbf24;' : '' ?>" title="一壘"></div>
                            <div style="position: absolute; bottom: 0; left: 44px; width: 12px; height: 12px; border: 2px solid #475569; background: #cbd5e1; transform: rotate(45deg); opacity: 0.6;"></div>
                        </div>
                        <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;">壘包狀態 (點擊可手動切換)</div>
                    </div>
                </div>

                <!-- Toggleable Manual Adjustments Panel -->
                <div style="margin-top: 15px; border-top: 1px solid #1e293b; padding-top: 15px;">
                    <details style="width: 100%;">
                        <summary style="cursor: pointer; color: #94a3b8; font-size: 0.85rem; font-weight: 700; user-select: none; outline: none;">
                            <i class="fas fa-cog"></i> 手動調整計分板狀態
                        </summary>
                        <form method="POST" style="margin-top: 15px; display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px;">
                            <input type="hidden" name="action" value="update_scoreboard">
                            
                            <div>
                                <label style="display:block; font-size:0.75rem; color:#94a3b8; margin-bottom:4px;">我方分數</label>
                                <input type="number" name="our_score" value="<?= $live_state ? $live_state['our_score'] : 0 ?>" min="0" style="width:100%; background:#1e293b; border:1px solid #334155; color:white; padding:6px; border-radius:4px; text-align:center;">
                            </div>
                            <div>
                                <label style="display:block; font-size:0.75rem; color:#94a3b8; margin-bottom:4px;">對手分數</label>
                                <input type="number" name="opponent_score" value="<?= $live_state ? $live_state['opponent_score'] : 0 ?>" min="0" style="width:100%; background:#1e293b; border:1px solid #334155; color:white; padding:6px; border-radius:4px; text-align:center;">
                            </div>
                            <div>
                                <label style="display:block; font-size:0.75rem; color:#94a3b8; margin-bottom:4px;">局數</label>
                                <input type="number" name="inning" value="<?= $live_state ? $live_state['inning'] : 1 ?>" min="1" style="width:100%; background:#1e293b; border:1px solid #334155; color:white; padding:6px; border-radius:4px; text-align:center;">
                            </div>
                            <div>
                                <label style="display:block; font-size:0.75rem; color:#94a3b8; margin-bottom:4px;">半局</label>
                                <select name="is_top" style="width:100%; background:#1e293b; border:1px solid #334155; color:white; padding:6px; border-radius:4px; text-align:center;">
                                    <option value="1" <?= ($live_state && $live_state['is_top'] == 1) ? 'selected' : '' ?>><?= ($batting_first === '先攻') ? '上半局 (我方攻)' : '上半局 (對手攻)' ?></option>
                                    <option value="0" <?= ($live_state && $live_state['is_top'] == 0) ? 'selected' : '' ?>><?= ($batting_first === '先攻') ? '下半局 (對手攻)' : '下半局 (我方攻)' ?></option>
                                </select>
                            </div>
                            <div>
                                <label style="display:block; font-size:0.75rem; color:#94a3b8; margin-bottom:4px;">出局數 (O)</label>
                                <input type="number" name="outs" value="<?= $live_state ? $live_state['outs'] : 0 ?>" min="0" max="2" style="width:100%; background:#1e293b; border:1px solid #334155; color:white; padding:6px; border-radius:4px; text-align:center;">
                            </div>
                            <div>
                                <label style="display:block; font-size:0.75rem; color:#94a3b8; margin-bottom:4px;">壞球數 (B)</label>
                                <input type="number" name="balls" value="<?= $live_state ? $live_state['balls'] : 0 ?>" min="0" max="3" style="width:100%; background:#1e293b; border:1px solid #334155; color:white; padding:6px; border-radius:4px; text-align:center;">
                            </div>
                            <div>
                                <label style="display:block; font-size:0.75rem; color:#94a3b8; margin-bottom:4px;">好球數 (S)</label>
                                <input type="number" name="strikes" value="<?= $live_state ? $live_state['strikes'] : 0 ?>" min="0" max="2" style="width:100%; background:#1e293b; border:1px solid #334155; color:white; padding:6px; border-radius:4px; text-align:center;">
                            </div>
                            <div>
                                <label style="display:block; font-size:0.75rem; color:#94a3b8; margin-bottom:4px;">我方安打</label>
                                <input type="number" name="our_hits" value="<?= $live_state ? $live_state['our_hits'] : 0 ?>" min="0" style="width:100%; background:#1e293b; border:1px solid #334155; color:white; padding:6px; border-radius:4px; text-align:center;">
                            </div>
                            <div>
                                <label style="display:block; font-size:0.75rem; color:#94a3b8; margin-bottom:4px;">對手安打</label>
                                <input type="number" name="opponent_hits" value="<?= $live_state ? $live_state['opponent_hits'] : 0 ?>" min="0" style="width:100%; background:#1e293b; border:1px solid #334155; color:white; padding:6px; border-radius:4px; text-align:center;">
                            </div>
                            <div>
                                <label style="display:block; font-size:0.75rem; color:#94a3b8; margin-bottom:4px;">我方失誤</label>
                                <input type="number" name="our_errors" value="<?= $live_state ? $live_state['our_errors'] : 0 ?>" min="0" style="width:100%; background:#1e293b; border:1px solid #334155; color:white; padding:6px; border-radius:4px; text-align:center;">
                            </div>
                            <div>
                                <label style="display:block; font-size:0.75rem; color:#94a3b8; margin-bottom:4px;">對手失誤</label>
                                <input type="number" name="opponent_errors" value="<?= $live_state ? $live_state['opponent_errors'] : 0 ?>" min="0" style="width:100%; background:#1e293b; border:1px solid #334155; color:white; padding:6px; border-radius:4px; text-align:center;">
                            </div>

                            <!-- Hidden inputs for runners toggles -->
                            <div style="display: none;">
                                <input type="checkbox" id="chk-runner-1" name="runner_first" <?= ($live_state && $live_state['runner_first']) ? 'checked' : '' ?>>
                                <input type="checkbox" id="chk-runner-2" name="runner_second" <?= ($live_state && $live_state['runner_second']) ? 'checked' : '' ?>>
                                <input type="checkbox" id="chk-runner-3" name="runner_third" <?= ($live_state && $live_state['runner_third']) ? 'checked' : '' ?>>
                            </div>

                            <div style="grid-column: 1 / -1; text-align: right; margin-top: 10px;">
                                <button type="submit" style="background: var(--secondary); color: #1a1a1a; border: none; padding: 8px 20px; font-weight: 700; border-radius: 4px; cursor: pointer; font-family: inherit;">
                                    更新計分板狀態
                                </button>
                            </div>
                        </form>
                    </details>
                </div>

                <!-- 登記跑壘與壘包事件 -->
                <div style="margin-top: 15px; border-top: 1px solid #1e293b; padding-top: 15px;">
                    <details style="width: 100%;" open>
                        <summary style="cursor: pointer; color: #fbbf24; font-size: 0.85rem; font-weight: 700; user-select: none; outline: none;">
                            <i class="fas fa-running"></i> 登記壘包跑壘事件 (盜壘 / 牽制 / 跑壘出局)
                        </summary>
                        
                        <div style="margin-top: 15px; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px;">
                            <!-- 我方跑壘事件 -->
                            <div style="position: relative; background: #1e293b; border: 1px solid #334155; padding: 15px; border-radius: 8px; opacity: <?= $our_base_event_locked ? '0.85' : '1' ?>;">
                                <?php if ($our_base_event_locked): ?>
                                    <div style="position: absolute; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.8); z-index:10; display:flex; flex-direction:column; align-items:center; justify-content:center; border-radius:8px; text-align:center; padding: 10px; box-sizing: border-box; backdrop-filter: blur(1.5px);">
                                        <i class="fas fa-lock" style="font-size: 1.2rem; color: #64748b; margin-bottom: 5px;"></i>
                                        <span style="font-weight: 700; color: #94a3b8; font-size: 0.8rem; margin-bottom: 3px;">目前為防守半局 (對手攻)</span>
                                        <span style="font-size: 0.7rem; color: #64748b;">(防守狀態中，無法登記我方跑壘)</span>
                                    </div>
                                <?php endif; ?>
                                <h4 style="margin: 0 0 10px 0; color: #fbbf24; font-size: 0.9rem; display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-user-friends"></i> 我方跑壘事件 (打擊/跑壘中)
                                </h4>
                                <form method="POST" style="display: flex; flex-direction: column; gap: 10px;">
                                    <input type="hidden" name="action" value="base_event">
                                    <input type="hidden" name="team" value="our">
                                    
                                    <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 8px;">
                                        <div>
                                            <label style="display: block; font-size: 0.7rem; color: #94a3b8; margin-bottom: 2px;">選擇跑者</label>
                                            <select name="player_id" required style="width: 100%; background: #0f172a; border: 1px solid #475569; color: white; padding: 6px; border-radius: 4px; font-size: 0.8rem; font-family: inherit;">
                                                <option value="">-- 選擇跑者 --</option>
                                                <?php foreach ($active_lineup as $b): ?>
                                                    <option value="<?= $b['player_id'] ?>"><?= $b['batting_order'] ?>棒 - <?= htmlspecialchars($b['Player_Name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label style="display: block; font-size: 0.7rem; color: #94a3b8; margin-bottom: 2px;">選擇事件與壘包影響</label>
                                            <select name="event_type" required style="width: 100%; background: #0f172a; border: 1px solid #475569; color: white; padding: 6px; border-radius: 4px; font-size: 0.8rem; font-family: inherit;">
                                                <option value="SB_1_2">盜二壘成功 (一壘 -> 二壘)</option>
                                                <option value="SB_2_3">盜三壘成功 (二壘 -> 三壘)</option>
                                                <option value="SB_3_H">盜本壘成功 (三壘 -> 得分)</option>
                                                <option value="CS_1_2">盜二壘失敗 (一壘出局 +1 Out)</option>
                                                <option value="CS_2_3">盜三壘失敗 (二壘出局 +1 Out)</option>
                                                <option value="PO_1">一壘牽制出局 (一壘出局 +1 Out)</option>
                                                <option value="PO_2">二壘牽制出局 (二壘出局 +1 Out)</option>
                                                <option value="PO_3">三壘牽制出局 (三壘出局 +1 Out)</option>
                                                <option value="OB_1">跑壘/牽制失誤進二壘 (一壘 -> 二壘)</option>
                                                <option value="OB_2">跑壘/牽制失誤進三壘 (二壘 -> 三壘)</option>
                                                <option value="OB_3">跑壘/牽制失誤回本壘 (三壘 -> 得分)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <button type="submit" style="background: #fbbf24; color: #1e293b; border: none; padding: 8px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 0.8rem; font-family: inherit; margin-top: 5px;">
                                        登記我方跑壘事件
                                    </button>
                                </form>
                            </div>

                            <!-- 對方跑壘事件 -->
                            <div style="position: relative; background: #1e293b; border: 1px solid #334155; padding: 15px; border-radius: 8px; opacity: <?= $opp_base_event_locked ? '0.85' : '1' ?>;">
                                <?php if ($opp_base_event_locked): ?>
                                    <div style="position: absolute; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.8); z-index:10; display:flex; flex-direction:column; align-items:center; justify-content:center; border-radius:8px; text-align:center; padding: 10px; box-sizing: border-box; backdrop-filter: blur(1.5px);">
                                        <i class="fas fa-lock" style="font-size: 1.2rem; color: #64748b; margin-bottom: 5px;"></i>
                                        <span style="font-weight: 700; color: #94a3b8; font-size: 0.8rem; margin-bottom: 3px;">目前為進攻半局 (我方攻)</span>
                                        <span style="font-size: 0.7rem; color: #64748b;">(進攻狀態中，無法登記對手跑壘)</span>
                                    </div>
                                <?php endif; ?>
                                <h4 style="margin: 0 0 10px 0; color: #ef4444; font-size: 0.9rem; display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-shield-alt"></i> 對方跑壘事件 (防守中)
                                </h4>
                                <form method="POST" style="display: flex; flex-direction: column; gap: 10px;">
                                    <input type="hidden" name="action" value="base_event">
                                    <input type="hidden" name="team" value="opponent">
                                    
                                    <div>
                                        <label style="display: block; font-size: 0.7rem; color: #94a3b8; margin-bottom: 2px;">選擇事件與壘包影響</label>
                                        <select name="event_type" required style="width: 100%; background: #0f172a; border: 1px solid #475569; color: white; padding: 6px; border-radius: 4px; font-size: 0.8rem; font-family: inherit;">
                                            <option value="SB_1_2">對手盜二壘成功 (對手一壘 -> 二壘)</option>
                                            <option value="SB_2_3">對手盜三壘成功 (對手二壘 -> 三壘)</option>
                                            <option value="SB_3_H">對手盜本壘成功 (對手三壘 -> 得分)</option>
                                            <option value="CS_1_2">對手盜二壘失敗 (對手一壘出局 +1 Out)</option>
                                            <option value="CS_2_3">對手盜三壘失敗 (對手二壘出局 +1 Out)</option>
                                            <option value="PO_1">對手一壘牽制出局 (對手一壘出局 +1 Out)</option>
                                            <option value="PO_2">對手二壘牽制出局 (對手二壘出局 +1 Out)</option>
                                            <option value="PO_3">對手三壘牽制出局 (對手三壘出局 +1 Out)</option>
                                            <option value="OB_1">對手一壘跑壘/牽制失誤進二壘 (對手一壘 -> 二壘)</option>
                                            <option value="OB_2">對手二壘跑壘/牽制失誤進三壘 (對手二壘 -> 三壘)</option>
                                            <option value="OB_3">對手三壘跑壘/牽制失誤回本壘 (對手三壘 -> 得分)</option>
                                        </select>
                                    </div>
                                    <button type="submit" style="background: #ef4444; color: white; border: none; padding: 8px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 0.8rem; font-family: inherit; margin-top: 5px;">
                                        登記對方跑壘事件
                                    </button>
                                </form>
                            </div>
                        </div>
                    </details>
                </div>
            </div>

            <!-- 2. Main 3-Column Grid Layout -->
            <div class="live-grid-container">
                
                <!-- 2.1 左側欄：打擊順序 -->
                <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; flex-direction: column; box-sizing: border-box; border: 1px solid #e2e8f0;">
                    <h3 style="margin-top:0; border-bottom: 2px solid #edf2f7; padding-bottom: 10px; color:#333; margin-bottom: 15px; display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-list-ol" style="color:var(--primary);"></i> 打擊順序
                    </h3>
                    <div style="display:flex; flex-direction:column; gap:10px; overflow-y: visible;">
                        <?php foreach ($active_lineup as $b): 
                            $is_current = ($b['batting_order'] == $curr_order);
                            $bg = $is_current ? 'background: #eff6ff; border: 2px solid #3b82f6; transform: scale(1.02);' : 'background: #f8f9fa; border: 1px solid #e2e8f0;';
                        ?>
                            <div style="padding:12px; border-radius:8px; display:flex; align-items:center; justify-content:space-between; transition: 0.2s; <?= $bg ?>">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span style="background: <?= $is_current ? '#3b82f6' : '#cbd5e1' ?>; color:white; width:24px; height:24px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-weight:bold; font-size:0.85rem;">
                                        <?= $b['batting_order'] ?>
                                    </span>
                                    <div>
                                        <div style="font-weight:700; color: #1e293b;"><?= htmlspecialchars($b['Player_Name']) ?></div>
                                        <span style="background:#e2e8f0; color:#475569; padding:2px 6px; border-radius:4px; font-size:0.75rem; font-weight:800;"><?= translatePosition($b['position']) ?></span>
                                        <span style="color:#64748b; font-size:0.8rem;">#<?= htmlspecialchars($b['jersey_number'] ?? '—') ?></span>
                                    </div>
                                </div>
                                <div style="display:flex; gap:5px;">
                                    <!-- 更換代打 -->
                                    <button onclick="openPinchBatterModal(<?= $b['batting_order'] ?>, <?= $b['player_id'] ?>, '<?= htmlspecialchars($b['Player_Name']) ?>', '<?= $b['position'] ?>')" class="admin-action-btn" style="padding: 4px 8px; font-size:0.75rem; background:#333; color:white;" title="代打">
                                        <i class="fas fa-exchange-alt"></i> 代打
                                    </button>
                                    <!-- 更換守位 -->
                                    <button onclick="openChangePositionModal(<?= $b['id'] ?>, '<?= htmlspecialchars($b['Player_Name']) ?>', '<?= $b['position'] ?>')" class="admin-action-btn" style="padding: 4px 8px; font-size:0.75rem; background:var(--secondary); color:#222;" title="位置">
                                        <i class="fas fa-shield-alt"></i> 位置
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 2.2 中間欄：我方進攻 (打擊登記) -->
                <div style="position: relative; background: <?= $offense_locked ? '#f8fafc' : 'white' ?>; border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; flex-direction: column; box-sizing: border-box; border: 1px solid #e2e8f0; opacity: <?= $offense_locked ? '0.85' : '1' ?>;">
                    <?php if ($offense_locked): ?>
                        <div style="position: absolute; top:0; left:0; width:100%; height:100%; background:rgba(241,245,249,0.7); z-index:10; display:flex; flex-direction:column; align-items:center; justify-content:center; border-radius:12px; text-align:center; padding: 20px; box-sizing: border-box; backdrop-filter: blur(1.5px);">
                            <div style="background: white; border: 1px solid #cbd5e1; padding: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); display: flex; flex-direction: column; align-items: center; max-width: 90%;">
                                <i class="fas fa-lock" style="font-size: 1.8rem; color: #94a3b8; margin-bottom: 10px;"></i>
                                <span style="font-weight: 800; color: #334155; font-size: 0.95rem; margin-bottom: 4px;">目前為對手進攻半局</span>
                                <span style="font-size: 0.8rem; color: #64748b;">(防守狀態中，無法登記我方打擊)</span>
                            </div>
                        </div>
                    <?php endif; ?>
                    <h3 style="margin-top:0; border-bottom: 2px solid var(--primary); padding-bottom: 10px; color:#333; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-baseball-bat-ball" style="color:var(--primary);"></i> 我方進攻 (打擊登記)
                    </h3>
                    
                    <!-- Current Batter Card -->
                    <div style="background:#eff6ff; border: 1px solid #bfdbfe; color:#1e3a8a; padding:12px 15px; border-radius:8px; margin-bottom:15px; display:flex; align-items:center; justify-content:space-between; box-sizing: border-box;">
                        <div>
                            <span style="font-size:0.75rem; opacity:0.9;">當前打者 (第 <?= $current_batter['batting_order'] ?> 棒)</span>
                            <h3 style="margin:0; font-size:1.25rem; font-weight:900; color: #1e3a8a;"><?= htmlspecialchars($current_batter['Player_Name']) ?> <span style="font-size:0.9rem; opacity:0.8;">#<?= htmlspecialchars($current_batter['jersey_number'] ?? '—') ?></span></h3>
                        </div>
                        <div style="text-align:right;">
                            <span style="background:#3b82f6; color:white; padding:4px 10px; border-radius:6px; font-weight:800; font-size:0.85rem;"><?= translatePosition($current_batter['position']) ?></span>
                        </div>
                    </div>

                    <form method="POST" id="offense-pa-form" style="flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between;">
                        <input type="hidden" name="action" value="record_pa">
                        <input type="hidden" name="recording_type" value="offense">
                        <input type="hidden" name="batter_id" value="<?= $current_batter['player_id'] ?>">
                        <input type="hidden" name="current_order" value="<?= $current_batter['batting_order'] ?>">
                        <input type="hidden" name="pitches_thrown" id="offense_pitches_thrown" value="0">

                        <div>
                            <!-- ── 對手投球與打者狀態登記 ── -->
                            <div style="background:#f8fafc; border: 1px solid #e2e8f0; padding:15px; border-radius:8px; margin-bottom:15px;">
                                <h4 style="margin-top:0; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; color: #334155; font-size: 0.95rem; font-weight: 700; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                                    <span><i class="fas fa-baseball-ball" style="color:var(--primary); margin-right:5px;"></i> 對手投球與打者狀態登記</span>
                                    <button type="button" onclick="resetOffensePitchCounter()" style="background:#e2e8f0; border:none; padding:3px 8px; border-radius:4px; font-size:0.75rem; font-weight:bold; color:#475569; cursor:pointer;">重設打席球數</button>
                                </h4>

                                <!-- B-S counts & total pitches display -->
                                <div style="display:flex; justify-content:space-around; align-items:center; background:white; padding:10px; border-radius:8px; border:1px solid #e2e8f0; margin-bottom:12px; text-align:center;">
                                    <div>
                                        <div style="font-size:0.7rem; color:#64748b; font-weight:bold; margin-bottom:4px;">BALL</div>
                                        <div id="offense-ball-dots" style="display:flex; gap:4px; justify-content:center;">
                                            <span class="off-ball-dot" style="width:10px; height:10px; border-radius:50%; border:2px solid #cbd5e1; display:inline-block;"></span>
                                            <span class="off-ball-dot" style="width:10px; height:10px; border-radius:50%; border:2px solid #cbd5e1; display:inline-block;"></span>
                                            <span class="off-ball-dot" style="width:10px; height:10px; border-radius:50%; border:2px solid #cbd5e1; display:inline-block;"></span>
                                        </div>
                                        <div id="offense-ball-text" style="font-size:1rem; font-weight:900; color:#10b981; margin-top:2px;">0</div>
                                    </div>
                                    
                                    <div style="width:1px; height:30px; background:#e2e8f0;"></div>
                                    
                                    <div>
                                        <div style="font-size:0.7rem; color:#64748b; font-weight:bold; margin-bottom:4px;">STRIKE</div>
                                        <div id="offense-strike-dots" style="display:flex; gap:4px; justify-content:center;">
                                            <span class="off-strike-dot" style="width:10px; height:10px; border-radius:50%; border:2px solid #cbd5e1; display:inline-block;"></span>
                                            <span class="off-strike-dot" style="width:10px; height:10px; border-radius:50%; border:2px solid #cbd5e1; display:inline-block;"></span>
                                        </div>
                                        <div id="offense-strike-text" style="font-size:1rem; font-weight:900; color:#f59e0b; margin-top:2px;">0</div>
                                    </div>

                                    <div style="width:1px; height:30px; background:#e2e8f0;"></div>

                                    <div>
                                        <div style="font-size:0.7rem; color:#64748b; font-weight:bold; margin-bottom:4px;">OUT</div>
                                        <div id="offense-out-dots" style="display:flex; gap:4px; justify-content:center;">
                                            <span class="off-out-dot" style="width:10px; height:10px; border-radius:50%; border:2px solid #cbd5e1; display:inline-block;"></span>
                                            <span class="off-out-dot" style="width:10px; height:10px; border-radius:50%; border:2px solid #cbd5e1; display:inline-block;"></span>
                                        </div>
                                        <div id="offense-out-text" style="font-size:1rem; font-weight:900; color:#ef4444; margin-top:2px;">0</div>
                                    </div>

                                    <div style="width:1px; height:30px; background:#e2e8f0;"></div>

                                    <div>
                                        <div style="font-size:0.7rem; color:#64748b; font-weight:bold; margin-bottom:4px;">此打席球數</div>
                                        <div id="offense-pa-pitches" style="font-size:1.1rem; font-weight:900; color:#1e293b;">0</div>
                                    </div>
                                </div>

                                <!-- Pitch count buttons -->
                                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:6px; margin-bottom:6px;">
                                    <button type="button" onclick="recordPitchOffense('strike')" style="padding:6px 2px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; font-weight:700; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:2px; font-family: inherit;">
                                        <span style="color:#f59e0b; font-size:0.85rem;"><i class="fas fa-circle"></i></span>
                                        <span style="font-size:0.7rem;">好球 (S)</span>
                                    </button>
                                    <button type="button" onclick="recordPitchOffense('ball')" style="padding:6px 2px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; font-weight:700; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:2px; font-family: inherit;">
                                        <span style="color:#10b981; font-size:0.85rem;"><i class="fas fa-circle"></i></span>
                                        <span style="font-size:0.7rem;">壞球 (B)</span>
                                    </button>
                                </div>

                                <!-- 進階投球特性快速記錄按鈕 -->
                                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:6px; margin-bottom:6px;">
                                    <button type="button" onclick="recordPitchOffense('foul')" style="padding:6px 2px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; font-weight:700; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:2px; font-family: inherit;">
                                        <span style="color:#64748b; font-size:0.85rem;"><i class="fas fa-redo"></i></span>
                                        <span style="font-size:0.7rem;">界外 (Foul)</span>
                                    </button>
                                    <button type="button" onclick="recordPitchOffense('whiff')" style="padding:6px 2px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; font-weight:700; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:2px; font-family: inherit;">
                                        <span style="color:#6366f1; font-size:0.85rem;"><i class="fas fa-wind"></i></span>
                                        <span style="font-size:0.7rem;">揮空 (Whiff)</span>
                                    </button>
                                    <button type="button" id="o-fps-toggle-btn" onclick="toggleOffenseFirstPitchSwing()" style="padding:6px 2px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; font-weight:700; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:2px; font-family: inherit; transition: 0.2s;">
                                        <span style="color:#ec4899; font-size:0.85rem;" id="o-fps-icon"><i class="far fa-star"></i></span>
                                        <span style="font-size:0.7rem;">首球揮棒</span>
                                    </button>
                                </div>
                                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:6px; margin-bottom:10px;">
                                    <button type="button" onclick="recordPitchOffense('gb')" style="padding:6px 2px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; font-weight:700; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:2px; font-family: inherit;">
                                        <span style="color:#8b5cf6; font-size:0.85rem;"><i class="fas fa-arrow-down"></i></span>
                                        <span style="font-size:0.7rem;">滾地球 (+1)</span>
                                    </button>
                                    <button type="button" onclick="recordPitchOffense('fb')" style="padding:6px 2px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; font-weight:700; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:2px; font-family: inherit;">
                                        <span style="color:#0ea5e9; font-size:0.85rem;"><i class="fas fa-arrow-up"></i></span>
                                        <span style="font-size:0.7rem;">高飛球 (+1)</span>
                                    </button>
                                    <button type="button" onclick="recordPitchOffense('ld')" style="padding:6px 2px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; font-weight:700; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:2px; font-family: inherit;">
                                        <span style="color:#14b8a6; font-size:0.85rem;"><i class="fas fa-arrow-right"></i></span>
                                        <span style="font-size:0.7rem;">平飛球 (+1)</span>
                                    </button>
                                </div>

                                <!-- Advanced faced pitching stats -->
                                <div style="margin-top: 10px; border-top: 1px dashed #cbd5e1; padding-top: 10px;">
                                    <details style="width: 100%;">
                                        <summary style="cursor: pointer; color: #64748b; font-size: 0.75rem; font-weight: bold; user-select: none;">顯示打者進階面對投球特性</summary>
                                        <div style="margin-top: 8px;">
                                            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:6px; margin-bottom:8px;">
                                                <div>
                                                    <label style="display:block; font-size:0.65rem; color:#64748b; margin-bottom:2px;">好球數</label>
                                                    <input type="number" name="strikes" id="o_strikes_input" value="0" min="0" style="width:100%; border:1px solid #cbd5e1; border-radius:4px; padding:4px; text-align:center;">
                                                </div>
                                                <div>
                                                    <label style="display:block; font-size:0.65rem; color:#64748b; margin-bottom:2px;">壞球數</label>
                                                    <input type="number" name="balls" id="o_balls_input" value="0" min="0" style="width:100%; border:1px solid #cbd5e1; border-radius:4px; padding:4px; text-align:center;">
                                                </div>
                                                <div>
                                                    <label style="display:block; font-size:0.65rem; color:#64748b; margin-bottom:2px;">揮棒數</label>
                                                    <input type="number" name="swings" id="o_swings_input" value="0" min="0" style="width:100%; border:1px solid #cbd5e1; border-radius:4px; padding:4px; text-align:center;">
                                                </div>
                                            </div>
                                            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:6px; margin-bottom:8px;">
                                                <div>
                                                    <label style="display:block; font-size:0.65rem; color:#64748b; margin-bottom:2px;">揮空數</label>
                                                    <input type="number" name="whiffs" id="o_whiffs_input" value="0" min="0" style="width:100%; border:1px solid #cbd5e1; border-radius:4px; padding:4px; text-align:center;">
                                                </div>
                                                <div>
                                                    <label style="display:block; font-size:0.65rem; color:#64748b; margin-bottom:2px;">滾地球</label>
                                                    <input type="number" name="gb_count" id="o_gb_input" value="0" min="0" style="width:100%; border:1px solid #cbd5e1; border-radius:4px; padding:4px; text-align:center;">
                                                </div>
                                                <div>
                                                    <label style="display:block; font-size:0.65rem; color:#64748b; margin-bottom:2px;">高飛球</label>
                                                    <input type="number" name="fb_count" id="o_fb_input" value="0" min="0" style="width:100%; border:1px solid #cbd5e1; border-radius:4px; padding:4px; text-align:center;">
                                                </div>
                                            </div>
                                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:6px;">
                                                <div>
                                                    <label style="display:block; font-size:0.65rem; color:#64748b; margin-bottom:2px;">平飛球</label>
                                                    <input type="number" name="ld_count" id="o_ld_input" value="0" min="0" style="width:100%; border:1px solid #cbd5e1; border-radius:4px; padding:4px; text-align:center;">
                                                </div>
                                                <div style="display:flex; flex-direction:column; justify-content:center;">
                                                    <label style="display:block; font-size:0.65rem; color:#64748b; margin-bottom:2px;">首球揮棒</label>
                                                    <div style="display:flex; gap:10px;">
                                                        <label style="font-size:0.75rem; font-weight:normal; cursor:pointer;"><input type="radio" name="first_pitch_swings" id="o_fps_yes" value="1">是</label>
                                                        <label style="font-size:0.75rem; font-weight:normal; cursor:pointer;"><input type="radio" name="first_pitch_swings" id="o_fps_no" value="0" checked>否</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </details>
                                </div>
                            </div>

                                <!-- ── 壘包上所有跑者 ── -->
                            <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:12px; border-radius:8px; margin-bottom:15px;">
                                <h4 style="margin:0 0 10px 0; font-size:0.85rem; color:#334155; font-weight:700; display:flex; align-items:center; gap:6px;">
                                    <i class="fas fa-running" style="color:var(--primary);"></i> 壘包上所有跑者
                                </h4>
                                <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:8px;">
                                    <div>
                                        <label style="display:block; font-size:0.7rem; color:#64748b; margin-bottom:3px; font-weight:600;">一壘跑者</label>
                                        <select name="runner_first_id" id="runner_first_select" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:6px; font-size:0.8rem; font-family:inherit;">
                                            <option value="">-- 無 --</option>
                                            <?php foreach ($active_lineup as $b): ?>
                                                <option value="<?= $b['player_id'] ?>" <?= ($live_state && $live_state['runner_first_id'] == $b['player_id']) ? 'selected' : '' ?>>
                                                    #<?= htmlspecialchars($b['jersey_number'] ?? '') ?> <?= htmlspecialchars($b['Player_Name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label style="display:block; font-size:0.7rem; color:#64748b; margin-bottom:3px; font-weight:600;">二壘跑者</label>
                                        <select name="runner_second_id" id="runner_second_select" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:6px; font-size:0.8rem; font-family:inherit;">
                                            <option value="">-- 無 --</option>
                                            <?php foreach ($active_lineup as $b): ?>
                                                <option value="<?= $b['player_id'] ?>" <?= ($live_state && $live_state['runner_second_id'] == $b['player_id']) ? 'selected' : '' ?>>
                                                    #<?= htmlspecialchars($b['jersey_number'] ?? '') ?> <?= htmlspecialchars($b['Player_Name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label style="display:block; font-size:0.7rem; color:#64748b; margin-bottom:3px; font-weight:600;">三壘跑者</label>
                                        <select name="runner_third_id" id="runner_third_select" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:6px; font-size:0.8rem; font-family:inherit;">
                                            <option value="">-- 無 --</option>
                                            <?php foreach ($active_lineup as $b): ?>
                                                <option value="<?= $b['player_id'] ?>" <?= ($live_state && $live_state['runner_third_id'] == $b['player_id']) ? 'selected' : '' ?>>
                                                    #<?= htmlspecialchars($b['jersey_number'] ?? '') ?> <?= htmlspecialchars($b['Player_Name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- 擊球品質/擊球狀態按鈕 -->
                            <h4 style="margin-top:15px; font-size:0.95rem; color:#475569; margin-bottom:12px; border-top:1px dashed #e2e8f0; padding-top:15px;">擊球狀態 (選填)</h4>
                            <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:10px; margin-bottom:15px;">
                                <input type="hidden" name="hard_hit" id="hard_hit_input" value="0">
                                <input type="hidden" name="soft_hit" id="soft_hit_input" value="0">
                                <button type="button" id="hard_hit_btn" onclick="toggleContactQuality('hard')" style="border: 1px solid #cbd5e1; padding: 10px; border-radius: 8px; cursor:pointer; font-weight:700; background:white; color:#475569; transition: all 0.25s ease; display:flex; flex-direction:column; align-items:center; justify-content:center; box-sizing:border-box;">
                                    <span style="font-size:0.95rem; display:flex; align-items:center; gap:4px; font-weight:800;">💪 強勁擊球</span>
                                    <span style="font-size:0.7rem; color:#64748b; font-weight:normal; margin-top:2px;">Hard Hit</span>
                                </button>
                                <button type="button" id="soft_hit_btn" onclick="toggleContactQuality('soft')" style="border: 1px solid #cbd5e1; padding: 10px; border-radius: 8px; cursor:pointer; font-weight:700; background:white; color:#475569; transition: all 0.25s ease; display:flex; flex-direction:column; align-items:center; justify-content:center; box-sizing:border-box;">
                                    <span style="font-size:0.95rem; display:flex; align-items:center; gap:4px; font-weight:800;">🍃 軟弱擊球</span>
                                    <span style="font-size:0.7rem; color:#64748b; font-weight:normal; margin-top:2px;">Soft Hit</span>
                                </button>
                            </div>

                            <h4 style="margin-top:0; font-size:0.95rem; color:#475569; margin-bottom:12px;">選擇打席結果</h4>
                            <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:6px; margin-bottom:15px;">
                                <?php 
                                $offense_pa_buttons = [
                                    '1B' => '一安', '2B' => '二安', '3B' => '三安', 'HR' => '全壘打',
                                    'K' => '三振', 'BB' => '保送', 'HBP' => '觸身', 'E' => '失誤上壘',
                                    'GO' => '滾地出局', 'FO' => '飛球出局', 'DP' => '雙殺打', 'FC' => '野選'
                                ];
                                foreach($offense_pa_buttons as $code => $label): ?>
                                    <label style="display:block; text-align:center; border: 1px solid #cbd5e1; padding: 6px 2px; border-radius: 6px; cursor:pointer; font-weight:700; transition:0.2s;" class="pa-label offense-pa-label">
                                        <input type="radio" name="pa_result" value="<?= $code ?>" required style="position: absolute; opacity: 0; width: 0; height: 0; pointer-events: none;" onchange="updateOffenseHighlights()">
                                        <div style="font-size: 0.9rem;"><?= $code ?></div>
                                        <div style="font-size: 0.7rem; color:#64748b; margin-top:2px; font-weight:normal;"><?= $label ?></div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- ── 跑者結果與打者額外數據 ── -->
                            <div style="margin-top: 15px; border-top: 1px dashed #e2e8f0; padding-top: 15px;">
                                <h4 style="margin-top:0; font-size:0.95rem; color:#475569; margin-bottom:10px;">壘包跑者動作設定</h4>
                                
                                <!-- 1B -->
                                <div id="base-1-events-block" style="margin-bottom:12px; display:none; background:#f0f7ff; padding:10px; border-radius:8px; border:1px solid #dbeafe;">
                                    <div style="font-size:0.8rem; font-weight:700; color:#1d4ed8; margin-bottom:6px; display:flex; align-items:center; gap:4px;">
                                        <span style="background:#3b82f6; color:white; width:18px; height:18px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:0.7rem;">1</span>
                                        一壘跑者 (<span id="runner_first_name_label"></span>) 結果：
                                    </div>
                                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(115px, 1fr)); gap:6px;">
                                        <label style="display:block; text-align:center; border: 1px solid #cbd5e1; padding: 6px 2px; border-radius: 6px; cursor:pointer; transition:0.2s;" class="runner-action-label">
                                            <input type="radio" name="runner_first_action" id="r1_action_stay" value="stay" checked style="display:none;" onchange="updateRunnerActionsHighlights()">
                                            <div style="font-size:0.75rem; font-weight:700;">安全留在原壘</div>
                                        </label>
                                        <label style="display:block; text-align:center; border: 1px solid #cbd5e1; padding: 6px 2px; border-radius: 6px; cursor:pointer; transition:0.2s;" class="runner-action-label">
                                            <input type="radio" name="runner_first_action" value="adv_2b" style="display:none;" onchange="updateRunnerActionsHighlights()">
                                            <div style="font-size:0.75rem; font-weight:700;">推進到二壘</div>
                                        </label>
                                        <label style="display:block; text-align:center; border: 1px solid #cbd5e1; padding: 6px 2px; border-radius: 6px; cursor:pointer; transition:0.2s;" class="runner-action-label">
                                            <input type="radio" name="runner_first_action" value="adv_3b" style="display:none;" onchange="updateRunnerActionsHighlights()">
                                            <div style="font-size:0.75rem; font-weight:700;">推進到三壘</div>
                                        </label>
                                        <label style="display:block; text-align:center; border: 1px solid #cbd5e1; padding: 6px 2px; border-radius: 6px; cursor:pointer; transition:0.2s;" class="runner-action-label">
                                            <input type="radio" name="runner_first_action" value="score" style="display:none;" onchange="updateRunnerActionsHighlights()">
                                            <div style="font-size:0.75rem; font-weight:700; color:#10b981;">回本壘得分</div>
                                            <div style="font-size:0.65rem; color:#64748b;">(打者打點+1)</div>
                                        </label>
                                        <label style="display:block; text-align:center; border: 1px solid #cbd5e1; padding: 6px 2px; border-radius: 6px; cursor:pointer; transition:0.2s;" class="runner-action-label">
                                            <input type="radio" name="runner_first_action" value="score_no_rbi" style="display:none;" onchange="updateRunnerActionsHighlights()">
                                            <div style="font-size:0.75rem; font-weight:700; color:#10b981;">得分(無打點)</div>
                                        </label>
                                        <label style="display:block; text-align:center; border: 1px solid #cbd5e1; padding: 6px 2px; border-radius: 6px; cursor:pointer; transition:0.2s;" class="runner-action-label">
                                            <input type="radio" name="runner_first_action" value="sb_2b" style="display:none;" onchange="updateRunnerActionsHighlights()">
                                            <div style="font-size:0.75rem; font-weight:700; color:#fbbf24;">盜二壘成功</div>
                                            <div style="font-size:0.65rem; color:#64748b;">(SB +1)</div>
                                        </label>
                                        <label style="display:block; text-align:center; border: 1px solid #cbd5e1; padding: 6px 2px; border-radius: 6px; cursor:pointer; transition:0.2s;" class="runner-action-label">
                                            <input type="radio" name="runner_first_action" value="out" style="display:none;" onchange="updateRunnerActionsHighlights()">
                                            <div style="font-size:0.75rem; font-weight:700; color:#ef4444;">跑壘出局</div>
                                        </label>
                                    </div>
                                </div>

                                <!-- 2B -->
                                <div id="base-2-events-block" style="margin-bottom:12px; display:none; background:#fff7ed; padding:10px; border-radius:8px; border:1px solid #ffedd5;">
                                    <div style="font-size:0.8rem; font-weight:700; color:#c2410c; margin-bottom:6px; display:flex; align-items:center; gap:4px;">
                                        <span style="background:#ea580c; color:white; width:18px; height:18px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:0.7rem;">2</span>
                                        二壘跑者 (<span id="runner_second_name_label"></span>) 結果：
                                    </div>
                                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(115px, 1fr)); gap:6px;">
                                        <label style="display:block; text-align:center; border: 1px solid #cbd5e1; padding: 6px 2px; border-radius: 6px; cursor:pointer; transition:0.2s;" class="runner-action-label">
                                            <input type="radio" name="runner_second_action" id="r2_action_stay" value="stay" checked style="display:none;" onchange="updateRunnerActionsHighlights()">
                                            <div style="font-size:0.75rem; font-weight:700;">安全留在原壘</div>
                                        </label>
                                        <label style="display:block; text-align:center; border: 1px solid #cbd5e1; padding: 6px 2px; border-radius: 6px; cursor:pointer; transition:0.2s;" class="runner-action-label">
                                            <input type="radio" name="runner_second_action" value="adv_3b" style="display:none;" onchange="updateRunnerActionsHighlights()">
                                            <div style="font-size:0.75rem; font-weight:700;">推進到三壘</div>
                                        </label>
                                        <label style="display:block; text-align:center; border: 1px solid #cbd5e1; padding: 6px 2px; border-radius: 6px; cursor:pointer; transition:0.2s;" class="runner-action-label">
                                            <input type="radio" name="runner_second_action" value="score" style="display:none;" onchange="updateRunnerActionsHighlights()">
                                            <div style="font-size:0.75rem; font-weight:700; color:#10b981;">回本壘得分</div>
                                            <div style="font-size:0.65rem; color:#64748b;">(打者打點+1)</div>
                                        </label>
                                        <label style="display:block; text-align:center; border: 1px solid #cbd5e1; padding: 6px 2px; border-radius: 6px; cursor:pointer; transition:0.2s;" class="runner-action-label">
                                            <input type="radio" name="runner_second_action" value="score_no_rbi" style="display:none;" onchange="updateRunnerActionsHighlights()">
                                            <div style="font-size:0.75rem; font-weight:700; color:#10b981;">得分(無打點)</div>
                                        </label>
                                        <label style="display:block; text-align:center; border: 1px solid #cbd5e1; padding: 6px 2px; border-radius: 6px; cursor:pointer; transition:0.2s;" class="runner-action-label">
                                            <input type="radio" name="runner_second_action" value="sb_3b" style="display:none;" onchange="updateRunnerActionsHighlights()">
                                            <div style="font-size:0.75rem; font-weight:700; color:#fbbf24;">盜三壘成功</div>
                                            <div style="font-size:0.65rem; color:#64748b;">(SB +1)</div>
                                        </label>
                                        <label style="display:block; text-align:center; border: 1px solid #cbd5e1; padding: 6px 2px; border-radius: 6px; cursor:pointer; transition:0.2s;" class="runner-action-label">
                                            <input type="radio" name="runner_second_action" value="out" style="display:none;" onchange="updateRunnerActionsHighlights()">
                                            <div style="font-size:0.75rem; font-weight:700; color:#ef4444;">跑壘出局</div>
                                        </label>
                                    </div>
                                </div>

                                <!-- 3B -->
                                <div id="base-3-events-block" style="margin-bottom:12px; display:none; background:#f0fdf4; padding:10px; border-radius:8px; border:1px solid #dcfce7;">
                                    <div style="font-size:0.8rem; font-weight:700; color:#15803d; margin-bottom:6px; display:flex; align-items:center; gap:4px;">
                                        <span style="background:#16a34a; color:white; width:18px; height:18px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:0.7rem;">3</span>
                                        三壘跑者 (<span id="runner_third_name_label"></span>) 結果：
                                    </div>
                                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(115px, 1fr)); gap:6px;">
                                        <label style="display:block; text-align:center; border: 1px solid #cbd5e1; padding: 6px 2px; border-radius: 6px; cursor:pointer; transition:0.2s;" class="runner-action-label">
                                            <input type="radio" name="runner_third_action" id="r3_action_stay" value="stay" checked style="display:none;" onchange="updateRunnerActionsHighlights()">
                                            <div style="font-size:0.75rem; font-weight:700;">安全留在原壘</div>
                                        </label>
                                        <label style="display:block; text-align:center; border: 1px solid #cbd5e1; padding: 6px 2px; border-radius: 6px; cursor:pointer; transition:0.2s;" class="runner-action-label">
                                            <input type="radio" name="runner_third_action" value="score" style="display:none;" onchange="updateRunnerActionsHighlights()">
                                            <div style="font-size:0.75rem; font-weight:700; color:#10b981;">回本壘得分</div>
                                            <div style="font-size:0.65rem; color:#64748b;">(打者打點+1)</div>
                                        </label>
                                        <label style="display:block; text-align:center; border: 1px solid #cbd5e1; padding: 6px 2px; border-radius: 6px; cursor:pointer; transition:0.2s;" class="runner-action-label">
                                            <input type="radio" name="runner_third_action" value="score_no_rbi" style="display:none;" onchange="updateRunnerActionsHighlights()">
                                            <div style="font-size:0.75rem; font-weight:700; color:#10b981;">得分(無打點)</div>
                                        </label>
                                        <label style="display:block; text-align:center; border: 1px solid #cbd5e1; padding: 6px 2px; border-radius: 6px; cursor:pointer; transition:0.2s;" class="runner-action-label">
                                            <input type="radio" name="runner_third_action" value="sb_h" style="display:none;" onchange="updateRunnerActionsHighlights()">
                                            <div style="font-size:0.75rem; font-weight:700; color:#fbbf24;">盜本壘成功</div>
                                            <div style="font-size:0.65rem; color:#64748b;">(SB +1)</div>
                                        </label>
                                        <label style="display:block; text-align:center; border: 1px solid #cbd5e1; padding: 6px 2px; border-radius: 6px; cursor:pointer; transition:0.2s;" class="runner-action-label">
                                            <input type="radio" name="runner_third_action" value="out" style="display:none;" onchange="updateRunnerActionsHighlights()">
                                            <div style="font-size:0.75rem; font-weight:700; color:#ef4444;">跑壘出局</div>
                                        </label>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div style="margin-top: 15px; border-top: 1px dashed #cbd5e1; padding-top: 15px;">
                            <label style="display:block; font-size:0.8rem; color:#475569; font-weight:bold; margin-bottom:4px;">打席描述 / 備註</label>
                            <input type="text" name="play_desc" placeholder="例如：打出左外野深遠安打，送回跑者" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px; font-size:0.85rem; font-family:inherit; box-sizing:border-box;">
                        </div>

                        <button type="submit" style="background:var(--primary); color:white; border:none; padding:14px; font-size:1.1rem; font-weight:700; border-radius:6px; cursor:pointer; width:100%; box-shadow: 0 4px 10px rgba(200,0,0,0.15); margin-top: 20px; font-family: inherit;">
                            確認登記我方打席
                        </button>
                    </form>
                </div>

                <!-- 2.3 右側欄：我方防守 (對手打席 & 投手管理) -->
                <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; flex-direction: column; box-sizing: border-box; border: 1px solid #e2e8f0; gap: 20px;">
                    
                    <!-- 2.3.1 當前投手資訊 -->
                    <div style="background: #1e293b; color: white; padding: 15px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <span style="font-size:0.75rem; opacity:0.8; font-weight:600;">ACTIVE PITCHER (現役投手)</span>
                            <h4 style="margin:5px 0 0 0; font-size:1.2rem; font-weight:900; color: white;"><?= $active_pitcher ? htmlspecialchars($active_pitcher['Player_Name']) : '未指定' ?></h4>
                            <span style="font-size:0.8rem; opacity:0.7;">背號：#<?= $active_pitcher ? htmlspecialchars($active_pitcher['jersey_number'] ?? '—') : '—' ?></span>
                        </div>
                        <?php if ($active_pitcher): ?>
                            <button type="button" onclick="openPinchPitcherModal(<?= $active_pitcher['player_id'] ?>, '<?= htmlspecialchars($active_pitcher['Player_Name']) ?>')" class="admin-action-btn" style="background:#dc3545; color:white; border-radius:6px; padding:6px 12px; font-family: inherit; font-size:0.85rem; font-weight:700; margin-right:0;">
                                <i class="fas fa-exchange-alt"></i> 換投
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if ($active_pitcher): ?>
                        <div style="position: relative; display: flex; flex-direction: column; gap: 20px; opacity: <?= $defense_locked ? '0.85' : '1' ?>;">
                            <?php if ($defense_locked): ?>
                                <div style="position: absolute; top:0; left:0; width:100%; height:100%; background:rgba(241,245,249,0.7); z-index:10; display:flex; flex-direction:column; align-items:center; justify-content:center; border-radius:12px; text-align:center; padding: 20px; box-sizing: border-box; backdrop-filter: blur(1.5px);">
                                    <div style="background: white; border: 1px solid #cbd5e1; padding: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); display: flex; flex-direction: column; align-items: center; max-width: 90%;">
                                        <i class="fas fa-lock" style="font-size: 1.8rem; color: #94a3b8; margin-bottom: 10px;"></i>
                                        <span style="font-weight: 800; color: #334155; font-size: 0.95rem; margin-bottom: 4px;">目前為我方進攻半局</span>
                                        <span style="font-size: 0.8rem; color: #64748b;">(進攻狀態中，無法登記我方投球)</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <!-- 2.3.2 對手打席登記 Form -->
                            <div style="background:#f8fafc; border: 1px solid #e2e8f0; padding:15px; border-radius:8px;">
                            <h4 style="margin-top:0; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; color: #334155; font-size: 0.95rem; font-weight: 700; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                                <span><i class="fas fa-baseball-ball" style="color:var(--secondary); margin-right:5px;"></i> 對手打席即時登記</span>
                                <button type="button" onclick="resetPitchCounter()" style="background:#e2e8f0; border:none; padding:3px 8px; border-radius:4px; font-size:0.75rem; font-weight:bold; color:#475569; cursor:pointer;">重設打席</button>
                            </h4>

                            <form method="POST" id="defense-pa-form">
                                <input type="hidden" name="action" value="record_pa">
                                <input type="hidden" name="recording_type" value="defense">
                                <input type="hidden" name="batter_id" value="0">
                                <input type="hidden" name="current_order" value="0">
                                
                                <!-- B-S counts & total pitches display -->
                                <div style="display:flex; justify-content:space-around; align-items:center; background:white; padding:10px; border-radius:8px; border:1px solid #e2e8f0; margin-bottom:12px; text-align:center;">
                                    <div>
                                        <div style="font-size:0.7rem; color:#64748b; font-weight:bold; margin-bottom:4px;">BALL</div>
                                        <div id="defense-ball-dots" style="display:flex; gap:4px; justify-content:center;">
                                            <span class="def-ball-dot" style="width:10px; height:10px; border-radius:50%; border:2px solid #cbd5e1; display:inline-block;"></span>
                                            <span class="def-ball-dot" style="width:10px; height:10px; border-radius:50%; border:2px solid #cbd5e1; display:inline-block;"></span>
                                            <span class="def-ball-dot" style="width:10px; height:10px; border-radius:50%; border:2px solid #cbd5e1; display:inline-block;"></span>
                                        </div>
                                        <div id="defense-ball-text" style="font-size:1rem; font-weight:900; color:#10b981; margin-top:2px;">0</div>
                                    </div>
                                    
                                    <div style="width:1px; height:30px; background:#e2e8f0;"></div>
                                    
                                    <div>
                                        <div style="font-size:0.7rem; color:#64748b; font-weight:bold; margin-bottom:4px;">STRIKE</div>
                                        <div id="defense-strike-dots" style="display:flex; gap:4px; justify-content:center;">
                                            <span class="def-strike-dot" style="width:10px; height:10px; border-radius:50%; border:2px solid #cbd5e1; display:inline-block;"></span>
                                            <span class="def-strike-dot" style="width:10px; height:10px; border-radius:50%; border:2px solid #cbd5e1; display:inline-block;"></span>
                                        </div>
                                        <div id="defense-strike-text" style="font-size:1rem; font-weight:900; color:#f59e0b; margin-top:2px;">0</div>
                                    </div>

                                    <div style="width:1px; height:30px; background:#e2e8f0;"></div>

                                    <div>
                                        <div style="font-size:0.7rem; color:#64748b; font-weight:bold; margin-bottom:4px;">OUT</div>
                                        <div id="defense-out-dots" style="display:flex; gap:4px; justify-content:center;">
                                            <span class="def-out-dot" style="width:10px; height:10px; border-radius:50%; border:2px solid #cbd5e1; display:inline-block;"></span>
                                            <span class="def-out-dot" style="width:10px; height:10px; border-radius:50%; border:2px solid #cbd5e1; display:inline-block;"></span>
                                        </div>
                                        <div id="defense-out-text" style="font-size:1rem; font-weight:900; color:#ef4444; margin-top:2px;">0</div>
                                    </div>

                                    <div style="width:1px; height:30px; background:#e2e8f0;"></div>

                                    <div>
                                        <div style="font-size:0.7rem; color:#64748b; font-weight:bold; margin-bottom:4px;">本場累計投球</div>
                                        <div style="font-size:1.1rem; font-weight:900; color:#1e293b;" id="defense-total-pitches">
                                            <?= $pitcher_stats ? $pitcher_stats['pitches'] : 0 ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pitch count buttons -->
                                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:6px; margin-bottom:6px;">
                                    <button type="button" onclick="recordPitchDefense('strike')" style="padding:6px 2px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; font-weight:700; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:2px; font-family: inherit;">
                                        <span style="color:#f59e0b; font-size:0.85rem;"><i class="fas fa-circle"></i></span>
                                        <span style="font-size:0.7rem;">好球 (S)</span>
                                    </button>
                                    <button type="button" onclick="recordPitchDefense('ball')" style="padding:6px 2px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; font-weight:700; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:2px; font-family: inherit;">
                                        <span style="color:#10b981; font-size:0.85rem;"><i class="fas fa-circle"></i></span>
                                        <span style="font-size:0.7rem;">壞球 (B)</span>
                                    </button>
                                </div>

                                <!-- 進階投球特性快速記錄按鈕 -->
                                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:6px; margin-bottom:6px;">
                                    <button type="button" onclick="recordPitchDefense('foul')" style="padding:6px 2px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; font-weight:700; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:2px; font-family: inherit;">
                                        <span style="color:#64748b; font-size:0.85rem;"><i class="fas fa-redo"></i></span>
                                        <span style="font-size:0.7rem;">界外 (Foul)</span>
                                    </button>
                                    <button type="button" onclick="recordPitchDefense('whiff')" style="padding:6px 2px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; font-weight:700; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:2px; font-family: inherit;">
                                        <span style="color:#6366f1; font-size:0.85rem;"><i class="fas fa-wind"></i></span>
                                        <span style="font-size:0.7rem;">揮空 (Whiff)</span>
                                    </button>
                                    <button type="button" id="fps-toggle-btn" onclick="toggleFirstPitchSwing()" style="padding:6px 2px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; font-weight:700; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:2px; font-family: inherit; transition: 0.2s;">
                                        <span style="color:#ec4899; font-size:0.85rem;" id="fps-icon"><i class="far fa-star"></i></span>
                                        <span style="font-size:0.7rem;">首球揮棒</span>
                                    </button>
                                </div>
                                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:6px; margin-bottom:10px;">
                                    <button type="button" onclick="recordPitchDefense('gb')" style="padding:6px 2px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; font-weight:700; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:2px; font-family: inherit;">
                                        <span style="color:#8b5cf6; font-size:0.85rem;"><i class="fas fa-arrow-down"></i></span>
                                        <span style="font-size:0.7rem;">滾地球 (+1)</span>
                                    </button>
                                    <button type="button" onclick="recordPitchDefense('fb')" style="padding:6px 2px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; font-weight:700; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:2px; font-family: inherit;">
                                        <span style="color:#0ea5e9; font-size:0.85rem;"><i class="fas fa-arrow-up"></i></span>
                                        <span style="font-size:0.7rem;">高飛球 (+1)</span>
                                    </button>
                                    <button type="button" onclick="recordPitchDefense('ld')" style="padding:6px 2px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; font-weight:700; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:2px; font-family: inherit;">
                                        <span style="color:#14b8a6; font-size:0.85rem;"><i class="fas fa-arrow-right"></i></span>
                                        <span style="font-size:0.7rem;">平飛球 (+1)</span>
                                    </button>
                                </div>

                                <div style="font-size:0.75rem; color:#64748b; margin-bottom:12px; display:flex; justify-content:space-between;">
                                    <span>此打席投球數：<strong id="defense-pa-pitches" style="color:#1e293b;">0</strong> 球</span>
                                    <span id="defense-wp-balk-label" style="color:#ef4444; font-weight:bold; display:none;"></span>
                                </div>

                                <!-- Hidden pitch counts to post -->
                                <input type="hidden" name="pitches_thrown" id="defense_pitches_thrown" value="0">
                                <input type="hidden" name="wild_pitches" id="defense_wild_pitches" value="0">
                                <input type="hidden" name="balks" id="defense_balks" value="0">

                                <h5 style="margin:10px 0 6px 0; color:#475569; font-size:0.85rem; font-weight:700;">選擇對手打席結果</h5>
                                <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:6px; margin-bottom:12px;">
                                    <?php foreach($offense_pa_buttons as $code => $label): ?>
                                        <label style="display:block; text-align:center; border: 1px solid #cbd5e1; padding: 6px 2px; border-radius: 6px; cursor:pointer; font-weight:700; transition:0.2s;" class="pa-label defense-pa-label">
                                            <input type="radio" name="pa_result" value="<?= $code ?>" required style="position: absolute; opacity: 0; width: 0; height: 0; pointer-events: none;" onchange="updateDefenseHighlights()">
                                            <div style="font-size: 0.85rem;"><?= $code ?></div>
                                            <div style="font-size: 0.65rem; color:#64748b; font-weight:normal;"><?= $label ?></div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Pitching details and runs/earned runs allowed -->
                                <div style="margin-top: 10px; border-top: 1px dashed #cbd5e1; padding-top: 10px;">
                                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px; margin-bottom:8px;">
                                        <div>
                                            <label style="display:block; font-size:0.7rem; color:#64748b; font-weight:bold; margin-bottom:4px;">失分 (Runs Allowed)</label>
                                            <input type="number" name="p_runs_allowed" id="d_runs_allowed_input" value="0" min="0" style="width:100%; border:1px solid #cbd5e1; border-radius:4px; padding:4px; text-align:center; font-family:inherit;">
                                        </div>
                                        <div>
                                            <label style="display:block; font-size:0.7rem; color:#64748b; font-weight:bold; margin-bottom:4px;">責失 (Earned Runs)</label>
                                            <input type="number" name="p_earned_runs" id="d_earned_runs_input" value="0" min="0" style="width:100%; border:1px solid #cbd5e1; border-radius:4px; padding:4px; text-align:center; font-family:inherit;">
                                        </div>
                                    </div>

                                    <details style="width: 100%;">
                                        <summary style="cursor: pointer; color: #64748b; font-size: 0.75rem; font-weight: bold; user-select: none;">顯示進階投球特性</summary>
                                        <div style="margin-top: 8px;">
                                            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:6px; margin-bottom:8px;">
                                                <div>
                                                    <label style="display:block; font-size:0.65rem; color:#64748b; margin-bottom:2px;">好球數</label>
                                                    <input type="number" name="p_strikes" id="d_strikes_input" value="0" min="0" style="width:100%; border:1px solid #cbd5e1; border-radius:4px; padding:4px; text-align:center;">
                                                </div>
                                                <div>
                                                    <label style="display:block; font-size:0.65rem; color:#64748b; margin-bottom:2px;">壞球數</label>
                                                    <input type="number" name="p_balls" id="d_balls_input" value="0" min="0" style="width:100%; border:1px solid #cbd5e1; border-radius:4px; padding:4px; text-align:center;">
                                                </div>
                                                <div>
                                                    <label style="display:block; font-size:0.65rem; color:#64748b; margin-bottom:2px;">揮棒數</label>
                                                    <input type="number" name="p_swings" id="d_swings_input" value="0" min="0" style="width:100%; border:1px solid #cbd5e1; border-radius:4px; padding:4px; text-align:center;">
                                                </div>
                                            </div>
                                            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:6px; margin-bottom:8px;">
                                                <div>
                                                    <label style="display:block; font-size:0.65rem; color:#64748b; margin-bottom:2px;">揮空數</label>
                                                    <input type="number" name="p_whiffs" id="d_whiffs_input" value="0" min="0" style="width:100%; border:1px solid #cbd5e1; border-radius:4px; padding:4px; text-align:center;">
                                                </div>
                                                <div>
                                                    <label style="display:block; font-size:0.65rem; color:#64748b; margin-bottom:2px;">滾地球</label>
                                                    <input type="number" name="p_gb_count" id="d_gb_input" value="0" min="0" style="width:100%; border:1px solid #cbd5e1; border-radius:4px; padding:4px; text-align:center;">
                                                </div>
                                                <div>
                                                    <label style="display:block; font-size:0.65rem; color:#64748b; margin-bottom:2px;">高飛球</label>
                                                    <input type="number" name="p_fb_count" id="d_fb_input" value="0" min="0" style="width:100%; border:1px solid #cbd5e1; border-radius:4px; padding:4px; text-align:center;">
                                                </div>
                                            </div>
                                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:6px;">
                                                <div>
                                                    <label style="display:block; font-size:0.65rem; color:#64748b; margin-bottom:2px;">平飛球</label>
                                                    <input type="number" name="p_ld_count" id="d_ld_input" value="0" min="0" style="width:100%; border:1px solid #cbd5e1; border-radius:4px; padding:4px; text-align:center;">
                                                </div>
                                                <div style="display:flex; flex-direction:column; justify-content:center;">
                                                    <label style="display:block; font-size:0.65rem; color:#64748b; margin-bottom:2px;">首球揮棒</label>
                                                    <div style="display:flex; gap:10px;">
                                                        <label style="font-size:0.75rem; font-weight:normal; cursor:pointer;"><input type="radio" name="p_first_pitch_swing" value="1">是</label>
                                                        <label style="font-size:0.75rem; font-weight:normal; cursor:pointer;"><input type="radio" name="p_first_pitch_swing" value="0" checked>否</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </details>
                                </div>

                                <div style="margin-top: 15px; border-top: 1px dashed #cbd5e1; padding-top: 15px;">
                                    <label style="display:block; font-size:0.8rem; color:#475569; font-weight:bold; margin-bottom:4px;">對手打席描述 / 備註</label>
                                    <input type="text" name="play_desc" placeholder="例如：打出平飛球被二壘手接殺" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px; font-size:0.85rem; font-family:inherit; box-sizing:border-box;">
                                </div>

                                <button type="submit" style="background:#1e293b; color:white; border:none; padding:12px; font-size:1rem; font-weight:700; border-radius:6px; cursor:pointer; width:100%; box-shadow: 0 4px 8px rgba(0,0,0,0.15); margin-top: 15px; font-family: inherit;">
                                    確認登記對手打席 (我方防守)
                                </button>
                            </form>
                        </div>

                        <!-- 2.3.3 投手累計數據 & 快速按鈕 -->
                        <div style="background:#f8fafc; border: 1px solid #e2e8f0; padding:15px; border-radius:8px;">
                            <h4 style="margin:0 0 10px 0; color:#334155; font-size:0.95rem; font-weight:700; border-bottom:1px solid #e2e8f0; padding-bottom:8px;">
                                <i class="fas fa-edit" style="color:var(--secondary); margin-right:5px;"></i> 投手累計數據與快速調整
                            </h4>
                            
                            <!-- Quick Actions Grid -->
                            <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:6px; margin-bottom:15px;">
                                <!-- SO -->
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="pitcher_quick">
                                    <input type="hidden" name="pitcher_id" value="<?= $active_pitcher['player_id'] ?>">
                                    <input type="hidden" name="stat" value="strikeouts">
                                    <input type="hidden" name="diff" value="1">
                                    <button type="submit" style="width:100%; padding:6px; background:#fff; border:1px solid #cbd5e1; border-radius:4px; font-weight:700; cursor:pointer; font-size:0.75rem; font-family: inherit;">
                                        +1 三振
                                    </button>
                                </form>
                                <!-- BB -->
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="pitcher_quick">
                                    <input type="hidden" name="pitcher_id" value="<?= $active_pitcher['player_id'] ?>">
                                    <input type="hidden" name="stat" value="walks">
                                    <input type="hidden" name="diff" value="1">
                                    <button type="submit" style="width:100%; padding:6px; background:#fff; border:1px solid #cbd5e1; border-radius:4px; font-weight:700; cursor:pointer; font-size:0.75rem; font-family: inherit;">
                                        +1 保送
                                    </button>
                                </form>
                                <!-- ER -->
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="pitcher_quick">
                                    <input type="hidden" name="pitcher_id" value="<?= $active_pitcher['player_id'] ?>">
                                    <input type="hidden" name="stat" value="earned_runs">
                                    <input type="hidden" name="diff" value="1">
                                    <button type="submit" style="width:100%; padding:6px; background:#fff; border:1px solid #cbd5e1; border-radius:4px; font-weight:700; cursor:pointer; font-size:0.75rem; font-family: inherit;">
                                        +1 責失
                                    </button>
                                </form>
                                <!-- Innings +1/3 -->
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="pitcher_quick">
                                    <input type="hidden" name="pitcher_id" value="<?= $active_pitcher['player_id'] ?>">
                                    <input type="hidden" name="stat" value="innings">
                                    <input type="hidden" name="diff" value="0.1">
                                    <button type="submit" style="width:100%; padding:6px; background:#fff; border:1px solid #cbd5e1; border-radius:4px; font-weight:700; cursor:pointer; font-size:0.75rem; font-family: inherit;">
                                        +1/3局
                                    </button>
                                </form>
                                <!-- Innings -1/3 -->
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="pitcher_quick">
                                    <input type="hidden" name="pitcher_id" value="<?= $active_pitcher['player_id'] ?>">
                                    <input type="hidden" name="stat" value="innings">
                                    <input type="hidden" name="diff" value="-0.1">
                                    <button type="submit" style="width:100%; padding:6px; background:#fff; border:1px solid #cbd5e1; border-radius:4px; font-weight:700; cursor:pointer; font-size:0.75rem; font-family: inherit;">
                                        -1/3局
                                    </button>
                                </form>
                                <!-- ER -1 -->
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="pitcher_quick">
                                    <input type="hidden" name="pitcher_id" value="<?= $active_pitcher['player_id'] ?>">
                                    <input type="hidden" name="stat" value="earned_runs">
                                    <input type="hidden" name="diff" value="-1">
                                    <button type="submit" style="width:100%; padding:6px; background:#fff; border:1px solid #cbd5e1; border-radius:4px; font-weight:700; cursor:pointer; font-size:0.75rem; font-family: inherit;">
                                        -1 責失
                                    </button>
                                </form>
                            </div>

                            <!-- Cumulative manual form -->
                            <details style="width:100%;">
                                <summary style="cursor:pointer; font-size:0.8rem; font-weight:700; color:#475569; user-select: none;"><i class="fas fa-edit"></i> 手動修改累計投手數據</summary>
                                <form method="POST" style="margin-top:10px;">
                                    <input type="hidden" name="action" value="update_pitcher">
                                    <input type="hidden" name="pitcher_id" value="<?= $active_pitcher['player_id'] ?>">

                                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px; margin-bottom:6px;">
                                        <div>
                                            <label style="display:block; font-size:0.65rem; color:#666;">投球數</label>
                                            <input type="number" name="pitches" value="<?= $pitcher_stats['pitches'] ?>" min="0" required style="width:100%; padding:6px; border-radius:4px; border:1px solid #cbd5e1; text-align:center;">
                                        </div>
                                        <div>
                                            <label style="display:block; font-size:0.65rem; color:#666;">局數 (如 1 1/3 或 2/3)</label>
                                            <input type="text" name="innings" value="<?= htmlspecialchars(formatInningsDisplay($pitcher_stats['innings'])) ?>" required style="width:100%; padding:6px; border-radius:4px; border:1px solid #cbd5e1; text-align:center;">
                                        </div>
                                    </div>
                                    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:4px; margin-bottom:6px;">
                                        <div>
                                            <label style="display:block; font-size:0.65rem; color:#666;">三振</label>
                                            <input type="number" name="strikeouts" value="<?= $pitcher_stats['strikeouts'] ?>" min="0" required style="width:100%; padding:6px; border-radius:4px; border:1px solid #cbd5e1; text-align:center;">
                                        </div>
                                        <div>
                                            <label style="display:block; font-size:0.65rem; color:#666;">保送</label>
                                            <input type="number" name="walks" value="<?= $pitcher_stats['walks'] ?>" min="0" required style="width:100%; padding:6px; border-radius:4px; border:1px solid #cbd5e1; text-align:center;">
                                        </div>
                                        <div>
                                            <label style="display:block; font-size:0.65rem; color:#666;">責失</label>
                                            <input type="number" name="earned_runs" value="<?= $pitcher_stats['earned_runs'] ?>" min="0" required style="width:100%; padding:6px; border-radius:4px; border:1px solid #cbd5e1; text-align:center;">
                                        </div>
                                    </div>
                                    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:4px; margin-bottom:6px;">
                                        <div>
                                            <label style="display:block; font-size:0.65rem; color:#666;">面對打席</label>
                                            <input type="number" name="batters_faced" value="<?= $pitcher_stats['batters_faced'] ?>" min="0" style="width:100%; padding:6px; border-radius:4px; border:1px solid #cbd5e1; text-align:center;">
                                        </div>
                                        <div>
                                            <label style="display:block; font-size:0.65rem; color:#666;">被安打</label>
                                            <input type="number" name="hits_allowed" value="<?= $pitcher_stats['hits_allowed'] ?>" min="0" style="width:100%; padding:6px; border-radius:4px; border:1px solid #cbd5e1; text-align:center;">
                                        </div>
                                        <div>
                                            <label style="display:block; font-size:0.65rem; color:#666;">失分</label>
                                            <input type="number" name="runs_allowed" value="<?= $pitcher_stats['runs_allowed'] ?>" min="0" style="width:100%; padding:6px; border-radius:4px; border:1px solid #cbd5e1; text-align:center;">
                                        </div>
                                    </div>
                                    
                                    <h5 style="margin:8px 0 4px 0; color:#475569; font-size:0.75rem; font-weight:700;">出賽決策與狀態</h5>
                                    <div style="display:flex; flex-wrap:wrap; gap:4px 8px; margin-bottom:8px; background:#fff; padding:6px; border:1px solid #cbd5e1; border-radius:4px;">
                                        <label style="font-size:0.7rem; font-weight:normal; cursor:pointer;"><input type="checkbox" name="win" value="1" <?= $pitcher_stats['win'] ? 'checked' : '' ?>> 勝投</label>
                                        <label style="font-size:0.7rem; font-weight:normal; cursor:pointer;"><input type="checkbox" name="loss" value="1" <?= $pitcher_stats['loss'] ? 'checked' : '' ?>> 敗投</label>
                                        <label style="font-size:0.7rem; font-weight:normal; cursor:pointer;"><input type="checkbox" name="save" value="1" <?= $pitcher_stats['save'] ? 'checked' : '' ?>> 救援</label>
                                        <label style="font-size:0.7rem; font-weight:normal; cursor:pointer;"><input type="checkbox" name="blown_save" value="1" <?= $pitcher_stats['blown_save'] ? 'checked' : '' ?>> BS</label>
                                        <label style="font-size:0.7rem; font-weight:normal; cursor:pointer;"><input type="checkbox" name="hold" value="1" <?= $pitcher_stats['hold'] ? 'checked' : '' ?>> 中繼</label>
                                        <label style="font-size:0.7rem; font-weight:normal; cursor:pointer;"><input type="checkbox" name="is_cg" value="1" <?= $pitcher_stats['is_cg'] ? 'checked' : '' ?>> 完投</label>
                                        <label style="font-size:0.7rem; font-weight:normal; cursor:pointer;"><input type="checkbox" name="is_sho" value="1" <?= $pitcher_stats['is_sho'] ? 'checked' : '' ?>> 完封</label>
                                    </div>

                                    <!-- Hidden fields for other pitching details so they aren't reset to 0 on save -->
                                    <input type="hidden" name="p_hr_allowed" value="<?= $pitcher_stats['p_hr_allowed'] ?>">
                                    <input type="hidden" name="p_hit_by_pitch" value="<?= $pitcher_stats['p_hit_by_pitch'] ?>">
                                    <input type="hidden" name="wild_pitches" value="<?= $pitcher_stats['wild_pitches'] ?>">
                                    <input type="hidden" name="balks" value="<?= $pitcher_stats['balks'] ?>">
                                    <input type="hidden" name="p_go_outs" value="<?= $pitcher_stats['p_go_outs'] ?>">
                                    <input type="hidden" name="p_fo_outs" value="<?= $pitcher_stats['p_fo_outs'] ?>">
                                    <input type="hidden" name="strikes" value="<?= $pitcher_stats['strikes'] ?>">
                                    <input type="hidden" name="balls" value="<?= $pitcher_stats['balls'] ?>">
                                    <input type="hidden" name="swings" value="<?= $pitcher_stats['swings'] ?>">
                                    <input type="hidden" name="first_pitch_swings" value="<?= $pitcher_stats['first_pitch_swings'] ?>">
                                    <input type="hidden" name="whiffs" value="<?= $pitcher_stats['whiffs'] ?>">
                                    <input type="hidden" name="gb_count" value="<?= $pitcher_stats['gb_count'] ?>">
                                    <input type="hidden" name="ld_count" value="<?= $pitcher_stats['ld_count'] ?>">
                                    <input type="hidden" name="fb_count" value="<?= $pitcher_stats['fb_count'] ?>">

                                    <button type="submit" style="width:100%; padding:8px; background:#334155; color:white; border:none; border-radius:4px; font-weight:700; cursor:pointer; font-size:0.75rem; font-family: inherit;">
                                        儲存投球累計數據
                                    </button>
                                </form>
                            </details>
                        </div>
                        </div>
                    <?php else: ?>
                        <!-- No active pitcher form -->
                        <div style="background:#fee2e2; color:#b91c1c; padding:15px; border-radius:8px; text-align:center; font-weight:bold;">
                            目前沒有指定現役投手，請先指定。
                            <form method="POST" style="margin-top: 10px; text-align: left;">
                                <input type="hidden" name="action" value="pinch_pitcher">
                                <input type="hidden" name="old_pitcher_id" value="0">
                                <div class="form-group" style="margin-bottom: 10px;">
                                    <label style="display:block; margin-bottom:5px; font-size: 0.8rem; color:#475569;">選擇上場投手</label>
                                    <select name="new_pitcher_id" required style="width:100%; padding:8px; border-radius:6px; border:1px solid #cbd5e1; font-family:inherit;">
                                        <option value="">-- 請選擇投手 --</option>
                                        <?php foreach ($players as $p): ?>
                                            <option value="<?= $p['Player_id'] ?>">#<?= $p['jersey_number'] ?> - <?= htmlspecialchars($p['Player_Name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" style="width:100%; background:#dc3545; color:white; border:none; padding:8px; border-radius:4px; font-weight:700; cursor:pointer; font-family:inherit; font-size:0.8rem;">
                                    指定現役投手
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
            </div>
        <?php endif; ?>

    </div>
</section>

<!-- Play-by-Play Log Card -->
<?php
$logs_stmt = $pdo->prepare("SELECT * FROM game_live_logs WHERE game_id = ? ORDER BY id DESC");
$logs_stmt->execute([$game_id]);
$game_logs = $logs_stmt->fetchAll();
?>
<div style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; margin-top: 25px;">
    <h3 style="margin-top:0; color:#1e293b; font-size:1.15rem; font-weight:800; border-bottom:2px solid #f1f5f9; padding-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
        <span><i class="fas fa-history" style="color:var(--primary); margin-right:6px;"></i> 本場打席敘述歷史 (Play-by-Play Log)</span>
        <span style="font-size:0.75rem; color:#64748b; font-weight:normal;">最新紀錄顯示在最上方</span>
    </h3>
    <div style="max-height: 350px; overflow-y: auto; padding-right:5px; margin-top:15px;">
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
                            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:4px;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <span style="background:<?= $log['type'] === 'offense' ? '#3b82f6' : '#64748b' ?>; color:white; padding:2px 8px; border-radius:4px; font-size:0.7rem; font-weight:800;">
                                        <?= $log['type'] === 'offense' ? '我方進攻' : '對手進攻' ?>
                                    </span>
                                    <span style="background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:4px; font-size:0.7rem; font-weight:800; font-family:'Outfit',sans-serif;">
                                        <?= htmlspecialchars($log['pa_result']) ?>
                                    </span>
                                    <span style="font-size:0.7rem; color:#94a3b8;"><?= $log['created_at'] ?></span>
                                </div>
                                <form method="POST" style="margin:0;" onsubmit="return confirm('確定要刪除這筆打席敘述紀錄嗎？');">
                                    <input type="hidden" name="action" value="delete_live_log">
                                    <input type="hidden" name="log_id" value="<?= $log['id'] ?>">
                                    <button type="submit" style="background:none; border:none; color:#ef4444; cursor:pointer; font-size:0.8rem; padding:2px 6px;" title="刪除打席敘述"><i class="fas fa-trash-alt"></i> 刪除</button>
                                </form>
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

<!-- ── 4. 浮動 Modal 彈窗 (更換代打/更換投手/更換守備位置) ── -->
<div id="modal-backdrop" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center;">
    <!-- modal 代打 -->
    <div id="modal-pinch-batter" class="modal-box" style="display:none; background:white; padding:25px; border-radius:12px; width:450px; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <h3 style="margin-top:0; border-bottom:2px solid var(--primary); padding-bottom:10px;">更換代打</h3>
        <form method="POST">
            <input type="hidden" name="action" value="pinch_batter">
            <input type="hidden" name="batting_order" id="pb-order">
            <input type="hidden" name="old_player_id" id="pb-old-id">
            
            <p style="color:#555;">將第 <span id="pb-order-label" style="font-weight:bold; color:var(--primary);"></span> 棒的 <strong id="pb-old-name"></strong> 更換為：</p>
            <div class="form-group" style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">新上場球員</label>
                <select name="new_player_id" required style="width:100%; padding:10px; border-radius:6px; border:1px solid #cbd5e1;">
                    <option value="">-- 請選擇隊員 --</option>
                    <?php foreach ($players as $p): ?>
                        <option value="<?= $p['Player_id'] ?>">#<?= $p['jersey_number'] ?> - <?= htmlspecialchars($p['Player_Name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:5px;">守備位置</label>
                <select name="position" id="pb-position" required style="width:100%; padding:10px; border-radius:6px; border:1px solid #cbd5e1;">
                    <?php foreach($positions_list as $pos): ?>
                        <option value="<?= $pos ?>"><?= htmlspecialchars($positions_map[$pos] ?? $pos) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="closeModal()" style="padding:10px 15px; border:1px solid #ddd; background:white; border-radius:6px; cursor:pointer;">取消</button>
                <button type="submit" style="padding:10px 20px; background:#333; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:700;">確認更換</button>
            </div>
        </form>
    </div>

    <!-- modal 更換投手 -->
    <div id="modal-pinch-pitcher" class="modal-box" style="display:none; background:white; padding:25px; border-radius:12px; width:450px; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <h3 style="margin-top:0; border-bottom:2px solid var(--secondary); padding-bottom:10px;">更換投手</h3>
        <form method="POST">
            <input type="hidden" name="action" value="pinch_pitcher">
            <input type="hidden" name="old_pitcher_id" id="pp-old-id">
            
            <p style="color:#555;">更換目前投手 <strong id="pp-old-name"></strong>，新投手為：</p>
            <div class="form-group" style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:5px;">新上場投手</label>
                <select name="new_pitcher_id" required style="width:100%; padding:10px; border-radius:6px; border:1px solid #cbd5e1;">
                    <option value="">-- 請選擇投手 --</option>
                    <?php foreach ($players as $p): ?>
                        <option value="<?= $p['Player_id'] ?>">#<?= $p['jersey_number'] ?> - <?= htmlspecialchars($p['Player_Name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="closeModal()" style="padding:10px 15px; border:1px solid #ddd; background:white; border-radius:6px; cursor:pointer;">取消</button>
                <button type="submit" style="padding:10px 20px; background:#dc3545; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:700;">更換投手</button>
            </div>
        </form>
    </div>

    <!-- modal 更換守備位置 -->
    <div id="modal-change-position" class="modal-box" style="display:none; background:white; padding:25px; border-radius:12px; width:450px; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <h3 style="margin-top:0; border-bottom:2px solid var(--secondary); padding-bottom:10px;">調整守備位置</h3>
        <form method="POST">
            <input type="hidden" name="action" value="change_position">
            <input type="hidden" name="lineup_id" id="cp-lineup-id">
            
            <p style="color:#555;">修改 <strong id="cp-player-name"></strong> 的守備位置為：</p>
            <div class="form-group" style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:5px;">守備位置</label>
                <select name="position" id="cp-position" required style="width:100%; padding:10px; border-radius:6px; border:1px solid #cbd5e1;">
                    <?php foreach($positions_list as $pos): ?>
                        <option value="<?= $pos ?>"><?= htmlspecialchars($positions_map[$pos] ?? $pos) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="closeModal()" style="padding:10px 15px; border:1px solid #ddd; background:white; border-radius:6px; cursor:pointer;">取消</button>
                <button type="submit" style="padding:10px 20px; background:var(--secondary); color:#222; border:none; border-radius:6px; cursor:pointer; font-weight:700;">儲存守位</button>
            </div>
        </form>
    </div>
</div>

<style>
.pa-label {
    border: 1px solid #cbd5e1;
    background: #f8f9fa;
    color: #333;
}
.pa-label:hover {
    border-color: #3b82f6;
    background: #eff6ff;
}
.pa-label input[type="radio"]:checked + div + div {
    color: white !important;
}
.pa-label:has(input[type="radio"]:checked) {
    background: #3b82f6 !important;
    border-color: #3b82f6 !important;
    color: white !important;
}
.pa-label:has(input[type="radio"]:checked) div {
    color: white !important;
}

/* RWD Layout Rules */
.live-grid-container {
    display: grid;
    grid-template-columns: 285px 1.1fr 1.2fr;
    gap: 20px;
    align-items: stretch;
    margin-bottom: 30px;
}

.scoreboard-center-panel {
    flex: 1.2;
    min-width: 220px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-left: 1px solid #1e293b;
    border-right: 1px solid #1e293b;
    padding: 0 15px;
    box-sizing: border-box;
}

@media (max-width: 1024px) {
    .live-grid-container {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .scoreboard-center-panel {
        border-left: none;
        border-right: none;
        border-top: 1px solid #1e293b;
        border-bottom: 1px solid #1e293b;
        padding: 15px 0;
        margin: 10px 0;
    }
}
</style>

<script>
const isOurOffense = <?= $is_our_offense ? 'true' : 'false' ?>;
let defBalls = <?= (int)($live_state['balls'] ?? 0) ?>;
let defStrikes = <?= (int)($live_state['strikes'] ?? 0) ?>;
let defWildPitches = 0;
let defBalks = 0;
let isSubmitting = false;

let defAdvancedStrikes = <?= (int)($live_state['strikes'] ?? 0) ?>;
let defAdvancedBalls = <?= (int)($live_state['balls'] ?? 0) ?>;
let defPitches = defAdvancedStrikes + defAdvancedBalls;
let defAdvancedSwings = 0;
let defAdvancedWhiffs = 0;
let defAdvancedGB = 0;
let defAdvancedFB = 0;
let defAdvancedLD = 0;
let defAdvancedFPS = 0;

const basePitcherTotal = <?= (int)($pitcher_stats['pitches'] ?? 0) ?>;

// 我方進攻打擊面對投球狀態變數
let offBalls = isOurOffense ? <?= (int)($live_state['balls'] ?? 0) ?> : 0;
let offStrikes = isOurOffense ? <?= (int)($live_state['strikes'] ?? 0) ?> : 0;
let offAdvancedStrikes = offStrikes;
let offAdvancedBalls = offBalls;
let offPitches = offAdvancedStrikes + offAdvancedBalls;
let offAdvancedSwings = 0;
let offAdvancedWhiffs = 0;
let offAdvancedGB = 0;
let offAdvancedFB = 0;
let offAdvancedLD = 0;
let offAdvancedFPS = 0;
let isOffenseSubmitting = false;

function updatePitchCounterOffenseUI() {
    // Balls indicator (up to 3 dots)
    const ballDots = document.querySelectorAll('.off-ball-dot');
    ballDots.forEach((dot, idx) => {
        if (idx < offBalls) {
            dot.style.background = '#10b981';
            dot.style.borderColor = '#10b981';
        } else {
            dot.style.background = 'transparent';
            dot.style.borderColor = '#cbd5e1';
        }
    });
    
    // Strikes indicator (up to 2 dots)
    const strikeDots = document.querySelectorAll('.off-strike-dot');
    strikeDots.forEach((dot, idx) => {
        if (idx < offStrikes) {
            dot.style.background = '#f59e0b';
            dot.style.borderColor = '#f59e0b';
        } else {
            dot.style.background = 'transparent';
            dot.style.borderColor = '#cbd5e1';
        }
    });

    // Outs indicator (up to 2 dots)
    const outDots = document.querySelectorAll('.off-out-dot');
    const currentOutsFromDB = <?= (int)($live_state['outs'] ?? 0) ?>;
    outDots.forEach((dot, idx) => {
        if (idx < currentOutsFromDB) {
            dot.style.background = '#ef4444';
            dot.style.borderColor = '#ef4444';
        } else {
            dot.style.background = 'transparent';
            dot.style.borderColor = '#cbd5e1';
        }
    });

    // Update text readouts
    const ballText = document.getElementById('offense-ball-text');
    if (ballText) ballText.textContent = offBalls;
    
    const strikeText = document.getElementById('offense-strike-text');
    if (strikeText) strikeText.textContent = offStrikes;

    const outText = document.getElementById('offense-out-text');
    if (outText) outText.textContent = currentOutsFromDB;

    const paPitchesText = document.getElementById('offense-pa-pitches');
    if (paPitchesText) paPitchesText.textContent = offPitches;

    // Set hidden form input field
    const pitchesThrownInput = document.getElementById('offense_pitches_thrown');
    if (pitchesThrownInput) pitchesThrownInput.value = offPitches;

    // Auto-sync strikes and balls to advanced inputs
    const oStrikesInput = document.getElementById('o_strikes_input');
    if (oStrikesInput) oStrikesInput.value = offAdvancedStrikes;

    const oBallsInput = document.getElementById('o_balls_input');
    if (oBallsInput) oBallsInput.value = offAdvancedBalls;

    const oSwingsInput = document.getElementById('o_swings_input');
    if (oSwingsInput) oSwingsInput.value = offAdvancedSwings;

    const oWhiffsInput = document.getElementById('o_whiffs_input');
    if (oWhiffsInput) oWhiffsInput.value = offAdvancedWhiffs;

    const oGBInput = document.getElementById('o_gb_input');
    if (oGBInput) oGBInput.value = offAdvancedGB;

    const oFBInput = document.getElementById('o_fb_input');
    if (oFBInput) oFBInput.value = offAdvancedFB;

    const oLDInput = document.getElementById('o_ld_input');
    if (oLDInput) oLDInput.value = offAdvancedLD;

    const fpsRadioYes = document.getElementById('o_fps_yes');
    const fpsRadioNo = document.getElementById('o_fps_no');
    if (fpsRadioYes && fpsRadioNo) {
        if (offAdvancedFPS === 1) {
            fpsRadioYes.checked = true;
        } else {
            fpsRadioNo.checked = true;
        }
    }
    updateOffenseFPSButtonUI();
}

function updateOffenseFPSButtonUI() {
    const fpsBtn = document.getElementById('o-fps-toggle-btn');
    const fpsIcon = document.getElementById('o-fps-icon');
    if (fpsBtn && fpsIcon) {
        if (offAdvancedFPS === 1) {
            fpsBtn.style.background = '#fdf2f8';
            fpsBtn.style.borderColor = '#fbcfe8';
            fpsBtn.style.color = '#db2777';
            fpsIcon.innerHTML = '<i class="fas fa-star"></i>';
        } else {
            fpsBtn.style.background = '#fff';
            fpsBtn.style.borderColor = '#cbd5e1';
            fpsBtn.style.color = 'inherit';
            fpsIcon.innerHTML = '<i class="far fa-star"></i>';
        }
    }
}

function toggleOffenseFirstPitchSwing() {
    offAdvancedFPS = (offAdvancedFPS === 1) ? 0 : 1;
    updatePitchCounterOffenseUI();
}

function resetOffensePitchCounter() {
    offBalls = 0;
    offStrikes = 0;
    offPitches = 0;
    offAdvancedStrikes = 0;
    offAdvancedBalls = 0;
    offAdvancedSwings = 0;
    offAdvancedWhiffs = 0;
    offAdvancedGB = 0;
    offAdvancedFB = 0;
    offAdvancedLD = 0;
    offAdvancedFPS = 0;
    isOffenseSubmitting = false;
    updatePitchCounterOffenseUI();
}

function recordPitchOffense(type) {
    if (type === 'strike') {
        offStrikes++;
        offPitches++;
        offAdvancedStrikes++;
        updatePitchCounterOffenseUI();
        if (offStrikes >= 3) {
            const kRadio = document.querySelector('#offense-pa-form input[name="pa_result"][value="K"]');
            if (kRadio) {
                kRadio.checked = true;
                updateOffenseHighlights();
                if (!isOffenseSubmitting) {
                    isOffenseSubmitting = true;
                    setTimeout(() => {
                        const form = document.getElementById('offense-pa-form');
                        if (form) form.submit();
                    }, 500);
                }
            }
        }
    } else if (type === 'ball') {
        offBalls++;
        offPitches++;
        offAdvancedBalls++;
        updatePitchCounterOffenseUI();
        if (offBalls >= 4) {
            const bbRadio = document.querySelector('#offense-pa-form input[name="pa_result"][value="BB"]');
            if (bbRadio) {
                bbRadio.checked = true;
                updateOffenseHighlights();
                if (!isOffenseSubmitting) {
                    isOffenseSubmitting = true;
                    setTimeout(() => {
                        const form = document.getElementById('offense-pa-form');
                        if (form) form.submit();
                    }, 500);
                }
            }
        }
    } else if (type === 'foul') {
        offPitches++;
        offAdvancedStrikes++;
        offAdvancedSwings++;
        if (offStrikes < 2) {
            offStrikes++;
        }
        updatePitchCounterOffenseUI();
    } else if (type === 'whiff') {
        offStrikes++;
        offPitches++;
        offAdvancedStrikes++;
        offAdvancedSwings++;
        offAdvancedWhiffs++;
        updatePitchCounterOffenseUI();
        if (offStrikes >= 3) {
            const kRadio = document.querySelector('#offense-pa-form input[name="pa_result"][value="K"]');
            if (kRadio) {
                kRadio.checked = true;
                updateOffenseHighlights();
                if (!isOffenseSubmitting) {
                    isOffenseSubmitting = true;
                    setTimeout(() => {
                        const form = document.getElementById('offense-pa-form');
                        if (form) form.submit();
                    }, 500);
                }
            }
        }
    } else if (type === 'gb') {
        offPitches++;
        offAdvancedStrikes++;
        offAdvancedSwings++;
        offAdvancedGB++;
        updatePitchCounterOffenseUI();
    } else if (type === 'fb') {
        offPitches++;
        offAdvancedStrikes++;
        offAdvancedSwings++;
        offAdvancedFB++;
        updatePitchCounterOffenseUI();
    } else if (type === 'ld') {
        offPitches++;
        offAdvancedStrikes++;
        offAdvancedSwings++;
        offAdvancedLD++;
        updatePitchCounterOffenseUI();
    }
}

function updatePitchCounterDefenseUI() {
    // Balls indicator (up to 3 dots)
    const ballDots = document.querySelectorAll('.def-ball-dot');
    ballDots.forEach((dot, idx) => {
        if (idx < defBalls) {
            dot.style.background = '#10b981';
            dot.style.borderColor = '#10b981';
        } else {
            dot.style.background = 'transparent';
            dot.style.borderColor = '#cbd5e1';
        }
    });
    
    // Strikes indicator (up to 2 dots)
    const strikeDots = document.querySelectorAll('.def-strike-dot');
    strikeDots.forEach((dot, idx) => {
        if (idx < defStrikes) {
            dot.style.background = '#f59e0b';
            dot.style.borderColor = '#f59e0b';
        } else {
            dot.style.background = 'transparent';
            dot.style.borderColor = '#cbd5e1';
        }
    });

    // Outs indicator (up to 2 dots)
    const outDots = document.querySelectorAll('.def-out-dot');
    const currentOutsFromDB = <?= (int)($live_state['outs'] ?? 0) ?>;
    outDots.forEach((dot, idx) => {
        if (idx < currentOutsFromDB) {
            dot.style.background = '#ef4444';
            dot.style.borderColor = '#ef4444';
        } else {
            dot.style.background = 'transparent';
            dot.style.borderColor = '#cbd5e1';
        }
    });

    // Update text readouts
    const ballText = document.getElementById('defense-ball-text');
    if (ballText) ballText.textContent = defBalls;
    
    const strikeText = document.getElementById('defense-strike-text');
    if (strikeText) strikeText.textContent = defStrikes;

    const outText = document.getElementById('defense-out-text');
    if (outText) outText.textContent = currentOutsFromDB;

    const paPitchesText = document.getElementById('defense-pa-pitches');
    if (paPitchesText) paPitchesText.textContent = defPitches;

    const totalPitchesText = document.getElementById('defense-total-pitches');
    if (totalPitchesText) totalPitchesText.textContent = basePitcherTotal + defPitches;

    // Set hidden form input fields
    const pitchesThrownInput = document.getElementById('defense_pitches_thrown');
    if (pitchesThrownInput) pitchesThrownInput.value = defPitches;

    const wildPitchesInput = document.getElementById('defense_wild_pitches');
    if (wildPitchesInput) wildPitchesInput.value = defWildPitches;

    const balksInput = document.getElementById('defense_balks');
    if (balksInput) balksInput.value = defBalks;

    // Auto-sync strikes and balls to advanced inputs
    const pStrikesInput = document.getElementById('d_strikes_input');
    if (pStrikesInput) pStrikesInput.value = defAdvancedStrikes;

    const pBallsInput = document.getElementById('d_balls_input');
    if (pBallsInput) pBallsInput.value = defAdvancedBalls;

    const pSwingsInput = document.getElementById('d_swings_input');
    if (pSwingsInput) pSwingsInput.value = defAdvancedSwings;

    const pWhiffsInput = document.getElementById('d_whiffs_input');
    if (pWhiffsInput) pWhiffsInput.value = defAdvancedWhiffs;

    const pGBInput = document.getElementById('d_gb_input');
    if (pGBInput) pGBInput.value = defAdvancedGB;

    const pFBInput = document.getElementById('d_fb_input');
    if (pFBInput) pFBInput.value = defAdvancedFB;

    const pLDInput = document.getElementById('d_ld_input');
    if (pLDInput) pLDInput.value = defAdvancedLD;

    const fpsRadioYes = document.querySelector('input[name="p_first_pitch_swing"][value="1"]');
    const fpsRadioNo = document.querySelector('input[name="p_first_pitch_swing"][value="0"]');
    if (fpsRadioYes && fpsRadioNo) {
        if (defAdvancedFPS === 1) {
            fpsRadioYes.checked = true;
        } else {
            fpsRadioNo.checked = true;
        }
    }
    updateFPSButtonUI();

    // Display labels for WP & Balk if any
    const label = document.getElementById('defense-wp-balk-label');
    if (label) {
        let texts = [];
        if (defWildPitches > 0) texts.push('暴投 +' + defWildPitches);
        if (defBalks > 0) texts.push('犯規 +' + defBalks);
        if (texts.length > 0) {
            label.textContent = texts.join(', ');
            label.style.display = 'inline';
        } else {
            label.style.display = 'none';
        }
    }
}

function recordPitchDefense(type) {
    if (type === 'strike') {
        defStrikes++;
        defPitches++;
        defAdvancedStrikes++;
        updatePitchCounterDefenseUI();
        if (defStrikes >= 3) {
            const kRadio = document.querySelector('#defense-pa-form input[name="pa_result"][value="K"]');
            if (kRadio) {
                kRadio.checked = true;
                updateDefenseHighlights();
                if (!isSubmitting) {
                    isSubmitting = true;
                    setTimeout(() => {
                        const form = document.getElementById('defense-pa-form');
                        if (form) form.submit();
                    }, 500);
                }
            }
        }
    } else if (type === 'ball') {
        defBalls++;
        defPitches++;
        defAdvancedBalls++;
        updatePitchCounterDefenseUI();
        if (defBalls >= 4) {
            const bbRadio = document.querySelector('#defense-pa-form input[name="pa_result"][value="BB"]');
            if (bbRadio) {
                bbRadio.checked = true;
                updateDefenseHighlights();
                if (!isSubmitting) {
                    isSubmitting = true;
                    setTimeout(() => {
                        const form = document.getElementById('defense-pa-form');
                        if (form) form.submit();
                    }, 500);
                }
            }
        }
    } else if (type === 'foul') {
        defPitches++;
        defAdvancedStrikes++;
        defAdvancedSwings++;
        if (defStrikes < 2) {
            defStrikes++;
        }
        updatePitchCounterDefenseUI();
    } else if (type === 'whiff') {
        defStrikes++;
        defPitches++;
        defAdvancedStrikes++;
        defAdvancedSwings++;
        defAdvancedWhiffs++;
        updatePitchCounterDefenseUI();
        if (defStrikes >= 3) {
            const kRadio = document.querySelector('#defense-pa-form input[name="pa_result"][value="K"]');
            if (kRadio) {
                kRadio.checked = true;
                updateDefenseHighlights();
                if (!isSubmitting) {
                    isSubmitting = true;
                    setTimeout(() => {
                        const form = document.getElementById('defense-pa-form');
                        if (form) form.submit();
                    }, 500);
                }
            }
        }
    } else if (type === 'gb') {
        defPitches++;
        defAdvancedStrikes++;
        defAdvancedSwings++;
        defAdvancedGB++;
        updatePitchCounterDefenseUI();
    } else if (type === 'fb') {
        defPitches++;
        defAdvancedStrikes++;
        defAdvancedSwings++;
        defAdvancedFB++;
        updatePitchCounterDefenseUI();
    } else if (type === 'ld') {
        defPitches++;
        defAdvancedStrikes++;
        defAdvancedSwings++;
        defAdvancedLD++;
        updatePitchCounterDefenseUI();
    } else if (type === 'wp') {
        defWildPitches++;
        defPitches++;
        defAdvancedBalls++;
        if (defBalls < 4) {
            defBalls++;
            updatePitchCounterDefenseUI();
            if (defBalls >= 4) {
                const bbRadio = document.querySelector('#defense-pa-form input[name="pa_result"][value="BB"]');
                if (bbRadio) {
                    bbRadio.checked = true;
                    updateDefenseHighlights();
                    if (!isSubmitting) {
                        isSubmitting = true;
                        setTimeout(() => {
                            const form = document.getElementById('defense-pa-form');
                            if (form) form.submit();
                        }, 500);
                    }
                }
            }
        } else {
            updatePitchCounterDefenseUI();
        }
    } else if (type === 'balk') {
        defBalks++;
        updatePitchCounterDefenseUI();
    } else if (type === 'hit' || type === 'out') {
        defPitches++;
        defAdvancedStrikes++;
        defAdvancedSwings++;
        updatePitchCounterDefenseUI();
    }
}

function resetPitchCounter() {
    defBalls = 0;
    defStrikes = 0;
    defPitches = 0;
    defWildPitches = 0;
    defBalks = 0;
    defAdvancedStrikes = 0;
    defAdvancedBalls = 0;
    defAdvancedSwings = 0;
    defAdvancedWhiffs = 0;
    defAdvancedGB = 0;
    defAdvancedFB = 0;
    defAdvancedLD = 0;
    defAdvancedFPS = 0;
    isSubmitting = false;
    updatePitchCounterDefenseUI();
}

function toggleFirstPitchSwing() {
    defAdvancedFPS = (defAdvancedFPS === 1) ? 0 : 1;
    updatePitchCounterDefenseUI();
}

function updateFPSButtonUI() {
    const fpsBtn = document.getElementById('fps-toggle-btn');
    const fpsIcon = document.getElementById('fps-icon');
    if (fpsBtn && fpsIcon) {
        if (defAdvancedFPS === 1) {
            fpsBtn.style.background = '#fdf2f8';
            fpsBtn.style.borderColor = '#fbcfe8';
            fpsBtn.style.color = '#db2777';
            fpsIcon.innerHTML = '<i class="fas fa-star"></i>';
        } else {
            fpsBtn.style.background = '#fff';
            fpsBtn.style.borderColor = '#cbd5e1';
            fpsBtn.style.color = 'inherit';
            fpsIcon.innerHTML = '<i class="far fa-star"></i>';
        }
    }
}

// 雙向綁定：手動修改進階欄位時同步回 JS 變數
document.addEventListener('DOMContentLoaded', () => {
    const pStrikesInput = document.getElementById('d_strikes_input');
    const pBallsInput = document.getElementById('d_balls_input');
    const pSwingsInput = document.getElementById('d_swings_input');
    const pWhiffsInput = document.getElementById('d_whiffs_input');
    const pGBInput = document.getElementById('d_gb_input');
    const pFBInput = document.getElementById('d_fb_input');
    const pLDInput = document.getElementById('d_ld_input');

    if (pStrikesInput) pStrikesInput.addEventListener('input', (e) => { defAdvancedStrikes = parseInt(e.target.value) || 0; });
    if (pBallsInput) pBallsInput.addEventListener('input', (e) => { defAdvancedBalls = parseInt(e.target.value) || 0; });
    if (pSwingsInput) pSwingsInput.addEventListener('input', (e) => { defAdvancedSwings = parseInt(e.target.value) || 0; });
    if (pWhiffsInput) pWhiffsInput.addEventListener('input', (e) => { defAdvancedWhiffs = parseInt(e.target.value) || 0; });
    if (pGBInput) pGBInput.addEventListener('input', (e) => { defAdvancedGB = parseInt(e.target.value) || 0; });
    if (pFBInput) pFBInput.addEventListener('input', (e) => { defAdvancedFB = parseInt(e.target.value) || 0; });
    if (pLDInput) pLDInput.addEventListener('input', (e) => { defAdvancedLD = parseInt(e.target.value) || 0; });

    document.querySelectorAll('input[name="p_first_pitch_swing"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            if (radio.checked) {
                defAdvancedFPS = parseInt(radio.value) || 0;
                updateFPSButtonUI();
            }
        });
    });

    // Offense pitch tracking double binding
    const oStrikesInput = document.getElementById('o_strikes_input');
    const oBallsInput = document.getElementById('o_balls_input');
    const oSwingsInput = document.getElementById('o_swings_input');
    const oWhiffsInput = document.getElementById('o_whiffs_input');
    const oGBInput = document.getElementById('o_gb_input');
    const oFBInput = document.getElementById('o_fb_input');
    const oLDInput = document.getElementById('o_ld_input');

    if (oStrikesInput) oStrikesInput.addEventListener('input', (e) => { offAdvancedStrikes = parseInt(e.target.value) || 0; });
    if (oBallsInput) oBallsInput.addEventListener('input', (e) => { offAdvancedBalls = parseInt(e.target.value) || 0; });
    if (oSwingsInput) oSwingsInput.addEventListener('input', (e) => { offAdvancedSwings = parseInt(e.target.value) || 0; });
    if (oWhiffsInput) oWhiffsInput.addEventListener('input', (e) => { offAdvancedWhiffs = parseInt(e.target.value) || 0; });
    if (oGBInput) oGBInput.addEventListener('input', (e) => { offAdvancedGB = parseInt(e.target.value) || 0; });
    if (oFBInput) oFBInput.addEventListener('input', (e) => { offAdvancedFB = parseInt(e.target.value) || 0; });
    if (oLDInput) oLDInput.addEventListener('input', (e) => { offAdvancedLD = parseInt(e.target.value) || 0; });

    document.querySelectorAll('input[name="first_pitch_swings"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            if (radio.checked) {
                offAdvancedFPS = parseInt(radio.value) || 0;
                updateOffenseFPSButtonUI();
            }
        });
    });

    updatePitchCounterOffenseUI();
});

function toggleContactQuality(type) {
    const hardHitInput = document.getElementById('hard_hit_input');
    const softHitInput = document.getElementById('soft_hit_input');
    const hardHitBtn = document.getElementById('hard_hit_btn');
    const softHitBtn = document.getElementById('soft_hit_btn');
    
    if (!hardHitInput || !softHitInput || !hardHitBtn || !softHitBtn) return;
    
    let isHardActive = hardHitInput.value === '1';
    let isSoftActive = softHitInput.value === '1';
    
    if (type === 'hard') {
        if (isHardActive) {
            // Deactivate
            hardHitInput.value = '0';
            hardHitBtn.style.background = 'white';
            hardHitBtn.style.borderColor = '#cbd5e1';
            hardHitBtn.style.color = '#475569';
            hardHitBtn.querySelector('span:last-of-type').style.color = '#64748b';
            hardHitBtn.style.boxShadow = 'none';
        } else {
            // Activate Hard, Deactivate Soft
            hardHitInput.value = '1';
            softHitInput.value = '0';
            
            hardHitBtn.style.background = 'linear-gradient(135deg, #f97316, #ea580c)';
            hardHitBtn.style.borderColor = '#ea580c';
            hardHitBtn.style.color = 'white';
            hardHitBtn.querySelector('span:last-of-type').style.color = '#ffedd5';
            hardHitBtn.style.boxShadow = '0 4px 6px -1px rgba(234, 88, 12, 0.2)';
            
            softHitBtn.style.background = 'white';
            softHitBtn.style.borderColor = '#cbd5e1';
            softHitBtn.style.color = '#475569';
            softHitBtn.querySelector('span:last-of-type').style.color = '#64748b';
            softHitBtn.style.boxShadow = 'none';
        }
    } else if (type === 'soft') {
        if (isSoftActive) {
            // Deactivate
            softHitInput.value = '0';
            softHitBtn.style.background = 'white';
            softHitBtn.style.borderColor = '#cbd5e1';
            softHitBtn.style.color = '#475569';
            softHitBtn.querySelector('span:last-of-type').style.color = '#64748b';
            softHitBtn.style.boxShadow = 'none';
        } else {
            // Activate Soft, Deactivate Hard
            softHitInput.value = '1';
            hardHitInput.value = '0';
            
            softHitBtn.style.background = 'linear-gradient(135deg, #94a3b8, #64748b)';
            softHitBtn.style.borderColor = '#64748b';
            softHitBtn.style.color = 'white';
            softHitBtn.querySelector('span:last-of-type').style.color = '#f1f5f9';
            softHitBtn.style.boxShadow = '0 4px 6px -1px rgba(100, 116, 139, 0.2)';
            
            hardHitBtn.style.background = 'white';
            hardHitBtn.style.borderColor = '#cbd5e1';
            hardHitBtn.style.color = '#475569';
            hardHitBtn.querySelector('span:last-of-type').style.color = '#64748b';
            hardHitBtn.style.boxShadow = 'none';
        }
    }
}

function updateOffenseHighlights() {
    let selectedVal = '';
    document.querySelectorAll('.offense-pa-label').forEach(label => {
        const radio = label.querySelector('input[type="radio"]');
        if (radio && radio.checked) {
            selectedVal = radio.value;
            label.style.background = '#3b82f6';
            label.style.borderColor = '#3b82f6';
            label.style.color = 'white';
            const divs = label.querySelectorAll('div');
            divs.forEach(div => div.style.color = 'white');
        } else {
            label.style.background = '#f8f9fa';
            label.style.borderColor = '#cbd5e1';
            label.style.color = '#333';
            const mainDiv = label.querySelector('div:first-of-type');
            if (mainDiv) mainDiv.style.color = '#333';
            const subDiv = label.querySelector('div:last-of-type');
            if (subDiv) subDiv.style.color = '#64748b';
        }
    });

    if (selectedVal === 'HR') {
        const runsInput = document.getElementById('o_runs_input');
        const rbiInput = document.getElementById('o_rbi_input');
        if (runsInput && parseInt(runsInput.value) === 0) runsInput.value = 1;
        if (rbiInput && parseInt(rbiInput.value) === 0) rbiInput.value = 1;
    }
}

function syncDropdownsToBases() {
    if (!isOurOffense) return;
    const base1 = document.getElementById('base-1');
    const base2 = document.getElementById('base-2');
    const base3 = document.getElementById('base-3');
    
    const sel1 = document.getElementById('runner_first_select');
    const sel2 = document.getElementById('runner_second_select');
    const sel3 = document.getElementById('runner_third_select');
    
    const chkRunner1 = document.getElementById('chk-runner-1');
    const chkRunner2 = document.getElementById('chk-runner-2');
    const chkRunner3 = document.getElementById('chk-runner-3');
    
    if (sel1 && base1) {
        if (sel1.value) {
            base1.style.background = '#fbbf24';
            base1.style.boxShadow = '0 0 8px #fbbf24';
            if (chkRunner1) chkRunner1.checked = true;
        } else {
            base1.style.background = '#1e293b';
            base1.style.boxShadow = 'none';
            if (chkRunner1) chkRunner1.checked = false;
        }
    }
    if (sel2 && base2) {
        if (sel2.value) {
            base2.style.background = '#fbbf24';
            base2.style.boxShadow = '0 0 8px #fbbf24';
            if (chkRunner2) chkRunner2.checked = true;
        } else {
            base2.style.background = '#1e293b';
            base2.style.boxShadow = 'none';
            if (chkRunner2) chkRunner2.checked = false;
        }
    }
    if (sel3 && base3) {
        if (sel3.value) {
            base3.style.background = '#fbbf24';
            base3.style.boxShadow = '0 0 8px #fbbf24';
            if (chkRunner3) chkRunner3.checked = true;
        } else {
            base3.style.background = '#1e293b';
            base3.style.boxShadow = 'none';
            if (chkRunner3) chkRunner3.checked = false;
        }
    }
}

function updateBaseEventsUI() {
    const sel1 = document.getElementById('runner_first_select');
    const sel2 = document.getElementById('runner_second_select');
    const sel3 = document.getElementById('runner_third_select');
    
    const block1 = document.getElementById('base-1-events-block');
    const block2 = document.getElementById('base-2-events-block');
    const block3 = document.getElementById('base-3-events-block');
    
    const label1 = document.getElementById('runner_first_name_label');
    const label2 = document.getElementById('runner_second_name_label');
    const label3 = document.getElementById('runner_third_name_label');
    
    if (sel1 && block1 && label1) {
        if (sel1.value) {
            block1.style.display = 'block';
            label1.textContent = sel1.options[sel1.selectedIndex].text.trim();
        } else {
            block1.style.display = 'none';
            const defaultRadio = document.getElementById('r1_action_stay');
            if (defaultRadio) defaultRadio.checked = true;
        }
    }
    if (sel2 && block2 && label2) {
        if (sel2.value) {
            block2.style.display = 'block';
            label2.textContent = sel2.options[sel2.selectedIndex].text.trim();
        } else {
            block2.style.display = 'none';
            const defaultRadio = document.getElementById('r2_action_stay');
            if (defaultRadio) defaultRadio.checked = true;
        }
    }
    if (sel3 && block3 && label3) {
        if (sel3.value) {
            block3.style.display = 'block';
            label3.textContent = sel3.options[sel3.selectedIndex].text.trim();
        } else {
            block3.style.display = 'none';
            const defaultRadio = document.getElementById('r3_action_stay');
            if (defaultRadio) defaultRadio.checked = true;
        }
    }
    updateRunnerActionsHighlights();
}

function updateRunnerActionsHighlights() {
    document.querySelectorAll('.runner-action-label').forEach(label => {
        const radio = label.querySelector('input[type="radio"]');
        if (radio && radio.checked) {
            label.style.background = '#bfdbfe';
            label.style.borderColor = '#3b82f6';
            label.style.color = '#1e3a8a';
        } else {
            label.style.background = 'white';
            label.style.borderColor = '#cbd5e1';
            label.style.color = '#333';
        }
    });
}

function updateDefenseHighlights() {
    let selectedVal = '';
    document.querySelectorAll('.defense-pa-label').forEach(label => {
        const radio = label.querySelector('input[type="radio"]');
        if (radio && radio.checked) {
            selectedVal = radio.value;
            label.style.background = '#3b82f6';
            label.style.borderColor = '#3b82f6';
            label.style.color = 'white';
            const divs = label.querySelectorAll('div');
            divs.forEach(div => div.style.color = 'white');
        } else {
            label.style.background = '#f8f9fa';
            label.style.borderColor = '#cbd5e1';
            label.style.color = '#333';
            const mainDiv = label.querySelector('div:first-of-type');
            if (mainDiv) mainDiv.style.color = '#333';
            const subDiv = label.querySelector('div:last-of-type');
            if (subDiv) subDiv.style.color = '#64748b';
        }
    });

    if (selectedVal === 'HR') {
        const runsAllowedInput = document.getElementById('d_runs_allowed_input');
        const earnedRunsInput = document.getElementById('d_earned_runs_input');
        if (runsAllowedInput && parseInt(runsAllowedInput.value) === 0) runsAllowedInput.value = 1;
        if (earnedRunsInput && parseInt(earnedRunsInput.value) === 0) earnedRunsInput.value = 1;
    }
}

function openPinchBatterModal(order, oldPlayerId, oldPlayerName, currentPos) {
    document.getElementById('pb-order').value = order;
    document.getElementById('pb-old-id').value = oldPlayerId;
    document.getElementById('pb-order-label').textContent = order;
    document.getElementById('pb-old-name').textContent = oldPlayerName;
    document.getElementById('pb-position').value = currentPos;
    
    document.getElementById('modal-backdrop').style.display = 'flex';
    document.getElementById('modal-pinch-batter').style.display = 'block';
}

function openPinchPitcherModal(oldPitcherId, oldPitcherName) {
    document.getElementById('pp-old-id').value = oldPitcherId;
    document.getElementById('pp-old-name').textContent = oldPitcherName;
    
    document.getElementById('modal-backdrop').style.display = 'flex';
    document.getElementById('modal-pinch-pitcher').style.display = 'block';
}

function openChangePositionModal(lineupId, playerName, currentPos) {
    document.getElementById('cp-lineup-id').value = lineupId;
    document.getElementById('cp-player-name').textContent = playerName;
    document.getElementById('cp-position').value = currentPos;
    
    document.getElementById('modal-backdrop').style.display = 'flex';
    document.getElementById('modal-change-position').style.display = 'block';
}

function closeModal() {
    document.getElementById('modal-backdrop').style.display = 'none';
    document.querySelectorAll('.modal-box').forEach(box => box.style.display = 'none');
}

// 點擊背景關閉
document.getElementById('modal-backdrop').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// Interactive Base Click Handlers to sync to hidden checkboxes in the manual updates form
document.addEventListener('DOMContentLoaded', () => {
    const base1 = document.getElementById('base-1');
    const base2 = document.getElementById('base-2');
    const base3 = document.getElementById('base-3');
    
    const chkRunner1 = document.getElementById('chk-runner-1');
    const chkRunner2 = document.getElementById('chk-runner-2');
    const chkRunner3 = document.getElementById('chk-runner-3');
    
    const sel1 = document.getElementById('runner_first_select');
    const sel2 = document.getElementById('runner_second_select');
    const sel3 = document.getElementById('runner_third_select');
    
    function toggleBase(baseEl, checkboxEl, dropdownEl) {
        if (!baseEl || !checkboxEl) return;
        const active = checkboxEl.checked;
        if (active) {
            checkboxEl.checked = false;
            baseEl.style.background = '#1e293b';
            baseEl.style.boxShadow = 'none';
            if (dropdownEl) {
                dropdownEl.value = "";
                updateBaseEventsUI();
            }
        } else {
            checkboxEl.checked = true;
            baseEl.style.background = '#fbbf24';
            baseEl.style.boxShadow = '0 0 8px #fbbf24';
        }
    }
    
    if (base1 && chkRunner1) {
        base1.addEventListener('click', () => toggleBase(base1, chkRunner1, sel1));
    }
    if (base2 && chkRunner2) {
        base2.addEventListener('click', () => toggleBase(base2, chkRunner2, sel2));
    }
    if (base3 && chkRunner3) {
        base3.addEventListener('click', () => toggleBase(base3, chkRunner3, sel3));
    }
    
    if (sel1) sel1.addEventListener('change', () => {
        syncDropdownsToBases();
        updateBaseEventsUI();
    });
    if (sel2) sel2.addEventListener('change', () => {
        syncDropdownsToBases();
        updateBaseEventsUI();
    });
    if (sel3) sel3.addEventListener('change', () => {
        syncDropdownsToBases();
        updateBaseEventsUI();
    });
    
    // Initialize radio highlights and UI states
    syncDropdownsToBases();
    updateBaseEventsUI();
    updateOffenseHighlights();
    updateDefenseHighlights();
    updatePitchCounterDefenseUI();
});
</script>

<?php require_once 'includes/footer.php'; ?>
