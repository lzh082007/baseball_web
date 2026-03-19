<?php include 'includes/header.php'; ?>
<div style="min-height: calc(100vh - 80px); display: flex; align-items: center; justify-content: center; padding: 2rem; position: relative; background: radial-gradient(circle at top right, rgba(0, 71, 171, 0.1), transparent 50%);">
    <div class="glass-morphism animate-fade" style="width: 100%; max-width: 450px; padding: 3rem; background: rgba(30, 41, 59, 0.6); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
        <h2 style="font-size: 2.25rem; font-weight: 800; margin-bottom: 0.5rem; text-align: center;">會員登入</h2>
        <p style="text-align: center; color: var(--text-secondary); margin-bottom: 2.5rem;">歡迎回到中科大棒球隊</p>

        <?php
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            require_once 'includes/db_handler.php';
            $db = new JsonDB('members');
            $members = $db->getAll();
            $account = $_POST['account'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = null;
            foreach ($members as $m) {
                if ($m['account'] == $account && $m['password'] == $password) {
                    $user = $m;
                    break;
                }
            }

            if ($user) {
                $_SESSION['user'] = $user;
                header('Location: member_zone.php');
                exit;
            } else {
                echo '<div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #EF4444; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-exclamation-circle"></i> 帳號或密碼錯誤</div>';
            }
        }
        ?>

        <form method="POST" style="display: flex; flex-direction: column; gap: 1.5rem;">
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 0.5rem;">帳號</label>
                <input type="text" name="account" required style="width: 100%; padding: 0.875rem 1.25rem; background: rgba(15, 23, 42, 0.5); border: 1px solid var(--glass-border); border-radius: 12px; color: white; outline: none; transition: border-color 0.2s;" placeholder="您的帳號">
            </div>
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 0.5rem;">密碼</label>
                <input type="password" name="password" required style="width: 100%; padding: 0.875rem 1.25rem; background: rgba(15, 23, 42, 0.5); border: 1px solid var(--glass-border); border-radius: 12px; color: white; outline: none; transition: border-color 0.2s;" placeholder="您的密碼">
            </div>
            <button type="submit" class="btn-login" style="padding: 1rem; margin-top: 1.5rem; border: none; font-size: 1rem; cursor: pointer;">登入系統</button>
        </form>
        
        <div style="margin-top: 2rem; text-align: center; color: var(--text-secondary); font-size: 0.875rem;">
            如果您忘記了密碼，請洽詢球隊球經。
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
