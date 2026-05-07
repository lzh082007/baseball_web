<?php
require_once 'includes/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $account = $_POST['account'] ?? '';
    $password = $_POST['password'] ?? '';
    $name = $_POST['name'] ?? '';

    // Check if account already exists
    $existing = $db->find('member', 'account', $account);
    if ($existing) {
        $error = '這個帳號 (Email) 已經存在！請使用其他帳號或直接登入。';
    } else {
        // Insert new pending member with plaintext password for admin to view
        $db->insert('member', [
            'account' => $account,
            'password' => $password,
            'name' => $name,
            'role' => 'player', // Default role; admin will adjust during approval if needed
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $success = '帳號註冊申請已送出！請等待管理員審核與開通，完成後即可登入。';
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚾ 註冊會員帳號 - 中科大棒球隊</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="login-page">
    <div class="login-card">
        <h2>註冊新帳號</h2>
        
        <?php if ($success): ?>
            <div class="err-msg" style="background: #e8f5e9; color: #2e7d32; border-color: #c8e6c9;">
                <?= $success ?>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="login.php" class="btn-submit login-btn-submit" style="display: block; text-decoration: none;">前往登入頁面</a>
            </div>
        <?php else: ?>

            <?php if ($error): ?>
                <div class="err-msg"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group login-form-group">
                    <label>真實姓名 Name</label>
                    <input type="text" name="name" class="form-control" required placeholder="如：王大明" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>
                <div class="form-group login-form-group">
                    <label>設定帳號 Account</label>
                    <input type="text" name="account" class="form-control" required placeholder="請輸入您想設定的帳號" value="<?= htmlspecialchars($_POST['account'] ?? '') ?>">
                </div>
                <div class="form-group login-form-group">
                    <label>設定密碼 Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="請填寫高強度密碼">
                </div>
                <button type="submit" class="btn-submit login-btn-submit">送出註冊申請</button>
            </form>
            
        <?php endif; ?>

        <div class="login-footer">
            <a href="index.php">返回首頁</a>
            <span style="color: #999; margin: 0 10px;">|</span>
            <a href="login.php">已經有帳號？立即登入</a>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
