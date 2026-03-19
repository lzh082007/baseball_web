<?php
include 'includes/header.php';
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
require_once 'includes/db_handler.php';
$gameDB = new JsonDB('games');
$aiDB = new JsonDB('ai_analysis');
$playerRecDB = new JsonDB('player_records');
$playerDB = new JsonDB('players');

$games = $gameDB->getAll();
$analyses = $aiDB->getAll();
?>

<div style="padding: 4rem 2rem; max-width: 1200px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 4rem;">
        <div>
            <h1 style="font-size: 3rem; margin-bottom: 0.5rem;">會員專區</h1>
            <p style="color: var(--text-secondary); font-size: 1.1rem;">歡迎回來，<?= htmlspecialchars($_SESSION['user']['name']) ?> 球員</p>
        </div>
        <div class="glass-morphism" style="padding: 1rem 2rem; display: flex; align-items: center; gap: 1rem; background: rgba(0, 71, 171, 0.1);">
            <div style="width: 12px; height: 12px; border-radius: 50%; background: #10B981; box-shadow: 0 0 10px #10B981;"></div>
            <span style="font-weight: 600; font-size: 0.875rem; letter-spacing: 0.5px;">系統已聯機</span>
        </div>
    </div>

    <!-- AI Analysis Section -->
    <section style="margin-bottom: 6rem;">
        <h2 style="font-size: 2rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
            <i class="fas fa-microchip" style="color: var(--primary-color);"></i> AI 戰報摘要
        </h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">
            <?php foreach($analyses as $analysis): 
                $game = $gameDB->getById('game_id', $analysis['game_id']);
            ?>
                <div class="glass-morphism" style="padding: 2.5rem; position: relative; overflow: hidden;">
                    <div style="position: absolute; top: 0; right: 0; padding: 1rem 2rem; background: var(--primary-color); border-bottom-left-radius: 16px; font-weight: 700; font-size: 0.875rem;">
                        <?= $game['result'] == 'W' ? '勝利 WIN' : '落敗 LOSS' ?>
                    </div>
                    <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 1rem; font-weight: 600;">
                        <?= htmlspecialchars($game['game_date']) ?> vs <?= htmlspecialchars($game['opponent']) ?>
                    </div>
                    <h3 style="margin-bottom: 1.5rem; font-size: 1.25rem;">比賽技術總結</h3>
                    <p style="line-height: 1.8; color: #CBD5E1; font-size: 1rem;">
                        <?= htmlspecialchars($analysis['summary']) ?>
                    </p>
                    <div style="margin-top: 2rem; display: flex; align-items: center; gap: 0.5rem; color: var(--text-secondary); font-size: 0.8125rem;">
                        <i class="far fa-clock"></i> 分析生成於：<?= htmlspecialchars($analysis['creat_at']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Detailed Stats Table -->
    <section>
        <h2 style="font-size: 2rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
            <i class="fas fa-table" style="color: var(--secondary-color);"></i> 近期比賽數據
        </h2>
        <div class="glass-morphism" style="overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead style="background: rgba(15, 23, 42, 0.4); border-bottom: 1px solid var(--glass-border);">
                    <tr>
                        <th style="padding: 1.5rem 2rem;">日期</th>
                        <th style="padding: 1.5rem 2rem;">對手</th>
                        <th style="padding: 1.5rem 2rem;">地點</th>
                        <th style="padding: 1.5rem 2rem; text-align: center;">結果</th>
                        <th style="padding: 1.5rem 2rem; text-align: right;">詳細</th>
                    </tr>
                </thead>
                <tbody style="color: var(--text-secondary);">
                    <?php foreach($games as $game): ?>
                        <tr style="border-bottom: 1px solid var(--glass-border); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 1.5rem 2rem; color: white; font-weight: 600;"><?= htmlspecialchars($game['game_date']) ?></td>
                            <td style="padding: 1.5rem 2rem;"><?= htmlspecialchars($game['opponent']) ?></td>
                            <td style="padding: 1.5rem 2rem;"><?= htmlspecialchars($game['location']) ?></td>
                            <td style="padding: 1.5rem 2rem; text-align: center;">
                                <span style="background: <?= $game['result'] == 'W' ? 'rgba(16,185,129,0.1)' : 'rgba(239, 68, 68, 0.1)' ?>; color: <?= $game['result'] == 'W' ? '#10B981' : '#EF4444' ?>; padding: 0.4rem 1rem; border-radius: 999px; font-weight: 700; font-size: 0.75rem; border: 1px solid <?= $game['result'] == 'W' ? 'rgba(16,185,129,0.2)' : 'rgba(239, 68, 68, 0.2)' ?>;">
                                    <?= $game['result'] == 'W' ? '勝利' : '落敗' ?>
                                </span>
                            </td>
                            <td style="padding: 1.5rem 2rem; text-align: right;">
                                <a href="game_detail.php?id=<?= $game['game_id'] ?>" style="color: var(--primary-color); text-decoration: none; font-weight: 600; font-size: 0.875rem;">查看亮點 <i class="fas fa-chevron-right" style="font-size: 0.75rem;"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php include 'includes/footer.php'; ?>
