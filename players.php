<?php
include 'includes/header.php';
require_once 'includes/db_handler.php';
$playerDB = new JsonDB('players');
$teamDB = new JsonDB('teams');

$players = $playerDB->getAll();
$teams = $teamDB->getAll();
$team_id = $_GET['team'] ?? 'all';

if ($team_id !== 'all') {
    $players = array_filter($players, function($p) use ($team_id) {
        return $p['team_id'] == $team_id;
    });
}
?>

<div style="padding: 4rem 2rem; max-width: 1200px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 4rem;">
        <h1 style="font-size: 3rem; margin-bottom: 1rem;">球員資訊</h1>
        <p style="color: var(--text-secondary); font-size: 1.1rem;">這裡彙整了我們最頂尖的運動員與教練團隊</p>
    </div>

    <!-- Filters -->
    <div style="display: flex; gap: 1rem; justify-content: center; margin-bottom: 4rem;">
        <a href="players.php?team=all" class="<?= $team_id == 'all' ? 'btn-login' : 'glass-morphism' ?>" style="padding: 0.6rem 1.5rem; text-decoration: none; color: white; border-radius: 999px; font-weight: 600; font-size: 0.875rem;">全部</a>
        <?php foreach($teams as $team): ?>
            <a href="players.php?team=<?= $team['team_id'] ?>" class="<?= $team_id == $team['team_id'] ? 'btn-login' : 'glass-morphism' ?>" style="padding: 0.6rem 1.5rem; text-decoration: none; color: white; border-radius: 999px; font-weight: 600; font-size: 0.875rem;"><?= htmlspecialchars($team['team_name']) ?></a>
        <?php endforeach; ?>
    </div>

    <!-- Player Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 2.5rem;">
        <?php foreach($players as $player): 
            $team = $teamDB->getById('team_id', $player['team_id']);
        ?>
            <div class="glass-morphism card" style="display: flex; flex-direction: column; overflow: hidden; padding: 0;">
                <div style="height: 320px; background: url('uploads/player1.png') center/cover no-repeat; position: relative;">
                    <div style="position: absolute; bottom: 0; left: 0; width: 100%; padding: 2rem 1.5rem 1rem; background: linear-gradient(0deg, var(--bg-dark), transparent);">
                        <span style="background: var(--primary-color); color: white; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700;"><?= htmlspecialchars($player['position']) ?></span>
                        <h3 style="font-size: 1.5rem; margin-top: 0.5rem;"><?= htmlspecialchars($player['Player_name']) ?></h3>
                    </div>
                </div>
                <div style="padding: 1.5rem;">
                    <p style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 1rem;"><?= htmlspecialchars($team['team_name']) ?></p>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; border-top: 1px solid var(--glass-border); padding-top: 1rem;">
                        <div>
                            <span style="display: block; font-size: 0.75rem; color: var(--text-secondary);">身高 / 體重</span>
                            <span style="font-weight: 600;"><?= htmlspecialchars($player['height']) ?>cm / <?= htmlspecialchars($player['weight']) ?>kg</span>
                        </div>
                        <div style="text-align: right;">
                            <span style="display: block; font-size: 0.75rem; color: var(--text-secondary);">球速</span>
                            <span style="font-weight: 600; color: var(--accent-color);"><?= htmlspecialchars($player['pitching_speed']) ?> Km/h</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
