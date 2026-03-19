    </main>
    <footer>
        <div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); text-align: left; gap: 4rem;">
            <div>
                <h3 style="color: white; margin-bottom: 1.5rem; font-size: 1.25rem;">國立臺中科技大學棒球隊</h3>
                <p style="line-height: 1.8;">以傳承球魂為核心，致敬拼搏之魂。這裡不僅是技術的磨練，更是意志的凝聚。</p>
            </div>
            <div>
                <h3 style="color: white; margin-bottom: 1.5rem; font-size: 1.25rem;">快速連結</h3>
                <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.75rem;">
                    <li><a href="about.php" style="color: var(--text-secondary); text-decoration: none;">球隊歷史</a></li>
                    <li><a href="matches.php" style="color: var(--text-secondary); text-decoration: none;">最新賽事</a></li>
                    <li><a href="join.php" style="color: var(--text-secondary); text-decoration: none;">招募細節</a></li>
                </ul>
            </div>
            <div>
                <h3 style="color: white; margin-bottom: 1.5rem; font-size: 1.25rem;">追蹤我們</h3>
                <div style="display: flex; gap: 1.5rem; font-size: 1.5rem;">
                    <a href="https://www.instagram.com/nutc_baseball/" target="_blank" style="color: #E1306C;"><i class="fab fa-instagram"></i></a>
                    <a href="#" style="color: #1877F2;"><i class="fab fa-facebook"></i></a>
                </div>
            </div>
        </div>
        <div style="margin-top: 4rem; padding-top: 2rem; border-top: 1px solid var(--glass-border); font-size: 0.875rem;">
            &copy; <?= date("Y") ?> National Taichung University of Science and Technology Baseball Team. All rights reserved.
        </div>
    </footer>
    <script>
        window.addEventListener('scroll', function() {
            var nav = document.getElementById('mainNav');
            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>
