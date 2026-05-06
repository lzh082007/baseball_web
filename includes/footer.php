<?php
if (!isset($db)) {
    require_once 'Database.php';
    $db = new Database();
}
$contactInfos = $db->getAll('contact_us');
?>
<footer>
    <div class="footer-content" style="display: flex; justify-content: center; align-items: flex-start; gap: 80px; max-width: 900px; margin: 0 auto; margin-bottom: 20px;">
        <div class="footer-section">
            <h3 style="margin-bottom: 15px; font-size: 1.4rem; letter-spacing: 2px;">導覽連結</h3>
            <ul style="list-style: none; padding: 0; display: flex; flex-direction: row; flex-wrap: wrap; gap: 10px 20px;">
                <li><a href="index.php" style="color: #ccc; text-decoration: none; transition: color 0.3s; font-size: 1.1rem;">首頁</a></li>
                <li><a href="about.php" style="color: #ccc; text-decoration: none; transition: color 0.3s; font-size: 1.1rem;">關於我們</a></li>
                <li><a href="matches.php" style="color: #ccc; text-decoration: none; transition: color 0.3s; font-size: 1.1rem;">賽事資訊</a></li>
                <li><a href="players.php" style="color: #ccc; text-decoration: none; transition: color 0.3s; font-size: 1.1rem;">球員資訊</a></li>
                <li><a href="join.php" style="color: #ccc; text-decoration: none; transition: color 0.3s; font-size: 1.1rem;">加入我們</a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h3 style="margin-bottom: 15px; font-size: 1.4rem; letter-spacing: 2px;">聯繫我們</h3>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <?php if (empty($contactInfos)): ?>
                    <p style="color: #888;">尚未設定聯絡資訊</p>
                <?php else: ?>
                    <?php foreach ($contactInfos as $c): ?>
                        <p style="margin: 0; font-size: 1.1rem; color: #ccc; display: flex; align-items: center; gap: 12px;">
                            <i class="<?= htmlspecialchars($c['icon_class']) ?>" style="color: var(--secondary); font-size: 1.2rem; width: 25px; text-align: center;"></i> 
                            <span><?= htmlspecialchars($c['content_text']) ?></span>
                        </p>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; 2026 NUTC Baseball Team Management System. All Rights Reserved.
        <br><small style="opacity: 0.5;">Developed by NUTC Information Applied Elite Class</small>
    </div>
</footer>
<script src="assets/js/script.js"></script>
</body>
</html>
