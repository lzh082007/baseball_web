<?php
if (!isset($db)) {
    require_once 'Database.php';
    $db = new Database();
}
$contactInfos = $db->getAll('contact_us');
?>
<footer>
    <div class="footer-content">
        <div class="footer-section">
            <h3>導覽連結</h3>
            <ul class="footer-links">
                <li><a href="index.php">首頁</a></li>
                <li><a href="about.php">關於我們</a></li>
                <li><a href="matches.php">賽事資訊</a></li>
                <li><a href="players.php">球員資訊</a></li>
                <li><a href="join.php">加入我們</a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h3>聯繫我們</h3>
            <div class="footer-contact-list">
                <?php if (empty($contactInfos)): ?>
                    <p class="footer-contact-empty">尚未設定聯絡資訊</p>
                <?php else: ?>
                    <?php foreach ($contactInfos as $c): ?>
                        <p class="footer-contact-item">
                            <i class="<?= htmlspecialchars($c['icon_class']) ?>"></i> 
                            <?php if (!empty($c['link'])): ?>
                                <a href="<?= htmlspecialchars($c['link']) ?>" target="_blank"><?= htmlspecialchars($c['content_text']) ?></a>
                            <?php else: ?>
                                <span><?= htmlspecialchars($c['content_text']) ?></span>
                            <?php endif; ?>
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
