<?php
require_once 'includes/header.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $name = $_POST['name'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $age = $_POST['age'] ?? '';
    $school_system = $_POST['school_system'] ?? ''; // 五專、大學、碩士...
    $position = $_POST['position'] ?? '';
    $motivation = $_POST['motivation'] ?? '';

    if ($name && $gender && $age) {
        $team_id = ($gender === '女') ? 2 : 1;
        $db->insert('form', [
            'team_id' => $team_id,
            'form_name' => $name,
            'form_gender' => $gender,
            'form_age' => $age,
            'form_education' => $school_system,
            'form_position' => $position,
            'form_motive' => $motivation,
            'form_contact' => $_POST['contact'] ?? ''
        ]);
        $success = '感謝您的申請！球經將於 1-3 個工作天內與您聯繫。';
    } else {
        $error = '請填寫完整必填欄位。';
    }
}
?>

<div class="page-header">
    <h1>招募資訊</h1>
    <p>打造妳的棒球夢想，中科大女棒校隊熱烈招募中！</p>
</div>

<!-- Section 1: Recruitment Info -->
<section class="join-intro-section">
    <div class="container join-intro-container">
        <div class="join-intro-header">
            <h2>招募簡介</h2>
            <p>致力於提供健全的培訓環境與賽事平台。不論實力，只要熱情！</p>
        </div>
    </div>
</section>

<!-- Section 2: Application Form -->
<section class="join-form-section">
    <div class="container join-form-container">
                
                <?php if ($success): ?>
                    <div class="join-success-box">
                        <i class="fas fa-check-circle"></i>
                        <?= $success ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="join-error-box">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>姓名 *</label>
                        <input type="text" name="name" class="form-control" placeholder="請輸入您的真實姓名" required>
                    </div>
                    <div class="join-form-grid">
                        <div class="form-group">
                            <label>性別 *</label>
                            <select name="gender" class="form-control" required>
                                <option value="女">女性</option>
                                <option value="男">男性 (男棒隊)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>年齡 *</label>
                            <input type="number" name="age" class="form-control" placeholder="請輸入您的年齡" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>學制 *</label>
                        <select name="school_system" class="form-control">
                            <option value="五專部">五專部</option>
                            <option value="大學部">大學部</option>
                            <option value="進修部">進修部</option>
                            <option value="碩士班">碩士班</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>預期守位 (非必填)</label>
                        <input type="text" name="position" class="form-control" placeholder="如：投手、內野手...">
                    </div>
                    <div class="form-group">
                        <label>聯絡資訊 (LINE ID / 手機號碼 / IG ID) *</label>
                        <input type="text" name="contact" class="form-control" placeholder="請留下能聯絡到您的聯絡方式" required>
                    </div>
                    <div class="form-group">
                        <label>加入動機 (簡單描述) *</label>
                        <textarea name="motivation" class="form-control" rows="4" placeholder="請告訴我們您為什麼想加入棒球隊..." required></textarea>
                    </div>
                    <button type="submit" name="submit" class="btn-submit join-submit-btn">提交申請表單 <i class="fas fa-paper-plane"></i></button>
                </form>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
