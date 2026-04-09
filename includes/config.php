<?php
require_once 'Database.php';

session_start();

$db = new Database();

// Utility functions
function isLoggedIn() {
    return isset($_SESSION['user']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['user']['role'] === 'admin';
}

function isPlayer() {
    return isLoggedIn() && $_SESSION['user']['role'] === 'player';
}

function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: login.php');
        exit;
    }
}

// Read JSON data into DB (Only triggers if team is empty)
if (empty($db->getAll('team'))) {
    // 1. Ensure Teams exist from JSON
    $teamJsonPath = __DIR__ . '/../data/team.json';
    if (file_exists($teamJsonPath)) {
        $teamsData = json_decode(file_get_contents($teamJsonPath), true);
        if ($teamsData) {
            foreach ($teamsData as $t) {
                // MySQL columns: team_Id, team_name, team_type
                $db->insert('team', [
                    'team_Id' => $t['team_id'], 
                    'team_name' => $t['team_name'], 
                    'team_type' => $t['team_type']
                ]);
            }
        }
    } else {
        $db->insert('team', ['team_Id' => 1, 'team_name' => '中科大男棒', 'team_type' => 'Men']);
        $db->insert('team', ['team_Id' => 2, 'team_name' => '中科大女棒', 'team_type' => 'Woman']);
        $db->insert('team', ['team_Id' => 3, 'team_name' => '中科大OB', 'team_type' => 'OB']);
    }

    // 2. Ensure Admin accounts exist
    if (!$db->find('member', 'account', 'admin')) {
        $db->insert('member', [
            'account' => 'admin',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'name' => '管理者(Admin)',
            'role' => 'admin',
            'status' => 'active'
        ]);
    }
    if (!$db->find('member', 'account', 'user1')) {
        $db->insert('member', [
            'account' => 'user1',
            'password' => password_hash('123', PASSWORD_DEFAULT),
            'name' => '管理者(User1)',
            'role' => 'admin',
            'status' => 'active'
        ]);
    }

    // 3. Seed News if empty
    if (empty($db->getAll('news'))) {
        $newsData = [
            ['title' => '2026 全國大專棒球聯賽開跑！', 'content' => '本校棒球隊將於 4 月 10 日出征，第一場對戰東海大學。歡迎各位同學前往台中棒球場加油！'],
            ['title' => '招募新血：女棒隊熱烈招生中', 'content' => '想要體驗熱血的棒球生活嗎？中科大女棒隊歡迎妳的加入，不限球技，只要有心！'],
            ['title' => 'OB 比賽：學長姐風采再現', 'content' => '上週舉行的 OB 對抗賽圓滿結束，感謝畢業學長姐回校參與，場面溫馨感人。'],
            ['title' => '春季訓練營圓滿落幕', 'content' => '為期一週的春季強化訓練已結束，球員們在打擊與守備技巧上有顯著進步。'],
            ['title' => '全新球隊裝備正式亮相', 'content' => '感謝贊助商支持，本屆聯賽我們將穿上全新的紅金配色球衣，展現中科大氣勢！']
        ];
        foreach ($newsData as $n) {
            $db->insert('news', array_merge($n, ['created_at' => date('Y-m-d H:i:s')]));
        }
    }

    // 4. Seed Players if empty
    if (empty($db->getAll('player'))) {
        $playerData = [
            ['Player_Name' => '黃郁婷', 'jersey_number' => 11, 'position' => '投手', 'height' => 170, 'weight' => 65],
            ['Player_Name' => '劉詠傑', 'jersey_number' => 23, 'position' => '捕手', 'height' => 178, 'weight' => 75],
            ['Player_Name' => '劉宙翰', 'jersey_number' => 5, 'position' => '內野手', 'height' => 175, 'weight' => 70],
            ['Player_Name' => '張美玲', 'jersey_number' => 7, 'position' => '外野手', 'height' => 165, 'weight' => 55],
            ['Player_Name' => '王小明', 'jersey_number' => 1, 'position' => '投手', 'height' => 182, 'weight' => 80],
            ['Player_Name' => '李華', 'jersey_number' => 45, 'position' => '內野手', 'height' => 172, 'weight' => 68],
            ['Player_Name' => '周杰', 'jersey_number' => 99, 'position' => '外野手', 'height' => 188, 'weight' => 85]
        ];
        foreach ($playerData as $p) {
            $db->insert('player', array_merge($p, [
                'Team_Id' => 1,
                'image_path' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($p['Player_Name'])
            ]));
        }
    }

    // 5. Seed OB if empty
    if (empty($db->getAll('ob'))) {
        $obData = [
            ['OB_name' => '陳大文 (108級)', 'graduation_year' => 2019, 'status' => '全國大專聯賽關鍵安打打點王'],
            ['OB_name' => '林小美 (110級)', 'graduation_year' => 2021, 'status' => '女棒創始隊員，首屆全壘打王'],
            ['OB_name' => '趙子龍 (105級)', 'graduation_year' => 2016, 'status' => '傳奇投手，曾創下單場 15 次三振紀錄']
        ];
        foreach ($obData as $o) {
            $db->insert('ob', array_merge($o, ['Team_Id' => 3]));
        }
    }
}
