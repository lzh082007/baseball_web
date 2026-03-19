<?php include 'includes/header.php'; ?>
<div class="hero">
    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: url('uploads/hero.png') center/cover no-repeat; opacity: 0.4; z-index: -1;"></div>
    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(0deg, var(--bg-dark), transparent); z-index: -1;"></div>
    <div class="animate-fade">
        <h1>國立臺中科技大學<br><span style="color: var(--primary-color);">棒球隊</span></h1>
        <p>承載夢想，擊出傳奇。我們致力於推廣棒球運動，並透過 AI 分析與詳細數據，致力於提升球員表現與球隊紀錄。</p>
        <div style="margin-top: 3rem; display: flex; gap: 1.5rem; justify-content: center;">
            <a href="join.php" class="btn-login" style="padding: 1rem 2.5rem; font-size: 1.1rem;">立即加入我們</a>
            <a href="about.php" class="glass-morphism" style="padding: 1rem 2.5rem; color: white; text-decoration: none; border-radius: 9999px; font-weight: 600;">了解球隊歷史</a>
        </div>
    </div>
</div>

<section style="padding: 6rem 2rem; max-width: 1200px; margin: 0 auto;">
    <h2 style="font-size: 2.5rem; margin-bottom: 3rem; text-align: center;">核心特色</h2>
    <div class="card-grid">
        <div class="card glass-morphism animate-fade">
            <i class="fas fa-brain" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 1.5rem;"></i>
            <h3>AI 比賽分析</h3>
            <p style="color: var(--text-secondary); margin-top: 1rem;">運用先進 AI 演算法，將比賽紀錄轉化為深度的戰術摘要，協助教練團調整策略。</p>
        </div>
        <div class="card glass-morphism animate-fade" style="animation-delay: 0.1s;">
            <i class="fas fa-chart-line" style="font-size: 2rem; color: var(--secondary-color); margin-bottom: 1.5rem;"></i>
            <h3>詳細數據庫</h3>
            <p style="color: var(--text-secondary); margin-top: 1rem;">完整紀錄每一場比賽、每一位球員的表現，從打擊率到球速，數據一目了然。</p>
        </div>
        <div class="card glass-morphism animate-fade" style="animation-delay: 0.2s;">
            <i class="fas fa-venus" style="font-size: 2rem; color: #F472B6; margin-bottom: 1.5rem;"></i>
            <h3>女子棒球隊</h3>
            <p style="color: var(--text-secondary); margin-top: 1rem;">我們致力於多元發展，不僅有男子隊伍，女子棒球隊同樣展現出強大的競技水平與拼勁。</p>
        </div>
    </div>
</section>

<section style="background: rgba(0, 71, 171, 0.05); padding: 6rem 2rem;">
    <div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 4rem; align-items: center;">
        <div>
            <h2 style="font-size: 2.5rem; margin-bottom: 1.5rem;">中科 OB <span style="font-size: 1.25rem; color: var(--text-secondary);">(Old Boys)</span></h2>
            <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: 2rem;">
                每一屆畢業的資深學長姐都是球隊的驕傲。透過中科 OB 系統，我們聯繫著球隊的過去與未來，傳承球技與信念。
            </p>
            <a href="ob.php" class="btn-login" style="padding: 0.75rem 2rem;">查看畢業校友列表</a>
        </div>
        <div class="glass-morphism" style="height: 400px; display: flex; align-items: center; justify-content: center;">
            <div style="font-size: 5rem; opacity: 0.1;"><i class="fas fa-graduation-cap"></i></div>
            <div style="position: absolute; text-align: center; padding: 2rem;">
                <p style="font-style: italic; font-size: 1.25rem;">"棒球不只是運動，是一種傳承。"</p>
                <p style="margin-top: 1rem; color: var(--text-secondary);">— 創隊成員</p>
            </div>
        </div>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
