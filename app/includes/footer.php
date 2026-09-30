<script src="./assets/js/app.js"></script>
<script src="./assets/js/sidebar.js"></script>
<script src="./assets/js/carousel.js"></script>
<script src="./assets/js/home.js"></script>
<script>

        // Function to update the top navigation header dynamically
        function updateHeaderData(userData) {
            const headerStreak = document.getElementById('header-streak-count');
            const headerProfileImg = document.getElementById('header-profile-img');

            // 1. Update Streak Count Display Node smoothly
            if (headerStreak) {
                headerStreak.textContent = userData.currentStreak + (userData.currentStreak === 1 ? ' Day' : ' Days');
            }

            // 2. Update Image Path Assets dynamically
            if (headerProfileImg && userData.profileImageUrl) {
                headerProfileImg.src = userData.profileImageUrl;
            }
        }

        // Example Integration Test Payload
        document.addEventListener("DOMContentLoaded", () => {
            const userPayload = {
                currentStreak: 24,
                profileImageUrl: "./assets/images/profile-picture.webp.jpg"
            };

            // Run the updater sequence
            // updateHeaderData(userPayload);
        });

        
    </script>
    <script>
        /**
     * Global Dashboard Manager Module
     * Coordinates active dynamic interface state tracking for MemPulse
     */
        const MemPulseDashboard = {
            // 1. Core State Updater Method
            updateAll(appPayload) {
                if (!appPayload) return;

                this.syncHeader(appPayload.user);
                this.syncSidebarProfile(appPayload.user);
                this.syncDatabaseMetrics(appPayload.stats);
                this.syncStreakMetrics(appPayload.stats);
            },

            // 2. Synchronize Top Fixed Navigation Header Elements
            syncHeader(user) {
                if (!user) return;
                const headerStreak = document.getElementById('header-streak-count');
                const headerImg = document.getElementById('header-profile-img');

                if (headerStreak) {
                    headerStreak.textContent = user.currentStreak + (user.currentStreak === 1 ? ' Day' : ' Days');
                }
                if (headerImg && user.profileImageUrl) {
                    headerImg.src = user.profileImageUrl;
                }
            },

            // 3. Synchronize Bottom Expanded/Collapsed Sidebar Components
            syncSidebarProfile(user) {
                if (!user) return;
                const sidebarName = document.getElementById('sidebar-profile-name');
                const sidebarRole = document.getElementById('sidebar-profile-role');
                const sidebarImg = document.getElementById('sidebar-profile-img');

                if (sidebarName) sidebarName.textContent = user.displayName;
                if (sidebarRole) sidebarRole.textContent = user.displayRole;
                if (sidebarImg && user.profileImageUrl) {
                    sidebarImg.src = user.profileImageUrl;
                }
            },

            // 4. Update the Spaced-Repetition Core Database Analytics Rows
            syncDatabaseMetrics(stats) {
                if (!stats) return;
                const cardsDue = document.getElementById('stat-cards-due');
                const mastered = document.getElementById('stat-mastered');
                const accuracy = document.getElementById('stat-accuracy');
                const tough = document.getElementById('stat-tough');

                if (cardsDue) cardsDue.textContent = stats.cardsDue ?? '--';
                if (mastered) mastered.textContent = stats.masteredCards ?? '--';
                if (accuracy) accuracy.textContent = (stats.lifetimeAccuracy !== undefined) ? `${stats.lifetimeAccuracy}%` : '--%';
                if (tough) tough.textContent = stats.toughCards ?? '--';
            },

            // 5. Update Streak and Performance Calculation Cards
            syncStreakMetrics(stats) {
                if (!stats) return;
                const currentStreak = document.getElementById('stat-current-streak');
                const bestStreak = document.getElementById('stat-best-streak');
                const streakAccuracy = document.getElementById('stat-streak-accuracy');

                if (currentStreak) currentStreak.textContent = stats.currentStreak ?? '--';
                if (bestStreak) bestStreak.textContent = stats.bestStreak ?? '--';
                if (streakAccuracy) streakAccuracy.textContent = stats.streakAccuracy ?? '--';
            }
        };

        // ─── INITIALIZATION / LIVE SQL DATABASE RUNNER ───
        document.addEventListener("DOMContentLoaded", () => {
            // This section at the bottom of your file now instantly matches your dynamic PHP calculations!
            const incomingAppPayload = {
                user: {
                    displayName: <?php echo json_encode($display_name); ?>,
                    displayRole: "Active Learner",
                    currentStreak: <?php echo intval($current_streak); ?>,
                    profileImageUrl: "./assets/images/profile-picture.webp.jpg" 
                },
                stats: {
                    cardsDue: <?php echo intval($cards_due); ?>,
                    masteredCards: <?php echo intval($mastered_cards); ?>,
                    lifetimeAccuracy: <?php echo floatval($avg_accuracy); ?>,
                    toughCards: <?php echo intval($tough_cards); ?>,
                    currentStreak: <?php echo intval($current_streak); ?>,
                    bestStreak: <?php echo intval($best_streak); ?>,
                    streakAccuracy: <?php echo floatval($streak_accuracy); ?>
                }
            };

            // Default the heatmap to the most recent study days
            document.addEventListener("DOMContentLoaded", () => {
            const heatmapScroller = document.getElementById("heatmap-scroll");

            if (heatmapScroller) {
                requestAnimationFrame(() => {
                    heatmapScroller.scrollLeft =
                        heatmapScroller.scrollWidth - heatmapScroller.clientWidth;
                });
            }
        });

            document.querySelectorAll("#heatmap-scroll .heatmap-cell").forEach(cell => {
                cell.addEventListener("mouseenter", () => {
                    const rect = cell.getBoundingClientRect();

                    cell.style.setProperty("--tooltip-left", `${rect.left + rect.width / 2}px`);
                    cell.style.setProperty("--tooltip-top", `${rect.top - 8}px`);
                });
            });

            // Run deep script synchronization across Header, Sidebar, and Stats Row
            MemPulseDashboard.updateAll(incomingAppPayload);
        });

    </script>