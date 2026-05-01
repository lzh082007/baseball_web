<?php
require_once 'includes/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $account = $_POST['account'] ?? '';
    $password = $_POST['password'] ?? '';

    $user = $db->find('member', 'account', $account);
    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] == 'active') {
            $_SESSION['user'] = $user;
            header('Location: index.php');
            exit;
        } else {
            $error = '您的帳號正在審核中，請耐心等候球經回覆。';
        }
    } else {
        $error = '帳號或密碼錯誤。';
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚾ 會員登入 - 中科大棒球隊</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="login-page">
    <div class="login-card">
        <h2>系統登入</h2>
        <?php if ($error): ?>
            <div class="err-msg"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group login-form-group">
                <label>帳號 Account</label>
                <input type="text" name="account" class="form-control" required placeholder="User ID">
            </div>
            <div class="form-group login-form-group">
                <label>密碼 Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Password">
            </div>
            <button type="submit" class="btn-submit login-btn-submit">登入並進入系統</button>
        </form>
        <div class="login-footer">
            <a href="index.php">返回首頁</a>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
